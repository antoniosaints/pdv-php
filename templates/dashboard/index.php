<main class="shell">
    <section class="panel dashboard-hero">
        <div>
            <p class="eyebrow">Dashboard protegido</p>
            <h1>Base operacional pronta.</h1>
            <p class="hero__copy">
                Olá, <?= htmlspecialchars((string) ($authUser['name'] ?? 'usuário'), ENT_QUOTES, 'UTF-8') ?>. A fundação do sistema está online: sessão, banco, migrations e layout já estão conectados.
            </p>
        </div>
        <form method="post" action="/logout">
            <input type="hidden" name="_token" value="<?= htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <div class="actions actions--vertical">
                <a class="button" href="/pos">Abrir PDV</a>
                <a class="button button--ghost" href="/service-orders">Ordens de serviço</a>
                <a class="button button--ghost" href="/catalog">Abrir catálogo</a>
                <a class="button button--ghost" href="/stock">Controlar estoque</a>
                <button class="button button--ghost" type="submit">Sair</button>
            </div>
        </form>
    </section>

    <section class="grid grid--three" aria-label="Resumo operacional">
        <?php foreach ($cards as $card): ?>
            <article class="panel metric-card">
                <span><?= htmlspecialchars((string) $card['label'], ENT_QUOTES, 'UTF-8') ?></span>
                <strong><?= htmlspecialchars((string) $card['value'], ENT_QUOTES, 'UTF-8') ?></strong>
                <p><?= htmlspecialchars((string) $card['hint'], ENT_QUOTES, 'UTF-8') ?></p>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="panel roadmap-panel">
        <p class="eyebrow">Próximas fatias</p>
        <ol>
            <li>Catálogo de produtos, variantes, serviços e códigos de barras.</li>
            <li>PDV com leitor de código de barras, pagamentos e baixa automática.</li>
            <li>Ordens de serviço com cliente, itens, status e fechamento em venda.</li>
            <li>Impressão de recibos e etiquetas via QZ Tray.</li>
            <li>Relatórios, reposição, dashboard gerencial e backup.</li>
        </ol>
    </section>
</main>
