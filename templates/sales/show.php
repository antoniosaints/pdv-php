<?php $money = static fn (int $cents): string => number_format($cents / 100, 2, ',', '.'); ?>
<main class="shell">
    <section class="panel dashboard-hero">
        <div>
            <p class="eyebrow">Venda concluída</p>
            <h1><?= htmlspecialchars((string) $sale['code'], ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="hero__copy">Venda registrada com itens, pagamento e movimentos de estoque vinculados.</p>
        </div>
        <div class="actions actions--vertical">
            <a class="button" href="/sales/<?= (int) $sale['id'] ?>/receipt">Imprimir recibo</a>
            <a class="button button--ghost" href="/pos">Nova venda</a>
            <a class="button button--ghost" href="/dashboard">Dashboard</a>
        </div>
    </section>

    <section class="grid grid--three" aria-label="Resumo da venda">
        <article class="panel metric-card">
            <span>Status</span>
            <strong><?= htmlspecialchars((string) $sale['status'], ENT_QUOTES, 'UTF-8') ?></strong>
            <p><?= htmlspecialchars((string) $sale['completed_at'], ENT_QUOTES, 'UTF-8') ?></p>
        </article>
        <article class="panel metric-card">
            <span>Total</span>
            <strong>R$ <?= $money((int) $sale['total_cents']) ?></strong>
            <p>Subtotal R$ <?= $money((int) $sale['subtotal_cents']) ?> · desconto R$ <?= $money((int) $sale['discount_cents']) ?></p>
        </article>
        <article class="panel metric-card">
            <span>Pagamento</span>
            <strong>R$ <?= $money((int) $sale['payment_total_cents']) ?></strong>
            <p>Troco R$ <?= $money((int) $sale['change_cents']) ?></p>
        </article>
    </section>

    <section class="panel table-panel">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Itens</p>
                <h2>Itens vendidos</h2>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qtd.</th>
                        <th>Preço</th>
                        <th>Desconto</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars((string) $item['product_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <small><?= htmlspecialchars((string) $item['variant_name'], ENT_QUOTES, 'UTF-8') ?></small>
                            </td>
                            <td><?= (int) $item['quantity'] ?></td>
                            <td>R$ <?= $money((int) $item['unit_price_cents']) ?></td>
                            <td>R$ <?= $money((int) $item['discount_cents']) ?></td>
                            <td>R$ <?= $money((int) $item['total_cents']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel table-panel">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Diagnóstico</p>
                <h2>Pagamentos e baixa de estoque</h2>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Detalhe</th>
                        <th>Valor / Movimento</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td>Pagamento</td>
                            <td><?= htmlspecialchars((string) $payment['method'], ENT_QUOTES, 'UTF-8') ?><?= $payment['reference'] !== null ? ' · ' . htmlspecialchars((string) $payment['reference'], ENT_QUOTES, 'UTF-8') : '' ?></td>
                            <td>R$ <?= $money((int) $payment['amount_cents']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php foreach ($movements as $movement): ?>
                        <tr>
                            <td>Estoque</td>
                            <td>Variante #<?= (int) $movement['variant_id'] ?> · <?= htmlspecialchars((string) $movement['reason'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int) $movement['quantity_before'] ?> → <?= (int) $movement['quantity_after'] ?> (<?= (int) $movement['quantity_delta'] ?>)</td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($movements === []): ?>
                        <tr>
                            <td>Estoque</td>
                            <td>Sem movimento físico</td>
                            <td>Serviço ou item não controlado por estoque</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
