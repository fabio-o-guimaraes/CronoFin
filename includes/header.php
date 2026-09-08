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




    </nav>

</header>