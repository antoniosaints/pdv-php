<?php

declare(strict_types=1);

namespace Pdv\Support;

use PDO;
use Pdv\Database\Migrator;

final class HealthCheck
{
    public function __construct(
        private readonly string $rootPath,
        private readonly PDO $pdo,
        private readonly Migrator $migrator,
    ) {
    }

    /** @return array<string, array{label:string,status:string,detail:string}> */
    public function report(): array
    {
        $storagePath = $this->rootPath . '/storage';
        $logPath = Env::path($this->rootPath, 'LOG_PATH', 'storage/logs/app.log');
        $databasePath = Env::path($this->rootPath, 'DB_DATABASE', 'storage/database/pdv.sqlite');
        $migrationStatus = $this->migrator->status();
        $driver = (string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        return [
            'php' => [
                'label' => 'PHP',
                'status' => version_compare(PHP_VERSION, '8.1.0', '>=') ? 'ok' : 'fail',
                'detail' => PHP_VERSION,
            ],
            'database' => [
                'label' => 'Banco de dados',
                'status' => $this->databaseResponds() ? 'ok' : 'fail',
                'detail' => strtoupper($driver) . ($driver === 'sqlite' ? ' · ' . $this->relativePath($databasePath) : ''),
            ],
            'migrations' => [
                'label' => 'Migrations',
                'status' => $migrationStatus['pending'] === [] ? 'ok' : 'warn',
                'detail' => count($migrationStatus['executed']) . ' executada(s), ' . count($migrationStatus['pending']) . ' pendente(s)',
            ],
            'storage' => [
                'label' => 'Storage',
                'status' => is_dir($storagePath) && is_writable($storagePath) ? 'ok' : 'fail',
                'detail' => $this->relativePath($storagePath) . (is_writable($storagePath) ? ' gravável' : ' sem escrita'),
            ],
            'logs' => [
                'label' => 'Logs',
                'status' => $this->directoryWritable(dirname($logPath)) ? 'ok' : 'fail',
                'detail' => $this->relativePath(dirname($logPath)) . (is_file($logPath) ? ' · app.log existe' : ' · app.log será criado sob demanda'),
            ],
            'environment' => [
                'label' => 'Ambiente',
                'status' => 'ok',
                'detail' => Env::get('APP_ENV', 'local') ?? 'local',
            ],
        ];
    }

    public function passes(): bool
    {
        foreach ($this->report() as $check) {
            if ($check['status'] === 'fail') {
                return false;
            }
        }

        return true;
    }

    private function databaseResponds(): bool
    {
        return $this->pdo->query('SELECT 1')->fetchColumn() !== false;
    }

    private function directoryWritable(string $path): bool
    {
        return is_dir($path) && is_writable($path);
    }

    private function relativePath(string $path): string
    {
        $normalizedRoot = str_replace('\\', '/', rtrim($this->rootPath, '/\\'));
        $normalizedPath = str_replace('\\', '/', $path);

        if (str_starts_with($normalizedPath, $normalizedRoot)) {
            return ltrim(substr($normalizedPath, strlen($normalizedRoot)), '/');
        }

        return basename($path);
    }
}
