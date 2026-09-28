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

if (!array_key_exists($section, $sections)) {
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

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/components.css">
</head>

<body>
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <main>
        <?php require __DIR__ . '/../includes/sections/' . $section . '.php'; ?>
    </main>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>

    <script src="assets/js/main.js"></script>
</body>

</html>