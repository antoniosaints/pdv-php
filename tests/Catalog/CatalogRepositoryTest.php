<?php

declare(strict_types=1);

namespace Tests\Catalog;

use PDO;
use Pdv\Catalog\CatalogRepository;
use Pdv\Catalog\ValidationException;
use Pdv\Database\ConnectionFactory;
use Pdv\Database\Migrator;
use Pdv\Support\Env;
use PHPUnit\Framework\TestCase;

final class CatalogRepositoryTest extends TestCase
{
    private string $rootPath;
    private string $tempDir;
    private PDO $pdo;
    private CatalogRepository $catalog;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rootPath = dirname(__DIR__, 2);
        $this->tempDir = sys_get_temp_dir() . '/pdv_catalog_test_' . bin2hex(random_bytes(6));
        mkdir($this->tempDir, 0775, true);

        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = $this->tempDir . '/test.sqlite';

        Env::load($this->rootPath);

        $this->pdo = (new ConnectionFactory($this->rootPath))->make();
        (new Migrator($this->pdo, $this->rootPath . '/database/migrations'))->migrate();
        $this->catalog = new CatalogRepository($this->pdo);
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

    public function testCreatesStockTrackedProductAndVariant(): void
    {
        $productId = $this->catalog->createProduct([
            'type' => 'product',
            'sku' => 'CAM-BASICA',
            'name' => 'Camiseta Básica',
            'cost' => '25,90',
            'price' => '59,90',
            'track_stock' => '1',
            'stock_min' => '5',
            'label_name' => 'CAMISETA BASICA',
        ]);

        $variantId = $this->catalog->createVariant($productId, [
            'name' => 'Preta / M',
            'sku' => 'CAM-BASICA-PRETA-M',
            'barcode' => '7890000000011',
            'current_stock' => '12',
        ]);

        $product = $this->catalog->findProduct($productId);
        $variants = $this->catalog->variantsForProduct($productId);
        $lookup = $this->catalog->findByBarcode('7890000000011');

        self::assertSame('Camiseta Básica', $product['name'] ?? null);
        self::assertSame(2590, $product['cost_cents'] ?? null);
        self::assertSame(5990, $product['price_cents'] ?? null);
        self::assertSame(1, $product['track_stock'] ?? null);
        self::assertSame(5, $product['stock_min'] ?? null);
        self::assertSame($variantId, $variants[0]['id'] ?? null);
        self::assertSame('Preta / M', $lookup['name'] ?? null);
        self::assertSame(5990, $lookup['effective_price_cents'] ?? null);
    }

    public function testServicesDoNotTrackStock(): void
    {
        $serviceId = $this->catalog->createProduct([
            'type' => 'service',
            'sku' => 'AJUSTE-BARRA',
            'name' => 'Ajuste de barra',
            'price' => '35.00',
            'track_stock' => '1',
        ]);

        $service = $this->catalog->findProduct($serviceId);

        self::assertSame('service', $service['type'] ?? null);
        self::assertSame(0, $service['track_stock'] ?? null);
    }

    public function testDuplicateBarcodeIsRejectedWithFieldError(): void
    {
        $productId = $this->catalog->createProduct([
            'type' => 'product',
            'name' => 'Produto com barcode',
            'price' => '10.00',
        ]);

        $this->catalog->createVariant($productId, [
            'name' => 'Unico',
            'barcode' => 'ABC123',
        ]);

        try {
            $this->catalog->createVariant($productId, [
                'name' => 'Duplicado',
                'barcode' => 'ABC123',
            ]);
            self::fail('Expected duplicate barcode validation error.');
        } catch (ValidationException $exception) {
            self::assertSame('Já existe uma variante com este código de barras.', $exception->errors()['barcode'] ?? null);
        }
    }

    public function testSearchForSaleFindsByNameSkuAndBarcode(): void
    {
        $productId = $this->catalog->createProduct([
            'type' => 'product',
            'sku' => 'TENIS-RUN',
            'name' => 'Tênis Runner',
            'price' => '199,90',
        ]);

        $this->catalog->createVariant($productId, [
            'name' => 'Azul 40',
            'sku' => 'TENIS-RUN-AZUL-40',
            'barcode' => '7899999999999',
            'price' => '209,90',
        ]);

        self::assertCount(1, $this->catalog->searchForSale('runner'));
        self::assertCount(1, $this->catalog->searchForSale('AZUL-40'));
        self::assertCount(1, $this->catalog->searchForSale('7899999999999'));
    }
}
