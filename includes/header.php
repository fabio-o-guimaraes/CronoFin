<?php

/**
 * Header comum a todas as páginas
 * Não bloqueia o acesso (isso é feito pelo auth.php) - só adapta os links - mediante sessão iniciada
 */

require_once __DIR__ . '/session.php';
startSecureSession();


$isLoggedIn = isset($_SESSION['user_id']);

if ($isLoggedIn) {
    $logoLink = 'dashboard.php';
} else {
    $logoLink = 'index.php';
}
?>

<header class="site-header">
    <nav class="main-nav">
        <a href="<?= $logoLink ?>" class="brand">
            <img src="assets/img/icon_v1.svg" alt="Logótipo CronoFin" class="brand-logo">
            <img src="assets/img/CronoFin_v1.svg" class="brand-name">
        </a>

        <button type="button" class="menu-toggle" aria-label="Abrir menu" aria-expanded="false" aria-controls="main-menu">
            <span class="menu-toggle-bar" aria-hidden="true"></span>
            <span class="menu-toggle-bar" aria-hidden="true"></span>
            <span class="menu-toggle-bar" aria-hidden="true"></span>
        </button>

        <ul class="main-menu" id="main-menu">
            <?php if ($isLoggedIn): ?>
                <li><a href="logout.php">Sair</a></li>
            <?php else: ?>
                <li><a href="login.php">Iniciar Sessão</a></li>
                <li><a href="register.php">Registo</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>