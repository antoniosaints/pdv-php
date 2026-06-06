<?php

declare(strict_types=1);

namespace Pdv\Controllers;

use Pdv\Auth\AuthService;
use Pdv\Catalog\CatalogRepository;
use Pdv\Http\Request;
use Pdv\Http\Response;
use Pdv\Sales\SalesRepository;
use Pdv\Sales\ValidationException;
use Pdv\Security\Csrf;
use Pdv\View\View;

final class SalesController
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
    public function pos(Request $request, array $vars = []): Response
    {
        $errors = [];
        $selectedItems = [];
        $barcode = $request->query('barcode', '') ?? '';
        $term = $request->query('q', '') ?? '';
        $variantId = (int) ($request->query('variant_id', '0') ?? '0');

        if ($barcode !== '') {
            $item = $this->catalog->findByBarcode($barcode);

            if ($item === null) {
                $errors['barcode'] = 'Código de barras não encontrado.';
            } else {
                $selectedItems[] = $this->selectedItem($item);
            }
        }

        if ($variantId > 0) {
            $item = $this->catalog->findForSaleVariant($variantId);

            if ($item === null) {
                $errors['variant_id'] = 'Item indisponível para venda.';
            } else {
                $selectedItems[] = $this->selectedItem($item);
            }
        }

        return $this->renderPos([
            'errors' => $errors,
            'selectedItems' => $selectedItems,
            'searchResults' => $term === '' ? [] : $this->catalog->searchForSale($term),
            'term' => $term,
            'barcode' => $barcode,
            'payment' => ['method' => 'cash', 'amount' => $this->suggestedPayment($selectedItems), 'reference' => ''],
        ]);
    }

    /** @param array<string, string> $vars */
    public function finalize(Request $request, array $vars = []): Response
    {
        $saleInput = $this->saleInput($request);

        if (! $this->csrf->validate($request->post('_token'))) {
            return $this->renderPosFromInput($saleInput, ['_token' => 'Sessão expirada. Recarregue a página.'], 422);
        }

        try {
            $saleId = $this->sales->completeSale($saleInput);
        } catch (ValidationException $exception) {
            return $this->renderPosFromInput($saleInput, $exception->errors(), 422);
        }

        return Response::redirect('/sales/' . $saleId);
    }

    /** @param array<string, string> $vars */
    public function show(Request $request, array $vars = []): Response
    {
        $saleId = (int) ($vars['id'] ?? 0);
        $sale = $this->sales->findSale($saleId);

        if ($sale === null) {
            return $this->notFound();
        }

        return $this->render('sales/show', [
            'title' => 'Venda ' . $sale['code'],
            'sale' => $sale,
            'items' => $this->sales->itemsForSale($saleId),
            'payments' => $this->sales->paymentsForSale($saleId),
            'movements' => $this->sales->stockMovementsForSale($saleId),
        ]);
    }

    /** @param array<string, string> $vars */
    public function lookupBarcode(Request $request, array $vars = []): Response
    {
        $barcode = $request->query('barcode', '') ?? '';
        $item = $barcode === '' ? null : $this->catalog->findByBarcode($barcode);

        if ($item === null) {
            return Response::json(['found' => false, 'message' => 'Código de barras não encontrado.'], 404);
        }

        return Response::json(['found' => true, 'item' => $item]);
    }

    /** @param array<string, mixed> $data */
    private function renderPos(array $data, int $status = 200): Response
    {
        return $this->render('sales/pos', $data + [
            'title' => 'PDV',
        ], $status);
    }

    /** @param array<string, mixed> $saleInput @param array<string, string> $errors */
    private function renderPosFromInput(array $saleInput, array $errors, int $status): Response
    {
        return $this->renderPos([
            'errors' => $errors,
            'selectedItems' => $this->selectedItemsFromInput($saleInput['items'] ?? []),
            'searchResults' => [],
            'term' => '',
            'barcode' => '',
            'payment' => $saleInput['payments'][0] ?? ['method' => 'cash', 'amount' => '', 'reference' => ''],
        ], $status);
    }

    /** @param array<string, mixed> $data */
    private function render(string $template, array $data = [], int $status = 200): Response
    {
        return $this->view->render($template, $data + [
            'currentRoute' => '/pos',
            'authUser' => $this->auth->user(),
            'csrfToken' => $this->csrf->token(),
        ], $status);
    }

    /** @return array<string, mixed> */
    private function saleInput(Request $request): array
    {
        $user = $this->auth->user();
        $payments = $request->postArray('payments');

        if ($payments === []) {
            $payments = [[
                'method' => $request->post('payment_method', 'cash') ?? 'cash',
                'amount' => $request->post('payment_amount', '0') ?? '0',
                'reference' => $request->post('payment_reference'),
            ]];
        }

        return [
            'cashier_user_id' => is_array($user) ? (int) $user['id'] : null,
            'customer_name' => $request->post('customer_name'),
            'notes' => $request->post('notes'),
            'items' => $request->postArray('items'),
            'payments' => $payments,
        ];
    }

    /** @param array<string, mixed> $item @return array<string, mixed> */
    private function selectedItem(array $item, int $quantity = 1, mixed $discount = '0,00'): array
    {
        return $item + [
            'quantity' => $quantity,
            'discount' => is_string($discount) ? $discount : $this->money((int) $discount),
        ];
    }

    /** @param mixed $items @return list<array<string, mixed>> */
    private function selectedItemsFromInput(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $selected = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $variant = $this->catalog->findForSaleVariant((int) ($item['variant_id'] ?? 0));

            if ($variant === null) {
                continue;
            }

            $selected[] = $this->selectedItem($variant, (int) ($item['quantity'] ?? 1), $item['discount'] ?? $item['discount_cents'] ?? '0,00');
        }

        return $selected;
    }

    /** @param list<array<string, mixed>> $selectedItems */
    private function suggestedPayment(array $selectedItems): string
    {
        $total = 0;

        foreach ($selectedItems as $item) {
            $total += (int) $item['effective_price_cents'] * (int) ($item['quantity'] ?? 1);
        }

        return $this->money($total);
    }

    private function money(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '.');
    }

    private function notFound(): Response
    {
        return Response::html(
            '<main class="shell shell--narrow"><section class="panel form-panel"><p class="eyebrow">PDV</p><h1>Venda não encontrada</h1><p>A venda solicitada não existe.</p><a class="button" href="/pos">Voltar ao PDV</a></section></main>',
            404
        );
    }
}
