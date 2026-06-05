<?php

declare(strict_types=1);

namespace Pdv\Controllers;

use Pdv\Auth\AuthService;
use Pdv\Catalog\CatalogRepository;
use Pdv\Catalog\ValidationException;
use Pdv\Http\Request;
use Pdv\Http\Response;
use Pdv\Security\Csrf;
use Pdv\View\View;

final class CatalogController
{
    public function __construct(
        private readonly View $view,
        private readonly CatalogRepository $catalog,
        private readonly AuthService $auth,
        private readonly Csrf $csrf,
    ) {
    }

    /** @param array<string, string> $vars */
    public function index(Request $request, array $vars = []): Response
    {
        $term = $request->query('q', '') ?? '';

        return $this->render('catalog/index', [
            'title' => 'Catálogo',
            'products' => $this->catalog->listProducts($term),
            'term' => $term,
        ]);
    }

    /** @param array<string, string> $vars */
    public function create(Request $request, array $vars = []): Response
    {
        return $this->render('catalog/create', [
            'title' => 'Novo item',
            'product' => $this->emptyProduct(),
            'errors' => [],
        ]);
    }

    /** @param array<string, string> $vars */
    public function store(Request $request, array $vars = []): Response
    {
        if (! $this->csrf->validate($request->post('_token'))) {
            return $this->formError('catalog/create', 'Novo item', $this->productInput($request), ['_token' => 'Sessão expirada. Recarregue a página.']);
        }

        try {
            $productId = $this->catalog->createProduct($this->productInput($request));
        } catch (ValidationException $exception) {
            return $this->formError('catalog/create', 'Novo item', $this->productInput($request), $exception->errors());
        }

        return Response::redirect('/catalog/' . $productId);
    }

    /** @param array<string, string> $vars */
    public function show(Request $request, array $vars = []): Response
    {
        $id = (int) ($vars['id'] ?? 0);
        $product = $this->catalog->findProduct($id);

        if ($product === null) {
            return $this->notFound();
        }

        return $this->render('catalog/show', [
            'title' => (string) $product['name'],
            'product' => $product,
            'variants' => $this->catalog->variantsForProduct($id),
            'errors' => [],
            'variantErrors' => [],
            'variantInput' => $this->emptyVariant(),
            'editingVariantId' => null,
        ]);
    }

    /** @param array<string, string> $vars */
    public function edit(Request $request, array $vars = []): Response
    {
        $id = (int) ($vars['id'] ?? 0);
        $product = $this->catalog->findProduct($id);

        if ($product === null) {
            return $this->notFound();
        }

        return $this->render('catalog/edit', [
            'title' => 'Editar ' . $product['name'],
            'product' => $product,
            'errors' => [],
        ]);
    }

    /** @param array<string, string> $vars */
    public function update(Request $request, array $vars = []): Response
    {
        $id = (int) ($vars['id'] ?? 0);

        if ($this->catalog->findProduct($id) === null) {
            return $this->notFound();
        }

        if (! $this->csrf->validate($request->post('_token'))) {
            return $this->formError('catalog/edit', 'Editar item', $this->productInput($request) + ['id' => $id], ['_token' => 'Sessão expirada. Recarregue a página.']);
        }

        try {
            $this->catalog->updateProduct($id, $this->productInput($request));
        } catch (ValidationException $exception) {
            return $this->formError('catalog/edit', 'Editar item', $this->productInput($request) + ['id' => $id], $exception->errors());
        }

        return Response::redirect('/catalog/' . $id);
    }

    /** @param array<string, string> $vars */
    public function toggle(Request $request, array $vars = []): Response
    {
        $id = (int) ($vars['id'] ?? 0);
        $product = $this->catalog->findProduct($id);

        if ($product === null) {
            return $this->notFound();
        }

        if ($this->csrf->validate($request->post('_token'))) {
            $this->catalog->setActive($id, ((int) $product['active']) !== 1);
        }

        return Response::redirect('/catalog/' . $id);
    }

    /** @param array<string, string> $vars */
    public function storeVariant(Request $request, array $vars = []): Response
    {
        $productId = (int) ($vars['id'] ?? 0);
        $product = $this->catalog->findProduct($productId);

        if ($product === null) {
            return $this->notFound();
        }

        if (! $this->csrf->validate($request->post('_token'))) {
            return $this->variantError($product, ['_token' => 'Sessão expirada. Recarregue a página.'], $this->variantInput($request));
        }

        try {
            $this->catalog->createVariant($productId, $this->variantInput($request));
        } catch (ValidationException $exception) {
            return $this->variantError($product, $exception->errors(), $this->variantInput($request));
        }

        return Response::redirect('/catalog/' . $productId);
    }

