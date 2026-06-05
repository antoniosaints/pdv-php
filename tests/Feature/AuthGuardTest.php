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
use Pdv\View\View;
use PHPUnit\Framework\TestCase;

final class AuthGuardTest extends TestCase
{
    private string $rootPath;
    private string $tempDir;
    private AuthService $auth;
    private Router $router;
    private Csrf $csrf;

    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        $_SESSION = [];
        session_id('pdvtest' . bin2hex(random_bytes(6)));

        $this->rootPath = dirname(__DIR__, 2);
        $this->tempDir = sys_get_temp_dir() . '/pdv_auth_test_' . bin2hex(random_bytes(6));
        mkdir($this->tempDir, 0775, true);

        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = $this->tempDir . '/test.sqlite';

        Env::load($this->rootPath);

        $pdo = (new ConnectionFactory($this->rootPath))->make();
        (new Migrator($pdo, $this->rootPath . '/database/migrations'))->migrate();

        $this->auth = new AuthService($pdo);
        $this->csrf = new Csrf();
        $this->router = new Router(new View($this->rootPath . '/templates'), $this->auth, $this->csrf);
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

    public function testDashboardRedirectsGuestToLogin(): void
    {
        $response = $this->router->dispatch(new Request('GET', '/dashboard', [], [], []));

        self::assertSame(302, $response->status());
        self::assertSame('/login?redirect=%2Fdashboard', $response->headers()['Location'] ?? null);
    }

    public function testFirstAdminSetupCreatesUserAndLocksSetupRoute(): void
    {
        $token = $this->csrf->token();

        $response = $this->router->dispatch(new Request('POST', '/setup/admin', [], [
            '_token' => $token,
            'name' => 'Dona Loja',
            'email' => 'admin@example.test',
            'password' => 'senha-segura',
            'password_confirmation' => 'senha-segura',
        ], ['REMOTE_ADDR' => '127.0.0.1']));

        self::assertSame(302, $response->status());
        self::assertSame('/dashboard', $response->headers()['Location'] ?? null);
        self::assertTrue($this->auth->hasUsers());
        self::assertTrue($this->auth->check());

        $blockedSetup = $this->router->dispatch(new Request('GET', '/setup/admin', [], [], []));

        self::assertSame(302, $blockedSetup->status());
        self::assertSame('/login', $blockedSetup->headers()['Location'] ?? null);
    }
}
