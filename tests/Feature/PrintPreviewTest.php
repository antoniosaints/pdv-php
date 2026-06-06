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

final class PrintPreviewTest extends TestCase
{
    private string $rootPath;
    private string $tempDir;
    private PDO $pdo;
    private AuthService $auth;
    private CatalogRepository $catalog;
    private SalesRepository $sales;
    private Router $router;

    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        $_SESSION = [];
        session_id('pdvprint' . bin2hex(random_bytes(6)));

        $this->rootPath = dirname(__DIR__, 2);
        $this->tempDir = sys_get_temp_dir() . '/pdv_print_preview_test_' . bin2hex(random_bytes(6));
        mkdir($this->tempDir, 0775, true);

        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = $this->tempDir . '/test.sqlite';

        Env::load($this->rootPath);

        $this->pdo = (new ConnectionFactory($this->rootPath))->make();
        (new Migrator($this->pdo, $this->rootPath . '/database/migrations'))->migrate();

        $this->auth = new AuthService($this->pdo);
        $this->catalog = new CatalogRepository($this->pdo);
        $this->sales = new SalesRepository($this->pdo);
        $this->router = new Router(new View($this->rootPath . '/templates'), $this->auth, new Csrf(), null, $this->catalog, $this->sales);
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

    public function testReceiptPreviewRequiresAuthentication(): void
    {
        $response = $this->router->dispatch(new Request('GET', '/sales/1/receipt', [], [], []));

        self::assertSame(302, $response->status());
        self::assertSame('/login?redirect=%2Fsales%2F1%2Freceipt', $response->headers()['Location'] ?? null);
    }

    public function testReceiptPreviewShowsSaleFactsAndPrintDiagnostics(): void
    {
        $this->loginAdmin();
        [, $variantId] = $this->createProductAndVariant();
        $saleId = $this->sales->completeSale([
            'items' => [
                ['variant_id' => $variantId, 'quantity' => 1],
            ],
            'payments' => [
                ['method' => 'pix', 'amount' => '79,90'],
            ],
        ]);

        $response = $this->router->dispatch(new Request('GET', '/sales/' . $saleId . '/receipt', [], [], []));

        self::assertSame(200, $response->status());
        self::assertStringContainsString('Preview do recibo', $response->body());
        self::assertStringContainsString('Recibo gerencial não fiscal', $response->body());
        self::assertStringContainsString('Camiseta', $response->body());
        self::assertStringContainsString('R$ 79,90', $response->body());
        self::assertStringContainsString('Status de impressão', $response->body());
        self::assertStringContainsString('Enviar para QZ Tray', $response->body());
        self::assertStringContainsString('data-print-status-panel', $response->body());
        self::assertStringContainsString('/assets/print.js', $response->body());
    }

    public function testLabelPreviewShowsCatalogFactsAndPrintDiagnostics(): void
    {
        $this->loginAdmin();
        [$productId, $variantId] = $this->createProductAndVariant();

        $response = $this->router->dispatch(new Request('GET', '/catalog/' . $productId . '/variants/' . $variantId . '/label', [], [], []));

        self::assertSame(200, $response->status());
        self::assertStringContainsString('Preview da etiqueta', $response->body());
        self::assertStringContainsString('CAMISETA ETIQUETA', $response->body());
        self::assertStringContainsString('7890000000010', $response->body());
        self::assertStringContainsString('R$ 79,90', $response->body());
        self::assertStringContainsString('Status de impressão', $response->body());
        self::assertStringContainsString('Impressão nativa', $response->body());
        self::assertStringContainsString('data-print-status-panel', $response->body());
        self::assertStringContainsString('/assets/print.js', $response->body());
    }

    public function testOperationalPagesExposeReceiptAndLabelLinks(): void
    {
        $this->loginAdmin();
        [$productId, $variantId] = $this->createProductAndVariant();
        $saleId = $this->sales->completeSale([
            'items' => [
                ['variant_id' => $variantId, 'quantity' => 1],
            ],
            'payments' => [
                ['method' => 'pix', 'amount' => '79,90'],
            ],
        ]);

        $salePage = $this->router->dispatch(new Request('GET', '/sales/' . $saleId, [], [], []));
        $catalogPage = $this->router->dispatch(new Request('GET', '/catalog/' . $productId, [], [], []));

        self::assertSame(200, $salePage->status());
        self::assertStringContainsString('/sales/' . $saleId . '/receipt', $salePage->body());
        self::assertSame(200, $catalogPage->status());
        self::assertStringContainsString('/catalog/' . $productId . '/variants/' . $variantId . '/label', $catalogPage->body());
    }

    private function loginAdmin(): void
    {
        $this->auth->createUser('Admin', 'admin@example.test', 'senha-segura', 'admin');
        self::assertTrue($this->auth->attempt('admin@example.test', 'senha-segura'));
    }

    /** @return array{0:int,1:int} */
    private function createProductAndVariant(): array
    {
        $productId = $this->catalog->createProduct([
            'type' => 'product',
            'sku' => 'CAMISETA',
            'name' => 'Camiseta',
            'cost' => '30,00',
            'price' => '79,90',
            'track_stock' => true,
            'label_name' => 'CAMISETA ETIQUETA',
            'active' => true,
        ]);
        $variantId = $this->catalog->createVariant($productId, [
            'name' => 'M Preta',
            'sku' => 'CAMISETA-M-PRETA',
            'barcode' => '7890000000010',
            'current_stock' => '5',
            'active' => true,
        ]);

        return [$productId, $variantId];
    }
}
