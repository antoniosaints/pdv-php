<main class="shell">
    <section class="panel dashboard-hero">
        <div>
            <p class="eyebrow">Diagnóstico operacional</p>
            <h1><?= $passes ? 'Sistema saudável.' : 'Atenção necessária.' ?></h1>
            <p class="hero__copy">Verifique banco, migrations, escrita em storage e logs antes de operar o caixa.</p>
        </div>
        <span class="health-badge health-badge--<?= $passes ? 'ok' : 'warn' ?>">
            <?= $passes ? 'OK' : 'Verificar' ?>
        </span>
    </section>

    <section class="health-grid" aria-label="Checks do sistema">
        <?php foreach ($checks as $check): ?>
            <article class="panel health-card health-card--<?= htmlspecialchars((string) $check['status'], ENT_QUOTES, 'UTF-8') ?>">
                <span><?= htmlspecialchars((string) $check['label'], ENT_QUOTES, 'UTF-8') ?></span>
                <strong><?= htmlspecialchars(strtoupper((string) $check['status']), ENT_QUOTES, 'UTF-8') ?></strong>
                <p><?= htmlspecialchars((string) $check['detail'], ENT_QUOTES, 'UTF-8') ?></p>
            </article>
        <?php endforeach; ?>
    </section>
</main>
