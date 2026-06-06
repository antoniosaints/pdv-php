<?php
$money = static fn (int $cents): string => number_format($cents / 100, 2, ',', '.');
$labelName = (string) ($product['label_name'] ?: $product['name']);
$priceCents = $variant['price_cents'] === null ? (int) $product['price_cents'] : (int) $variant['price_cents'];
?>
<main class="shell print-shell">
    <section class="panel dashboard-hero no-print">
        <div>
            <p class="eyebrow">Etiqueta</p>
            <h1>Preview da etiqueta</h1>
            <p class="hero__copy">Confira nome, preço e código antes de enviar para a impressora de etiquetas.</p>
        </div>
        <div class="actions actions--vertical">
            <button class="button" type="button" data-print-action="qz" data-print-target="<?= htmlspecialchars((string) $printTarget, ENT_QUOTES, 'UTF-8') ?>">Enviar para QZ Tray</button>
            <button class="button button--ghost" type="button" data-print-action="native" data-print-target="<?= htmlspecialchars((string) $printTarget, ENT_QUOTES, 'UTF-8') ?>">Impressão nativa</button>
            <a class="button button--ghost" href="/catalog/<?= (int) $product['id'] ?>">Voltar ao item</a>
        </div>
    </section>

    <section class="panel print-status no-print" data-print-status-panel>
        <p class="eyebrow">Status de impressão</p>
        <strong data-print-status>QZ Tray não verificado</strong>
        <p data-print-detail>O diagnóstico de impressão será atualizado pelo navegador quando o adaptador carregar.</p>
    </section>

    <section class="print-preview print-preview--label" id="<?= htmlspecialchars((string) $printTarget, ENT_QUOTES, 'UTF-8') ?>" data-print-kind="label">
        <article class="label-card">
            <small><?= htmlspecialchars($product['type'] === 'service' ? 'SERVIÇO' : 'PRODUTO', ENT_QUOTES, 'UTF-8') ?></small>
            <strong><?= htmlspecialchars($labelName, ENT_QUOTES, 'UTF-8') ?></strong>
            <span><?= htmlspecialchars((string) $variant['name'], ENT_QUOTES, 'UTF-8') ?></span>
            <b>R$ <?= $money($priceCents) ?></b>
            <div class="label-barcode" aria-label="Código de barras">
                <?= htmlspecialchars((string) ($variant['barcode'] ?: 'SEM-BARCODE'), ENT_QUOTES, 'UTF-8') ?>
            </div>
            <em>SKU <?= htmlspecialchars((string) ($variant['sku'] ?: $product['sku'] ?: 'não definido'), ENT_QUOTES, 'UTF-8') ?></em>
        </article>
    </section>
</main>
