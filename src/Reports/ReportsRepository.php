<?php

declare(strict_types=1);

namespace Pdv\Reports;

use DateTimeImmutable;
use DateTimeZone;
use PDO;

final class ReportsRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<string, mixed> */
    public function dashboard(?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $sales = $this->salesSummary();
        $monthly = $this->monthlyPerformance($now);
        $lowStock = $this->lowStockVariants();
        $openOrders = $this->openServiceOrders();

        return [
            'sales' => $sales,
            'monthly' => $monthly,
            'payments' => $this->paymentBreakdown(),
            'top_items' => $this->topItems(),
            'low_stock' => [
                'count' => count($lowStock),
                'items' => $lowStock,
            ],
            'open_orders' => [
                'count' => count($openOrders),
                'items' => $openOrders,
            ],
            'tips' => $this->tips($sales, $monthly, $lowStock, $openOrders),
        ];
    }

    /** @return array{sales_count:int,total_cents:int,gross_profit_cents:int,average_ticket_cents:int} */
    public function salesSummary(): array
    {
        $sales = $this->pdo->query(<<<'SQL'
SELECT COUNT(*) AS sales_count,
       COALESCE(SUM(total_cents), 0) AS total_cents
FROM sales
WHERE status = 'completed'
SQL)->fetch();

        $profit = $this->pdo->query(<<<'SQL'
SELECT COALESCE(SUM(total_cents - (unit_cost_cents * quantity)), 0) AS gross_profit_cents
FROM sale_items
SQL)->fetch();

        $count = (int) ($sales['sales_count'] ?? 0);
        $total = (int) ($sales['total_cents'] ?? 0);

        return [
            'sales_count' => $count,
            'total_cents' => $total,
            'gross_profit_cents' => (int) ($profit['gross_profit_cents'] ?? 0),
            'average_ticket_cents' => $count === 0 ? 0 : (int) round($total / $count),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function paymentBreakdown(): array
    {
        $statement = $this->pdo->query(<<<'SQL'
SELECT method,
       COUNT(*) AS payment_count,
       COALESCE(SUM(amount_cents), 0) AS amount_cents
FROM sale_payments
GROUP BY method
ORDER BY amount_cents DESC, method ASC
SQL);

        return array_map(static function (array $row): array {
            $row['payment_count'] = (int) $row['payment_count'];
            $row['amount_cents'] = (int) $row['amount_cents'];

            return $row;
        }, $statement->fetchAll());
    }

    /** @return list<array<string, mixed>> */
    public function topItems(int $limit = 10): array
    {
        $statement = $this->pdo->prepare(<<<'SQL'
SELECT product_name,
       variant_name,
       product_type,
       COALESCE(SUM(quantity), 0) AS quantity,
       COALESCE(SUM(total_cents), 0) AS total_cents,
       COALESCE(SUM(total_cents - (unit_cost_cents * quantity)), 0) AS gross_profit_cents
FROM sale_items
GROUP BY product_name, variant_name, product_type
ORDER BY total_cents DESC, quantity DESC, product_name ASC
LIMIT :limit
SQL);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return array_map(static function (array $row): array {
            foreach (['quantity', 'total_cents', 'gross_profit_cents'] as $field) {
                $row[$field] = (int) $row[$field];
            }

            return $row;
        }, $statement->fetchAll());
    }

    /** @return array{month:string,sales_count:int,total_cents:int,gross_profit_cents:int,projection_cents:int,days_elapsed:int,days_in_month:int} */
    public function monthlyPerformance(?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $start = $now->modify('first day of this month')->setTime(0, 0, 0);
        $next = $start->modify('first day of next month');

        $sales = $this->pdo->prepare(<<<'SQL'
SELECT COUNT(*) AS sales_count,
       COALESCE(SUM(total_cents), 0) AS total_cents
FROM sales
WHERE status = 'completed'
  AND completed_at >= :start
  AND completed_at < :next
SQL);
        $sales->execute([
            'start' => $start->format('c'),
            'next' => $next->format('c'),
        ]);
        $salesRow = $sales->fetch();

        $profit = $this->pdo->prepare(<<<'SQL'
SELECT COALESCE(SUM(i.total_cents - (i.unit_cost_cents * i.quantity)), 0) AS gross_profit_cents
FROM sale_items i
INNER JOIN sales s ON s.id = i.sale_id
WHERE s.status = 'completed'
  AND s.completed_at >= :start
  AND s.completed_at < :next
SQL);
        $profit->execute([
            'start' => $start->format('c'),
            'next' => $next->format('c'),
        ]);
        $profitRow = $profit->fetch();

        $total = (int) ($salesRow['total_cents'] ?? 0);
        $daysElapsed = max(1, (int) $now->format('j'));
        $daysInMonth = (int) $now->format('t');

        return [
            'month' => $now->format('Y-m'),
            'sales_count' => (int) ($salesRow['sales_count'] ?? 0),
            'total_cents' => $total,
            'gross_profit_cents' => (int) ($profitRow['gross_profit_cents'] ?? 0),
            'projection_cents' => $total === 0 ? 0 : (int) round(($total / $daysElapsed) * $daysInMonth),
            'days_elapsed' => $daysElapsed,
            'days_in_month' => $daysInMonth,
        ];
    }

    /** @return list<array<string, mixed>> */
    public function lowStockVariants(int $limit = 10): array
    {
        $statement = $this->pdo->prepare(<<<'SQL'
SELECT v.id AS variant_id,
       v.product_id,
       p.name AS product_name,
       v.name AS variant_name,
       v.barcode,
       v.current_stock,
       p.stock_min
FROM product_variants v
INNER JOIN products p ON p.id = v.product_id
WHERE p.track_stock = 1
  AND p.active = 1
  AND v.active = 1
  AND v.current_stock <= p.stock_min
ORDER BY v.current_stock ASC, p.name ASC, v.name ASC
LIMIT :limit
SQL);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return array_map(static function (array $row): array {
            foreach (['variant_id', 'product_id', 'current_stock', 'stock_min'] as $field) {
                $row[$field] = (int) $row[$field];
            }

            return $row;
        }, $statement->fetchAll());
    }

    /** @return list<array<string, mixed>> */
    public function openServiceOrders(int $limit = 10): array
    {
        $statement = $this->pdo->prepare(<<<'SQL'
SELECT id,
       code,
       status,
       customer_name,
       total_cents,
       opened_at
FROM service_orders
WHERE sale_id IS NULL
  AND status NOT IN ('closed', 'cancelled')
ORDER BY id DESC
LIMIT :limit
SQL);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return array_map(static function (array $row): array {
            foreach (['id', 'total_cents'] as $field) {
                $row[$field] = (int) $row[$field];
            }

            return $row;
        }, $statement->fetchAll());
    }

    /** @param array<string, int> $sales @param array<string, int|string> $monthly @param list<array<string, mixed>> $lowStock @param list<array<string, mixed>> $openOrders @return list<string> */
    private function tips(array $sales, array $monthly, array $lowStock, array $openOrders): array
    {
        $tips = [];

        if ((int) $sales['sales_count'] === 0) {
            $tips[] = 'Registre vendas no PDV para liberar leitura de lucro, ticket médio e projeção mensal.';
        }

        if ($lowStock !== []) {
            $tips[] = 'Reponha ' . count($lowStock) . ' item(ns) em estoque baixo antes de perder vendas.';
        }

        if ($openOrders !== []) {
            $tips[] = 'Acompanhe ' . count($openOrders) . ' ordem(ns) aberta(s) para converter serviços em venda.';
        }

        if ((int) $sales['gross_profit_cents'] < 0) {
            $tips[] = 'Revise custos e descontos: o lucro bruto acumulado está negativo.';
        }

        if ((int) $monthly['projection_cents'] > (int) $monthly['total_cents']) {
            $tips[] = 'A projeção mensal está acima do realizado atual; acompanhe se o ritmo de vendas se mantém.';
        }

        if ($tips === []) {
            $tips[] = 'Operação saudável: vendas, estoque e ordens não exigem ação imediata.';
        }

        return $tips;
    }
}
