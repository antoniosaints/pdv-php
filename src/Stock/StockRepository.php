<?php

declare(strict_types=1);

namespace Pdv\Stock;

use PDO;
use Throwable;

final class StockRepository
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly StockValidator $validator = new StockValidator(),
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function listTrackedVariants(): array
    {
        $statement = $this->pdo->query(<<<'SQL'
SELECT v.id AS variant_id,
       v.product_id,
       p.name AS product_name,
       p.sku AS product_sku,
       p.stock_min,
       v.name AS variant_name,
       v.sku AS variant_sku,
       v.barcode,
       v.current_stock,
       CASE WHEN v.current_stock <= p.stock_min THEN 1 ELSE 0 END AS is_low_stock
FROM product_variants v
INNER JOIN products p ON p.id = v.product_id
WHERE p.track_stock = 1 AND p.active = 1 AND v.active = 1
ORDER BY is_low_stock DESC, p.name ASC, v.name ASC
SQL);

        return array_map(fn (array $row): array => $this->castStockRow($row), $statement->fetchAll());
    }

    /** @return list<array<string, mixed>> */
    public function lowStockVariants(): array
    {
        return array_values(array_filter(
            $this->listTrackedVariants(),
            static fn (array $row): bool => (int) $row['is_low_stock'] === 1
        ));
    }

    /** @return list<array<string, mixed>> */
    public function recentMovements(int $limit = 50): array
    {
        $statement = $this->pdo->prepare(<<<'SQL'
SELECT m.*,
       p.name AS product_name,
       v.name AS variant_name,
       v.barcode
FROM stock_movements m
INNER JOIN products p ON p.id = m.product_id
INNER JOIN product_variants v ON v.id = m.variant_id
ORDER BY m.id DESC
LIMIT :limit
SQL);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return array_map(fn (array $row): array => $this->castMovement($row), $statement->fetchAll());
    }

    /** @param array<string, mixed> $input */
    public function recordReplenishment(array $input): int
    {
        $data = $this->validator->normalizeReplenishment($input);
        $errors = $this->validator->replenishment($data);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $this->recordMovement((int) $data['variant_id'], (int) $data['quantity'], 'purchase', (string) $data['reason']);
    }

    /** @param array<string, mixed> $input */
    public function recordAdjustment(array $input): int
    {
        $data = $this->validator->normalizeAdjustment($input);
        $errors = $this->validator->adjustment($data);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $this->recordMovement((int) $data['variant_id'], (int) $data['delta'], 'adjustment', (string) $data['reason']);
    }

    private function recordMovement(int $variantId, int $delta, string $type, string $reason): int
    {
        $this->pdo->beginTransaction();

        try {
            $variant = $this->findTrackedVariantForUpdate($variantId);

            if ($variant === null) {
                throw new ValidationException(['variant_id' => 'Variante controlada por estoque não encontrada.']);
            }

            $before = (int) $variant['current_stock'];
            $after = $before + $delta;

            if ($after < 0) {
                throw new ValidationException(['quantity' => 'Movimento deixaria o estoque negativo.']);
            }

            $this->pdo->prepare('UPDATE product_variants SET current_stock = :current_stock, updated_at = :updated_at WHERE id = :id')
                ->execute([
                    'id' => $variantId,
                    'current_stock' => $after,
                    'updated_at' => gmdate('c'),
                ]);

            $this->pdo->prepare(<<<'SQL'
INSERT INTO stock_movements (product_id, variant_id, type, quantity_delta, quantity_before, quantity_after, reference_type, reference_id, reason, created_at)
VALUES (:product_id, :variant_id, :type, :quantity_delta, :quantity_before, :quantity_after, 'manual', NULL, :reason, :created_at)
SQL)->execute([
                'product_id' => $variant['product_id'],
                'variant_id' => $variantId,
                'type' => $type,
                'quantity_delta' => $delta,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'reason' => $reason,
                'created_at' => gmdate('c'),
            ]);

            $movementId = (int) $this->pdo->lastInsertId();
            $this->pdo->commit();

            return $movementId;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    /** @return array<string, mixed>|null */
    private function findTrackedVariantForUpdate(int $variantId): ?array
    {
        $statement = $this->pdo->prepare(<<<'SQL'
SELECT v.id AS variant_id,
       v.product_id,
       v.current_stock,
       p.track_stock
FROM product_variants v
INNER JOIN products p ON p.id = v.product_id
WHERE v.id = :id AND v.active = 1 AND p.active = 1 AND p.track_stock = 1
LIMIT 1
SQL);
        $statement->execute(['id' => $variantId]);
        $row = $statement->fetch();

        if (! is_array($row)) {
            return null;
        }

        foreach (['variant_id', 'product_id', 'current_stock', 'track_stock'] as $field) {
            $row[$field] = (int) $row[$field];
        }

        return $row;
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function castStockRow(array $row): array
    {
        foreach (['variant_id', 'product_id', 'stock_min', 'current_stock', 'is_low_stock'] as $field) {
            if (array_key_exists($field, $row)) {
                $row[$field] = (int) $row[$field];
            }
        }

        return $row;
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function castMovement(array $row): array
    {
        foreach (['id', 'product_id', 'variant_id', 'quantity_delta', 'quantity_before', 'quantity_after', 'reference_id'] as $field) {
            if (array_key_exists($field, $row) && $row[$field] !== null) {
                $row[$field] = (int) $row[$field];
            }
        }

        return $row;
    }
}
