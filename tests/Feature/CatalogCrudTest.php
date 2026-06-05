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

final class CatalogCrudTest extends TestCase
{
    private string $rootPath;
    private string $tempDir;
    private PDO $pdo;
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
        session_id('pdvcatalog' . bin2hex(random_bytes(6)));

        $this->rootPath = dirname(__DIR__, 2);
        $this->tempDir = sys_get_temp_dir() . '/pdv_catalog_crud_test_' . bin2hex(random_bytes(6));
        mkdir($this->tempDir, 0775, true);

        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = $this->tempDir . '/test.sqlite';

        Env::load($this->rootPath);

        $this->pdo = (new ConnectionFactory($this->rootPath))->make();
        (new Migrator($this->pdo, $this->rootPath . '/database/migrations'))->migrate();

        $this->auth = new AuthService($this->pdo);
        $this->catalog = new CatalogRepository($this->pdo);
        $this->csrf = new Csrf();
        $this->router = new Router(new View($this->rootPath . '/templates'), $this->auth, $this->csrf, null, $this->catalog);
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

    public function testCatalogRoutesRequireAuthentication(): void
    {
        $response = $this->router->dispatch(new Request('GET', '/catalog', [], [], []));

        self::assertSame(302, $response->status());
        self::assertSame('/login?redirect=%2Fcatalog', $response->headers()['Location'] ?? null);
    }

    public function testAdminCreatesAndViewsProduct(): void
    {
        $this->loginAdmin();
        $token = $this->csrf->token();

        $response = $this->router->dispatch(new Request('POST', '/catalog', [], [
            '_token' => $token,
            'type' => 'product',
            'sku' => 'BOLSA-COURO',
            'name' => 'Bolsa de Couro',
            'description' => 'Bolsa artesanal',
            'cost' => '80,00',
            'price' => '149,90',
            'track_stock' => '1',
            'stock_min' => '3',
            'label_name' => 'BOLSA COURO',
            'active' => '1',
        ], []));

        self::assertSame(302, $response->status());
        $location = $response->headers()['Location'] ?? '';
        self::assertStringStartsWith('/catalog/', $location);

        $show = $this->router->dispatch(new Request('GET', $location, [], [], []));

        self::assertSame(200, $show->status());
        self::assertStringContainsString('Bolsa de Couro', $show->body());
        self::assertStringContainsString('R$ 149,90', $show->body());
    }

    public function testInvalidCatalogFormReturnsFieldError(): void
    {
        $this->loginAdmin();

        $response = $this->router->dispatch(new Request('POST', '/catalog', [], [
            '_token' => $this->csrf->token(),
            'type' => 'product',
            'name' => '',
            'price' => '-1',
            'active' => '1',
        ], []));

        self::assertSame(422, $response->status());
        self::assertStringContainsString('Informe um nome com pelo menos 2 caracteres.', $response->body());
        self::assertStringContainsString('Preço não pode ser negativo.', $response->body());
    }

    public function testAdminUpdatesAndTogglesProduct(): void
    {
        $this->loginAdmin();
        $productId = $this->catalog->createProduct([
            'type' => 'product',
            'sku' => 'MEIA',
            'name' => 'Meia',
            'price' => '12,00',
            'active' => true,
        ]);

        $update = $this->router->dispatch(new Request('POST', '/catalog/' . $productId, [], [
            '_token' => $this->csrf->token(),
            'type' => 'service',
            'sku' => 'AJUSTE-MEIA',
            'name' => 'Ajuste especial',
            'description' => 'Serviço ajustado',
            'cost' => '5,00',
            'price' => '20,00',
            'track_stock' => '1',
            'stock_min' => '0',
            'label_name' => 'AJUSTE',
            'active' => '1',
        ], []));

        self::assertSame(302, $update->status());
        $updated = $this->catalog->findProduct($productId);
        self::assertSame('service', $updated['type'] ?? null);
        self::assertSame(0, $updated['track_stock'] ?? null);
        self::assertSame(2000, $updated['price_cents'] ?? null);

        $toggle = $this->router->dispatch(new Request('POST', '/catalog/' . $productId . '/toggle', [], [
            '_token' => $this->csrf->token(),
        ], []));

        self::assertSame(302, $toggle->status());
        self::assertSame(0, $this->catalog->findProduct($productId)['active'] ?? null);
    }

    private function loginAdmin(): void
    {
        $this->auth->createUser('Admin', 'admin@example.test', 'senha-segura', 'admin');
        self::assertTrue($this->auth->attempt('admin@example.test', 'senha-segura'));
    }
}