    /** @param array<string, string> $vars */
    public function updateVariant(Request $request, array $vars = []): Response
    {
        $productId = (int) ($vars['id'] ?? 0);
        $variantId = (int) ($vars['variantId'] ?? 0);
        $product = $this->catalog->findProduct($productId);
        $variant = $this->catalog->findVariant($variantId);

        if ($product === null || $variant === null || (int) $variant['product_id'] !== $productId) {
            return $this->notFound();
        }

        if (! $this->csrf->validate($request->post('_token'))) {
            return $this->variantError($product, ['_token' => 'Sessão expirada. Recarregue a página.'], $this->variantInput($request), $variantId);
        }

        try {
            $this->catalog->updateVariant($variantId, $this->variantInput($request));
        } catch (ValidationException $exception) {
            return $this->variantError($product, $exception->errors(), $this->variantInput($request), $variantId);
        }

        return Response::redirect('/catalog/' . $productId);
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

    /** @param array<string, string> $vars */
    public function search(Request $request, array $vars = []): Response
    {
        $term = $request->query('q', '') ?? '';

        return Response::json([
            'items' => $term === '' ? [] : $this->catalog->searchForSale($term),
        ]);
    }

    /** @param array<string, mixed> $data */
    private function render(string $template, array $data = [], int $status = 200): Response
    {
        return $this->view->render($template, $data + [
            'currentRoute' => '/catalog',
            'authUser' => $this->auth->user(),
            'csrfToken' => $this->csrf->token(),
        ], $status);
    }

    /** @param array<string, mixed> $product @param array<string, string> $errors */
    private function formError(string $template, string $title, array $product, array $errors): Response
    {
        return $this->render($template, [
            'title' => $title,
            'product' => $product + $this->emptyProduct(),
            'errors' => $errors,
        ], 422);
    }

    /** @param array<string, mixed> $product @param array<string, string> $errors @param array<string, mixed> $variant */
    private function variantError(array $product, array $errors, array $variant, ?int $editingVariantId = null): Response
    {
        return $this->render('catalog/show', [
            'title' => (string) $product['name'],
            'product' => $product,
            'variants' => $this->catalog->variantsForProduct((int) $product['id']),
            'errors' => [],
            'variantErrors' => $errors,
            'variantInput' => $variant + $this->emptyVariant(),
            'editingVariantId' => $editingVariantId,
        ], 422);
    }

    /** @return array<string, mixed> */
    private function variantInput(Request $request): array
    {
        return [
            'name' => $request->post('variant_name', '') ?? '',
            'sku' => $request->post('variant_sku'),
            'barcode' => $request->post('barcode'),
            'attributes' => $request->post('attributes'),
            'cost' => $request->post('variant_cost'),
            'price' => $request->post('variant_price'),
            'current_stock' => $request->post('current_stock', '0') ?? '0',
            'active' => $request->post('variant_active', '0') ?? '0',
        ];
    }

    /** @return array<string, mixed> */
    private function emptyVariant(): array
    {
        return [
            'name' => '',
            'sku' => '',
            'barcode' => '',
            'attributes' => '',
            'cost' => '',
            'price' => '',
            'current_stock' => 0,
            'active' => 1,
        ];
    }

    /** @return array<string, mixed> */
    private function productInput(Request $request): array
    {
        return [
            'type' => $request->post('type', 'product') ?? 'product',
            'sku' => $request->post('sku'),
            'name' => $request->post('name', '') ?? '',
            'description' => $request->post('description'),
            'cost' => $request->post('cost', '0') ?? '0',
            'price' => $request->post('price', '0') ?? '0',
            'track_stock' => $request->post('track_stock', '0') ?? '0',
            'stock_min' => $request->post('stock_min', '0') ?? '0',
            'label_name' => $request->post('label_name'),
            'active' => $request->post('active', '0') ?? '0',
        ];
    }

    /** @return array<string, mixed> */
    private function emptyProduct(): array
    {
        return [
            'id' => null,
            'type' => 'product',
            'sku' => '',
            'name' => '',
            'description' => '',
            'cost_cents' => 0,
            'cost' => '0,00',
            'price_cents' => 0,
            'price' => '0,00',
            'track_stock' => 1,
            'stock_min' => 0,
            'label_name' => '',
            'active' => 1,
            'variant_count' => 0,
            'variant_stock' => 0,
        ];
    }

    private function notFound(): Response
    {
        return Response::html(
            '<main class="shell shell--narrow"><section class="panel form-panel"><p class="eyebrow">Catálogo</p><h1>Item não encontrado</h1><p>O item solicitado não existe ou foi removido.</p><a class="button" href="/catalog">Voltar ao catálogo</a></section></main>',
            404
        );
    }
}
