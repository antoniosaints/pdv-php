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
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'admin',
    active INTEGER NOT NULL DEFAULT 1,
    last_login_at TEXT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
)
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    actor_user_id INTEGER NULL,
    action TEXT NOT NULL,
    entity_type TEXT NOT NULL,
    entity_id TEXT NULL,
    metadata_json TEXT NULL,
    ip_address TEXT NULL,
    created_at TEXT NOT NULL,
    FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
)
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS app_settings (
    setting_key TEXT PRIMARY KEY,
    setting_value TEXT NULL,
    updated_at TEXT NOT NULL
)
SQL);
    }

    private function mysql(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(40) NOT NULL DEFAULT 'admin',
    active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at VARCHAR(40) NULL,
    created_at VARCHAR(40) NOT NULL,
    updated_at VARCHAR(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_user_id BIGINT UNSIGNED NULL,
    action VARCHAR(120) NOT NULL,
    entity_type VARCHAR(120) NOT NULL,
    entity_id VARCHAR(120) NULL,
    metadata_json TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at VARCHAR(40) NOT NULL,
    CONSTRAINT audit_logs_actor_user_id_foreign FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS app_settings (
    setting_key VARCHAR(120) PRIMARY KEY,
    setting_value TEXT NULL,
    updated_at VARCHAR(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }
};
