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
use Pdv\Support\Env;
use Pdv\View\View;
use PHPUnit\Framework\TestCase;

final class SalesFlowTest extends TestCase
{
    private string $rootPath;
    private string $tempDir;
    private PDO $pdo;
    private AuthService $auth;
    private CatalogRepository $catalog;
    private SalesRepository $sales;
    private Router $router;
    private Csrf $csrf;

    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        $_SESSION = [];
        session_id('pdvsales' . bin2hex(random_bytes(6)));

        $this->rootPath = dirname(__DIR__, 2);
        $this->tempDir = sys_get_temp_dir() . '/pdv_sales_flow_test_' . bin2hex(random_bytes(6));
        mkdir($this->tempDir, 0775, true);

        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = $this->tempDir . '/test.sqlite';

        Env::load($this->rootPath);

        $this->pdo = (new ConnectionFactory($this->rootPath))->make();
        (new Migrator($this->pdo, $this->rootPath . '/database/migrations'))->migrate();

        $this->auth = new AuthService($this->pdo);
        $this->catalog = new CatalogRepository($this->pdo);
        $this->sales = new SalesRepository($this->pdo);
        $this->csrf = new Csrf();
        $this->router = new Router(new View($this->rootPath . '/templates'), $this->auth, $this->csrf, null, $this->catalog, $this->sales);
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

    public function testPosRoutesRequireAuthentication(): void
    {
        $response = $this->router->dispatch(new Request('GET', '/pos', [], [], []));

        self::assertSame(302, $response->status());
        self::assertSame('/login?redirect=%2Fpos', $response->headers()['Location'] ?? null);
    }

    public function testCashierCanOpenPosAndSelectBarcodeItem(): void
    {
        $this->loginCashier();
        $this->createStockProduct();

        $response = $this->router->dispatch(new Request('GET', '/pos', ['barcode' => '7890000000010'], [], []));

        self::assertSame(200, $response->status());
        self::assertStringContainsString('Venda rápida', $response->body());
        self::assertStringContainsString('Camiseta', $response->body());
        self::assertStringContainsString('Finalizar venda', $response->body());
    }

    public function testCashierFinalizesSaleAndSeesStockDecrement(): void
    {
        $this->loginCashier();
        $variantId = $this->createStockProduct();

        $response = $this->router->dispatch(new Request('POST', '/pos/sales', [], [
            '_token' => $this->csrf->token(),
            'items' => [
                ['variant_id' => (string) $variantId, 'quantity' => '2', 'discount' => '10,00'],
            ],
            'payments' => [
                ['method' => 'cash', 'amount' => '150,00', 'reference' => 'gaveta'],
            ],
        ], []));

        self::assertSame(302, $response->status());
        $location = $response->headers()['Location'] ?? '';
        self::assertStringStartsWith('/sales/', $location);
        self::assertSame(3, $this->catalog->findVariant($variantId)['current_stock'] ?? null);

        $show = $this->router->dispatch(new Request('GET', $location, [], [], []));

        self::assertSame(200, $show->status());
        self::assertStringContainsString('Venda concluída', $show->body());
        self::assertStringContainsString('Camiseta', $show->body());
        self::assertStringContainsString('5 → 3', $show->body());
    }

    public function testInsufficientStockReturnsValidationMessageWithoutChangingStock(): void
    {
        $this->loginCashier();
        $variantId = $this->createStockProduct();

        $response = $this->router->dispatch(new Request('POST', '/pos/sales', [], [
            '_token' => $this->csrf->token(),
            'items' => [
                ['variant_id' => (string) $variantId, 'quantity' => '6', 'discount' => '0,00'],
            ],
            'payments' => [
                ['method' => 'cash', 'amount' => '500,00'],
            ],
        ], []));

        self::assertSame(422, $response->status());
        self::assertStringContainsString('Estoque insuficiente para finalizar a venda.', $response->body());
        self::assertSame(5, $this->catalog->findVariant($variantId)['current_stock'] ?? null);
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM sales')->fetchColumn());
    }

    private function loginCashier(): void
    {
        $this->auth->createUser('Caixa', 'caixa@example.test', 'senha-segura', 'caixa');
        self::assertTrue($this->auth->attempt('caixa@example.test', 'senha-segura'));
    }

    private function createStockProduct(): int
    {
        $productId = $this->catalog->createProduct([
            'type' => 'product',
            'sku' => 'CAMISETA',
            'name' => 'Camiseta',
            'cost' => '30,00',
            'price' => '79,90',
            'track_stock' => true,
            'stock_min' => '2',
            'active' => true,
        ]);

        return $this->catalog->createVariant($productId, [
            'name' => 'M Preta',
            'sku' => 'CAMISETA-M-PRETA',
            'barcode' => '7890000000010',
            'cost' => '30,00',
            'price' => '79,90',
            'current_stock' => '5',
            'active' => true,
        ]);
    }
}
