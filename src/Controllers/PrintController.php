<?php

declare(strict_types=1);

namespace Pdv\Controllers;

use Pdv\Auth\AuthService;
use Pdv\Catalog\CatalogRepository;
use Pdv\Http\Request;
use Pdv\Http\Response;
use Pdv\Sales\SalesRepository;
use Pdv\Security\Csrf;
use Pdv\View\View;

final class PrintController
{
    public function __construct(
        private readonly View $view,
        private readonly SalesRepository $sales,
        private readonly CatalogRepository $catalog,
        private readonly AuthService $auth,
        private readonly Csrf $csrf,
    ) {
    }

    /** @param array<string, string> $vars */
    public function receipt(Request $request, array $vars = []): Response
    {
        $saleId = (int) ($vars['id'] ?? 0);
        $sale = $this->sales->findSale($saleId);

        if ($sale === null) {
            return $this->notFound('Recibo não encontrado', 'A venda solicitada não existe ou ainda não foi concluída.');
        }

        return $this->render('print/receipt', [
            'title' => 'Recibo ' . $sale['code'],
            'currentRoute' => '/pos',
            'sale' => $sale,
            'items' => $this->sales->itemsForSale($saleId),
            'payments' => $this->sales->paymentsForSale($saleId),
            'printTarget' => 'receipt-' . $saleId,
        ]);
    }

    /** @param array<string, string> $vars */
    public function label(Request $request, array $vars = []): Response
    {
        $productId = (int) ($vars['id'] ?? 0);
        $variantId = (int) ($vars['variantId'] ?? 0);
        $product = $this->catalog->findProduct($productId);
        $variant = $this->catalog->findVariant($variantId);

        if ($product === null || $variant === null || (int) $variant['product_id'] !== $productId) {
            return $this->notFound('Etiqueta não encontrada', 'O produto ou variante solicitado não existe.');
        }

        return $this->render('print/label', [
            'title' => 'Etiqueta ' . $product['name'],
            'currentRoute' => '/catalog',
            'product' => $product,
            'variant' => $variant,
            'printTarget' => 'label-' . $variantId,
        ]);
    }

    /** @param array<string, mixed> $data */
    private function render(string $template, array $data = [], int $status = 200): Response
    {
        return $this->view->render($template, $data + [
            'authUser' => $this->auth->user(),
            'csrfToken' => $this->csrf->token(),
        ], $status);
    }

    private function notFound(string $title, string $message): Response
    {
        return Response::html(
            '<main class="shell shell--narrow"><section class="panel form-panel"><p class="eyebrow">Impressão</p><h1>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p><a class="button" href="/dashboard">Voltar ao painel</a></section></main>',
            404
        );
    }
}
