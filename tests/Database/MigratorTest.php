<?php

declare(strict_types=1);

namespace Tests\Database;

use PDO;
use Pdv\Database\ConnectionFactory;
use Pdv\Database\Migrator;
use Pdv\Support\Env;
use PHPUnit\Framework\TestCase;

final class MigratorTest extends TestCase
{
    private string $rootPath;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rootPath = dirname(__DIR__, 2);
        $this->tempDir = sys_get_temp_dir() . '/pdv_migration_test_' . bin2hex(random_bytes(6));

        mkdir($this->tempDir, 0775, true);

        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = $this->tempDir . '/test.sqlite';

        Env::load($this->rootPath);
    }

    protected function tearDown(): void
    {
        $files = glob($this->tempDir . '/*') ?: [];

        foreach ($files as $file) {
            @unlink($file);
        }

        @rmdir($this->tempDir);

        unset($_ENV['DB_CONNECTION'], $_ENV['DB_DATABASE']);

        parent::tearDown();
    }

    public function testMigrationsRunOnceAndCreateCoreTables(): void
    {
        $pdo = (new ConnectionFactory($this->rootPath))->make();
        $migrator = new Migrator($pdo, $this->rootPath . '/database/migrations');

        $firstRun = $migrator->migrate();
        $secondRun = $migrator->migrate();
        $status = $migrator->status();

        self::assertSame(['001_create_core_tables.php', '002_create_catalog_tables.php', '003_create_sales_tables.php'], $firstRun);
        self::assertSame([], $secondRun);
        self::assertSame(['001_create_core_tables.php', '002_create_catalog_tables.php', '003_create_sales_tables.php'], $status['executed']);
        self::assertSame([], $status['pending']);

        foreach (['schema_migrations', 'users', 'audit_logs', 'app_settings', 'products', 'product_variants', 'sales', 'sale_items', 'sale_payments', 'stock_movements'] as $table) {
            self::assertTrue($this->sqliteTableExists($pdo, $table), "Table [{$table}] should exist.");
        }
    }

    private function sqliteTableExists(PDO $pdo, string $table): bool
    {
        $statement = $pdo->prepare('SELECT name FROM sqlite_master WHERE type = :type AND name = :name');
        $statement->execute([
            'type' => 'table',
            'name' => $table,
        ]);

        return $statement->fetchColumn() === $table;
    }
}
