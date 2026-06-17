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
use Pdv\Controllers\PrintController;
use Pdv\Controllers\SalesController;
use Pdv\Controllers\ServiceOrderController;
use Pdv\Controllers\StockController;
use Pdv\Http\Middleware\AuthMiddleware;
use Pdv\Sales\SalesRepository;
use Pdv\ServiceOrders\ServiceOrderRepository;
use Pdv\Stock\StockRepository;
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
        private readonly ?SalesRepository $sales = null,
        private readonly ?StockRepository $stock = null,
        private readonly ?ServiceOrderRepository $serviceOrders = null,
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
        $sales = $this->sales === null || $this->catalog === null ? null : new SalesController($this->view, $this->sales, $this->catalog, $this->auth, $this->csrf);
        $print = $this->sales === null || $this->catalog === null ? null : new PrintController($this->view, $this->sales, $this->catalog, $this->auth, $this->csrf);
        $stock = $this->stock === null ? null : new StockController($this->view, $this->stock, $this->auth, $this->csrf);
        $serviceOrders = $this->serviceOrders === null || $this->catalog === null || $this->sales === null ? null : new ServiceOrderController($this->view, $this->serviceOrders, $this->catalog, $this->sales, $this->auth, $this->csrf);

        return simpleDispatcher(static function (RouteCollector $route) use ($home, $authController, $dashboard, $health, $catalog, $sales, $print, $stock, $serviceOrders): void {
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

            if ($stock !== null) {
                $route->addRoute('GET', '/stock', ['callable' => [$stock, 'index'], 'auth' => true, 'roles' => ['admin', 'estoque']]);
                $route->addRoute('POST', '/stock/replenishments', ['callable' => [$stock, 'replenish'], 'auth' => true, 'roles' => ['admin', 'estoque']]);
                $route->addRoute('POST', '/stock/adjustments', ['callable' => [$stock, 'adjust'], 'auth' => true, 'roles' => ['admin', 'estoque']]);
            }

            if ($serviceOrders !== null) {
                $route->addRoute('GET', '/service-orders', ['callable' => [$serviceOrders, 'index'], 'auth' => true, 'roles' => ['admin', 'caixa']]);
                $route->addRoute('GET', '/service-orders/create', ['callable' => [$serviceOrders, 'create'], 'auth' => true, 'roles' => ['admin', 'caixa']]);
                $route->addRoute('POST', '/service-orders', ['callable' => [$serviceOrders, 'store'], 'auth' => true, 'roles' => ['admin', 'caixa']]);
                $route->addRoute('GET', '/service-orders/{id:\\d+}', ['callable' => [$serviceOrders, 'show'], 'auth' => true, 'roles' => ['admin', 'caixa']]);
                $route->addRoute('POST', '/service-orders/{id:\\d+}/status', ['callable' => [$serviceOrders, 'updateStatus'], 'auth' => true, 'roles' => ['admin', 'caixa']]);
                $route->addRoute('POST', '/service-orders/{id:\\d+}/close-sale', ['callable' => [$serviceOrders, 'closeSale'], 'auth' => true, 'roles' => ['admin', 'caixa']]);
            }

            if ($sales !== null) {
                $route->addRoute('GET', '/pos', ['callable' => [$sales, 'pos'], 'auth' => true, 'roles' => ['admin', 'caixa']]);
                $route->addRoute('POST', '/pos/sales', ['callable' => [$sales, 'finalize'], 'auth' => true, 'roles' => ['admin', 'caixa']]);
                $route->addRoute('GET', '/pos/lookup/barcode', ['callable' => [$sales, 'lookupBarcode'], 'auth' => true, 'roles' => ['admin', 'caixa']]);
                $route->addRoute('GET', '/sales/{id:\\d+}', ['callable' => [$sales, 'show'], 'auth' => true, 'roles' => ['admin', 'caixa']]);
            }

            if ($print !== null) {
                $route->addRoute('GET', '/sales/{id:\\d+}/receipt', ['callable' => [$print, 'receipt'], 'auth' => true, 'roles' => ['admin', 'caixa']]);
                $route->addRoute('GET', '/catalog/{id:\\d+}/variants/{variantId:\\d+}/label', ['callable' => [$print, 'label'], 'auth' => true, 'roles' => ['admin', 'estoque']]);
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
