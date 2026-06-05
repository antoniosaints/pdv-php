<?php

declare(strict_types=1);

namespace Tests\Feature;

use Pdv\Auth\AuthService;
use Pdv\Database\ConnectionFactory;
use Pdv\Database\Migrator;
use Pdv\Http\Request;
use Pdv\Http\Router;
use Pdv\Security\Csrf;
use Pdv\Support\Env;
use Pdv\Support\HealthCheck;
use Pdv\View\View;
use PHPUnit\Framework\TestCase;

final class HealthTest extends TestCase
{
    private string $rootPath;
    private string $tempDir;
    private AuthService $auth;
    private Router $router;

    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        $_SESSION = [];
        session_id('pdvhealth' . bin2hex(random_bytes(6)));

        $this->rootPath = dirname(__DIR__, 2);
        $this->tempDir = sys_get_temp_dir() . '/pdv_health_test_' . bin2hex(random_bytes(6));
        mkdir($this->tempDir, 0775, true);
        mkdir($this->tempDir . '/logs', 0775, true);

        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = $this->tempDir . '/test.sqlite';
        $_ENV['LOG_PATH'] = $this->tempDir . '/logs/app.log';

        Env::load($this->rootPath);

        $pdo = (new ConnectionFactory($this->rootPath))->make();
        $migrator = new Migrator($pdo, $this->rootPath . '/database/migrations');
        $migrator->migrate();

        $this->auth = new AuthService($pdo);
        $csrf = new Csrf();
        $this->router = new Router(
            new View($this->rootPath . '/templates'),
            $this->auth,
            $csrf,
            new HealthCheck($this->rootPath, $pdo, $migrator)
        );
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        $_SESSION = [];

        $this->removeDirectory($this->tempDir);

        unset($_ENV['DB_CONNECTION'], $_ENV['DB_DATABASE'], $_ENV['LOG_PATH']);

        parent::tearDown();
    }

    public function testHealthRouteRequiresAuthentication(): void
    {
        $response = $this->router->dispatch(new Request('GET', '/health', [], [], []));

        self::assertSame(302, $response->status());
        self::assertSame('/login?redirect=%2Fhealth', $response->headers()['Location'] ?? null);
    }

    public function testAdminCanSeeHealthyDiagnostics(): void
    {
        $this->auth->createUser('Admin', 'admin@example.test', 'senha-segura', 'admin');
        self::assertTrue($this->auth->attempt('admin@example.test', 'senha-segura'));

        $response = $this->router->dispatch(new Request('GET', '/health', [], [], []));

        self::assertSame(200, $response->status());
        self::assertStringContainsString('Sistema saudável.', $response->body());
        self::assertStringContainsString('Banco de dados', $response->body());
        self::assertStringContainsString('Migrations', $response->body());
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = scandir($directory) ?: [];

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($directory);
    }
}
