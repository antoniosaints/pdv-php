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
use Pdv\Security\Csrf;
use Pdv\Support\Env;
use Pdv\View\View;
use PHPUnit\Framework\TestCase;

final class CatalogVariantTest extends TestCase
{
    private string $rootPath;
    private string $tempDir;
    private AuthService $auth;
    private CatalogRepository $catalog;
    private Router $router;
    private Csrf $csrf;

    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        $_SESSION = [];
        session_id('pdvvariant' . bin2hex(random_bytes(6)));

        $this->rootPath = dirname(__DIR__, 2);
        $this->tempDir = sys_get_temp_dir() . '/pdv_variant_test_' . bin2hex(random_bytes(6));
        mkdir($this->tempDir, 0775, true);

        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = $this->tempDir . '/test.sqlite';

        Env::load($this->rootPath);

        $pdo = (new ConnectionFactory($this->rootPath))->make();
        (new Migrator($pdo, $this->rootPath . '/database/migrations'))->migrate();

        $this->auth = new AuthService($pdo);
        $this->catalog = new CatalogRepository($pdo);
        $this->csrf = new Csrf();
        $this->router = new Router(new View($this->rootPath . '/templates'), $this->auth, $this->csrf, null, $this->catalog);
        $this->loginAdmin();
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

    public function testAdminAddsVariantWithBarcodeFromProductDetail(): void
    {
        $productId = $this->productId();

        $response = $this->router->dispatch(new Request('POST', '/catalog/' . $productId . '/variants', [], [
            '_token' => $this->csrf->token(),
            'variant_name' => 'Preta / M',
            'variant_sku' => 'CAM-PRETA-M',
            'barcode' => '7890000000011',
            'variant_price' => '64,90',
            'current_stock' => '8',
            'variant_active' => '1',
        ], []));

        self::assertSame(302, $response->status());
        self::assertSame('/catalog/' . $productId, $response->headers()['Location'] ?? null);

        $detail = $this->router->dispatch(new Request('GET', '/catalog/' . $productId, [], [], []));

        self::assertStringContainsString('Preta / M', $detail->body());
        self::assertStringContainsString('7890000000011', $detail->body());
    }

    public function testDuplicateBarcodeShowsActionableValidation(): void
    {
        $productId = $this->productId();
        $this->catalog->createVariant($productId, [
            'name' => 'Azul / P',
            'barcode' => 'DUP-001',
        ]);

        $response = $this->router->dispatch(new Request('POST', '/catalog/' . $productId . '/variants', [], [
            '_token' => $this->csrf->token(),
            'variant_name' => 'Azul / M',
            'barcode' => 'DUP-001',
            'current_stock' => '2',
            'variant_active' => '1',
        ], []));

        self::assertSame(422, $response->status());
        self::assertStringContainsString('Já existe uma variante com este código de barras.', $response->body());
    }

    public function testLookupEndpointsReturnSaleReadyData(): void
    {
        $productId = $this->productId();
        $this->catalog->createVariant($productId, [
            'name' => 'Branca / G',
            'sku' => 'CAM-BRANCA-G',
            'barcode' => '7890000000028',
            'price' => '69,90',
            'current_stock' => '4',
        ]);

        $barcode = $this->router->dispatch(new Request('GET', '/catalog/lookup/barcode', ['barcode' => '7890000000028'], [], []));
        $search = $this->router->dispatch(new Request('GET', '/catalog/search', ['q' => 'branca'], [], []));

        self::assertSame(200, $barcode->status());
        self::assertStringContainsString('7890000000028', $barcode->body());
        self::assertStringContainsString('effective_price_cents', $barcode->body());
        self::assertSame(200, $search->status());
        self::assertStringContainsString('Branca / G', $search->body());
    }

    private function productId(): int
    {
        return $this->catalog->createProduct([
            'type' => 'product',
            'name' => 'Camiseta Básica',
            'sku' => 'CAM-BASICA',
            'price' => '59,90',
            'track_stock' => true,
        ]);
    }

    private function loginAdmin(): void
    {
        $this->auth->createUser('Admin', 'admin@example.test', 'senha-segura', 'admin');
        self::assertTrue($this->auth->attempt('admin@example.test', 'senha-segura'));
    }
}
