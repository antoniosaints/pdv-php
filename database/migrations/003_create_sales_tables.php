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
CREATE TABLE IF NOT EXISTS sales (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT NOT NULL UNIQUE,
    status TEXT NOT NULL DEFAULT 'completed' CHECK (status IN ('completed', 'cancelled')),
    cashier_user_id INTEGER NULL,
    customer_name TEXT NULL,
    subtotal_cents INTEGER NOT NULL DEFAULT 0,
    discount_cents INTEGER NOT NULL DEFAULT 0,
    total_cents INTEGER NOT NULL DEFAULT 0,
    payment_total_cents INTEGER NOT NULL DEFAULT 0,
    change_cents INTEGER NOT NULL DEFAULT 0,
    notes TEXT NULL,
    completed_at TEXT NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (cashier_user_id) REFERENCES users(id) ON DELETE SET NULL
)
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS sale_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sale_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    variant_id INTEGER NOT NULL,
    product_type TEXT NOT NULL,
    product_name TEXT NOT NULL,
    variant_name TEXT NOT NULL,
    sku TEXT NULL,
    barcode TEXT NULL,
    quantity INTEGER NOT NULL,
    unit_cost_cents INTEGER NOT NULL DEFAULT 0,
    unit_price_cents INTEGER NOT NULL DEFAULT 0,
    discount_cents INTEGER NOT NULL DEFAULT 0,
    total_cents INTEGER NOT NULL DEFAULT 0,
    track_stock INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE RESTRICT
)
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS sale_payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sale_id INTEGER NOT NULL,
    method TEXT NOT NULL,
    amount_cents INTEGER NOT NULL DEFAULT 0,
    reference TEXT NULL,
    created_at TEXT NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE
)
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS stock_movements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    variant_id INTEGER NOT NULL,
    type TEXT NOT NULL,
    quantity_delta INTEGER NOT NULL,
    quantity_before INTEGER NOT NULL,
    quantity_after INTEGER NOT NULL,
    reference_type TEXT NULL,
    reference_id INTEGER NULL,
    reason TEXT NULL,
    created_at TEXT NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE RESTRICT
)
SQL);

        $pdo->exec('CREATE INDEX IF NOT EXISTS sales_status_index ON sales (status)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS sales_completed_at_index ON sales (completed_at)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS sale_items_sale_id_index ON sale_items (sale_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS sale_items_variant_id_index ON sale_items (variant_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS sale_payments_sale_id_index ON sale_payments (sale_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS stock_movements_variant_id_index ON stock_movements (variant_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS stock_movements_reference_index ON stock_movements (reference_type, reference_id)');
    }

    private function mysql(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS sales (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(40) NOT NULL UNIQUE,
    status VARCHAR(20) NOT NULL DEFAULT 'completed',
    cashier_user_id BIGINT UNSIGNED NULL,
    customer_name VARCHAR(190) NULL,
    subtotal_cents INT NOT NULL DEFAULT 0,
    discount_cents INT NOT NULL DEFAULT 0,
    total_cents INT NOT NULL DEFAULT 0,
    payment_total_cents INT NOT NULL DEFAULT 0,
    change_cents INT NOT NULL DEFAULT 0,
    notes TEXT NULL,
    completed_at VARCHAR(40) NOT NULL,
    created_at VARCHAR(40) NOT NULL,
    updated_at VARCHAR(40) NOT NULL,
    INDEX sales_status_index (status),
    INDEX sales_completed_at_index (completed_at),
    CONSTRAINT sales_cashier_user_id_foreign FOREIGN KEY (cashier_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS sale_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    variant_id BIGINT UNSIGNED NOT NULL,
    product_type VARCHAR(20) NOT NULL,
    product_name VARCHAR(190) NOT NULL,
    variant_name VARCHAR(190) NOT NULL,
    sku VARCHAR(80) NULL,
    barcode VARCHAR(80) NULL,
    quantity INT NOT NULL,
    unit_cost_cents INT NOT NULL DEFAULT 0,
    unit_price_cents INT NOT NULL DEFAULT 0,
    discount_cents INT NOT NULL DEFAULT 0,
    total_cents INT NOT NULL DEFAULT 0,
    track_stock TINYINT(1) NOT NULL DEFAULT 0,
    created_at VARCHAR(40) NOT NULL,
    INDEX sale_items_sale_id_index (sale_id),
    INDEX sale_items_variant_id_index (variant_id),
    CONSTRAINT sale_items_sale_id_foreign FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    CONSTRAINT sale_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    CONSTRAINT sale_items_variant_id_foreign FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS sale_payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id BIGINT UNSIGNED NOT NULL,
    method VARCHAR(40) NOT NULL,
    amount_cents INT NOT NULL DEFAULT 0,
    reference VARCHAR(190) NULL,
    created_at VARCHAR(40) NOT NULL,
    INDEX sale_payments_sale_id_index (sale_id),
    CONSTRAINT sale_payments_sale_id_foreign FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS stock_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    variant_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(40) NOT NULL,
    quantity_delta INT NOT NULL,
    quantity_before INT NOT NULL,
    quantity_after INT NOT NULL,
    reference_type VARCHAR(40) NULL,
    reference_id BIGINT UNSIGNED NULL,
    reason VARCHAR(190) NULL,
    created_at VARCHAR(40) NOT NULL,
    INDEX stock_movements_variant_id_index (variant_id),
    INDEX stock_movements_reference_index (reference_type, reference_id),
    CONSTRAINT stock_movements_product_id_foreign FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    CONSTRAINT stock_movements_variant_id_foreign FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }
};
