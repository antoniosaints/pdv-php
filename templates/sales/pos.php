<?php
$money = static fn (int $cents): string => number_format($cents / 100, 2, ',', '.');
$paymentAmount = $payment['amount'] ?? null;
if ($paymentAmount === null && isset($payment['amount_cents'])) {
    $paymentAmount = $money((int) $payment['amount_cents']);
}
$paymentAmount = (string) ($paymentAmount ?? '0,00');
?>
<main class="shell">
    <section class="panel dashboard-hero">
        <div>
            <p class="eyebrow">PDV</p>
            <h1>Venda rápida</h1>
            <p class="hero__copy">Busque por nome ou leia um código de barras, confira quantidade/desconto, registre o pagamento e finalize com baixa automática de estoque.</p>
        </div>
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
            <p class="eyebrow">Adicionar item</p>
            <form class="stack" method="get" action="/pos">
                <label class="field">
                    <span>Código de barras</span>
                    <input type="search" name="barcode" value="<?= htmlspecialchars((string) $barcode, ENT_QUOTES, 'UTF-8') ?>" placeholder="Bipe ou digite o código" autofocus>
                </label>
                <button class="button" type="submit">Adicionar por código</button>
            </form>

            <form class="stack" method="get" action="/pos">
                <label class="field">
                    <span>Buscar por nome, SKU ou código</span>
                    <input type="search" name="q" value="<?= htmlspecialchars((string) $term, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: camiseta, ajuste, 789...">
                </label>
                <button class="button button--ghost" type="submit">Buscar item</button>
            </form>

            <?php if ($searchResults !== []): ?>
                <div class="table-wrap table-wrap--spaced">
                    <table class="pos-results">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Preço</th>
                                <th>Estoque</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($searchResults as $result): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars((string) $result['product_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        <small><?= htmlspecialchars((string) $result['name'], ENT_QUOTES, 'UTF-8') ?></small>
                                    </td>
                                    <td>R$ <?= $money((int) $result['effective_price_cents']) ?></td>
                                    <td><?= ((int) $result['track_stock']) === 1 ? (int) $result['current_stock'] : 'Serviço' ?></td>
                                    <td><a class="button button--ghost" href="/pos?variant_id=<?= (int) $result['id'] ?>">Selecionar</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="panel form-panel">
            <p class="eyebrow">Carrinho</p>
            <form class="stack" method="post" action="/pos/sales">
                <input type="hidden" name="_token" value="<?= htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                <?php if ($selectedItems === []): ?>
                    <div class="empty-state">
                        <h2>Nenhum item selecionado.</h2>
                        <p>Use a busca ou o código de barras para preparar a venda.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($selectedItems as $index => $item): ?>
                        <article class="pos-cart-item">
                            <input type="hidden" name="items[<?= (int) $index ?>][variant_id]" value="<?= (int) $item['id'] ?>">
                            <div>
                                <strong><?= htmlspecialchars((string) $item['product_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <small><?= htmlspecialchars((string) $item['name'], ENT_QUOTES, 'UTF-8') ?> · R$ <?= $money((int) $item['effective_price_cents']) ?></small>
                                <small><?= ((int) $item['track_stock']) === 1 ? 'Estoque atual: ' . (int) $item['current_stock'] : 'Serviço sem baixa de estoque' ?></small>
                            </div>
                            <div class="form-grid">
                                <label class="field">
                                    <span>Qtd.</span>
                                    <input type="number" min="1" name="items[<?= (int) $index ?>][quantity]" value="<?= (int) ($item['quantity'] ?? 1) ?>">
                                </label>
                                <label class="field">
                                    <span>Desconto</span>
                                    <input type="text" name="items[<?= (int) $index ?>][discount]" value="<?= htmlspecialchars((string) ($item['discount'] ?? '0,00'), ENT_QUOTES, 'UTF-8') ?>">
                                </label>
                            </div>
                        </article>
                    <?php endforeach; ?>

                    <div class="form-grid">
                        <label class="field">
                            <span>Forma de pagamento</span>
                            <select name="payments[0][method]">
                                <?php foreach (['cash' => 'Dinheiro', 'pix' => 'Pix', 'debit_card' => 'Débito', 'credit_card' => 'Crédito', 'other' => 'Outro'] as $value => $label): ?>
                                    <option value="<?= $value ?>" <?= (string) ($payment['method'] ?? 'cash') === $value ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="field">
                            <span>Valor recebido</span>
                            <input type="text" name="payments[0][amount]" value="<?= htmlspecialchars($paymentAmount, ENT_QUOTES, 'UTF-8') ?>">
                        </label>
                        <label class="field">
                            <span>Referência</span>
                            <input type="text" name="payments[0][reference]" value="<?= htmlspecialchars((string) ($payment['reference'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Opcional">
                        </label>
                    </div>

                    <button class="button" type="submit">Finalizar venda</button>
                <?php endif; ?>
            </form>
        </div>
    </section>
</main>
