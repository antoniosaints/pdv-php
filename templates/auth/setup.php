<main class="shell shell--narrow">
    <section class="panel form-panel">
        <p class="eyebrow">Primeiro acesso</p>
        <h1>Criar administrador</h1>
        <p class="form-panel__copy">Essa etapa aparece apenas enquanto não existir nenhum usuário. Depois de criado, o setup fica bloqueado automaticamente.</p>

        <?php if (! empty($error)): ?>
            <div class="alert alert--danger" role="alert">
                <?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form class="stack" method="post" action="/setup/admin">
            <input type="hidden" name="_token" value="<?= htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8') ?>">

            <label class="field">
                <span>Nome</span>
                <input type="text" name="name" autocomplete="name" required value="<?= htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8') ?>">
            </label>

            <label class="field">
                <span>E-mail</span>
                <input type="email" name="email" autocomplete="username" required value="<?= htmlspecialchars((string) $email, ENT_QUOTES, 'UTF-8') ?>">
            </label>

            <label class="field">
                <span>Senha</span>
                <input type="password" name="password" autocomplete="new-password" minlength="8" required>
            </label>

            <label class="field">
                <span>Confirmar senha</span>
                <input type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required>
            </label>

            <button class="button" type="submit">Criar administrador</button>
        </form>
    </section>
</main>
