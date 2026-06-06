<?php

declare(strict_types=1);

namespace Pdv\Catalog;

use PDO;

final class CatalogRepository
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly CatalogValidator $validator = new CatalogValidator(),
    ) {
    }

    /** @param array<string, mixed> $input */
    public function createProduct(array $input): int
    {
        $data = $this->validator->normalizeProduct($input);
        $errors = $this->validator->product($data);

        if ($this->skuExists('products', $data['sku'])) {
            $errors['sku'] = 'Já existe um produto ou serviço com este SKU.';
        }

        $this->throwIfErrors($errors);

        $now = gmdate('c');
        $statement = $this->pdo->prepare(<<<'SQL'
INSERT INTO products (type, sku, name, description, cost_cents, price_cents, track_stock, stock_min, label_name, active, created_at, updated_at)
VALUES (:type, :sku, :name, :description, :cost_cents, :price_cents, :track_stock, :stock_min, :label_name, :active, :created_at, :updated_at)
SQL);
        $statement->execute([
            'type' => $data['type'],
            'sku' => $data['sku'],
            'name' => $data['name'],
            'description' => $data['description'],
            'cost_cents' => $data['cost_cents'],
            'price_cents' => $data['price_cents'],
            'track_stock' => $data['track_stock'] ? 1 : 0,
            'stock_min' => $data['stock_min'],
            'label_name' => $data['label_name'],
            'active' => $data['active'] ? 1 : 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<string, mixed> $input */
    public function updateProduct(int $id, array $input): void
    {
        $data = $this->validator->normalizeProduct($input);
        $errors = $this->validator->product($data);

        if ($this->skuExists('products', $data['sku'], $id)) {
            $errors['sku'] = 'Já existe um produto ou serviço com este SKU.';
        }

        $this->throwIfErrors($errors);

        $statement = $this->pdo->prepare(<<<'SQL'
UPDATE products
SET type = :type,
    sku = :sku,
    name = :name,
    description = :description,
    cost_cents = :cost_cents,
    price_cents = :price_cents,
    track_stock = :track_stock,
    stock_min = :stock_min,
    label_name = :label_name,
    active = :active,
    updated_at = :updated_at
WHERE id = :id
SQL);
        $statement->execute([
            'id' => $id,
            'type' => $data['type'],
            'sku' => $data['sku'],
            'name' => $data['name'],
            'description' => $data['description'],
            'cost_cents' => $data['cost_cents'],
            'price_cents' => $data['price_cents'],
            'track_stock' => $data['track_stock'] ? 1 : 0,
            'stock_min' => $data['stock_min'],
            'label_name' => $data['label_name'],
            'active' => $data['active'] ? 1 : 0,
            'updated_at' => gmdate('c'),
        ]);
    }

    public function setActive(int $id, bool $active): void
    {
        $this->pdo->prepare('UPDATE products SET active = :active, updated_at = :updated_at WHERE id = :id')
            ->execute([
                'id' => $id,
                'active' => $active ? 1 : 0,
                'updated_at' => gmdate('c'),
            ]);
    }

    /** @return array<string, mixed>|null */
    public function findProduct(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $product = $statement->fetch();

        return is_array($product) ? $this->castProduct($product) : null;
    }

    /** @return list<array<string, mixed>> */
    public function listProducts(?string $term = null): array
    {
        $sql = <<<'SQL'
SELECT p.*,
       COUNT(v.id) AS variant_count,
       COALESCE(SUM(CASE WHEN v.active = 1 THEN v.current_stock ELSE 0 END), 0) AS variant_stock
FROM products p
LEFT JOIN product_variants v ON v.product_id = p.id
SQL;
        $params = [];

        if ($term !== null && trim($term) !== '') {
            $sql .= " WHERE LOWER(p.name) LIKE :term OR LOWER(COALESCE(p.sku, '')) LIKE :term";
            $params['term'] = '%' . strtolower(trim($term)) . '%';
        }

        $sql .= ' GROUP BY p.id ORDER BY p.active DESC, p.name ASC';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return array_map(fn (array $row): array => $this->castProduct($row), $statement->fetchAll());
    }

    /** @param array<string, mixed> $input */
    public function createVariant(int $productId, array $input): int
    {
        $data = $this->validator->normalizeVariant($input);
        $errors = $this->validator->variant($data);

        if ($this->findProduct($productId) === null) {
            $errors['product_id'] = 'Produto não encontrado.';
        }

        if ($this->skuExists('product_variants', $data['sku'])) {
            $errors['sku'] = 'Já existe uma variante com este SKU.';
        }

        if ($this->barcodeExists($data['barcode'])) {
            $errors['barcode'] = 'Já existe uma variante com este código de barras.';
        }

        $this->throwIfErrors($errors);

        $now = gmdate('c');
        $statement = $this->pdo->prepare(<<<'SQL'
INSERT INTO product_variants (product_id, name, sku, barcode, attributes_json, cost_cents, price_cents, current_stock, active, created_at, updated_at)
VALUES (:product_id, :name, :sku, :barcode, :attributes_json, :cost_cents, :price_cents, :current_stock, :active, :created_at, :updated_at)
SQL);
        $statement->execute([
            'product_id' => $productId,
            'name' => $data['name'],
            'sku' => $data['sku'],
            'barcode' => $data['barcode'],
            'attributes_json' => $data['attributes_json'],
            'cost_cents' => $data['cost_cents'],
            'price_cents' => $data['price_cents'],
            'current_stock' => $data['current_stock'],
            'active' => $data['active'] ? 1 : 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<string, mixed> $input */
    public function updateVariant(int $variantId, array $input): void
    {
        $data = $this->validator->normalizeVariant($input);
        $errors = $this->validator->variant($data);

        if ($this->skuExists('product_variants', $data['sku'], $variantId)) {
            $errors['sku'] = 'Já existe uma variante com este SKU.';
        }

        if ($this->barcodeExists($data['barcode'], $variantId)) {
            $errors['barcode'] = 'Já existe uma variante com este código de barras.';
        }

        $this->throwIfErrors($errors);

        $statement = $this->pdo->prepare(<<<'SQL'
UPDATE product_variants
SET name = :name,
    sku = :sku,
    barcode = :barcode,
    attributes_json = :attributes_json,
    cost_cents = :cost_cents,
    price_cents = :price_cents,
    current_stock = :current_stock,
    active = :active,
    updated_at = :updated_at
WHERE id = :id
SQL);
        $statement->execute([
            'id' => $variantId,
            'name' => $data['name'],
            'sku' => $data['sku'],
            'barcode' => $data['barcode'],
            'attributes_json' => $data['attributes_json'],
            'cost_cents' => $data['cost_cents'],
            'price_cents' => $data['price_cents'],
            'current_stock' => $data['current_stock'],
            'active' => $data['active'] ? 1 : 0,
            'updated_at' => gmdate('c'),
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function variantsForProduct(int $productId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM product_variants WHERE product_id = :product_id ORDER BY active DESC, name ASC');
        $statement->execute(['product_id' => $productId]);

        return array_map(fn (array $row): array => $this->castVariant($row), $statement->fetchAll());
    }

    /** @return array<string, mixed>|null */
    public function findVariant(int $variantId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM product_variants WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $variantId]);
        $variant = $statement->fetch();

        return is_array($variant) ? $this->castVariant($variant) : null;
    }

    /** @return array<string, mixed>|null */
    public function findByBarcode(string $barcode): ?array
    {
        $statement = $this->pdo->prepare(<<<'SQL'
SELECT v.*, p.name AS product_name, p.type AS product_type, p.track_stock, p.price_cents AS product_price_cents, p.cost_cents AS product_cost_cents
FROM product_variants v
INNER JOIN products p ON p.id = v.product_id
WHERE v.barcode = :barcode AND v.active = 1 AND p.active = 1
LIMIT 1
SQL);
        $statement->execute(['barcode' => trim($barcode)]);
        $row = $statement->fetch();

        return is_array($row) ? $this->castLookup($row) : null;
    }

    /** @return array<string, mixed>|null */
    public function findForSaleVariant(int $variantId): ?array
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

        return is_array($row) ? $this->castLookup($row) : null;
    }

    /** @return list<array<string, mixed>> */
    public function searchForSale(string $term, int $limit = 15): array
    {
        $like = '%' . strtolower(trim($term)) . '%';
        $statement = $this->pdo->prepare(<<<'SQL'
SELECT v.*, p.name AS product_name, p.type AS product_type, p.track_stock, p.price_cents AS product_price_cents, p.cost_cents AS product_cost_cents
FROM product_variants v
INNER JOIN products p ON p.id = v.product_id
WHERE p.active = 1
  AND v.active = 1
  AND (LOWER(p.name) LIKE :term OR LOWER(v.name) LIKE :term OR LOWER(COALESCE(v.sku, '')) LIKE :term OR LOWER(COALESCE(v.barcode, '')) LIKE :term)
ORDER BY p.name ASC, v.name ASC
LIMIT :limit
SQL);
        $statement->bindValue('term', $like);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return array_map(fn (array $row): array => $this->castLookup($row), $statement->fetchAll());
    }

    private function skuExists(string $table, ?string $sku, ?int $exceptId = null): bool
    {
        if ($sku === null) {
            return false;
        }

        $sql = "SELECT COUNT(*) FROM {$table} WHERE sku = :sku";
        $params = ['sku' => $sku];

        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn() > 0;
    }

    private function barcodeExists(?string $barcode, ?int $exceptId = null): bool
    {
        if ($barcode === null) {
            return false;
        }

        $sql = 'SELECT COUNT(*) FROM product_variants WHERE barcode = :barcode';
        $params = ['barcode' => $barcode];

        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn() > 0;
    }

    /** @param array<string, string> $errors */
    private function throwIfErrors(array $errors): void
    {
        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function castProduct(array $row): array
    {
        foreach (['id', 'cost_cents', 'price_cents', 'track_stock', 'stock_min', 'active', 'variant_count', 'variant_stock'] as $field) {
            if (array_key_exists($field, $row)) {
                $row[$field] = (int) $row[$field];
            }
        }

        return $row;
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function castVariant(array $row): array
    {
        foreach (['id', 'product_id', 'cost_cents', 'price_cents', 'current_stock', 'active'] as $field) {
            if (array_key_exists($field, $row) && $row[$field] !== null) {
                $row[$field] = (int) $row[$field];
            }
        }

        return $row;
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function castLookup(array $row): array
    {
        $row = $this->castVariant($row);
        $row['track_stock'] = (int) $row['track_stock'];
        $row['product_price_cents'] = (int) $row['product_price_cents'];
        $row['product_cost_cents'] = (int) $row['product_cost_cents'];
        $row['effective_price_cents'] = $row['price_cents'] ?? $row['product_price_cents'];
        $row['effective_cost_cents'] = $row['cost_cents'] ?? $row['product_cost_cents'];

        return $row;
    }
}
