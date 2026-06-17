<?php

declare(strict_types=1);

namespace Tests\ServiceOrders;

use PDO;
use Pdv\Catalog\CatalogRepository;
use Pdv\Database\ConnectionFactory;
use Pdv\Database\Migrator;
use Pdv\Sales\SalesRepository;
use Pdv\ServiceOrders\ServiceOrderRepository;
use Pdv\ServiceOrders\ValidationException;
use Pdv\Support\Env;
use PHPUnit\Framework\TestCase;

final class ServiceOrderRepositoryTest extends TestCase
{
    private string $rootPath;
    private string $tempDir;
    private PDO $pdo;
    private CatalogRepository $catalog;
    private SalesRepository $sales;
    private ServiceOrderRepository $orders;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rootPath = dirname(__DIR__, 2);
        $this->tempDir = sys_get_temp_dir() . '/pdv_service_order_test_' . bin2hex(random_bytes(6));
        mkdir($this->tempDir, 0775, true);

        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = $this->tempDir . '/test.sqlite';

        Env::load($this->rootPath);

        $this->pdo = (new ConnectionFactory($this->rootPath))->make();
        (new Migrator($this->pdo, $this->rootPath . '/database/migrations'))->migrate();

        $this->catalog = new CatalogRepository($this->pdo);
        $this->sales = new SalesRepository($this->pdo);
        $this->orders = new ServiceOrderRepository($this->pdo);
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

    public function testServiceOrderSchemaCreatesOrderItemAndHistoryTables(): void
    {
        foreach (['service_orders', 'service_order_items', 'service_order_status_history'] as $table) {
            self::assertTrue($this->sqliteTableExists($table), "Table [{$table}] should exist.");
        }
    }

    public function testCreateOrderSnapshotsServiceAndProductItemsWithTotals(): void
    {
        $serviceVariantId = $this->createServiceVariant();
        [, $productVariantId] = $this->createStockProduct();

        $orderId = $this->orders->createOrder([
            'customer_name' => 'Maria Cliente',
            'customer_phone' => '(11) 99999-0000',
            'customer_document' => '123.456.789-00',
            'description' => 'Barra e camiseta',
            'items' => [
                ['variant_id' => $serviceVariantId, 'quantity' => 1, 'discount' => '5,00'],
                ['variant_id' => $productVariantId, 'quantity' => 2, 'discount' => '10,00'],
            ],
        ]);

        $order = $this->orders->findOrder($orderId);
        $items = $this->orders->itemsForOrder($orderId);
        $history = $this->orders->statusHistoryForOrder($orderId);

        self::assertNotNull($order);
        self::assertStringStartsWith('OS-', (string) $order['code']);
        self::assertSame('open', $order['status']);
        self::assertSame('Maria Cliente', $order['customer_name']);
        self::assertSame(19480, $order['subtotal_cents']);
        self::assertSame(1500, $order['discount_cents']);
        self::assertSame(17980, $order['total_cents']);
        self::assertNull($order['sale_id']);
        self::assertCount(2, $items);
        self::assertSame('service', $items[0]['product_type']);
        self::assertSame('Ajuste de barra', $items[0]['product_name']);
        self::assertSame(0, $items[0]['track_stock']);
        self::assertSame(3000, $items[0]['total_cents']);
        self::assertSame('product', $items[1]['product_type']);
        self::assertSame(1, $items[1]['track_stock']);
        self::assertSame(14980, $items[1]['total_cents']);
        self::assertCount(1, $history);
        self::assertNull($history[0]['from_status']);
        self::assertSame('open', $history[0]['to_status']);
    }

    public function testInvalidOrderInputReturnsFieldErrorsAndPersistsNothing(): void
    {
        try {
            $this->orders->createOrder([
                'customer_name' => '',
                'items' => [
                    ['variant_id' => 0, 'quantity' => 0, 'discount' => '-1,00'],
                ],
            ]);
            self::fail('Expected service order validation.');
        } catch (ValidationException $exception) {
            self::assertSame('Informe o nome do cliente.', $exception->errors()['customer_name'] ?? null);
            self::assertSame('Selecione um item válido.', $exception->errors()['items.0.variant_id'] ?? null);
            self::assertSame('Quantidade deve ser maior que zero.', $exception->errors()['items.0.quantity'] ?? null);
            self::assertSame('Desconto não pode ser negativo.', $exception->errors()['items.0.discount'] ?? null);
        }

        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM service_orders')->fetchColumn());
    }

