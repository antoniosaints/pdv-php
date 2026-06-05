<?php

declare(strict_types=1);

namespace Pdv\Http;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Pdv\Auth\AuthService;
use Pdv\Catalog\CatalogRepository;
use Pdv\Controllers\AuthController;
use Pdv\Controllers\CatalogController;
use Pdv\Controllers\DashboardController;
use Pdv\Controllers\HealthController;
use Pdv\Controllers\HomeController;
use Pdv\Http\Middleware\AuthMiddleware;
use Pdv\Security\Csrf;
use Pdv\Support\HealthCheck;
use Pdv\View\View;
use function FastRoute\simpleDispatcher;

final class Router
{
    public function __construct(
        private readonly View $view,
        private readonly AuthService $auth,
        private readonly Csrf $csrf,
        private readonly ?HealthCheck $health = null,
        private readonly ?CatalogRepository $catalog = null,
    ) {
    }

    public function dispatch(Request $request): Response
    {
        $dispatcher = $this->dispatcher();
        $routeInfo = $dispatcher->dispatch($request->method(), $request->path());

        return match ($routeInfo[0]) {
            Dispatcher::NOT_FOUND => $this->notFound(),
            Dispatcher::METHOD_NOT_ALLOWED => $this->methodNotAllowed(),
            Dispatcher::FOUND => $this->runFoundRoute($request, $routeInfo[1], $routeInfo[2]),
            default => $this->notFound(),
        };
    }

    private function dispatcher(): Dispatcher
    {
        $home = new HomeController($this->view);
        $authController = new AuthController($this->view, $this->auth, $this->csrf);
        $dashboard = new DashboardController($this->view, $this->auth, $this->csrf);
        $health = $this->health === null ? null : new HealthController($this->view, $this->health, $this->auth, $this->csrf);
        $catalog = $this->catalog === null ? null : new CatalogController($this->view, $this->catalog, $this->auth, $this->csrf);

        return simpleDispatcher(static function (RouteCollector $route) use ($home, $authController, $dashboard, $health, $catalog): void {
            $route->addRoute('GET', '/', ['callable' => [$home, 'index']]);
            $route->addRoute('GET', '/login', ['callable' => [$authController, 'showLogin']]);
            $route->addRoute('POST', '/login', ['callable' => [$authController, 'login']]);
            $route->addRoute('POST', '/logout', ['callable' => [$authController, 'logout'], 'auth' => true]);
            $route->addRoute('GET', '/setup/admin', ['callable' => [$authController, 'showSetup']]);
            $route->addRoute('POST', '/setup/admin', ['callable' => [$authController, 'storeSetup']]);
            $route->addRoute('GET', '/dashboard', ['callable' => [$dashboard, 'index'], 'auth' => true]);

            if ($health !== null) {
                $route->addRoute('GET', '/health', ['callable' => [$health, 'index'], 'auth' => true, 'roles' => ['admin']]);
            }

            if ($catalog !== null) {
                $route->addRoute('GET', '/catalog', ['callable' => [$catalog, 'index'], 'auth' => true, 'roles' => ['admin', 'estoque']]);
                $route->addRoute('GET', '/catalog/create', ['callable' => [$catalog, 'create'], 'auth' => true, 'roles' => ['admin', 'estoque']]);
                $route->addRoute('POST', '/catalog', ['callable' => [$catalog, 'store'], 'auth' => true, 'roles' => ['admin', 'estoque']]);
                $route->addRoute('GET', '/catalog/{id:\\d+}', ['callable' => [$catalog, 'show'], 'auth' => true, 'roles' => ['admin', 'estoque']]);
                $route->addRoute('GET', '/catalog/{id:\\d+}/edit', ['callable' => [$catalog, 'edit'], 'auth' => true, 'roles' => ['admin', 'estoque']]);
                $route->addRoute('POST', '/catalog/{id:\\d+}', ['callable' => [$catalog, 'update'], 'auth' => true, 'roles' => ['admin', 'estoque']]);
                $route->addRoute('POST', '/catalog/{id:\\d+}/toggle', ['callable' => [$catalog, 'toggle'], 'auth' => true, 'roles' => ['admin', 'estoque']]);
                $route->addRoute('POST', '/catalog/{id:\\d+}/variants', ['callable' => [$catalog, 'storeVariant'], 'auth' => true, 'roles' => ['admin', 'estoque']]);
                $route->addRoute('POST', '/catalog/{id:\\d+}/variants/{variantId:\\d+}', ['callable' => [$catalog, 'updateVariant'], 'auth' => true, 'roles' => ['admin', 'estoque']]);
                $route->addRoute('GET', '/catalog/lookup/barcode', ['callable' => [$catalog, 'lookupBarcode'], 'auth' => true, 'roles' => ['admin', 'estoque', 'caixa']]);
                $route->addRoute('GET', '/catalog/search', ['callable' => [$catalog, 'search'], 'auth' => true, 'roles' => ['admin', 'estoque', 'caixa']]);
            }
        });
    }

    /**
     * @param array{callable:callable,auth?:bool,roles?:list<string>} $handler
     * @param array<string, string> $vars
     */
    private function runFoundRoute(Request $request, array $handler, array $vars = []): Response
    {
        $next = fn (): Response => $handler['callable']($request, $vars);

        if (($handler['auth'] ?? false) === true) {
            return (new AuthMiddleware($this->auth))->handle($request, $next, $handler['roles'] ?? []);
        }

        return $next();
    }

    private function notFound(): Response
    {
        return Response::html(
            '<main class="shell shell--narrow"><section class="panel form-panel"><p class="eyebrow">404</p><h1>Página não encontrada</h1><p>Essa rota ainda não existe no PDV.</p><a class="button" href="/">Voltar ao início</a></section></main>',
            404
        );
    }

    private function methodNotAllowed(): Response
    {
        return Response::html(
            '<main class="shell shell--narrow"><section class="panel form-panel"><p class="eyebrow">405</p><h1>Método não permitido</h1><p>A ação enviada não combina com esta rota.</p><a class="button" href="/">Voltar ao início</a></section></main>',
            405
        );
    }
}
