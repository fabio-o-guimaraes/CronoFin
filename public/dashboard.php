<?php
require_once __DIR__ . '/../includes/auth.php';

$sections = [
    'summary'    => 'Resumo',
    'movements'  => 'Movimentos',
    'categories' => 'Categorias',
    'goals'      => 'Objetivos',
    'profile'    => 'Perfil',
];

$section = $_GET['section'] ?? 'summary';

if (!is_string($section) || !array_key_exists($section, $sections)) {
    $section = 'summary';
}
?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>CronoFin - <?= htmlspecialchars($sections[$section]) ?></title>

    <!-- Fontes -->
    <?php require __DIR__ . '/../includes/fonts.php'; ?>

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/pages/dashboard.css">
    <link rel="stylesheet" href="assets/css/pages/profile.css">
</head>

<body class="dashboard-page">
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <div class="app-layout">
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

        <main class="app-content">
            <?php require __DIR__ . '/../includes/sections/' . $section . '.php'; ?>
        </main>
    </div>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>

    <script src="assets/js/main.js"></script>
</body>

</html>