<?php

declare(strict_types=1);

use Pdv\Database\Migration;

return new class implements Migration {
    public function up(PDO $pdo, string $driver): void
    {
        if ($driver === 'mysql') {
            $this->mysql($pdo);
            return;
        }

        $this->sqlite($pdo);
    }

    private function sqlite(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type TEXT NOT NULL CHECK (type IN ('product', 'service')),
    sku TEXT NULL UNIQUE,
    name TEXT NOT NULL,
    description TEXT NULL,
    cost_cents INTEGER NOT NULL DEFAULT 0,
    price_cents INTEGER NOT NULL DEFAULT 0,
    track_stock INTEGER NOT NULL DEFAULT 1,
    stock_min INTEGER NOT NULL DEFAULT 0,
    label_name TEXT NULL,
    active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
)
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS product_variants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    sku TEXT NULL UNIQUE,
    barcode TEXT NULL UNIQUE,
    attributes_json TEXT NULL,
    cost_cents INTEGER NULL,
    price_cents INTEGER NULL,
    current_stock INTEGER NOT NULL DEFAULT 0,
    active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
)
SQL);

        $pdo->exec('CREATE INDEX IF NOT EXISTS product_variants_product_id_index ON product_variants (product_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS products_name_index ON products (name)');
    }

    private function mysql(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(20) NOT NULL,
    sku VARCHAR(80) NULL UNIQUE,
    name VARCHAR(190) NOT NULL,
    description TEXT NULL,
    cost_cents INT NOT NULL DEFAULT 0,
    price_cents INT NOT NULL DEFAULT 0,
    track_stock TINYINT(1) NOT NULL DEFAULT 1,
    stock_min INT NOT NULL DEFAULT 0,
    label_name VARCHAR(190) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at VARCHAR(40) NOT NULL,
    updated_at VARCHAR(40) NOT NULL,
    INDEX products_name_index (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS product_variants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    sku VARCHAR(80) NULL UNIQUE,
    barcode VARCHAR(80) NULL UNIQUE,
    attributes_json TEXT NULL,
    cost_cents INT NULL,
    price_cents INT NULL,
    current_stock INT NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at VARCHAR(40) NOT NULL,
    updated_at VARCHAR(40) NOT NULL,
    INDEX product_variants_product_id_index (product_id),
    CONSTRAINT product_variants_product_id_foreign FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }
};