    public function testUnavailableVariantAndExcessiveDiscountRollBackOrderCreation(): void
    {
        $serviceVariantId = $this->createServiceVariant();

        try {
            $this->orders->createOrder([
                'customer_name' => 'Cliente com erro',
                'items' => [
                    ['variant_id' => 999999, 'quantity' => 1],
                    ['variant_id' => $serviceVariantId, 'quantity' => 1, 'discount' => '40,00'],
                ],
            ]);
            self::fail('Expected prepared item validation.');
        } catch (ValidationException $exception) {
            self::assertSame('Item indisponível para ordem de serviço.', $exception->errors()['items.0.variant_id'] ?? null);
            self::assertSame('Desconto não pode ser maior que o valor do item.', $exception->errors()['items.1.discount'] ?? null);
        }

        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM service_orders')->fetchColumn());
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM service_order_items')->fetchColumn());
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM service_order_status_history')->fetchColumn());
    }

    public function testChangeStatusUpdatesOrderAndRecordsHistory(): void
    {
        $actorId = $this->createUser();
        $orderId = $this->orders->createOrder([
            'opened_by_user_id' => $actorId,
            'customer_name' => 'Cliente acompanhamento',
            'items' => [
                ['variant_id' => $this->createServiceVariant(), 'quantity' => 1],
            ],
        ]);

        $this->orders->changeStatus($orderId, [
            'status' => 'in_progress',
            'actor_user_id' => $actorId,
            'notes' => 'Serviço iniciado',
        ]);
        $this->orders->changeStatus($orderId, [
            'status' => 'ready',
            'actor_user_id' => $actorId,
            'notes' => 'Pronto para retirada',
        ]);

        $order = $this->orders->findOrder($orderId);
        $history = $this->orders->statusHistoryForOrder($orderId);
        $listed = $this->orders->listOrders('ready');

        self::assertSame('ready', $order['status'] ?? null);
        self::assertCount(3, $history);
        self::assertSame('open', $history[1]['from_status']);
        self::assertSame('in_progress', $history[1]['to_status']);
        self::assertSame($actorId, $history[1]['actor_user_id']);
        self::assertSame('Serviço iniciado', $history[1]['notes']);
        self::assertSame('in_progress', $history[2]['from_status']);
        self::assertSame('ready', $history[2]['to_status']);
        self::assertCount(1, $listed);
        self::assertSame($orderId, $listed[0]['id']);
        self::assertSame(1, $listed[0]['item_count']);
    }

    public function testSaleInputAndMarkClosedLinkOrderToSaleAndDecrementStockOnlyOnSale(): void
    {
        $actorId = $this->createUser();
        $serviceVariantId = $this->createServiceVariant();
        [, $productVariantId] = $this->createStockProduct();
        $orderId = $this->orders->createOrder([
            'opened_by_user_id' => $actorId,
            'customer_name' => 'Cliente fechamento real',
            'items' => [
                ['variant_id' => $serviceVariantId, 'quantity' => 1, 'discount' => '0,00'],
                ['variant_id' => $productVariantId, 'quantity' => 2, 'discount' => '10,00'],
            ],
        ]);

        self::assertSame(5, $this->catalog->findVariant($productVariantId)['current_stock'] ?? null);

        $saleId = $this->orders->closeIntoSale($orderId, [
            'payments' => [
                ['method' => 'cash', 'amount' => '200,00', 'reference' => 'ordem'],
            ],
        ], $actorId, $actorId, $this->sales);

        $order = $this->orders->findOrder($orderId);
        $sale = $this->sales->findSale($saleId);
        $saleItems = $this->sales->itemsForSale($saleId);
        $movements = $this->sales->stockMovementsForSale($saleId);
        $history = $this->orders->statusHistoryForOrder($orderId);

        self::assertSame('closed', $order['status'] ?? null);
        self::assertSame($saleId, $order['sale_id'] ?? null);
        self::assertNotNull($order['closed_at'] ?? null);
        self::assertSame('Cliente fechamento real', $sale['customer_name'] ?? null);
        self::assertSame(18480, $sale['total_cents'] ?? null);
        self::assertCount(2, $saleItems);
        self::assertSame('service', $saleItems[0]['product_type'] ?? null);
        self::assertSame('product', $saleItems[1]['product_type'] ?? null);
        self::assertSame(3, $this->catalog->findVariant($productVariantId)['current_stock'] ?? null);
        self::assertCount(1, $movements);
        self::assertSame(-2, $movements[0]['quantity_delta'] ?? null);
        self::assertSame('closed', $history[array_key_last($history)]['to_status'] ?? null);
    }

