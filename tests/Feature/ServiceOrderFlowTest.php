<?php

declare(strict_types=1);

namespace Tests\Feature;

use PDO;
use Pdv\Auth\AuthService;
use Pdv\Catalog\CatalogRepository;
use Pdv\Database\ConnectionFactory;
use Pdv\Database\Migrator;
use Pdv\Http\Request;
use Pdv\Http\Router;
use Pdv\Sales\SalesRepository;
use Pdv\Security\Csrf;
use Pdv\ServiceOrders\ServiceOrderRepository;
use Pdv\Support\Env;
use Pdv\View\View;
use PHPUnit\Framework\TestCase;

final class ServiceOrderFlowTest extends TestCase
{
    private string $rootPath;
    private string $tempDir;
    private PDO $pdo;
    private AuthService $auth;
    private CatalogRepository $catalog;
    private SalesRepository $sales;
    private ServiceOrderRepository $orders;
    private Router $router;
    private Csrf $csrf;

    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        $_SESSION = [];
        session_id('pdvorders' . bin2hex(random_bytes(6)));

        $this->rootPath = dirname(__DIR__, 2);
        $this->tempDir = sys_get_temp_dir() . '/pdv_service_order_flow_test_' . bin2hex(random_bytes(6));
        mkdir($this->tempDir, 0775, true);

        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = $this->tempDir . '/test.sqlite';

        Env::load($this->rootPath);

        $this->pdo = (new ConnectionFactory($this->rootPath))->make();
        (new Migrator($this->pdo, $this->rootPath . '/database/migrations'))->migrate();

        $this->auth = new AuthService($this->pdo);
        $this->catalog = new CatalogRepository($this->pdo);
        $this->sales = new SalesRepository($this->pdo);
        $this->orders = new ServiceOrderRepository($this->pdo);
        $this->csrf = new Csrf();
        $this->router = new Router(new View($this->rootPath . '/templates'), $this->auth, $this->csrf, null, $this->catalog, $this->sales, null, $this->orders);
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        $_SESSION = [];

        $files = glob($this->tempDir . '/*') ?: [];
        foreach ($files as $file) {
            @unlink($file);
        }
        @rmdir($this->tempDir);

        unset($_ENV['DB_CONNECTION'], $_ENV['DB_DATABASE']);

