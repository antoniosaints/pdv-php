#!/usr/bin/env php
<?php

declare(strict_types=1);

use Pdv\Database\ConnectionFactory;
use Pdv\Database\Migrator;
use Pdv\Support\Env;
use Pdv\Support\HealthCheck;

$rootPath = dirname(__DIR__);
$autoloadPath = $rootPath . '/vendor/autoload.php';

if (! is_file($autoloadPath)) {
    fwrite(STDERR, "[FAIL] vendor/autoload.php não encontrado. Execute composer install.\n");
    exit(1);
}

require $autoloadPath;

Env::load($rootPath);

try {
    $pdo = (new ConnectionFactory($rootPath))->make();
    $health = new HealthCheck($rootPath, $pdo, new Migrator($pdo, $rootPath . '/database/migrations'));
    $report = $health->report();

    foreach ($report as $check) {
        $prefix = match ($check['status']) {
            'ok' => '[OK] ',
            'warn' => '[WARN] ',
            default => '[FAIL] ',
        };

        echo $prefix . $check['label'] . ': ' . $check['detail'] . PHP_EOL;
    }

    if (! $health->passes()) {
        fwrite(STDERR, "Instalação precisa de atenção antes de operar o PDV.\n");
        exit(1);
    }

    echo "Instalação verificada com sucesso.\n";
} catch (Throwable $throwable) {
    fwrite(STDERR, '[FAIL] Verificação falhou: ' . $throwable->getMessage() . PHP_EOL);
    exit(1);
}
