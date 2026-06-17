<?php
$money = static fn (int $cents): string => number_format($cents / 100, 2, ',', '.');
$statusCounts = array_count_values(array_map(static fn (array $order): string => (string) $order['status'], $orders));
?>
<main class="shell">
    <section class="panel dashboard-hero">
        <div>
            <p class="eyebrow">Serviços</p>
            <h1>Ordens de serviço</h1>
            <p class="hero__copy">Acompanhe clientes, itens, status e totais antes de fechar a ordem no fluxo de venda.</p>
        </div>
        <a class="button" href="/service-orders/create">Nova ordem</a>
    </section>

    <?php if ($errors !== []): ?>
        <section class="alert alert--danger" role="alert">
            <?php foreach ($errors as $message): ?>
                <div><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <section class="grid grid--three" aria-label="Resumo de ordens">
        <article class="panel metric-card">
            <span>Ordens listadas</span>
            <strong><?= count($orders) ?></strong>
            <p>Resultado do filtro atual.</p>
        </article>
        <article class="panel metric-card">
            <span>Abertas</span>
            <strong><?= (int) ($statusCounts['open'] ?? 0) ?></strong>
            <p>Ordens aguardando início.</p>
        </article>
        <article class="panel metric-card">
            <span>Em execução</span>
            <strong><?= (int) ($statusCounts['in_progress'] ?? 0) ?></strong>
            <p>Serviços sendo acompanhados.</p>
        </article>
    </section>

    <section class="panel table-panel">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Fila operacional</p>
                <h2>Ordens recentes</h2>
            </div>
            <form class="toolbar" method="get" action="/service-orders">
                <label class="field toolbar__search">
                    <span>Status</span>
                    <select name="status">
                        <option value="">Todos</option>
                        <?php foreach ($statusLabels as $value => $label): ?>
                            <option value="<?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?>" <?= (string) ($statusFilter ?? '') === (string) $value ? 'selected' : '' ?>><?= htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button class="button button--ghost" type="submit">Filtrar</button>
            </form>
        </div>

        <?php if ($orders === []): ?>
            <div class="empty-state">
                <h2>Nenhuma ordem encontrada.</h2>
                <p>Crie uma ordem para acompanhar serviços, produtos e status antes do fechamento.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Ordem</th><th>Cliente</th><th>Status</th><th>Itens</th><th>Total</th><th>Abertura</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars((string) $order['code'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                <td><?= htmlspecialchars((string) $order['customer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="pill pill--muted"><?= htmlspecialchars((string) ($statusLabels[$order['status']] ?? $order['status']), ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td><?= (int) ($order['item_count'] ?? 0) ?></td>
                                <td>R$ <?= $money((int) $order['total_cents']) ?></td>
                                <td><?= htmlspecialchars((string) $order['opened_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><a class="table-link" href="/service-orders/<?= (int) $order['id'] ?>">Abrir</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
