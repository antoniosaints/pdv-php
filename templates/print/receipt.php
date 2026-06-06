<?php $money = static fn (int $cents): string => number_format($cents / 100, 2, ',', '.'); ?>
<main class="shell print-shell">
    <section class="panel dashboard-hero no-print">
        <div>
            <p class="eyebrow">Recibo gerencial</p>
            <h1>Preview do recibo</h1>
            <p class="hero__copy">Confira os dados antes de imprimir. Este recibo é gerencial e não substitui documento fiscal quando exigido por lei.</p>
        </div>
        <div class="actions actions--vertical">
            <button class="button" type="button" data-print-action="qz" data-print-target="<?= htmlspecialchars((string) $printTarget, ENT_QUOTES, 'UTF-8') ?>">Enviar para QZ Tray</button>
            <button class="button button--ghost" type="button" data-print-action="native" data-print-target="<?= htmlspecialchars((string) $printTarget, ENT_QUOTES, 'UTF-8') ?>">Impressão nativa</button>
            <a class="button button--ghost" href="/sales/<?= (int) $sale['id'] ?>">Voltar à venda</a>
        </div>
    </section>

    <section class="panel print-status no-print" data-print-status-panel>
        <p class="eyebrow">Status de impressão</p>
        <strong data-print-status>QZ Tray não verificado</strong>
        <p data-print-detail>O diagnóstico de impressão será atualizado pelo navegador quando o adaptador carregar.</p>
    </section>

    <section class="print-preview print-preview--receipt" id="<?= htmlspecialchars((string) $printTarget, ENT_QUOTES, 'UTF-8') ?>" data-print-kind="receipt">
        <div class="receipt-paper">
            <header class="receipt-header">
                <strong>PDV Estoque</strong>
                <span>Recibo gerencial não fiscal</span>
                <small><?= htmlspecialchars((string) $sale['code'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string) $sale['completed_at'], ENT_QUOTES, 'UTF-8') ?></small>
            </header>

            <table class="receipt-table">
                <thead>
                    <tr><th>Item</th><th>Qtd</th><th>Total</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars((string) $item['product_name'], ENT_QUOTES, 'UTF-8') ?>
                                <small><?= htmlspecialchars((string) $item['variant_name'], ENT_QUOTES, 'UTF-8') ?></small>
                            </td>
                            <td><?= (int) $item['quantity'] ?></td>
                            <td>R$ <?= $money((int) $item['total_cents']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <dl class="receipt-totals">
                <div><dt>Subtotal</dt><dd>R$ <?= $money((int) $sale['subtotal_cents']) ?></dd></div>
                <div><dt>Desconto</dt><dd>R$ <?= $money((int) $sale['discount_cents']) ?></dd></div>
                <div><dt>Total</dt><dd>R$ <?= $money((int) $sale['total_cents']) ?></dd></div>
                <div><dt>Pago</dt><dd>R$ <?= $money((int) $sale['payment_total_cents']) ?></dd></div>
                <div><dt>Troco</dt><dd>R$ <?= $money((int) $sale['change_cents']) ?></dd></div>
            </dl>

            <footer class="receipt-footer">
                <?php foreach ($payments as $payment): ?>
                    <span>Pagamento: <?= htmlspecialchars((string) $payment['method'], ENT_QUOTES, 'UTF-8') ?> · R$ <?= $money((int) $payment['amount_cents']) ?></span>
                <?php endforeach; ?>
                <strong>Obrigado pela preferência.</strong>
            </footer>
        </div>
    </section>
</main>