        parent::tearDown();
    }

    public function testServiceOrderRoutesRequireAuthentication(): void
    {
        $response = $this->router->dispatch(new Request('GET', '/service-orders', [], [], []));

        self::assertSame(302, $response->status());
        self::assertSame('/login?redirect=%2Fservice-orders', $response->headers()['Location'] ?? null);
    }

    public function testCashierCanOpenCreatePageAndSeeCatalogItems(): void
    {
        $this->loginCashier();
        $this->createServiceVariant();
        $this->createStockProduct();

        $response = $this->router->dispatch(new Request('GET', '/service-orders/create', ['q' => 'Ajuste'], [], []));

        self::assertSame(200, $response->status());
        self::assertStringContainsString('Criar ordem de serviço', $response->body());
        self::assertStringContainsString('Ajuste de barra', $response->body());
        self::assertStringContainsString('Camiseta', $response->body());
        self::assertStringContainsString('Criar ordem', $response->body());
    }

    public function testCashierCreatesOrderWithServiceAndProductItems(): void
    {
        $this->loginCashier();
        $serviceVariantId = $this->createServiceVariant();
        $productVariantId = $this->createStockProduct();

        $response = $this->router->dispatch(new Request('POST', '/service-orders', [], [
            '_token' => $this->csrf->token(),
            'customer_name' => 'Maria Cliente',
            'customer_phone' => '11999990000',
            'description' => 'Ajuste e venda complementar',
            'items' => [
                ['variant_id' => (string) $serviceVariantId, 'quantity' => '1', 'discount' => '0,00'],
                ['variant_id' => (string) $productVariantId, 'quantity' => '2', 'discount' => '10,00'],
                ['variant_id' => '', 'quantity' => '', 'discount' => ''],
            ],
        ], []));

        self::assertSame(302, $response->status());
        $location = $response->headers()['Location'] ?? '';
        self::assertStringStartsWith('/service-orders/', $location);

        $show = $this->router->dispatch(new Request('GET', $location, [], [], []));

        self::assertSame(200, $show->status());
        self::assertStringContainsString('Maria Cliente', $show->body());
        self::assertStringContainsString('Aberta', $show->body());
        self::assertStringContainsString('Ajuste de barra', $show->body());
        self::assertStringContainsString('Camiseta', $show->body());
        self::assertStringContainsString('Sem baixa', $show->body());
        self::assertStringContainsString('Baixa no fechamento', $show->body());
        self::assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM service_orders')->fetchColumn());
        self::assertSame(2, (int) $this->pdo->query('SELECT COUNT(*) FROM service_order_items')->fetchColumn());
    }

    public function testStatusUpdateRecordsHistoryAndRedirects(): void
    {
        $this->loginCashier();
        $orderId = $this->orders->createOrder([
            'opened_by_user_id' => $this->auth->user()['id'] ?? null,
            'customer_name' => 'Cliente status',
            'items' => [
                ['variant_id' => $this->createServiceVariant(), 'quantity' => 1],
            ],
        ]);

        $response = $this->router->dispatch(new Request('POST', '/service-orders/' . $orderId . '/status', [], [
            '_token' => $this->csrf->token(),
            'status' => 'in_progress',
            'notes' => 'Serviço iniciado',
        ], []));

        self::assertSame(302, $response->status());
        self::assertSame('/service-orders/' . $orderId, $response->headers()['Location'] ?? null);

        $show = $this->router->dispatch(new Request('GET', '/service-orders/' . $orderId, [], [], []));

        self::assertSame(200, $show->status());
        self::assertStringContainsString('Em execução', $show->body());
        self::assertStringContainsString('Serviço iniciado', $show->body());
        self::assertCount(2, $this->orders->statusHistoryForOrder($orderId));
    }

    public function testCloseSaleCreatesSaleLinksOrderAndDecrementsProductStock(): void
    {
        $this->loginCashier();
        $serviceVariantId = $this->createServiceVariant();
        $productVariantId = $this->createStockProduct();
        $orderId = $this->orders->createOrder([
            'opened_by_user_id' => $this->auth->user()['id'] ?? null,
            'customer_name' => 'Cliente fechamento web',
            'items' => [
                ['variant_id' => $serviceVariantId, 'quantity' => 1],
                ['variant_id' => $productVariantId, 'quantity' => 2, 'discount' => '10,00'],
            ],
        ]);

        $response = $this->router->dispatch(new Request('POST', '/service-orders/' . $orderId . '/close-sale', [], [
            '_token' => $this->csrf->token(),
            'payments' => [
                ['method' => 'cash', 'amount' => '200,00', 'reference' => 'ordem'],
            ],
        ], []));

        self::assertSame(302, $response->status());
        $location = $response->headers()['Location'] ?? '';
        self::assertStringStartsWith('/sales/', $location);

        $order = $this->orders->findOrder($orderId);
        $saleId = (int) str_replace('/sales/', '', $location);
        $saleShow = $this->router->dispatch(new Request('GET', $location, [], [], []));
        $orderShow = $this->router->dispatch(new Request('GET', '/service-orders/' . $orderId, [], [], []));

        self::assertSame('closed', $order['status'] ?? null);
        self::assertSame($saleId, $order['sale_id'] ?? null);
        self::assertSame(3, $this->catalog->findVariant($productVariantId)['current_stock'] ?? null);
        self::assertCount(1, $this->sales->stockMovementsForSale($saleId));
        self::assertSame(200, $saleShow->status());
        self::assertStringContainsString('Venda concluída', $saleShow->body());
        self::assertStringContainsString('Ajuste de barra', $saleShow->body());
        self::assertStringContainsString('Camiseta', $saleShow->body());
        self::assertSame(200, $orderShow->status());
        self::assertStringContainsString('Fechada', $orderShow->body());
        self::assertStringContainsString('Venda #' . $saleId, $orderShow->body());

        $secondClose = $this->router->dispatch(new Request('POST', '/service-orders/' . $orderId . '/close-sale', [], [
            '_token' => $this->csrf->token(),
            'payments' => [
                ['method' => 'cash', 'amount' => '200,00', 'reference' => 'duplicado'],
            ],
        ], []));
        $manualReopen = $this->router->dispatch(new Request('POST', '/service-orders/' . $orderId . '/status', [], [
            '_token' => $this->csrf->token(),
            'status' => 'open',
            'notes' => 'Tentativa de reabrir',
        ], []));

        self::assertSame(422, $secondClose->status());
        self::assertStringContainsString('Ordem de serviço não pode ser fechada em venda.', $secondClose->body());
        self::assertSame(422, $manualReopen->status());
        self::assertStringContainsString('Ordem fechada em venda não pode ter status alterado manualmente.', $manualReopen->body());
        self::assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM sales')->fetchColumn());
        self::assertSame(3, $this->catalog->findVariant($productVariantId)['current_stock'] ?? null);
        self::assertSame('closed', $this->orders->findOrder($orderId)['status'] ?? null);
    }

    public function testInsufficientPaymentKeepsServiceOrderOpenWithoutSale(): void
    {
        $this->loginCashier();
        $orderId = $this->orders->createOrder([
            'opened_by_user_id' => $this->auth->user()['id'] ?? null,
            'customer_name' => 'Cliente pagamento curto',
            'items' => [
                ['variant_id' => $this->createServiceVariant(), 'quantity' => 1],
            ],
        ]);

        $response = $this->router->dispatch(new Request('POST', '/service-orders/' . $orderId . '/close-sale', [], [
            '_token' => $this->csrf->token(),
            'payments' => [
                ['method' => 'cash', 'amount' => '10,00'],
            ],
        ], []));

        self::assertSame(422, $response->status());
        self::assertStringContainsString('Pagamento insuficiente para finalizar a venda.', $response->body());
        self::assertSame('open', $this->orders->findOrder($orderId)['status'] ?? null);
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM sales')->fetchColumn());
        self::assertCount(1, $this->orders->statusHistoryForOrder($orderId));
    }

    public function testOperationalNavigationLinksToServiceOrders(): void
    {
        $this->loginCashier();

        $dashboard = $this->router->dispatch(new Request('GET', '/dashboard', [], [], []));
        $orders = $this->router->dispatch(new Request('GET', '/service-orders', [], [], []));

        self::assertSame(200, $dashboard->status());
        self::assertStringContainsString('/service-orders', $dashboard->body());
        self::assertStringContainsString('Ordens de serviço', $dashboard->body());
        self::assertSame(200, $orders->status());
        self::assertStringContainsString('Ordens</a>', $orders->body());
        self::assertStringContainsString('Nova ordem', $orders->body());
    }

    public function testInvalidCreateShowsValidationAndDoesNotPersist(): void
    {
        $this->loginCashier();

        $response = $this->router->dispatch(new Request('POST', '/service-orders', [], [
            '_token' => $this->csrf->token(),
            'customer_name' => '',
            'items' => [
                ['variant_id' => '', 'quantity' => '', 'discount' => ''],
            ],
        ], []));

        self::assertSame(422, $response->status());
        self::assertStringContainsString('Informe o nome do cliente.', $response->body());
        self::assertStringContainsString('Adicione pelo menos um serviço ou produto à ordem.', $response->body());
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM service_orders')->fetchColumn());
    }

    private function loginCashier(): void
    {
        $this->auth->createUser('Caixa', 'caixa-os@example.test', 'senha-segura', 'caixa');
        self::assertTrue($this->auth->attempt('caixa-os@example.test', 'senha-segura'));
    }

    private function createServiceVariant(): int
    {
        $productId = $this->catalog->createProduct([
            'type' => 'service',
            'sku' => 'AJUSTE-BARRA-' . bin2hex(random_bytes(3)),
            'name' => 'Ajuste de barra',
            'cost' => '0,00',
            'price' => '35,00',
            'track_stock' => true,
            'active' => true,
        ]);

        return $this->catalog->createVariant($productId, [
            'name' => 'Serviço padrão',
            'sku' => 'AJUSTE-BARRA-PADRAO-' . bin2hex(random_bytes(3)),
            'barcode' => 'SERV-FLOW-' . bin2hex(random_bytes(4)),
            'current_stock' => 0,
            'active' => true,
        ]);
    }

    private function createStockProduct(): int
    {
        $productId = $this->catalog->createProduct([
            'type' => 'product',
            'sku' => 'CAMISETA-OS-' . bin2hex(random_bytes(3)),
            'name' => 'Camiseta',
            'cost' => '30,00',
            'price' => '79,90',
            'track_stock' => true,
            'stock_min' => '2',
            'active' => true,
        ]);

        return $this->catalog->createVariant($productId, [
            'name' => 'M Preta',
            'sku' => 'CAMISETA-OS-M-PRETA-' . bin2hex(random_bytes(3)),
            'barcode' => '789' . random_int(1000000000, 9999999999),
            'cost' => '30,00',
            'price' => '79,90',
            'current_stock' => '5',
            'active' => true,
        ]);
    }
}
