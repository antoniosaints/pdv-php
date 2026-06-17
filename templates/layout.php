<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title><?= htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string) $appName, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
    <div class="app-frame">
        <header class="topbar" aria-label="Cabeçalho principal">
            <a class="brand" href="/" aria-label="Ir para o início">
                <span class="brand__mark">PDV</span>
                <span>
                    <strong><?= htmlspecialchars((string) $appName, ENT_QUOTES, 'UTF-8') ?></strong>
                    <small>caixa · estoque · relatórios</small>
                </span>
            </a>
            <nav class="topbar__nav" aria-label="Navegação principal">
                <a href="/" class="<?= $currentRoute === '/' ? 'is-active' : '' ?>">Início</a>
                <?php if (is_array($authUser)): ?>
                    <a href="/dashboard" class="<?= $currentRoute === '/dashboard' ? 'is-active' : '' ?>">Dashboard</a>
                    <a href="/pos" class="<?= $currentRoute === '/pos' ? 'is-active' : '' ?>">PDV</a>
                    <a href="/service-orders" class="<?= $currentRoute === '/service-orders' ? 'is-active' : '' ?>">Ordens</a>
                    <a href="/catalog" class="<?= $currentRoute === '/catalog' ? 'is-active' : '' ?>">Catálogo</a>
                    <a href="/stock" class="<?= $currentRoute === '/stock' ? 'is-active' : '' ?>">Estoque</a>
                    <?php if (isset($csrfToken)): ?>
                        <form class="nav-logout" method="post" action="/logout">
                            <input type="hidden" name="_token" value="<?= htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit">Sair</button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="/login" class="<?= $currentRoute === '/login' ? 'is-active' : '' ?>">Login</a>
                <?php endif; ?>
                <a href="/health" class="<?= $currentRoute === '/health' ? 'is-active' : '' ?>">Health</a>
            </nav>
        </header>

        <?= $content ?>
    </div>
    <script src="/assets/print.js" defer></script>
</body>
</html>
