<?php

declare(strict_types=1);

namespace Pdv\Controllers;

use Pdv\Auth\AuthService;
use Pdv\Http\Request;
use Pdv\Http\Response;
use Pdv\Security\Csrf;
use Pdv\Stock\StockRepository;
use Pdv\Stock\ValidationException;
use Pdv\View\View;

final class StockController
{
    public function __construct(
        private readonly View $view,
        private readonly StockRepository $stock,
        private readonly AuthService $auth,
        private readonly Csrf $csrf,
    ) {
    }

    /** @param array<string, string> $vars */
    public function index(Request $request, array $vars = []): Response
    {
        return $this->renderIndex();
    }

    /** @param array<string, string> $vars */
    public function replenish(Request $request, array $vars = []): Response
    {
        if (! $this->csrf->validate($request->post('_token'))) {
            return $this->renderIndex(['_token' => 'Sessão expirada. Recarregue a página.'], 'replenishment', $this->replenishmentInput($request), 422);
        }

        try {
            $this->stock->recordReplenishment($this->replenishmentInput($request));
        } catch (ValidationException $exception) {
            return $this->renderIndex($exception->errors(), 'replenishment', $this->replenishmentInput($request), 422);
        }

        return Response::redirect('/stock');
    }

    /** @param array<string, string> $vars */
    public function adjust(Request $request, array $vars = []): Response
    {
        if (! $this->csrf->validate($request->post('_token'))) {
            return $this->renderIndex(['_token' => 'Sessão expirada. Recarregue a página.'], 'adjustment', $this->adjustmentInput($request), 422);
        }

        try {
            $this->stock->recordAdjustment($this->adjustmentInput($request));
        } catch (ValidationException $exception) {
            return $this->renderIndex($exception->errors(), 'adjustment', $this->adjustmentInput($request), 422);
        }

        return Response::redirect('/stock');
    }

    /** @param array<string, string> $errors @param array<string, mixed> $input */
    private function renderIndex(array $errors = [], string $activeForm = '', array $input = [], int $status = 200): Response
    {
        return $this->view->render('stock/index', [
            'title' => 'Estoque',
            'currentRoute' => '/stock',
            'authUser' => $this->auth->user(),
            'csrfToken' => $this->csrf->token(),
            'trackedVariants' => $this->stock->listTrackedVariants(),
            'lowStockVariants' => $this->stock->lowStockVariants(),
            'movements' => $this->stock->recentMovements(),
            'errors' => $errors,
            'activeForm' => $activeForm,
            'input' => $input,
        ], $status);
    }

    /** @return array<string, mixed> */
    private function replenishmentInput(Request $request): array
    {
        return [
            'variant_id' => $request->post('variant_id', '0') ?? '0',
            'quantity' => $request->post('quantity', '0') ?? '0',
            'reason' => $request->post('reason'),
        ];
    }

    /** @return array<string, mixed> */
    private function adjustmentInput(Request $request): array
    {
        return [
            'variant_id' => $request->post('variant_id', '0') ?? '0',
            'delta' => $request->post('delta', '0') ?? '0',
            'reason' => $request->post('reason'),
        ];
    }
}
