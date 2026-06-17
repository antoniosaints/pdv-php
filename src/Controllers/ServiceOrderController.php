<?php

declare(strict_types=1);

namespace Pdv\Controllers;

use Pdv\Auth\AuthService;
use Pdv\Catalog\CatalogRepository;
use Pdv\Http\Request;
use Pdv\Http\Response;
use Pdv\Sales\SalesRepository;
use Pdv\Sales\ValidationException as SalesValidationException;
use Pdv\Security\Csrf;
use Pdv\ServiceOrders\ServiceOrderRepository;
use Pdv\ServiceOrders\ValidationException;
use Pdv\View\View;

final class ServiceOrderController
{
    public function __construct(
        private readonly View $view,
        private readonly ServiceOrderRepository $orders,
        private readonly CatalogRepository $catalog,
        private readonly SalesRepository $sales,
        private readonly AuthService $auth,
        private readonly Csrf $csrf,
    ) {
    }

    /** @param array<string, string> $vars */
    public function index(Request $request, array $vars = []): Response
    {
        $status = $request->query('status');
        $errors = [];

        try {
            $orders = $this->orders->listOrders($status === '' ? null : $status);
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            $orders = $this->orders->listOrders();
            $status = null;
        }

        return $this->render('service-orders/index', [
            'title' => 'Ordens de serviço',
            'orders' => $orders,
            'statusFilter' => $status,
            'statusLabels' => $this->statusLabels(),
            'errors' => $errors,
        ]);
    }

