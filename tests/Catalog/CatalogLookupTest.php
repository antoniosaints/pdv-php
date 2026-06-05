<?php

declare(strict_types=1);

namespace Tests\Catalog;

use PDO;
use Pdv\Catalog\CatalogRepository;
use Pdv\Database\ConnectionFactory;
use Pdv\Database\Migrator;
use Pdv\Support\Env;
use PHPUnit\Framework\TestCase;

final class CatalogLookupTest extends TestCase
{
    private string $rootPath;
    private string $tempDir;
    private CatalogRepository $catalog;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rootPath = dirname(__DIR__, 2);
        $this->tempDir = sys_get_temp_dir() . '/pdv_lookup_test_' . bin2hex(random_bytes(6));
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

    public function testInactiveProductsAndVariantsAreExcludedFromSaleLookup(): void
    {
        $activeProduct = $this->catalog->createProduct([
            'type' => 'product',
            'name' => 'Produto ativo',
            'price' => '10,00',
            'active' => true,
        ]);
        $inactiveProduct = $this->catalog->createProduct([
            'type' => 'product',
            'name' => 'Produto inativo',
            'price' => '10,00',
            'active' => false,
        ]);

        $this->catalog->createVariant($activeProduct, [
            'name' => 'Variante ativa',
            'barcode' => 'ACTIVE-001',
            'active' => true,
        ]);
        $this->catalog->createVariant($activeProduct, [
            'name' => 'Variante inativa',
            'barcode' => 'INACTIVE-001',
            'active' => false,
        ]);
        $this->catalog->createVariant($inactiveProduct, [
            'name' => 'Produto inativo variante',
            'barcode' => 'INACTIVE-PRODUCT-001',
            'active' => true,
        ]);

        self::assertNotNull($this->catalog->findByBarcode('ACTIVE-001'));
        self::assertNull($this->catalog->findByBarcode('INACTIVE-001'));
        self::assertNull($this->catalog->findByBarcode('INACTIVE-PRODUCT-001'));
        self::assertCount(1, $this->catalog->searchForSale('variante'));
    }
}
