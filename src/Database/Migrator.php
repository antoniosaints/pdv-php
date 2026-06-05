<?php

declare(strict_types=1);

namespace Pdv\Database;

use PDO;
use RuntimeException;
use Throwable;

final class Migrator
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $migrationsPath,
    ) {
    }

    /** @return list<string> */
    public function migrate(): array
    {
        $this->ensureMigrationsTable();

        $executed = $this->executedMigrations();
        $ran = [];

        foreach ($this->migrationFiles() as $file) {
            $name = basename($file);

            if (in_array($name, $executed, true)) {
                continue;
            }

            $migration = require $file;

            if (! $migration instanceof Migration) {
                throw new RuntimeException("Migration [{$name}] must return an instance of " . Migration::class . '.');
            }

            $this->runMigration($name, $migration);
            $ran[] = $name;
        }

        return $ran;
    }

    /** @return array{executed:list<string>,pending:list<string>} */
    public function status(): array
    {
        $this->ensureMigrationsTable();

        $executed = $this->executedMigrations();
        $all = array_map('basename', $this->migrationFiles());
        $pending = array_values(array_diff($all, $executed));

        return [
            'executed' => $executed,
            'pending' => $pending,
        ];
    }

    private function runMigration(string $name, Migration $migration): void
    {
        $driver = (string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        try {
            $this->pdo->beginTransaction();
            $migration->up($this->pdo, $driver);

            $statement = $this->pdo->prepare('INSERT INTO schema_migrations (migration, executed_at) VALUES (:migration, :executed_at)');
            $statement->execute([
                'migration' => $name,
                'executed_at' => gmdate('c'),
            ]);

            $this->pdo->commit();
        } catch (Throwable $throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw new RuntimeException("Migration [{$name}] failed: " . $throwable->getMessage(), 0, $throwable);
        }
    }

    private function ensureMigrationsTable(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations (
                migration VARCHAR(255) PRIMARY KEY,
                executed_at VARCHAR(40) NOT NULL
            )'
        );
    }

    /** @return list<string> */
    private function executedMigrations(): array
    {
        $rows = $this->pdo->query('SELECT migration FROM schema_migrations ORDER BY migration')->fetchAll();

        return array_values(array_map(static fn (array $row): string => (string) $row['migration'], $rows));
    }

    /** @return list<string> */
    private function migrationFiles(): array
    {
        if (! is_dir($this->migrationsPath)) {
            return [];
        }

        $files = glob(rtrim($this->migrationsPath, '/\\') . DIRECTORY_SEPARATOR . '*.php') ?: [];
        sort($files, SORT_STRING);

        return array_values($files);
    }
}
