<main class="shell">
    <section class="panel dashboard-hero">
        <div>
            <p class="eyebrow">Estoque</p>
            <h1>Reposição e ajustes</h1>
            <p class="hero__copy">Acompanhe saldos, veja alertas de estoque mínimo e registre entradas ou ajustes manuais com histórico auditável.</p>
        </div>
        <a class="button button--ghost" href="/catalog">Catálogo</a>
    </section>

    <?php if ($errors !== []): ?>
        <section class="alert alert--danger" role="alert">
            <?php foreach ($errors as $message): ?>
                <div><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <section class="grid grid--three" aria-label="Resumo de estoque">
        <article class="panel metric-card">
            <span>Variantes controladas</span>
            <strong><?= count($trackedVariants) ?></strong>
            <p>Itens ativos com controle físico de estoque.</p>
        </article>
        <article class="panel metric-card">
            <span>Estoque baixo</span>
            <strong><?= count($lowStockVariants) ?></strong>
            <p>Variantes no estoque mínimo ou abaixo dele.</p>
        </article>
        <article class="panel metric-card">
            <span>Movimentos</span>
            <strong><?= count($movements) ?></strong>
            <p>Últimos lançamentos de venda, entrada ou ajuste.</p>
        </article>
    </section>

    <section class="panel table-panel">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Alertas</p>
                <h2>Estoque mínimo</h2>
            </div>
            <span class="pill <?= $lowStockVariants === [] ? 'pill--ok' : 'pill--muted' ?>"><?= $lowStockVariants === [] ? 'Sem alertas' : count($lowStockVariants) . ' alerta(s)' ?></span>
        </div>

        <?php if ($lowStockVariants === []): ?>
            <div class="empty-state"><p>Nenhuma variante ativa está no estoque mínimo.</p></div>
        <?php else: ?>
            <div class="stock-alerts">
                <?php foreach ($lowStockVariants as $variant): ?>
                    <article class="stock-alert">
                        <strong><?= htmlspecialchars((string) $variant['product_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <span><?= htmlspecialchars((string) $variant['variant_name'], ENT_QUOTES, 'UTF-8') ?> · saldo <?= (int) $variant['current_stock'] ?> / mínimo <?= (int) $variant['stock_min'] ?></span>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="stock-actions">
        <form class="panel form-panel stack" method="post" action="/stock/replenishments">
            <input type="hidden" name="_token" value="<?= htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <p class="eyebrow">Entrada</p>
            <h2>Registrar reposição</h2>
            <label class="field">
                <span>Variante</span>
                <select name="variant_id" required>
                    <option value="">Selecione</option>
                    <?php foreach ($trackedVariants as $variant): ?>
                        <option value="<?= (int) $variant['variant_id'] ?>" <?= $activeForm === 'replenishment' && (int) ($input['variant_id'] ?? 0) === (int) $variant['variant_id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $variant['product_name'] . ' · ' . (string) $variant['variant_name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span>Quantidade</span>
                <input type="number" min="1" name="quantity" value="<?= htmlspecialchars((string) ($activeForm === 'replenishment' ? ($input['quantity'] ?? '') : ''), ENT_QUOTES, 'UTF-8') ?>" required>
            </label>
            <label class="field">
                <span>Motivo / origem</span>
                <input type="text" name="reason" value="<?= htmlspecialchars((string) ($activeForm === 'replenishment' ? ($input['reason'] ?? '') : ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Compra, devolução, conferência...">
            </label>
            <button class="button" type="submit">Registrar entrada</button>
        </form>

        <form class="panel form-panel stack" method="post" action="/stock/adjustments">
            <input type="hidden" name="_token" value="<?= htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <p class="eyebrow">Ajuste</p>
            <h2>Corrigir saldo</h2>
            <label class="field">
                <span>Variante</span>
                <select name="variant_id" required>
                    <option value="">Selecione</option>
                    <?php foreach ($trackedVariants as $variant): ?>
                        <option value="<?= (int) $variant['variant_id'] ?>" <?= $activeForm === 'adjustment' && (int) ($input['variant_id'] ?? 0) === (int) $variant['variant_id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $variant['product_name'] . ' · ' . (string) $variant['variant_name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span>Delta (+ ou -)</span>
                <input type="number" name="delta" value="<?= htmlspecialchars((string) ($activeForm === 'adjustment' ? ($input['delta'] ?? '') : ''), ENT_QUOTES, 'UTF-8') ?>" required>
            </label>
            <label class="field">
                <span>Motivo obrigatório</span>
                <input type="text" name="reason" value="<?= htmlspecialchars((string) ($activeForm === 'adjustment' ? ($input['reason'] ?? '') : ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Quebra, inventário, perda..." required>
            </label>
            <button class="button button--ghost" type="submit">Registrar ajuste</button>
        </form>
    </section>

    <section class="panel table-panel">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Saldos</p>
                <h2>Variantes controladas</h2>
            </div>
        </div>
        <?php if ($trackedVariants === []): ?>
            <div class="empty-state"><p>Nenhum produto ativo com controle de estoque.</p></div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Item</th><th>Barcode</th><th>Saldo</th><th>Mínimo</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($trackedVariants as $variant): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars((string) $variant['product_name'], ENT_QUOTES, 'UTF-8') ?></strong><small><?= htmlspecialchars((string) $variant['variant_name'], ENT_QUOTES, 'UTF-8') ?></small></td>
                                <td><?= htmlspecialchars((string) ($variant['barcode'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int) $variant['current_stock'] ?></td>
                                <td><?= (int) $variant['stock_min'] ?></td>
                                <td><span class="pill <?= ((int) $variant['is_low_stock']) === 1 ? 'pill--muted' : 'pill--ok' ?>"><?= ((int) $variant['is_low_stock']) === 1 ? 'Baixo' : 'OK' ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="panel table-panel">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Histórico</p>
                <h2>Movimentos recentes</h2>
            </div>
        </div>
        <?php if ($movements === []): ?>
            <div class="empty-state"><p>Nenhum movimento de estoque registrado.</p></div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Quando</th><th>Tipo</th><th>Item</th><th>Delta</th><th>Antes → Depois</th><th>Motivo</th></tr></thead>
                    <tbody>
                        <?php foreach ($movements as $movement): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $movement['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) $movement['type'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><strong><?= htmlspecialchars((string) $movement['product_name'], ENT_QUOTES, 'UTF-8') ?></strong><small><?= htmlspecialchars((string) $movement['variant_name'], ENT_QUOTES, 'UTF-8') ?></small></td>
                                <td class="<?= (int) $movement['quantity_delta'] >= 0 ? 'stock-delta--positive' : 'stock-delta--negative' ?>"><?= (int) $movement['quantity_delta'] ?></td>
                                <td><?= (int) $movement['quantity_before'] ?> → <?= (int) $movement['quantity_after'] ?></td>
                                <td><?= htmlspecialchars((string) ($movement['reason'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
