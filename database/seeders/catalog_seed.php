<?php

declare(strict_types=1);

use Pdv\Catalog\CatalogRepository;

return static function (CatalogRepository $catalog): array {
    $created = [];

    $camiseta = $catalog->createProduct([
        'type' => 'product',
        'sku' => 'DEMO-CAMISETA',
        'name' => 'Camiseta Demo',
        'description' => 'Produto com variante e código de barras para testar o PDV.',
        'cost' => '25,90',
        'price' => '59,90',
        'track_stock' => true,
        'stock_min' => 5,
        'label_name' => 'CAMISETA DEMO',
        'active' => true,
    ]);
    $catalog->createVariant($camiseta, [
        'name' => 'Preta / M',
        'sku' => 'DEMO-CAMISETA-PRETA-M',
        'barcode' => '7891000000010',
        'price' => '64,90',
        'current_stock' => 12,
        'active' => true,
    ]);
    $created[] = 'Produto Demo: Camiseta Demo / Preta M / barcode 7891000000010';

    $ajuste = $catalog->createProduct([
        'type' => 'service',
        'sku' => 'DEMO-AJUSTE-BARRA',
        'name' => 'Ajuste de Barra Demo',
        'description' => 'Serviço de exemplo sem baixa de estoque físico.',
        'cost' => '0,00',
        'price' => '35,00',
        'track_stock' => false,
        'stock_min' => 0,
        'label_name' => 'AJUSTE BARRA',
        'active' => true,
    ]);
    $catalog->createVariant($ajuste, [
        'name' => 'Serviço padrão',
        'sku' => 'DEMO-AJUSTE-BARRA-PADRAO',
        'barcode' => '7891000000027',
        'current_stock' => 0,
        'active' => true,
    ]);
    $created[] = 'Serviço Demo: Ajuste de Barra Demo / barcode 7891000000027';

    return $created;
};
