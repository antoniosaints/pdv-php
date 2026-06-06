<?php

declare(strict_types=1);

namespace Tests\Stock;

use PDO;
use Pdv\Catalog\CatalogRepository;
use Pdv\Database\ConnectionFactory;
use Pdv\Database\Migrator;
use Pdv\Stock\StockRepository;
use Pdv\Stock\ValidationException;
use Pdv\Support\Env;
use PHPUnit\Framework\TestCase;

final class StockRepositoryTest extends TestCase
{
    private string $rootPath;
    private string $tempDir;
    private PDO $pdo;
    private CatalogRepository $catalog;
    private StockRepository $stock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rootPath = dirname(__DIR__, 2);
        $this->tempDir = sys_get_temp_dir() . '/pdv_stock_test_' . bin2hex(random_bytes(6));
        mkdir($this->tempDir, 0775, true);

        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = $this->tempDir . '/test.sqlite';

        Env::load($this->rootPath);

        $this->pdo = (new ConnectionFactory($this->rootPath))->make();
        (new Migrator($this->pdo, $this->rootPath . '/database/migrations'))->migrate();
        $this->catalog = new CatalogRepository($this->pdo);
        $this->stock = new StockRepository($this->pdo);
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

    public function testListsLowStockTrackedVariants(): void
    {
        $lowVariant = $this->createTrackedVariant('Produto baixo', 2, 3);
        $healthyVariant = $this->createTrackedVariant('Produto ok', 8, 3);

        $tracked = $this->stock->listTrackedVariants();
        $lowStock = $this->stock->lowStockVariants();

        self::assertCount(2, $tracked);
        self::assertSame($lowVariant, $lowStock[0]['variant_id'] ?? null);
        self::assertSame(1, $lowStock[0]['is_low_stock'] ?? null);
        self::assertNotSame($healthyVariant, $lowStock[0]['variant_id'] ?? null);
    }

    public function testReplenishmentUpdatesCurrentStockAndMovementLedger(): void
    {
        $variantId = $this->createTrackedVariant('Produto reposicao', 2, 3);

        $movementId = $this->stock->recordReplenishment([
            'variant_id' => $variantId,
            'quantity' => 5,
            'reason' => 'Compra fornecedor A',
        ]);

        $variant = $this->catalog->findVariant($variantId);
        $movement = $this->stock->recentMovements(1)[0];

        self::assertGreaterThan(0, $movementId);
        self::assertSame(7, $variant['current_stock'] ?? null);
        self::assertSame('purchase', $movement['type'] ?? null);
        self::assertSame(5, $movement['quantity_delta'] ?? null);
        self::assertSame(2, $movement['quantity_before'] ?? null);
        self::assertSame(7, $movement['quantity_after'] ?? null);
        self::assertSame('Compra fornecedor A', $movement['reason'] ?? null);
    }

    public function testPositiveAndNegativeAdjustmentsAreRecorded(): void
    {
        $variantId = $this->createTrackedVariant('Produto ajuste', 10, 2);

        $this->stock->recordAdjustment([
            'variant_id' => $variantId,
            'delta' => -3,
            'reason' => 'Quebra conferida',
        ]);
        $this->stock->recordAdjustment([
            'variant_id' => $variantId,
            'delta' => 2,
            'reason' => 'Recontagem',
        ]);

        $variant = $this->catalog->findVariant($variantId);
        $movements = $this->stock->recentMovements(2);

        self::assertSame(9, $variant['current_stock'] ?? null);
        self::assertSame('adjustment', $movements[0]['type'] ?? null);
        self::assertSame(2, $movements[0]['quantity_delta'] ?? null);
        self::assertSame(7, $movements[0]['quantity_before'] ?? null);
        self::assertSame(9, $movements[0]['quantity_after'] ?? null);
        self::assertSame(-3, $movements[1]['quantity_delta'] ?? null);
    }

    public function testAdjustmentCannotLeaveNegativeStockAndRollsBack(): void
    {
        $variantId = $this->createTrackedVariant('Produto invalido', 2, 1);

        try {
            $this->stock->recordAdjustment([
                'variant_id' => $variantId,
                'delta' => -3,
                'reason' => 'Erro de contagem',
            ]);
            self::fail('Expected negative stock validation.');
        } catch (ValidationException $exception) {
            self::assertSame('Movimento deixaria o estoque negativo.', $exception->errors()['quantity'] ?? null);
        }

        self::assertSame(2, $this->catalog->findVariant($variantId)['current_stock'] ?? null);
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM stock_movements')->fetchColumn());
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
            'barcode' => 'BAR-' . bin2hex(random_bytes(4)),
            'current_stock' => $stock,
            'active' => true,
        ]);
    }
}
