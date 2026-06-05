<?php

declare(strict_types=1);

namespace Tests\Feature;

use Pdv\Catalog\CatalogRepository;
use Pdv\Database\ConnectionFactory;
use Pdv\Database\Migrator;
use Pdv\Support\Env;
use PHPUnit\Framework\TestCase;

final class CatalogSeedTest extends TestCase
{
    private string $rootPath;
    private string $tempDir;
    private CatalogRepository $catalog;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rootPath = dirname(__DIR__, 2);
        $this->tempDir = sys_get_temp_dir() . '/pdv_seed_test_' . bin2hex(random_bytes(6));
        mkdir($this->tempDir, 0775, true);

        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = $this->tempDir . '/test.sqlite';

        Env::load($this->rootPath);

        $pdo = (new ConnectionFactory($this->rootPath))->make();
        (new Migrator($pdo, $this->rootPath . '/database/migrations'))->migrate();
        $this->catalog = new CatalogRepository($pdo);
    }

    protected function tearDown(): void
    {
        $files = glob($this->tempDir . '/*') ?: [];
        foreach ($files as $file) {
            @unlink($file);
        }
        @rmdir($this->tempDir);
        unset($_ENV['DB_CONNECTION'], $_ENV['DB_DATABASE']);

        parent::tearDown();
    }

    public function testCatalogSeedCreatesProductAndServiceForDownstreamPdv(): void
    {
        $seed = require $this->rootPath . '/database/seeders/catalog_seed.php';
        $created = $seed($this->catalog);

        self::assertCount(2, $created);
        self::assertNotNull($this->catalog->findByBarcode('7891000000010'));
        self::assertNotNull($this->catalog->findByBarcode('7891000000027'));

        $service = $this->catalog->findByBarcode('7891000000027');
        self::assertSame('service', $service['product_type'] ?? null);
        self::assertSame(0, $service['track_stock'] ?? null);
    }
}
