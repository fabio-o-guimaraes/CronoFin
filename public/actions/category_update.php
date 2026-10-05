<?php
require_once __DIR__ . '/../../includes/session.php';
startSecureSession();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/category_icons.php';

/* Redireciona sempre para a secção das categorias, com uma query extra (erro ou sucesso) */
function redirectToCategories(string $query): never
{
    header('Location: ../dashboard.php?section=categories&' . $query);
    exit;
}

/* Guarda de entrada */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboard.php?section=categories');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
/* Recolhe os dados do form */
$idInput = $_POST['id'] ?? '';
$name    = trim($_POST['name'] ?? '');
$icon    = $_POST['icon'] ?? DEFAULT_CATEGORY_ICON;

if (!is_string($idInput) || !ctype_digit($idInput)) {
    redirectToCategories('error=notfound');
}

$id = (int) $idInput;

/* Guarda os dados para repor no form se houver erro */
$_SESSION['category_form_data'] = [
    'name' => $name,
    'icon' => $icon,
];

/* Em caso de erro, volta ao modo de edição desta categoria */
$editQuery = 'edit=' . $id . '&';

/* A categoria tem de existir e ser do utilizador (as predefinidas nunca coincidem) */
$stmt = $pdo->prepare(
    'SELECT id_categories FROM categories WHERE id_categories = :id AND user_id = :user_id'
);
$stmt->execute(['id' => $id, 'user_id' => $userId]);

if (!$stmt->fetch()) {
    unset($_SESSION['category_form_data']);
    redirectToCategories('error=notfound');
}

/* Validar nome e ícone (igual ao criar, mas com $editQuery) */
if ($name === '') {
    redirectToCategories($editQuery . 'error=empty');
}

if (mb_strlen($name) > 100) {
    redirectToCategories($editQuery . 'error=length');
}

if (!in_array($icon, getCategoryIcons(), true)) {
    redirectToCategories($editQuery . 'error=icon');
}

/* Nome já usado por OUTRA categoria */
$stmt = $pdo->prepare(
    'SELECT id_categories FROM categories
     WHERE name = :name AND (user_id IS NULL OR user_id = :user_id) AND id_categories != :id'
);
$stmt->execute(['name' => $name, 'user_id' => $userId, 'id' => $id]);

if ($stmt->fetch()) {
    redirectToCategories($editQuery . 'error=exists');
}

/* Gravar na base de dados */
try {
    $stmt = $pdo->prepare(
        'UPDATE categories SET name = :name, icon = :icon WHERE id_categories = :id AND user_id = :user_id'
    );
    $stmt->execute([
        'name'    => $name,
        'icon'    => $icon,
        'id'      => $id,
        'user_id' => $userId,
    ]);
} catch (PDOException $e) {
    error_log('Erro ao atualizar categoria: ' . $e->getMessage());
    redirectToCategories($editQuery . 'error=unknown');
}

unset($_SESSION['category_form_data']);

redirectToCategories('success=updated');
