<?php

declare(strict_types=1);

namespace Pdv\ServiceOrders;

use PDO;
use Pdv\Sales\SalesRepository;
use Throwable;

final class ServiceOrderRepository
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly ServiceOrderValidator $validator = new ServiceOrderValidator(),
    ) {
    }

    /** @param array<string, mixed> $input */
    public function createOrder(array $input): int
    {
        $data = $this->validator->normalizeOrder($input);
        $errors = $this->validator->order($data);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $this->pdo->beginTransaction();

        try {
            $preparedItems = $this->prepareItems($data['items']);
            $totals = $this->calculateTotals($preparedItems);
            $orderId = $this->insertOrder($data, $totals);

            foreach ($preparedItems as $item) {
                $this->insertItem($orderId, $item);
            }

            $this->insertStatusHistory(
                $orderId,
                null,
                'open',
                $data['opened_by_user_id'],
                'Ordem criada'
            );

            $this->pdo->commit();

            return $orderId;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    /** @return list<array<string, mixed>> */
    public function listOrders(?string $status = null): array
    {
        $sql = <<<'SQL'
SELECT o.*,
       COUNT(i.id) AS item_count
FROM service_orders o
LEFT JOIN service_order_items i ON i.service_order_id = o.id
SQL;
        $params = [];

        if ($status !== null && trim($status) !== '') {
            if (! in_array($status, $this->validator->statuses(), true)) {
                throw new ValidationException(['status' => 'Status inválido.']);
            }

            $sql .= ' WHERE o.status = :status';
            $params['status'] = $status;
        }

        $sql .= ' GROUP BY o.id ORDER BY o.id DESC';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return array_map(fn (array $row): array => $this->castOrder($row), $statement->fetchAll());
    }

    /** @return array<string, mixed>|null */
    public function findOrder(int $orderId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM service_orders WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $orderId]);
        $order = $statement->fetch();

        return is_array($order) ? $this->castOrder($order) : null;
    }

    /** @return list<array<string, mixed>> */
    public function itemsForOrder(int $orderId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM service_order_items WHERE service_order_id = :service_order_id ORDER BY id ASC');
        $statement->execute(['service_order_id' => $orderId]);

        return array_map(fn (array $row): array => $this->castItem($row), $statement->fetchAll());
    }

    /** @return list<array<string, mixed>> */
    public function statusHistoryForOrder(int $orderId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM service_order_status_history WHERE service_order_id = :service_order_id ORDER BY id ASC');
        $statement->execute(['service_order_id' => $orderId]);

        return array_map(fn (array $row): array => $this->castStatusHistory($row), $statement->fetchAll());
    }

    /** @param array<string, mixed> $input */
    public function changeStatus(int $orderId, array $input): void
    {
        $data = $this->validator->normalizeStatus($input);
        $errors = $this->validator->status($data);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $this->pdo->beginTransaction();

        try {
            $order = $this->findOrder($orderId);

            if ($order === null) {
                throw new ValidationException(['order_id' => 'Ordem de serviço não encontrada.']);
            }

            if ($order['sale_id'] !== null || (string) $order['status'] === 'closed') {
                throw new ValidationException(['status' => 'Ordem fechada em venda não pode ter status alterado manualmente.']);
            }

            if ((string) $order['status'] === 'cancelled') {
                throw new ValidationException(['status' => 'Ordem cancelada não pode ter status alterado manualmente.']);
            }

            $fromStatus = (string) $order['status'];
            $toStatus = $data['status'];

            if ($fromStatus === $toStatus) {
                $this->pdo->commit();
                return;
            }

            $this->pdo->prepare(<<<'SQL'
UPDATE service_orders
SET status = :status,
    closed_at = CASE WHEN :status_closed = 'cancelled' THEN :closed_at ELSE closed_at END,
    updated_at = :updated_at
WHERE id = :id
SQL)->execute([
                'id' => $orderId,
                'status' => $toStatus,
                'status_closed' => $toStatus,
                'closed_at' => $toStatus === 'cancelled' ? gmdate('c') : null,
                'updated_at' => gmdate('c'),
            ]);

            $this->insertStatusHistory(
                $orderId,
                $fromStatus,
                $toStatus,
                $data['actor_user_id'],
                $data['notes']
            );

            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    /** @param array<string, mixed> $paymentInput @return array<string, mixed> */
    public function saleInputForOrder(int $orderId, array $paymentInput, ?int $cashierUserId): array
    {
        $order = $this->findOrder($orderId);

        if ($order === null) {
            throw new ValidationException(['order_id' => 'Ordem de serviço não encontrada.']);
        }

        if ($order['sale_id'] !== null || (string) $order['status'] === 'closed') {
            throw new ValidationException(['status' => 'Ordem de serviço já fechada em venda.']);
        }

        if ((string) $order['status'] === 'cancelled') {
            throw new ValidationException(['status' => 'Ordem cancelada não pode ser fechada em venda.']);
        }

        return $this->saleInputFromOrder($order, $paymentInput, $cashierUserId);
    }

    /** @param array<string, mixed> $paymentInput */
    public function closeIntoSale(int $orderId, array $paymentInput, ?int $cashierUserId, ?int $actorUserId, SalesRepository $sales): int
    {
        $this->pdo->beginTransaction();

        try {
            $this->claimOrderForClosure($orderId);
            $order = $this->findOrder($orderId);

            if ($order === null) {
                throw new ValidationException(['order_id' => 'Ordem de serviço não encontrada.']);
            }

            $saleInput = $this->saleInputFromOrder($order, $paymentInput, $cashierUserId);
            $saleId = $sales->completeSaleInCurrentTransaction($saleInput);
            $this->markClosedWithSaleInCurrentTransaction($order, $saleId, $actorUserId, null);

            $this->pdo->commit();

            return $saleId;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function markClosedWithSale(int $orderId, int $saleId, ?int $actorUserId, ?string $notes = null): void
    {
        $this->pdo->beginTransaction();

        try {
            $this->claimOrderForClosure($orderId);
            $order = $this->findOrder($orderId);

            if ($order === null) {
                throw new ValidationException(['order_id' => 'Ordem de serviço não encontrada.']);
            }

            $this->markClosedWithSaleInCurrentTransaction($order, $saleId, $actorUserId, $notes);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    /** @param array<string, mixed> $order @param array<string, mixed> $paymentInput @return array<string, mixed> */
    private function saleInputFromOrder(array $order, array $paymentInput, ?int $cashierUserId): array
    {
        if ($order['sale_id'] !== null || (string) $order['status'] === 'closed') {
            throw new ValidationException(['status' => 'Ordem de serviço já fechada em venda.']);
        }

        if ((string) $order['status'] === 'cancelled') {
            throw new ValidationException(['status' => 'Ordem cancelada não pode ser fechada em venda.']);
        }

        $items = $this->itemsForOrder((int) $order['id']);

        if ($items === []) {
            throw new ValidationException(['items' => 'Ordem sem itens para fechar em venda.']);
        }

        $saleItems = array_map(static fn (array $item): array => [
            'variant_id' => (int) $item['variant_id'],
            'quantity' => (int) $item['quantity'],
            'discount_cents' => (int) $item['discount_cents'],
        ], $items);

        return [
            'cashier_user_id' => $cashierUserId,
            'customer_name' => $order['customer_name'],
            'notes' => 'Ordem de serviço ' . $order['code'],
            'items' => $saleItems,
            'payments' => is_array($paymentInput['payments'] ?? null) ? $paymentInput['payments'] : [],
        ];
    }

    private function claimOrderForClosure(int $orderId): void
    {
        $statement = $this->pdo->prepare(<<<'SQL'
UPDATE service_orders
SET updated_at = :updated_at
WHERE id = :id
  AND sale_id IS NULL
  AND status NOT IN ('closed', 'cancelled')
SQL);
        $statement->execute([
            'id' => $orderId,
            'updated_at' => gmdate('c'),
        ]);

        if ($statement->rowCount() !== 1) {
            throw new ValidationException(['status' => 'Ordem de serviço não pode ser fechada em venda.']);
        }
    }

    /** @param array<string, mixed> $order */
    private function markClosedWithSaleInCurrentTransaction(array $order, int $saleId, ?int $actorUserId, ?string $notes): void
    {
        $now = gmdate('c');
        $orderId = (int) $order['id'];
        $fromStatus = (string) $order['status'];

        $statement = $this->pdo->prepare(<<<'SQL'
UPDATE service_orders
SET status = 'closed',
    sale_id = :sale_id,
    closed_at = :closed_at,
    updated_at = :updated_at
WHERE id = :id
  AND sale_id IS NULL
  AND status NOT IN ('closed', 'cancelled')
SQL);
        $statement->execute([
            'id' => $orderId,
            'sale_id' => $saleId,
            'closed_at' => $now,
            'updated_at' => $now,
        ]);

        if ($statement->rowCount() !== 1) {
            throw new ValidationException(['status' => 'Ordem de serviço já foi fechada.']);
        }

        $this->insertStatusHistory(
            $orderId,
            $fromStatus,
            'closed',
            $actorUserId,
            $notes ?? 'Fechada pela venda #' . $saleId
        );
    }

    /** @param list<array<string, int>> $items @return list<array<string, mixed>> */
    private function prepareItems(array $items): array
    {
        $prepared = [];
        $errors = [];

        foreach ($items as $index => $item) {
            $variantId = (int) $item['variant_id'];
            $lookup = $this->findVariantForOrder($variantId);

            if ($lookup === null) {
                $errors['items.' . $index . '.variant_id'] = 'Item indisponível para ordem de serviço.';
                continue;
            }

            $quantity = (int) $item['quantity'];
            $lineSubtotal = (int) $lookup['effective_price_cents'] * $quantity;
            $discount = (int) $item['discount_cents'];

            if ($discount > $lineSubtotal) {
                $errors['items.' . $index . '.discount'] = 'Desconto não pode ser maior que o valor do item.';
            }

            $prepared[] = [
                'product_id' => (int) $lookup['product_id'],
                'variant_id' => $variantId,
                'product_type' => (string) $lookup['product_type'],
                'product_name' => (string) $lookup['product_name'],
                'variant_name' => (string) $lookup['name'],
                'sku' => $lookup['sku'],
                'barcode' => $lookup['barcode'],
                'quantity' => $quantity,
                'unit_cost_cents' => (int) $lookup['effective_cost_cents'],
                'unit_price_cents' => (int) $lookup['effective_price_cents'],
                'discount_cents' => $discount,
                'total_cents' => $lineSubtotal - $discount,
                'track_stock' => (int) $lookup['track_stock'],
            ];
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $prepared;
    }

    /** @param list<array<string, mixed>> $items @return array{subtotal_cents:int,discount_cents:int,total_cents:int} */
    private function calculateTotals(array $items): array
    {
        $subtotal = 0;
        $discount = 0;

        foreach ($items as $item) {
            $subtotal += (int) $item['unit_price_cents'] * (int) $item['quantity'];
            $discount += (int) $item['discount_cents'];
        }

        $total = $subtotal - $discount;

        if ($total <= 0) {
            throw new ValidationException(['total' => 'Total da ordem deve ser maior que zero.']);
        }

        return [
            'subtotal_cents' => $subtotal,
            'discount_cents' => $discount,
            'total_cents' => $total,
        ];
    }

    /** @param array<string, mixed> $data @param array{subtotal_cents:int,discount_cents:int,total_cents:int} $totals */
    private function insertOrder(array $data, array $totals): int
    {
        $now = gmdate('c');
        $statement = $this->pdo->prepare(<<<'SQL'
INSERT INTO service_orders (code, status, customer_name, customer_phone, customer_document, description, notes, opened_by_user_id, sale_id, subtotal_cents, discount_cents, total_cents, opened_at, closed_at, created_at, updated_at)
VALUES (:code, 'open', :customer_name, :customer_phone, :customer_document, :description, :notes, :opened_by_user_id, NULL, :subtotal_cents, :discount_cents, :total_cents, :opened_at, NULL, :created_at, :updated_at)
SQL);
        $statement->execute([
            'code' => $this->nextOrderCode(),
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'],
            'customer_document' => $data['customer_document'],
            'description' => $data['description'],
            'notes' => $data['notes'],
            'opened_by_user_id' => $data['opened_by_user_id'],
            'subtotal_cents' => $totals['subtotal_cents'],
            'discount_cents' => $totals['discount_cents'],
            'total_cents' => $totals['total_cents'],
            'opened_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<string, mixed> $item */
    private function insertItem(int $orderId, array $item): void
    {
        $statement = $this->pdo->prepare(<<<'SQL'
INSERT INTO service_order_items (service_order_id, product_id, variant_id, product_type, product_name, variant_name, sku, barcode, quantity, unit_cost_cents, unit_price_cents, discount_cents, total_cents, track_stock, created_at)
VALUES (:service_order_id, :product_id, :variant_id, :product_type, :product_name, :variant_name, :sku, :barcode, :quantity, :unit_cost_cents, :unit_price_cents, :discount_cents, :total_cents, :track_stock, :created_at)
SQL);
        $statement->execute([
            'service_order_id' => $orderId,
            'product_id' => $item['product_id'],
            'variant_id' => $item['variant_id'],
            'product_type' => $item['product_type'],
            'product_name' => $item['product_name'],
            'variant_name' => $item['variant_name'],
            'sku' => $item['sku'],
            'barcode' => $item['barcode'],
            'quantity' => $item['quantity'],
            'unit_cost_cents' => $item['unit_cost_cents'],
            'unit_price_cents' => $item['unit_price_cents'],
            'discount_cents' => $item['discount_cents'],
            'total_cents' => $item['total_cents'],
            'track_stock' => $item['track_stock'],
            'created_at' => gmdate('c'),
        ]);
    }

    private function insertStatusHistory(int $orderId, ?string $fromStatus, string $toStatus, ?int $actorUserId, ?string $notes): void
    {
        $this->pdo->prepare(<<<'SQL'
INSERT INTO service_order_status_history (service_order_id, from_status, to_status, actor_user_id, notes, created_at)
VALUES (:service_order_id, :from_status, :to_status, :actor_user_id, :notes, :created_at)
SQL)->execute([
            'service_order_id' => $orderId,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'actor_user_id' => $actorUserId,
            'notes' => $notes,
            'created_at' => gmdate('c'),
        ]);
    }

    /** @return array<string, mixed>|null */
    private function findVariantForOrder(int $variantId): ?array
    {
        $statement = $this->pdo->prepare(<<<'SQL'
SELECT v.*, p.name AS product_name, p.type AS product_type, p.track_stock, p.price_cents AS product_price_cents, p.cost_cents AS product_cost_cents
FROM product_variants v
INNER JOIN products p ON p.id = v.product_id
WHERE v.id = :id AND v.active = 1 AND p.active = 1
LIMIT 1
SQL);
        $statement->execute(['id' => $variantId]);
        $row = $statement->fetch();

        if (! is_array($row)) {
            return null;
        }

        foreach (['id', 'product_id', 'cost_cents', 'price_cents', 'current_stock', 'active', 'track_stock', 'product_price_cents', 'product_cost_cents'] as $field) {
            if (array_key_exists($field, $row) && $row[$field] !== null) {
                $row[$field] = (int) $row[$field];
            }
        }

        $row['effective_price_cents'] = $row['price_cents'] ?? $row['product_price_cents'];
        $row['effective_cost_cents'] = $row['cost_cents'] ?? $row['product_cost_cents'];

        return $row;
    }

    private function nextOrderCode(): string
    {
        return 'OS-' . gmdate('YmdHis') . '-' . random_int(1000, 9999);
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function castOrder(array $row): array
    {
        foreach (['id', 'opened_by_user_id', 'sale_id', 'subtotal_cents', 'discount_cents', 'total_cents', 'item_count'] as $field) {
            if (array_key_exists($field, $row) && $row[$field] !== null) {
                $row[$field] = (int) $row[$field];
            }
        }

        return $row;
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function castItem(array $row): array
    {
        foreach (['id', 'service_order_id', 'product_id', 'variant_id', 'quantity', 'unit_cost_cents', 'unit_price_cents', 'discount_cents', 'total_cents', 'track_stock'] as $field) {
            if (array_key_exists($field, $row) && $row[$field] !== null) {
                $row[$field] = (int) $row[$field];
            }
        }

        return $row;
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function castStatusHistory(array $row): array
    {
        foreach (['id', 'service_order_id', 'actor_user_id'] as $field) {
            if (array_key_exists($field, $row) && $row[$field] !== null) {
                $row[$field] = (int) $row[$field];
            }
        }

        return $row;
    }
}
