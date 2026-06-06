<main class="shell">
    <section class="panel dashboard-hero">
        <div>
            <p class="eyebrow"><?= $product['type'] === 'service' ? 'Serviço' : 'Produto' ?></p>
            <h1><?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="hero__copy">
                <?= htmlspecialchars((string) ($product['description'] ?: 'Sem descrição cadastrada.'), ENT_QUOTES, 'UTF-8') ?>
            </p>
        </div>
        <div class="actions actions--vertical">
            <a class="button" href="/catalog/<?= (int) $product['id'] ?>/edit">Editar</a>
            <form method="post" action="/catalog/<?= (int) $product['id'] ?>/toggle">
                <input type="hidden" name="_token" value="<?= htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <button class="button button--ghost" type="submit"><?= ((int) $product['active']) === 1 ? 'Inativar' : 'Ativar' ?></button>
            </form>
        </div>
    </section>

    <section class="grid grid--three">
        <article class="panel metric-card">
            <span>Preço</span>
            <strong>R$ <?= number_format(((int) $product['price_cents']) / 100, 2, ',', '.') ?></strong>
            <p>Custo: R$ <?= number_format(((int) $product['cost_cents']) / 100, 2, ',', '.') ?></p>
        </article>
        <article class="panel metric-card">
            <span>Estoque</span>
            <strong><?= ((int) $product['track_stock']) === 1 ? 'Controlado' : 'Não controla' ?></strong>
            <p>Mínimo: <?= (int) $product['stock_min'] ?></p>
        </article>
        <article class="panel metric-card">
            <span>Status</span>
            <strong><?= ((int) $product['active']) === 1 ? 'Ativo' : 'Inativo' ?></strong>
            <p>SKU: <?= htmlspecialchars((string) ($product['sku'] ?: 'não definido'), ENT_QUOTES, 'UTF-8') ?></p>
        </article>
    </section>

    <section class="panel table-panel">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Variantes</p>
                <h2>Códigos, tamanhos, cores e estoque inicial</h2>
            </div>
            <span class="pill pill--ok">Pronto para PDV</span>
        </div>

        <?php
            $originalVariantErrors = $variantErrors;
            $formVariant = $editingVariantId === null ? $variantInput : [];
            $formAction = '/catalog/' . (int) $product['id'] . '/variants';
            $formTitle = 'Adicionar variante';
            $formSubmit = 'Salvar variante';
            $variantErrors = $editingVariantId === null ? $originalVariantErrors : [];
            require __DIR__ . '/_variant_form.php';
            $variantErrors = $originalVariantErrors;
        ?>

        <?php if ($variants === []): ?>
            <div class="empty-state empty-state--spaced">
                <h2>Nenhuma variante cadastrada.</h2>
                <p>Cadastre pelo menos uma variante para usar barcode e lookup no PDV.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap table-wrap--spaced">
                <table>
                    <thead><tr><th>Variante</th><th>SKU</th><th>Barcode</th><th>Preço</th><th>Estoque</th><th>Status</th><th>Etiqueta / editar rápido</th></tr></thead>
                    <tbody>
                        <?php foreach ($variants as $variant): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $variant['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($variant['sku'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($variant['barcode'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= $variant['price_cents'] === null ? 'Produto' : 'R$ ' . number_format(((int) $variant['price_cents']) / 100, 2, ',', '.') ?></td>
                                <td><?= (int) $variant['current_stock'] ?></td>
                                <td><span class="pill <?= ((int) $variant['active']) === 1 ? 'pill--ok' : 'pill--muted' ?>"><?= ((int) $variant['active']) === 1 ? 'Ativa' : 'Inativa' ?></span></td>
                                <td>
                                    <a class="button button--ghost" href="/catalog/<?= (int) $product['id'] ?>/variants/<?= (int) $variant['id'] ?>/label">Imprimir etiqueta</a>
                                    <?php
                                        $isEditingThisVariant = $editingVariantId === (int) $variant['id'];
                                        $formVariant = $isEditingThisVariant ? $variantInput + $variant : $variant;
                                        $formAction = '/catalog/' . (int) $product['id'] . '/variants/' . (int) $variant['id'];
                                        $formTitle = 'Editar ' . (string) $variant['name'];
                                        $formSubmit = 'Atualizar variante';
                                        $variantErrors = $isEditingThisVariant ? $originalVariantErrors : [];
                                        require __DIR__ . '/_variant_form.php';
                                        $variantErrors = $originalVariantErrors;
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
