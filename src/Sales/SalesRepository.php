<?php

declare(strict_types=1);

namespace Pdv\Sales;

use PDO;
use Throwable;

final class SalesRepository
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly SalesValidator $validator = new SalesValidator(),
    ) {
    }

    /** @param array<string, mixed> $input */
    public function completeSale(array $input): int
    {
        $data = $this->validator->normalizeSale($input);
        $errors = $this->validator->sale($data);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $this->pdo->beginTransaction();

        try {
            $prepared = $this->prepareItems($data['items']);
            $totals = $this->calculateTotals($prepared, $data['payments']);
            $saleId = $this->insertSale($data, $totals);

            foreach ($prepared['items'] as $item) {
                $this->insertSaleItem($saleId, $item);

                if ((int) $item['track_stock'] === 1) {
                    $this->decrementStock($saleId, $item, $prepared['stock_by_variant']);
                }
            }

            foreach ($data['payments'] as $payment) {
                $this->insertPayment($saleId, $payment);
            }

            $this->pdo->commit();

            return $saleId;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    /** @return array<string, mixed>|null */
    public function findSale(int $saleId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM sales WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $saleId]);
        $sale = $statement->fetch();

        if (! is_array($sale)) {
            return null;
        }

        foreach (['id', 'cashier_user_id', 'subtotal_cents', 'discount_cents', 'total_cents', 'payment_total_cents', 'change_cents'] as $field) {
            if (array_key_exists($field, $sale) && $sale[$field] !== null) {
                $sale[$field] = (int) $sale[$field];
            }
        }

        return $sale;
    }

    /** @return list<array<string, mixed>> */
    public function itemsForSale(int $saleId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM sale_items WHERE sale_id = :sale_id ORDER BY id ASC');
        $statement->execute(['sale_id' => $saleId]);

        return array_map(fn (array $row): array => $this->castItem($row), $statement->fetchAll());
    }

    /** @return list<array<string, mixed>> */
    public function paymentsForSale(int $saleId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM sale_payments WHERE sale_id = :sale_id ORDER BY id ASC');
        $statement->execute(['sale_id' => $saleId]);

        return array_map(static function (array $row): array {
            foreach (['id', 'sale_id', 'amount_cents'] as $field) {
                $row[$field] = (int) $row[$field];
            }

            return $row;
        }, $statement->fetchAll());
    }

    /** @return list<array<string, mixed>> */
    public function stockMovementsForSale(int $saleId): array
    {
        $statement = $this->pdo->prepare("SELECT * FROM stock_movements WHERE reference_type = 'sale' AND reference_id = :sale_id ORDER BY id ASC");
        $statement->execute(['sale_id' => $saleId]);

        return array_map(static function (array $row): array {
            foreach (['id', 'product_id', 'variant_id', 'quantity_delta', 'quantity_before', 'quantity_after', 'reference_id'] as $field) {
                if (array_key_exists($field, $row) && $row[$field] !== null) {
                    $row[$field] = (int) $row[$field];
                }
            }

            return $row;
        }, $statement->fetchAll());
    }

    /** @param list<array<string, int>> $items @return array{items:list<array<string, mixed>>,stock_by_variant:array<int, int>} */
    private function prepareItems(array $items): array
    {
        $prepared = [];
        $requestedByVariant = [];
        $stockByVariant = [];
        $errors = [];

        foreach ($items as $index => $item) {
            $variantId = (int) $item['variant_id'];
            $lookup = $this->findVariantForSale($variantId);

            if ($lookup === null) {
                $errors['items.' . $index . '.variant_id'] = 'Item indisponível para venda.';
                continue;
            }

            $quantity = (int) $item['quantity'];
            $lineSubtotal = (int) $lookup['effective_price_cents'] * $quantity;
            $discount = (int) $item['discount_cents'];

            if ($discount > $lineSubtotal) {
                $errors['items.' . $index . '.discount'] = 'Desconto não pode ser maior que o valor do item.';
            }

            $requestedByVariant[$variantId] = ($requestedByVariant[$variantId] ?? 0) + $quantity;
            $stockByVariant[$variantId] = (int) $lookup['current_stock'];

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

        foreach ($prepared as $item) {
            if ((int) $item['track_stock'] !== 1) {
                continue;
            }

            $variantId = (int) $item['variant_id'];
            $available = $stockByVariant[$variantId] ?? 0;
            $requested = $requestedByVariant[$variantId] ?? 0;

            if ($requested > $available) {
                $errors['items.' . $variantId . '.stock'] = 'Estoque insuficiente para finalizar a venda.';
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return ['items' => $prepared, 'stock_by_variant' => $stockByVariant];
    }

    /** @param array{items:list<array<string, mixed>>,stock_by_variant:array<int, int>} $prepared @param list<array<string, mixed>> $payments @return array<string, int|string> */
    private function calculateTotals(array $prepared, array $payments): array
    {
        $subtotal = 0;
        $discount = 0;

        foreach ($prepared['items'] as $item) {
            $subtotal += (int) $item['unit_price_cents'] * (int) $item['quantity'];
            $discount += (int) $item['discount_cents'];
        }

        $total = $subtotal - $discount;
        $paymentTotal = 0;

        foreach ($payments as $payment) {
            $paymentTotal += (int) $payment['amount_cents'];
        }

        if ($total <= 0) {
            throw new ValidationException(['total' => 'Total da venda deve ser maior que zero.']);
        }

        if ($paymentTotal < $total) {
            throw new ValidationException(['payments' => 'Pagamento insuficiente para finalizar a venda.']);
        }

        return [
            'code' => $this->nextSaleCode(),
            'subtotal_cents' => $subtotal,
            'discount_cents' => $discount,
            'total_cents' => $total,
            'payment_total_cents' => $paymentTotal,
            'change_cents' => $paymentTotal - $total,
        ];
    }

    /** @param array<string, mixed> $data @param array<string, int|string> $totals */
    private function insertSale(array $data, array $totals): int
    {
        $now = gmdate('c');
        $statement = $this->pdo->prepare(<<<'SQL'
INSERT INTO sales (code, status, cashier_user_id, customer_name, subtotal_cents, discount_cents, total_cents, payment_total_cents, change_cents, notes, completed_at, created_at, updated_at)
VALUES (:code, 'completed', :cashier_user_id, :customer_name, :subtotal_cents, :discount_cents, :total_cents, :payment_total_cents, :change_cents, :notes, :completed_at, :created_at, :updated_at)
SQL);
        $statement->execute([
            'code' => $totals['code'],
            'cashier_user_id' => $data['cashier_user_id'],
            'customer_name' => $data['customer_name'],
            'subtotal_cents' => $totals['subtotal_cents'],
            'discount_cents' => $totals['discount_cents'],
            'total_cents' => $totals['total_cents'],
            'payment_total_cents' => $totals['payment_total_cents'],
            'change_cents' => $totals['change_cents'],
            'notes' => $data['notes'],
            'completed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<string, mixed> $item */
    private function insertSaleItem(int $saleId, array $item): void
    {
        $statement = $this->pdo->prepare(<<<'SQL'
INSERT INTO sale_items (sale_id, product_id, variant_id, product_type, product_name, variant_name, sku, barcode, quantity, unit_cost_cents, unit_price_cents, discount_cents, total_cents, track_stock, created_at)
VALUES (:sale_id, :product_id, :variant_id, :product_type, :product_name, :variant_name, :sku, :barcode, :quantity, :unit_cost_cents, :unit_price_cents, :discount_cents, :total_cents, :track_stock, :created_at)
SQL);
        $statement->execute([
            'sale_id' => $saleId,
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

    /** @param array<string, mixed> $payment */
    private function insertPayment(int $saleId, array $payment): void
    {
        $statement = $this->pdo->prepare(<<<'SQL'
INSERT INTO sale_payments (sale_id, method, amount_cents, reference, created_at)
VALUES (:sale_id, :method, :amount_cents, :reference, :created_at)
SQL);
        $statement->execute([
            'sale_id' => $saleId,
            'method' => $payment['method'],
            'amount_cents' => $payment['amount_cents'],
            'reference' => $payment['reference'],
            'created_at' => gmdate('c'),
        ]);
    }

    /** @param array<string, mixed> $item @param array<int, int> $stockByVariant */
    private function decrementStock(int $saleId, array $item, array &$stockByVariant): void
    {
        $variantId = (int) $item['variant_id'];
        $before = $stockByVariant[$variantId];
        $after = $before - (int) $item['quantity'];
        $stockByVariant[$variantId] = $after;

        $this->pdo->prepare('UPDATE product_variants SET current_stock = :current_stock, updated_at = :updated_at WHERE id = :id')
            ->execute([
                'id' => $variantId,
                'current_stock' => $after,
                'updated_at' => gmdate('c'),
            ]);

        $this->pdo->prepare(<<<'SQL'
INSERT INTO stock_movements (product_id, variant_id, type, quantity_delta, quantity_before, quantity_after, reference_type, reference_id, reason, created_at)
VALUES (:product_id, :variant_id, 'sale', :quantity_delta, :quantity_before, :quantity_after, 'sale', :reference_id, :reason, :created_at)
SQL)->execute([
            'product_id' => $item['product_id'],
            'variant_id' => $variantId,
            'quantity_delta' => -1 * (int) $item['quantity'],
            'quantity_before' => $before,
            'quantity_after' => $after,
            'reference_id' => $saleId,
            'reason' => 'Venda #' . $saleId,
            'created_at' => gmdate('c'),
        ]);
    }

    /** @return array<string, mixed>|null */
    private function findVariantForSale(int $variantId): ?array
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

    private function nextSaleCode(): string
    {
        return 'SALE-' . gmdate('YmdHis') . '-' . random_int(1000, 9999);
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function castItem(array $row): array
    {
        foreach (['id', 'sale_id', 'product_id', 'variant_id', 'quantity', 'unit_cost_cents', 'unit_price_cents', 'discount_cents', 'total_cents', 'track_stock'] as $field) {
            if (array_key_exists($field, $row) && $row[$field] !== null) {
                $row[$field] = (int) $row[$field];
            }
        }

        return $row;
    }
}
