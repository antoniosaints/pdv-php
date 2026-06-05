<?php

declare(strict_types=1);

use Pdv\Auth\AuthService;
use Pdv\Catalog\CatalogRepository;
use Pdv\Database\ConnectionFactory;
use Pdv\Database\Migrator;
use Pdv\Http\Request;
use Pdv\Http\Response;
use Pdv\Http\Router;
use Pdv\Security\Csrf;
use Pdv\Support\Env;
use Pdv\Support\HealthCheck;
use Pdv\View\View;

$rootPath = dirname(__DIR__);
$autoloadPath = $rootPath . '/vendor/autoload.php';

if (! is_file($autoloadPath)) {
    http_response_code(500);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<h1>Dependências não instaladas</h1>';
    echo '<p>Execute <code>composer install</code> antes de acessar o sistema.</p>';
    exit;
}

require $autoloadPath;

Env::load($rootPath);

try {
    $view = new View($rootPath . '/templates');
    $pdo = (new ConnectionFactory($rootPath))->make();
    $migrator = new Migrator($pdo, $rootPath . '/database/migrations');
    $auth = new AuthService($pdo);
    $router = new Router($view, $auth, new Csrf(), new HealthCheck($rootPath, $pdo, $migrator), new CatalogRepository($pdo));

    $response = $router->dispatch(Request::fromGlobals());
} catch (Throwable $throwable) {
    error_log('[pdv] bootstrap_failure ' . $throwable->getMessage());
    $response = Response::html(
        '<main class="shell shell--narrow"><section class="panel form-panel"><p class="eyebrow">Erro de inicialização</p><h1>Não foi possível abrir o sistema.</h1><p>Confira se o Composer foi instalado e se as migrations foram executadas com <code>php bin/console migrate</code>.</p></section></main>',
        500
    );
}

$response->send();
