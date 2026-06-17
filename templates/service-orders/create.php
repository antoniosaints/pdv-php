<?php
$money = static fn (int $cents): string => number_format($cents / 100, 2, ',', '.');
$customer = static fn (string $key): string => htmlspecialchars((string) ($input[$key] ?? ''), ENT_QUOTES, 'UTF-8');
$itemRows = $selectedItems;
$rowCount = max(3, count($itemRows));
?>
<main class="shell">
    <section class="panel dashboard-hero">
        <div>
            <p class="eyebrow">Nova ordem</p>
            <h1>Criar ordem de serviço</h1>
            <p class="hero__copy">Registre cliente, serviços e produtos. A ordem fica aberta para acompanhamento de status antes do fechamento em venda.</p>
        </div>
        <a class="button button--ghost" href="/service-orders">Voltar às ordens</a>
    </section>

    <?php if ($errors !== []): ?>
        <section class="alert alert--danger" role="alert">
            <?php foreach ($errors as $message): ?>
                <div><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <section class="pos-grid">
        <div class="panel form-panel">
            <p class="eyebrow">Catálogo</p>
            <h2>Buscar itens</h2>
            <form class="stack" method="get" action="/service-orders/create">
                <label class="field">
                    <span>Código de barras</span>
                    <input type="search" name="barcode" value="<?= htmlspecialchars((string) $barcode, ENT_QUOTES, 'UTF-8') ?>" placeholder="Bipe ou digite o código" autofocus>
                </label>
                <button class="button" type="submit">Selecionar por código</button>
            </form>

            <form class="stack" method="get" action="/service-orders/create">
                <label class="field">
                    <span>Buscar por nome, SKU ou código</span>
                    <input type="search" name="q" value="<?= htmlspecialchars((string) $term, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: ajuste, instalação, camiseta...">
                </label>
                <button class="button button--ghost" type="submit">Buscar no catálogo</button>
            </form>

            <?php if ($searchResults !== []): ?>
                <div class="table-wrap table-wrap--spaced">
                    <table class="pos-results">
                        <thead><tr><th>Item</th><th>Preço</th><th>Estoque</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($searchResults as $result): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars((string) $result['product_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        <small><?= htmlspecialchars((string) $result['name'], ENT_QUOTES, 'UTF-8') ?></small>
                                    </td>
                                    <td>R$ <?= $money((int) $result['effective_price_cents']) ?></td>
                                    <td><?= ((int) $result['track_stock']) === 1 ? (int) $result['current_stock'] : 'Serviço' ?></td>
                                    <td><a class="button button--ghost" href="/service-orders/create?variant_id=<?= (int) $result['id'] ?>">Selecionar</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <form class="panel form-panel stack" method="post" action="/service-orders">
            <input type="hidden" name="_token" value="<?= htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <p class="eyebrow">Dados da ordem</p>
            <h2>Cliente e itens</h2>

            <div class="form-grid">
                <label class="field">
                    <span>Cliente</span>
                    <input type="text" name="customer_name" value="<?= $customer('customer_name') ?>" required>
                </label>
                <label class="field">
                    <span>Telefone</span>
                    <input type="text" name="customer_phone" value="<?= $customer('customer_phone') ?>" placeholder="Opcional">
                </label>
                <label class="field">
                    <span>Documento</span>
                    <input type="text" name="customer_document" value="<?= $customer('customer_document') ?>" placeholder="Opcional">
                </label>
                <label class="field">
                    <span>Descrição</span>
                    <input type="text" name="description" value="<?= $customer('description') ?>" placeholder="Ex.: barra, conserto, instalação...">
                </label>
            </div>

            <label class="field">
                <span>Notas internas</span>
                <textarea name="notes" rows="3" placeholder="Observações para acompanhamento"><?= $customer('notes') ?></textarea>
            </label>

            <div class="service-order-items">
                <?php for ($index = 0; $index < $rowCount; $index++): ?>
                    <?php
                    $selected = $itemRows[$index] ?? null;
                    $selectedVariantId = is_array($selected) ? (int) $selected['id'] : (int) (($input['items'][$index]['variant_id'] ?? 0));
                    $quantity = is_array($selected) ? (int) ($selected['quantity'] ?? 1) : (string) ($input['items'][$index]['quantity'] ?? '');
                    $discount = is_array($selected) ? (string) ($selected['discount'] ?? '0,00') : (string) ($input['items'][$index]['discount'] ?? '');
                    ?>
                    <article class="service-order-item-row">
                        <label class="field">
                            <span>Item <?= $index + 1 ?></span>
                            <select name="items[<?= (int) $index ?>][variant_id]">
                                <option value="">Selecione</option>
                                <?php foreach ($availableItems as $item): ?>
                                    <option value="<?= (int) $item['id'] ?>" <?= $selectedVariantId === (int) $item['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $item['product_name'] . ' · ' . (string) $item['name'] . ' — R$ ' . $money((int) $item['effective_price_cents']), ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="field">
                            <span>Qtd.</span>
                            <input type="number" min="1" name="items[<?= (int) $index ?>][quantity]" value="<?= htmlspecialchars((string) $quantity, ENT_QUOTES, 'UTF-8') ?>">
                        </label>
                        <label class="field">
                            <span>Desconto</span>
                            <input type="text" name="items[<?= (int) $index ?>][discount]" value="<?= htmlspecialchars($discount, ENT_QUOTES, 'UTF-8') ?>" placeholder="0,00">
                        </label>
                    </article>
                <?php endfor; ?>
            </div>

            <button class="button" type="submit">Criar ordem</button>
        </form>
    </section>
</main>