    /** @param array<string, string> $vars */
    public function create(Request $request, array $vars = []): Response
    {
        $selectedItems = [];
        $errors = [];
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
                $errors['variant_id'] = 'Item indisponível para ordem de serviço.';
            } else {
                $selectedItems[] = $this->selectedItem($item);
            }
        }

        return $this->renderCreate([
            'errors' => $errors,
            'input' => [],
            'selectedItems' => $selectedItems,
            'availableItems' => $this->catalog->searchForSale('', 50),
            'searchResults' => $term === '' ? [] : $this->catalog->searchForSale($term),
            'term' => $term,
            'barcode' => $barcode,
        ]);
    }

    /** @param array<string, string> $vars */
    public function store(Request $request, array $vars = []): Response
    {
        $input = $this->orderInput($request);

        if (! $this->csrf->validate($request->post('_token'))) {
            return $this->renderCreateFromInput($input, ['_token' => 'Sessão expirada. Recarregue a página.'], 422);
        }

        try {
            $orderId = $this->orders->createOrder($input);
        } catch (ValidationException $exception) {
            return $this->renderCreateFromInput($input, $exception->errors(), 422);
        }

        return Response::redirect('/service-orders/' . $orderId);
    }

    /** @param array<string, string> $vars */
    public function show(Request $request, array $vars = []): Response
    {
        $orderId = (int) ($vars['id'] ?? 0);
        $order = $this->orders->findOrder($orderId);

        if ($order === null) {
            return $this->notFound();
        }

        return $this->renderShow($order);
    }

    /** @param array<string, string> $vars */
    public function updateStatus(Request $request, array $vars = []): Response
    {
        $orderId = (int) ($vars['id'] ?? 0);
        $order = $this->orders->findOrder($orderId);

        if ($order === null) {
            return $this->notFound();
        }

        if (! $this->csrf->validate($request->post('_token'))) {
            return $this->renderShow($order, ['_token' => 'Sessão expirada. Recarregue a página.'], 422);
        }

        try {
            $this->orders->changeStatus($orderId, $this->statusInput($request));
        } catch (ValidationException $exception) {
            return $this->renderShow($order, $exception->errors(), 422);
        }

        return Response::redirect('/service-orders/' . $orderId);
    }

    /** @param array<string, string> $vars */
    public function closeSale(Request $request, array $vars = []): Response
    {
        $orderId = (int) ($vars['id'] ?? 0);
        $order = $this->orders->findOrder($orderId);

        if ($order === null) {
            return $this->notFound();
        }

        if (! $this->csrf->validate($request->post('_token'))) {
            return $this->renderShow($order, ['_token' => 'Sessão expirada. Recarregue a página.'], 422);
        }

        $user = $this->auth->user();
        $userId = is_array($user) ? (int) $user['id'] : null;

        try {
            $saleId = $this->orders->closeIntoSale($orderId, ['payments' => $this->paymentInput($request)], $userId, $userId, $this->sales);
        } catch (ValidationException $exception) {
            return $this->renderShow($this->orders->findOrder($orderId) ?? $order, $exception->errors(), 422);
        } catch (SalesValidationException $exception) {
            return $this->renderShow($this->orders->findOrder($orderId) ?? $order, $exception->errors(), 422);
        }

        return Response::redirect('/sales/' . $saleId);
    }

    /** @param array<string, mixed> $data */
    private function renderCreate(array $data, int $status = 200): Response
    {
        return $this->render('service-orders/create', $data + [
            'title' => 'Nova ordem de serviço',
        ], $status);
    }

    /** @param array<string, mixed> $input @param array<string, string> $errors */
    private function renderCreateFromInput(array $input, array $errors, int $status): Response
    {
        return $this->renderCreate([
            'errors' => $errors,
            'input' => $input,
            'selectedItems' => $this->selectedItemsFromInput($input['items'] ?? []),
            'availableItems' => $this->catalog->searchForSale('', 50),
            'searchResults' => [],
            'term' => '',
            'barcode' => '',
        ], $status);
    }

    /** @param array<string, mixed> $order @param array<string, string> $errors */
    private function renderShow(array $order, array $errors = [], int $status = 200): Response
    {
        return $this->render('service-orders/show', [
            'title' => 'Ordem ' . $order['code'],
            'order' => $order,
            'items' => $this->orders->itemsForOrder((int) $order['id']),
            'history' => $this->orders->statusHistoryForOrder((int) $order['id']),
            'statusLabels' => $this->statusLabels(),
            'manualStatuses' => ['open', 'in_progress', 'ready', 'cancelled'],
            'payment' => ['method' => 'cash', 'amount' => $this->money((int) $order['total_cents']), 'reference' => ''],
            'errors' => $errors,
        ], $status);
    }

    /** @param array<string, mixed> $data */
    private function render(string $template, array $data = [], int $status = 200): Response
    {
        return $this->view->render($template, $data + [
            'currentRoute' => '/service-orders',
            'authUser' => $this->auth->user(),
            'csrfToken' => $this->csrf->token(),
        ], $status);
    }

    /** @return array<string, mixed> */
    private function orderInput(Request $request): array
    {
        $user = $this->auth->user();

        return [
            'opened_by_user_id' => is_array($user) ? (int) $user['id'] : null,
            'customer_name' => $request->post('customer_name'),
            'customer_phone' => $request->post('customer_phone'),
            'customer_document' => $request->post('customer_document'),
            'description' => $request->post('description'),
            'notes' => $request->post('notes'),
            'items' => $request->postArray('items'),
        ];
    }

    /** @return array<string, mixed> */
    private function statusInput(Request $request): array
    {
        $user = $this->auth->user();

        return [
            'status' => $request->post('status', '') ?? '',
            'actor_user_id' => is_array($user) ? (int) $user['id'] : null,
            'notes' => $request->post('notes'),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function paymentInput(Request $request): array
    {
        $payments = $request->postArray('payments');

        if ($payments !== []) {
            return $payments;
        }

        return [[
            'method' => $request->post('payment_method', 'cash') ?? 'cash',
            'amount' => $request->post('payment_amount', '0') ?? '0',
            'reference' => $request->post('payment_reference'),
        ]];
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

    /** @return array<string, string> */
    private function statusLabels(): array
    {
        return [
            'open' => 'Aberta',
            'in_progress' => 'Em execução',
            'ready' => 'Pronta',
            'closed' => 'Fechada',
            'cancelled' => 'Cancelada',
        ];
    }

    private function money(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '.');
    }

    private function notFound(): Response
    {
        return Response::html(
            '<main class="shell shell--narrow"><section class="panel form-panel"><p class="eyebrow">Ordens</p><h1>Ordem não encontrada</h1><p>A ordem de serviço solicitada não existe.</p><a class="button" href="/service-orders">Voltar às ordens</a></section></main>',
            404
        );
    }
}
