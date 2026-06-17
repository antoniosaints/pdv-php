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
CREATE TABLE IF NOT EXISTS service_orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT NOT NULL UNIQUE,
    status TEXT NOT NULL DEFAULT 'open' CHECK (status IN ('open', 'in_progress', 'ready', 'closed', 'cancelled')),
    customer_name TEXT NOT NULL,
    customer_phone TEXT NULL,
    customer_document TEXT NULL,
    description TEXT NULL,
    notes TEXT NULL,
    opened_by_user_id INTEGER NULL,
    sale_id INTEGER NULL,
    subtotal_cents INTEGER NOT NULL DEFAULT 0,
    discount_cents INTEGER NOT NULL DEFAULT 0,
    total_cents INTEGER NOT NULL DEFAULT 0,
    opened_at TEXT NOT NULL,
    closed_at TEXT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (opened_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE SET NULL
)
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS service_order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    service_order_id INTEGER NOT NULL,
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
    FOREIGN KEY (service_order_id) REFERENCES service_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE RESTRICT
)
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS service_order_status_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    service_order_id INTEGER NOT NULL,
    from_status TEXT NULL,
    to_status TEXT NOT NULL,
    actor_user_id INTEGER NULL,
    notes TEXT NULL,
    created_at TEXT NOT NULL,
    FOREIGN KEY (service_order_id) REFERENCES service_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
)
SQL);

        $pdo->exec('CREATE INDEX IF NOT EXISTS service_orders_status_index ON service_orders (status)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS service_orders_sale_id_index ON service_orders (sale_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS service_orders_customer_name_index ON service_orders (customer_name)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS service_order_items_service_order_id_index ON service_order_items (service_order_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS service_order_items_variant_id_index ON service_order_items (variant_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS service_order_status_history_order_id_index ON service_order_status_history (service_order_id)');
    }

    private function mysql(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS service_orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(40) NOT NULL UNIQUE,
    status VARCHAR(30) NOT NULL DEFAULT 'open',
    customer_name VARCHAR(190) NOT NULL,
    customer_phone VARCHAR(60) NULL,
    customer_document VARCHAR(80) NULL,
    description TEXT NULL,
    notes TEXT NULL,
    opened_by_user_id BIGINT UNSIGNED NULL,
    sale_id BIGINT UNSIGNED NULL,
    subtotal_cents INT NOT NULL DEFAULT 0,
    discount_cents INT NOT NULL DEFAULT 0,
    total_cents INT NOT NULL DEFAULT 0,
    opened_at VARCHAR(40) NOT NULL,
    closed_at VARCHAR(40) NULL,
    created_at VARCHAR(40) NOT NULL,
    updated_at VARCHAR(40) NOT NULL,
    INDEX service_orders_status_index (status),
    INDEX service_orders_sale_id_index (sale_id),
    INDEX service_orders_customer_name_index (customer_name),
    CONSTRAINT service_orders_opened_by_user_id_foreign FOREIGN KEY (opened_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT service_orders_sale_id_foreign FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS service_order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_order_id BIGINT UNSIGNED NOT NULL,
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
    INDEX service_order_items_service_order_id_index (service_order_id),
    INDEX service_order_items_variant_id_index (variant_id),
    CONSTRAINT service_order_items_service_order_id_foreign FOREIGN KEY (service_order_id) REFERENCES service_orders(id) ON DELETE CASCADE,
    CONSTRAINT service_order_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    CONSTRAINT service_order_items_variant_id_foreign FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS service_order_status_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_order_id BIGINT UNSIGNED NOT NULL,
    from_status VARCHAR(30) NULL,
    to_status VARCHAR(30) NOT NULL,
    actor_user_id BIGINT UNSIGNED NULL,
    notes VARCHAR(190) NULL,
    created_at VARCHAR(40) NOT NULL,
    INDEX service_order_status_history_order_id_index (service_order_id),
    CONSTRAINT service_order_status_history_order_id_foreign FOREIGN KEY (service_order_id) REFERENCES service_orders(id) ON DELETE CASCADE,
    CONSTRAINT service_order_status_history_actor_user_id_foreign FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }
};
