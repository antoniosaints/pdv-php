<?php
$money = static fn (int $cents): string => number_format($cents / 100, 2, ',', '.');
$statusLabel = (string) ($statusLabels[$order['status']] ?? $order['status']);
?>
<main class="shell">
    <section class="panel dashboard-hero">
        <div>
            <p class="eyebrow">Ordem de serviço</p>
            <h1><?= htmlspecialchars((string) $order['code'], ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="hero__copy">Cliente: <strong><?= htmlspecialchars((string) $order['customer_name'], ENT_QUOTES, 'UTF-8') ?></strong> · Status atual: <span class="pill pill--muted"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span></p>
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

    <section class="grid grid--three" aria-label="Totais da ordem">
        <article class="panel metric-card">
            <span>Subtotal</span>
            <strong>R$ <?= $money((int) $order['subtotal_cents']) ?></strong>
            <p>Valor bruto dos itens.</p>
        </article>
        <article class="panel metric-card">
            <span>Desconto</span>
            <strong>R$ <?= $money((int) $order['discount_cents']) ?></strong>
            <p>Desconto acumulado.</p>
        </article>
        <article class="panel metric-card">
            <span>Total</span>
            <strong>R$ <?= $money((int) $order['total_cents']) ?></strong>
            <p>Valor previsto para fechamento.</p>
        </article>
    </section>

    <section class="service-order-detail-grid">
        <article class="panel form-panel">
            <p class="eyebrow">Cliente</p>
            <h2>Dados e observações</h2>
            <dl class="service-order-facts">
                <div><dt>Telefone</dt><dd><?= htmlspecialchars((string) ($order['customer_phone'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Documento</dt><dd><?= htmlspecialchars((string) ($order['customer_document'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Descrição</dt><dd><?= htmlspecialchars((string) ($order['description'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Notas</dt><dd><?= htmlspecialchars((string) ($order['notes'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Aberta em</dt><dd><?= htmlspecialchars((string) $order['opened_at'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Venda vinculada</dt><dd><?= $order['sale_id'] === null ? '—' : '<a class="table-link" href="/sales/' . (int) $order['sale_id'] . '">Venda #' . (int) $order['sale_id'] . '</a>' ?></dd></div>
            </dl>
        </article>

        <?php if ($order['sale_id'] === null && ! in_array((string) $order['status'], ['closed', 'cancelled'], true)): ?>
            <form class="panel form-panel stack" method="post" action="/service-orders/<?= (int) $order['id'] ?>/status">
                <input type="hidden" name="_token" value="<?= htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <p class="eyebrow">Status</p>
                <h2>Atualizar andamento</h2>
                <label class="field">
                    <span>Novo status</span>
                    <select name="status" required>
                        <?php foreach ($manualStatuses as $statusOption): ?>
                            <option value="<?= htmlspecialchars((string) $statusOption, ENT_QUOTES, 'UTF-8') ?>" <?= (string) $order['status'] === (string) $statusOption ? 'selected' : '' ?>><?= htmlspecialchars((string) ($statusLabels[$statusOption] ?? $statusOption), ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">
                    <span>Nota da mudança</span>
                    <input type="text" name="notes" placeholder="Ex.: serviço iniciado, aguardando peça...">
                </label>
                <button class="button" type="submit">Salvar status</button>
            </form>
        <?php else: ?>
            <article class="panel form-panel">
                <p class="eyebrow">Status</p>
                <h2>Andamento encerrado</h2>
                <p class="form-panel__copy">Ordens fechadas ou canceladas preservam o histórico e não aceitam mudança manual de status.</p>
            </article>
        <?php endif; ?>
    </section>

    <?php if ($order['sale_id'] === null && (string) $order['status'] !== 'cancelled'): ?>
        <form class="panel form-panel stack table-panel" method="post" action="/service-orders/<?= (int) $order['id'] ?>/close-sale">
            <input type="hidden" name="_token" value="<?= htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <p class="eyebrow">Fechamento</p>
            <h2>Fechar em venda</h2>
            <p class="form-panel__copy">Cria uma venda concluída, registra o pagamento e baixa estoque dos produtos controlados. Serviços não geram movimento de estoque.</p>
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
                    <input type="text" name="payments[0][amount]" value="<?= htmlspecialchars((string) ($payment['amount'] ?? $money((int) $order['total_cents'])), ENT_QUOTES, 'UTF-8') ?>">
                </label>
                <label class="field">
                    <span>Referência</span>
                    <input type="text" name="payments[0][reference]" value="<?= htmlspecialchars((string) ($payment['reference'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Opcional">
                </label>
            </div>
            <button class="button" type="submit">Fechar ordem em venda</button>
        </form>
    <?php endif; ?>

    <section class="panel table-panel">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Itens</p>
                <h2>Serviços e produtos da ordem</h2>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Item</th><th>Tipo</th><th>Qtd.</th><th>Preço</th><th>Desconto</th><th>Total</th><th>Estoque</th></tr></thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars((string) $item['product_name'], ENT_QUOTES, 'UTF-8') ?></strong><small><?= htmlspecialchars((string) $item['variant_name'], ENT_QUOTES, 'UTF-8') ?></small></td>
                            <td><?= htmlspecialchars((string) $item['product_type'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int) $item['quantity'] ?></td>
                            <td>R$ <?= $money((int) $item['unit_price_cents']) ?></td>
                            <td>R$ <?= $money((int) $item['discount_cents']) ?></td>
                            <td>R$ <?= $money((int) $item['total_cents']) ?></td>
                            <td><?= ((int) $item['track_stock']) === 1 ? 'Baixa no fechamento' : 'Sem baixa' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel table-panel">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Histórico</p>
                <h2>Mudanças de status</h2>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Quando</th><th>De</th><th>Para</th><th>Operador</th><th>Notas</th></tr></thead>
                <tbody>
                    <?php foreach ($history as $entry): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) $entry['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($entry['from_status'] === null ? '—' : ($statusLabels[$entry['from_status']] ?? $entry['from_status'])), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($statusLabels[$entry['to_status']] ?? $entry['to_status']), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= $entry['actor_user_id'] === null ? '—' : (int) $entry['actor_user_id'] ?></td>
                            <td><?= htmlspecialchars((string) ($entry['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