    public function testOversizedCustomerFieldsAreRejected(): void
    {
        try {
            $this->orders->createOrder([
                'customer_name' => str_repeat('A', 191),
                'customer_phone' => str_repeat('1', 61),
                'items' => [
                    ['variant_id' => $this->createServiceVariant(), 'quantity' => 1],
                ],
            ]);
            self::fail('Expected length validation.');
        } catch (ValidationException $exception) {
            self::assertSame('Nome do cliente deve ter até 190 caracteres.', $exception->errors()['customer_name'] ?? null);
            self::assertSame('Telefone deve ter até 60 caracteres.', $exception->errors()['customer_phone'] ?? null);
        }

        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM service_orders')->fetchColumn());
    }

    public function testClosedStatusCannotBeSetManually(): void
    {
        $orderId = $this->orders->createOrder([
            'customer_name' => 'Cliente fechamento',
            'items' => [
                ['variant_id' => $this->createServiceVariant(), 'quantity' => 1],
            ],
        ]);

        try {
            $this->orders->changeStatus($orderId, ['status' => 'closed']);
            self::fail('Expected manual status validation.');
        } catch (ValidationException $exception) {
            self::assertSame('Status inválido para atualização manual.', $exception->errors()['status'] ?? null);
        }

        self::assertSame('open', $this->orders->findOrder($orderId)['status'] ?? null);
        self::assertCount(1, $this->orders->statusHistoryForOrder($orderId));
    }

    private function createServiceVariant(): int
    {
        $productId = $this->catalog->createProduct([
            'type' => 'service',
            'sku' => 'AJUSTE-BARRA-' . bin2hex(random_bytes(3)),
            'name' => 'Ajuste de barra',
            'cost' => '0,00',
            'price' => '35,00',
            'track_stock' => true,
            'active' => true,
        ]);

        return $this->catalog->createVariant($productId, [
            'name' => 'Serviço padrão',
            'sku' => 'AJUSTE-BARRA-PADRAO-' . bin2hex(random_bytes(3)),
            'barcode' => 'SERV-' . bin2hex(random_bytes(4)),
            'current_stock' => 0,
            'active' => true,
        ]);
    }

    /** @return array{0:int,1:int} */
    private function createStockProduct(): array
    {
        $productId = $this->catalog->createProduct([
            'type' => 'product',
            'sku' => 'CAMISETA-' . bin2hex(random_bytes(3)),
            'name' => 'Camiseta',
            'cost' => '30,00',
            'price' => '79,90',
            'track_stock' => true,
            'stock_min' => '2',
            'active' => true,
        ]);

        $variantId = $this->catalog->createVariant($productId, [
            'name' => 'M Preta',
            'sku' => 'CAMISETA-M-PRETA-' . bin2hex(random_bytes(3)),
            'barcode' => '789' . random_int(1000000000, 9999999999),
            'cost' => '30,00',
            'price' => '79,90',
            'current_stock' => '5',
            'active' => true,
        ]);

        return [$productId, $variantId];
    }

    private function createUser(): int
    {
        $now = gmdate('c');
        $this->pdo->prepare(<<<'SQL'
INSERT INTO users (name, email, password_hash, role, active, created_at, updated_at)
VALUES ('Operador', :email, :password_hash, 'admin', 1, :created_at, :updated_at)
SQL)->execute([
            'email' => 'operador-' . bin2hex(random_bytes(4)) . '@example.test',
            'password_hash' => password_hash('secret', PASSWORD_DEFAULT),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function sqliteTableExists(string $table): bool
    {
        $statement = $this->pdo->prepare('SELECT name FROM sqlite_master WHERE type = :type AND name = :name');
        $statement->execute([
            'type' => 'table',
            'name' => $table,
        ]);

        return $statement->fetchColumn() === $table;
    }
}
