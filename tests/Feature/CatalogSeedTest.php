<?php

declare(strict_types=1);

namespace Tests\Feature;

use PDO;
use Pdv\Catalog\CatalogRepository;
use Pdv\Database\ConnectionFactory;
use Pdv\Database\Migrator;
use Pdv\Sales\SalesRepository;
use Pdv\Support\Env;
use PHPUnit\Framework\TestCase;

final class CatalogSeedTest extends TestCase
{
    private string $rootPath;
    private string $tempDir;
    private PDO $pdo;
    private CatalogRepository $catalog;
    private SalesRepository $sales;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rootPath = dirname(__DIR__, 2);
        $this->tempDir = sys_get_temp_dir() . '/pdv_seed_test_' . bin2hex(random_bytes(6));
        mkdir($this->tempDir, 0775, true);

        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = $this->tempDir . '/test.sqlite';

        Env::load($this->rootPath);

        $this->pdo = (new ConnectionFactory($this->rootPath))->make();
        (new Migrator($this->pdo, $this->rootPath . '/database/migrations'))->migrate();
        $this->catalog = new CatalogRepository($this->pdo);
        $this->sales = new SalesRepository($this->pdo);
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

    public function testCatalogSeedProductCanBeSoldThroughPdvRepository(): void
    {
        $seed = require $this->rootPath . '/database/seeders/catalog_seed.php';
        $seed($this->catalog);

        $product = $this->catalog->findByBarcode('7891000000010');
        self::assertNotNull($product);

        $saleId = $this->sales->completeSale([
            'items' => [
                ['variant_id' => $product['id'], 'quantity' => 1],
            ],
            'payments' => [
                ['method' => 'pix', 'amount' => '64,90'],
            ],
        ]);

        self::assertSame(6490, $this->sales->findSale($saleId)['total_cents'] ?? null);
        self::assertSame(11, $this->catalog->findVariant((int) $product['id'])['current_stock'] ?? null);
        self::assertSame(-1, $this->sales->stockMovementsForSale($saleId)[0]['quantity_delta'] ?? null);
        self::assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM sale_payments')->fetchColumn());
    }
}
