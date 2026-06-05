<main class="shell shell--narrow">
    <section class="panel form-panel">
        <p class="eyebrow">Acesso protegido</p>
        <h1>Entrar no PDV</h1>
        <p class="form-panel__copy">Use seu usuário para acessar caixa, estoque, relatórios e configurações.</p>

        <?php if (! empty($error)): ?>
            <div class="alert alert--danger" role="alert">
                <?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form class="stack" method="post" action="/login">
            <input type="hidden" name="_token" value="<?= htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars((string) $redirect, ENT_QUOTES, 'UTF-8') ?>">

            <label class="field">
                <span>E-mail</span>
                <input type="email" name="email" autocomplete="username" required value="<?= htmlspecialchars((string) $email, ENT_QUOTES, 'UTF-8') ?>">
            </label>

            <label class="field">
                <span>Senha</span>
                <input type="password" name="password" autocomplete="current-password" required>
            </label>

            <button class="button" type="submit">Entrar</button>
        </form>
    </section>
</main>
