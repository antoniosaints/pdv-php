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
use Pdv\Stock\StockRepository;
use Pdv\Support\Env;
use Pdv\View\View;
use PHPUnit\Framework\TestCase;

final class StockFlowTest extends TestCase
{
    private string $rootPath;
    private string $tempDir;
    private PDO $pdo;
    private AuthService $auth;
    private CatalogRepository $catalog;
    private StockRepository $stock;
    private Router $router;
    private Csrf $csrf;

    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        $_SESSION = [];
        session_id('pdvstock' . bin2hex(random_bytes(6)));

        $this->rootPath = dirname(__DIR__, 2);
        $this->tempDir = sys_get_temp_dir() . '/pdv_stock_flow_test_' . bin2hex(random_bytes(6));
        mkdir($this->tempDir, 0775, true);

        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = $this->tempDir . '/test.sqlite';

        Env::load($this->rootPath);

        $this->pdo = (new ConnectionFactory($this->rootPath))->make();
        (new Migrator($this->pdo, $this->rootPath . '/database/migrations'))->migrate();

        $this->auth = new AuthService($this->pdo);
        $this->catalog = new CatalogRepository($this->pdo);
        $this->stock = new StockRepository($this->pdo);
        $this->csrf = new Csrf();
        $this->router = new Router(new View($this->rootPath . '/templates'), $this->auth, $this->csrf, null, $this->catalog, new SalesRepository($this->pdo), $this->stock);
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

    public function testStockRouteRequiresAuthentication(): void
    {
        $response = $this->router->dispatch(new Request('GET', '/stock', [], [], []));

        self::assertSame(302, $response->status());
        self::assertSame('/login?redirect=%2Fstock', $response->headers()['Location'] ?? null);
    }

    public function testStockPageShowsLowStockAndMovementHistory(): void
    {
        $this->loginStockUser();
        $variantId = $this->createTrackedVariant('Camiseta baixa', 2, 3);
        $this->stock->recordReplenishment([
            'variant_id' => $variantId,
            'quantity' => 1,
            'reason' => 'Compra teste',
        ]);

        $response = $this->router->dispatch(new Request('GET', '/stock', [], [], []));

        self::assertSame(200, $response->status());
        self::assertStringContainsString('Reposição e ajustes', $response->body());
        self::assertStringContainsString('Camiseta baixa', $response->body());
        self::assertStringContainsString('Estoque mínimo', $response->body());
        self::assertStringContainsString('Compra teste', $response->body());
        self::assertStringContainsString('purchase', $response->body());
    }

    public function testReplenishmentFormUpdatesStockAndRedirects(): void
    {
        $this->loginStockUser();
        $variantId = $this->createTrackedVariant('Bolsa reposicao', 1, 2);

        $response = $this->router->dispatch(new Request('POST', '/stock/replenishments', [], [
            '_token' => $this->csrf->token(),
            'variant_id' => (string) $variantId,
            'quantity' => '4',
            'reason' => 'Compra semanal',
        ], []));

        self::assertSame(302, $response->status());
        self::assertSame('/stock', $response->headers()['Location'] ?? null);
        self::assertSame(5, $this->catalog->findVariant($variantId)['current_stock'] ?? null);
        self::assertSame('purchase', $this->stock->recentMovements(1)[0]['type'] ?? null);
    }

    public function testInvalidAdjustmentShowsValidationAndDoesNotChangeStock(): void
    {
        $this->loginStockUser();
        $variantId = $this->createTrackedVariant('Produto ajuste invalido', 1, 1);

        $response = $this->router->dispatch(new Request('POST', '/stock/adjustments', [], [
            '_token' => $this->csrf->token(),
            'variant_id' => (string) $variantId,
            'delta' => '-2',
            'reason' => 'Perda',
        ], []));

        self::assertSame(422, $response->status());
        self::assertStringContainsString('Movimento deixaria o estoque negativo.', $response->body());
        self::assertSame(1, $this->catalog->findVariant($variantId)['current_stock'] ?? null);
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM stock_movements')->fetchColumn());
    }

    public function testOperationalNavigationLinksToStock(): void
    {
        $this->loginStockUser();

        $dashboard = $this->router->dispatch(new Request('GET', '/dashboard', [], [], []));
        $stock = $this->router->dispatch(new Request('GET', '/stock', [], [], []));

        self::assertSame(200, $dashboard->status());
        self::assertStringContainsString('/stock', $dashboard->body());
        self::assertSame(200, $stock->status());
        self::assertStringContainsString('Estoque</a>', $stock->body());
    }

    private function loginStockUser(): void
    {
        $this->auth->createUser('Estoque', 'estoque@example.test', 'senha-segura', 'estoque');
        self::assertTrue($this->auth->attempt('estoque@example.test', 'senha-segura'));
    }

    private function createTrackedVariant(string $name, int $stock, int $minimum): int
    {
        $productId = $this->catalog->createProduct([
            'type' => 'product',
            'name' => $name,
            'price' => '10,00',
            'track_stock' => true,
            'stock_min' => $minimum,
            'active' => true,
        ]);

        return $this->catalog->createVariant($productId, [
            'name' => 'Única',
            'barcode' => 'FLOW-' . bin2hex(random_bytes(4)),
            'current_stock' => $stock,
            'active' => true,
        ]);
    }
}
