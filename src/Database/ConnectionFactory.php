<?php

declare(strict_types=1);

namespace Pdv\Database;

use PDO;
use RuntimeException;

final class ConnectionFactory
{
    public function __construct(private readonly string $rootPath)
    {
    }

    public function make(): PDO
    {
        $rootPath = $this->rootPath;
        $configFile = $rootPath . '/config/database.php';

        if (! is_file($configFile)) {
            throw new RuntimeException('Database configuration file not found.');
        }

        /** @var array{default:string,connections:array<string,array<string,string>>} $config */
        $config = require $configFile;
        $connectionName = $config['default'];
        $connection = $config['connections'][$connectionName] ?? null;

        if ($connection === null) {
            throw new RuntimeException("Database connection [{$connectionName}] is not configured.");
        }

        return match ($connection['driver']) {
            'sqlite' => $this->sqlite($connection),
            'mysql' => $this->mysql($connection),
            default => throw new RuntimeException("Unsupported database driver [{$connection['driver']}]."),
        };
    }

    /** @param array<string, string> $config */
    private function sqlite(array $config): PDO
    {
        $database = $config['database'];
        $directory = dirname($database);

        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException("Unable to create SQLite directory [{$directory}].");
        }

        $pdo = new PDO('sqlite:' . $database, null, null, $this->options());
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');

        return $pdo;
    }

    /** @param array<string, string> $config */
    private function mysql(array $config): PDO
    {
        $charset = $config['charset'] ?? 'utf8mb4';
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $charset
        );

        return new PDO($dsn, $config['username'], $config['password'], $this->options());
    }

    /** @return array<int, mixed> */
    private function options(): array
    {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
    }
}
