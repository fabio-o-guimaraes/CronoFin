<?php
require_once __DIR__ . '/../../includes/session.php';
startSecureSession();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/category_icons.php';

/* Redireciona sempre para categorias, com uma query extra (erro ou sucesso) */
function redirectToCategories(string $query): never
{
    header('Location: ../dashboard.php?section=categories&' . $query);
    exit;
}

/* Guarda de entrada */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboard.php?section=categories'); /* redireciona se não for via POST */
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php'); /* Redireciona se não tiver login */
    exit;
}

$userId = (int) $_SESSION['user_id'];

/* Recolhe os dados do form */
$name = trim($_POST['name'] ?? '');
$icon = $_POST['icon'] ?? DEFAULT_CATEGORY_ICON;

/* Guarda os dados para repor no form se houver erro */
$_SESSION['category_form_data'] = [
    'name' => $name,
    'icon' => $icon,
];

/* Validar nome */
if ($name === '') {
    redirectToCategories('error=empty');
}

if (mb_strlen($name) > 100) {
    redirectToCategories('error=length');
}

/* Validar ícone: tem de estar na lista permitida */
if (!in_array($icon, getCategoryIcons(), true)) {
    redirectToCategories('error=icon');
}

/* Nome já usado numa predefinida ou numa categoria do utilizador */
$stmt = $pdo->prepare(
    'SELECT id_categories FROM categories
     WHERE name = :name AND (user_id IS NULL OR user_id = :user_id)'
);
$stmt->execute(['name' => $name, 'user_id' => $userId]);

if ($stmt->fetch()) {
    redirectToCategories('error=exists');
}

/* Gravar na base de dados */
try {
    $stmt = $pdo->prepare(
        'INSERT INTO categories (name, icon, status, user_id) VALUES (:name, :icon, \'active\', :user_id)'
    );
    $stmt->execute([
        'name'    => $name,
        'icon'    => $icon,
        'user_id' => $userId,
    ]);
} catch (PDOException $e) {
    error_log('Erro ao criar categoria: ' . $e->getMessage());
    redirectToCategories('error=unknown');
}

unset($_SESSION['category_form_data']);

redirectToCategories('success=created');
