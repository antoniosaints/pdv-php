<?php

declare(strict_types=1);

namespace Tests\Reports;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use Pdv\Catalog\CatalogRepository;
use Pdv\Database\ConnectionFactory;
use Pdv\Database\Migrator;
use Pdv\Reports\ReportsRepository;
use Pdv\Sales\SalesRepository;
use Pdv\ServiceOrders\ServiceOrderRepository;
use Pdv\Support\Env;
use PHPUnit\Framework\TestCase;

final class ReportsRepositoryTest extends TestCase
{
    private string $rootPath;
    private string $tempDir;
    private PDO $pdo;
    private CatalogRepository $catalog;
    private SalesRepository $sales;
    private ServiceOrderRepository $orders;
    private ReportsRepository $reports;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rootPath = dirname(__DIR__, 2);
        $this->tempDir = sys_get_temp_dir() . '/pdv_reports_test_' . bin2hex(random_bytes(6));
        mkdir($this->tempDir, 0775, true);

        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = $this->tempDir . '/test.sqlite';

        Env::load($this->rootPath);

        $this->pdo = (new ConnectionFactory($this->rootPath))->make();
        (new Migrator($this->pdo, $this->rootPath . '/database/migrations'))->migrate();

        $this->catalog = new CatalogRepository($this->pdo);
        $this->sales = new SalesRepository($this->pdo);
        $this->orders = new ServiceOrderRepository($this->pdo);
        $this->reports = new ReportsRepository($this->pdo);
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

    public function testDashboardFactsAreZeroAndActionableWhenThereIsNoData(): void
    {
        $facts = $this->reports->dashboard(new DateTimeImmutable('2026-06-15T12:00:00+00:00'));

        self::assertSame(0, $facts['sales']['sales_count']);
        self::assertSame(0, $facts['sales']['total_cents']);
        self::assertSame(0, $facts['sales']['gross_profit_cents']);
        self::assertSame(0, $facts['monthly']['projection_cents']);
        self::assertSame(0, $facts['low_stock']['count']);
        self::assertSame(0, $facts['open_orders']['count']);
        self::assertSame([], $facts['payments']);
        self::assertStringContainsString('Registre vendas no PDV', implode(' ', $facts['tips']));
    }

    public function testDashboardAggregatesSalesProfitStockAndOpenOrders(): void
    {
        $serviceVariantId = $this->createServiceVariant();
        [, $productVariantId] = $this->createStockProduct();

        $saleId = $this->sales->completeSale([
            'customer_name' => 'Cliente relatório',
            'items' => [
                ['variant_id' => $serviceVariantId, 'quantity' => 1],
                ['variant_id' => $productVariantId, 'quantity' => 2, 'discount' => '10,00'],
            ],
            'payments' => [
                ['method' => 'cash', 'amount' => '200,00', 'reference' => 'relatorio'],
            ],
        ]);
        $orderId = $this->orders->createOrder([
            'customer_name' => 'Cliente ordem aberta',
            'items' => [
                ['variant_id' => $serviceVariantId, 'quantity' => 1],
            ],
        ]);

        $facts = $this->reports->dashboard(new DateTimeImmutable('now', new DateTimeZone('UTC')));

        self::assertGreaterThan(0, $saleId);
        self::assertGreaterThan(0, $orderId);
        self::assertSame(1, $facts['sales']['sales_count']);
        self::assertSame(18480, $facts['sales']['total_cents']);
        self::assertSame(12480, $facts['sales']['gross_profit_cents']);
        self::assertSame(18480, $facts['sales']['average_ticket_cents']);
        self::assertSame(18480, $facts['monthly']['total_cents']);
        self::assertSame(12480, $facts['monthly']['gross_profit_cents']);
        self::assertGreaterThanOrEqual(18480, $facts['monthly']['projection_cents']);
        self::assertSame(1, $facts['low_stock']['count']);
        self::assertSame('Camiseta Relatório', $facts['low_stock']['items'][0]['product_name']);
        self::assertSame(1, $facts['open_orders']['count']);
        self::assertSame('Cliente ordem aberta', $facts['open_orders']['items'][0]['customer_name']);
        self::assertSame('cash', $facts['payments'][0]['method']);
        self::assertSame(20000, $facts['payments'][0]['amount_cents']);
        self::assertSame('Camiseta Relatório', $facts['top_items'][0]['product_name']);
        self::assertSame(2, $facts['top_items'][0]['quantity']);
        self::assertStringContainsString('Reponha 1 item', implode(' ', $facts['tips']));
        self::assertStringContainsString('Acompanhe 1 ordem', implode(' ', $facts['tips']));
    }

    public function testClosedOrdersAreNotCountedAsOpen(): void
    {
        $serviceVariantId = $this->createServiceVariant();
        $orderId = $this->orders->createOrder([
            'customer_name' => 'Cliente fechado',
            'items' => [
                ['variant_id' => $serviceVariantId, 'quantity' => 1],
            ],
        ]);

        $this->orders->closeIntoSale($orderId, [
            'payments' => [
                ['method' => 'pix', 'amount' => '35,00'],
            ],
        ], null, null, $this->sales);

        $facts = $this->reports->dashboard(new DateTimeImmutable('now', new DateTimeZone('UTC')));

        self::assertSame(0, $facts['open_orders']['count']);
        self::assertSame([], $facts['open_orders']['items']);
        self::assertSame(1, $facts['sales']['sales_count']);
        self::assertSame(3500, $facts['sales']['total_cents']);
    }

    private function createServiceVariant(): int
    {
        $productId = $this->catalog->createProduct([
            'type' => 'service',
            'sku' => 'SERV-REL-' . bin2hex(random_bytes(3)),
            'name' => 'Ajuste Relatório',
            'cost' => '0,00',
            'price' => '35,00',
            'track_stock' => true,
            'active' => true,
        ]);

        return $this->catalog->createVariant($productId, [
            'name' => 'Serviço padrão',
            'sku' => 'SERV-REL-PADRAO-' . bin2hex(random_bytes(3)),
            'barcode' => 'SERV-REL-' . bin2hex(random_bytes(4)),
            'current_stock' => 0,
            'active' => true,
        ]);
    }

    /** @return array{0:int,1:int} */
    private function createStockProduct(): array
    {
        $productId = $this->catalog->createProduct([
            'type' => 'product',
            'sku' => 'CAM-REL-' . bin2hex(random_bytes(3)),
            'name' => 'Camiseta Relatório',
            'cost' => '30,00',
            'price' => '79,90',
            'track_stock' => true,
            'stock_min' => '3',
            'active' => true,
        ]);

        $variantId = $this->catalog->createVariant($productId, [
            'name' => 'M Preta',
            'sku' => 'CAM-REL-M-' . bin2hex(random_bytes(3)),
            'barcode' => '789' . random_int(1000000000, 9999999999),
            'cost' => '30,00',
            'price' => '79,90',
            'current_stock' => '5',
            'active' => true,
        ]);

        return [$productId, $variantId];
    }
}
