<?php

declare(strict_types=1);

namespace Tests\Sales;

use PDO;
use Pdv\Catalog\CatalogRepository;
use Pdv\Database\ConnectionFactory;
use Pdv\Database\Migrator;
use Pdv\Sales\SalesRepository;
use Pdv\Sales\ValidationException;
use Pdv\Support\Env;
use PHPUnit\Framework\TestCase;

final class SaleRepositoryTest extends TestCase
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
        $this->tempDir = sys_get_temp_dir() . '/pdv_sale_schema_test_' . bin2hex(random_bytes(6));
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

    public function testSalesSchemaStoresSalePaymentAndStockMovementFacts(): void
    {
        [$productId, $variantId] = $this->createStockProduct();
        $now = gmdate('c');

        $this->pdo->prepare(<<<'SQL'
INSERT INTO sales (code, status, subtotal_cents, discount_cents, total_cents, payment_total_cents, change_cents, completed_at, created_at, updated_at)
VALUES (:code, 'completed', 15980, 1000, 14980, 15000, 20, :completed_at, :created_at, :updated_at)
SQL)->execute([
            'code' => 'SALE-000001',
            'completed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $saleId = (int) $this->pdo->lastInsertId();

        $this->pdo->prepare(<<<'SQL'
INSERT INTO sale_items (sale_id, product_id, variant_id, product_type, product_name, variant_name, sku, barcode, quantity, unit_cost_cents, unit_price_cents, discount_cents, total_cents, track_stock, created_at)
VALUES (:sale_id, :product_id, :variant_id, 'product', 'Camiseta', 'M Preta', 'CAMISETA-M-PRETA', '7890000000010', 2, 3000, 7990, 1000, 14980, 1, :created_at)
SQL)->execute([
            'sale_id' => $saleId,
            'product_id' => $productId,
            'variant_id' => $variantId,
            'created_at' => $now,
        ]);

        $this->pdo->prepare(<<<'SQL'
INSERT INTO sale_payments (sale_id, method, amount_cents, reference, created_at)
VALUES (:sale_id, 'cash', 15000, 'drawer', :created_at)
SQL)->execute([
            'sale_id' => $saleId,
            'created_at' => $now,
        ]);

        $this->pdo->prepare(<<<'SQL'
INSERT INTO stock_movements (product_id, variant_id, type, quantity_delta, quantity_before, quantity_after, reference_type, reference_id, reason, created_at)
VALUES (:product_id, :variant_id, 'sale', -2, 5, 3, 'sale', :sale_id, 'Venda SALE-000001', :created_at)
SQL)->execute([
            'product_id' => $productId,
            'variant_id' => $variantId,
            'sale_id' => $saleId,
            'created_at' => $now,
        ]);

        self::assertSame(14980, (int) $this->pdo->query('SELECT total_cents FROM sales WHERE id = ' . $saleId)->fetchColumn());
        self::assertSame(2, (int) $this->pdo->query('SELECT quantity FROM sale_items WHERE sale_id = ' . $saleId)->fetchColumn());
        self::assertSame(15000, (int) $this->pdo->query('SELECT amount_cents FROM sale_payments WHERE sale_id = ' . $saleId)->fetchColumn());
        self::assertSame(-2, (int) $this->pdo->query("SELECT quantity_delta FROM stock_movements WHERE reference_type = 'sale' AND reference_id = " . $saleId)->fetchColumn());
    }

    public function testCompleteSalePersistsFactsAndDecrementsTrackedStock(): void
    {
        [, $variantId] = $this->createStockProduct();

        $saleId = $this->sales->completeSale([
            'items' => [
                ['variant_id' => $variantId, 'quantity' => 2, 'discount' => '10,00'],
            ],
            'payments' => [
                ['method' => 'cash', 'amount' => '150,00', 'reference' => 'gaveta'],
            ],
        ]);

        $sale = $this->sales->findSale($saleId);
        $items = $this->sales->itemsForSale($saleId);
        $payments = $this->sales->paymentsForSale($saleId);
        $movements = $this->sales->stockMovementsForSale($saleId);
        $variant = $this->catalog->findVariant($variantId);

        self::assertSame('completed', $sale['status'] ?? null);
        self::assertSame(15980, $sale['subtotal_cents'] ?? null);
        self::assertSame(1000, $sale['discount_cents'] ?? null);
        self::assertSame(14980, $sale['total_cents'] ?? null);
        self::assertSame(20, $sale['change_cents'] ?? null);
        self::assertSame(2, $items[0]['quantity'] ?? null);
        self::assertSame(14980, $items[0]['total_cents'] ?? null);
        self::assertSame(15000, $payments[0]['amount_cents'] ?? null);
        self::assertSame(-2, $movements[0]['quantity_delta'] ?? null);
        self::assertSame(5, $movements[0]['quantity_before'] ?? null);
        self::assertSame(3, $movements[0]['quantity_after'] ?? null);
        self::assertSame(3, $variant['current_stock'] ?? null);
    }

    public function testServiceSaleDoesNotCreateStockMovement(): void
    {
        $serviceId = $this->catalog->createProduct([
            'type' => 'service',
            'sku' => 'AJUSTE-BARRA',
            'name' => 'Ajuste de barra',
            'cost' => '0,00',
            'price' => '35,00',
            'track_stock' => true,
            'active' => true,
        ]);
        $variantId = $this->catalog->createVariant($serviceId, [
            'name' => 'Serviço padrão',
            'sku' => 'AJUSTE-BARRA-PADRAO',
            'barcode' => 'SERV-001',
            'current_stock' => '0',
            'active' => true,
        ]);

        $saleId = $this->sales->completeSale([
            'items' => [
                ['variant_id' => $variantId, 'quantity' => 3],
            ],
            'payments' => [
                ['method' => 'pix', 'amount' => '105,00'],
            ],
        ]);

        self::assertSame(10500, $this->sales->findSale($saleId)['total_cents'] ?? null);
        self::assertSame([], $this->sales->stockMovementsForSale($saleId));
        self::assertSame(0, $this->catalog->findVariant($variantId)['current_stock'] ?? null);
    }

    public function testInsufficientStockRollsBackWithoutSaleOrStockChange(): void
    {
        [, $variantId] = $this->createStockProduct();

        try {
            $this->sales->completeSale([
                'items' => [
                    ['variant_id' => $variantId, 'quantity' => 6],
                ],
                'payments' => [
                    ['method' => 'cash', 'amount' => '500,00'],
                ],
            ]);
            self::fail('Expected insufficient stock validation.');
        } catch (ValidationException $exception) {
            self::assertContains('Estoque insuficiente para finalizar a venda.', $exception->errors());
        }

        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM sales')->fetchColumn());
        self::assertSame(5, $this->catalog->findVariant($variantId)['current_stock'] ?? null);
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM stock_movements')->fetchColumn());
    }

    public function testInsufficientPaymentIsRejectedBeforePersistingSale(): void
    {
        [, $variantId] = $this->createStockProduct();

        try {
            $this->sales->completeSale([
                'items' => [
                    ['variant_id' => $variantId, 'quantity' => 1],
                ],
                'payments' => [
                    ['method' => 'cash', 'amount' => '10,00'],
                ],
            ]);
            self::fail('Expected insufficient payment validation.');
        } catch (ValidationException $exception) {
            self::assertSame('Pagamento insuficiente para finalizar a venda.', $exception->errors()['payments'] ?? null);
        }

        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM sales')->fetchColumn());
        self::assertSame(5, $this->catalog->findVariant($variantId)['current_stock'] ?? null);
    }

    /** @return array{0:int,1:int} */
    private function createStockProduct(): array
    {
        $productId = $this->catalog->createProduct([
            'type' => 'product',
            'sku' => 'CAMISETA',
            'name' => 'Camiseta',
            'cost' => '30,00',
            'price' => '79,90',
            'track_stock' => true,
            'stock_min' => '2',
            'active' => true,
        ]);
        $variantId = $this->catalog->createVariant($productId, [
            'name' => 'M Preta',
            'sku' => 'CAMISETA-M-PRETA',
            'barcode' => '7890000000010',
            'cost' => '30,00',
            'price' => '79,90',
            'current_stock' => '5',
            'active' => true,
        ]);

        return [$productId, $variantId];
    }
}
