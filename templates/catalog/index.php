<main class="shell">
    <section class="panel dashboard-hero">
        <div>
            <p class="eyebrow">Catálogo</p>
            <h1>Produtos e serviços</h1>
            <p class="hero__copy">Cadastre itens, custos, preços, estoque mínimo, dados de etiqueta, variantes e códigos de barras para alimentar o PDV.</p>
        </div>
        <a class="button" href="/catalog/create">Novo item</a>
    </section>

    <section class="panel table-panel">
        <form class="toolbar" method="get" action="/catalog">
            <label class="field toolbar__search">
                <span>Buscar</span>
                <input type="search" name="q" value="<?= htmlspecialchars((string) $term, ENT_QUOTES, 'UTF-8') ?>" placeholder="Nome ou SKU">
            </label>
            <button class="button button--ghost" type="submit">Filtrar</button>
        </form>

        <?php if ($products === []): ?>
            <div class="empty-state">
                <p class="eyebrow">Sem itens</p>
                <h2>Nenhum produto ou serviço cadastrado.</h2>
                <p>Crie o primeiro item para liberar PDV, etiquetas, reposição e relatórios.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Tipo</th>
                            <th>Preço</th>
                            <th>Estoque mínimo</th>
                            <th>Variantes</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <a class="table-link" href="/catalog/<?= (int) $product['id'] ?>"><?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?></a>
                                    <?php if (! empty($product['sku'])): ?><small><?= htmlspecialchars((string) $product['sku'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
                                </td>
                                <td><?= $product['type'] === 'service' ? 'Serviço' : 'Produto' ?></td>
                                <td>R$ <?= number_format(((int) $product['price_cents']) / 100, 2, ',', '.') ?></td>
                                <td><?= (int) $product['stock_min'] ?></td>
                                <td><?= (int) ($product['variant_count'] ?? 0) ?></td>
                                <td><span class="pill <?= ((int) $product['active']) === 1 ? 'pill--ok' : 'pill--muted' ?>"><?= ((int) $product['active']) === 1 ? 'Ativo' : 'Inativo' ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
