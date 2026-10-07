<?php
require_once __DIR__ . '/../../includes/session.php';
startSecureSession();

require_once __DIR__ . '/../../config/database.php';

/* Redireciona sempre para a seccção das categorias, com uma query extra (erro ou sucesso) */
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

/* Recolhe o id */
$idInput = $_POST['id'] ?? '';

if (!is_string($idInput) || !ctype_digit($idInput)) {
    redirectToCategories('error=notfound');
}

$id = (int) $idInput;

/* A categoria tem de existir e ser do utilizador */
$stmt = $pdo->prepare(
    'SELECT id_categories FROM categories WHERE id_categories = :id AND user_id = :user_id'
);
$stmt->execute(['id' => $id, 'user_id' => $userId]);

if (!$stmt->fetch()) {
    redirectToCategories('error=notfound');
}

/* Conta os movimentos associados */
$stmt = $pdo->prepare('SELECT COUNT(*) FROM movements WHERE category_id = :id');
$stmt->execute(['id' => $id]);
$movementsCount = (int) $stmt->fetchColumn();

/* Sem movimentos: elimina. Com movimentos: desativa. */
try {
    if ($movementsCount === 0) {
        $stmt = $pdo->prepare('DELETE FROM categories WHERE id_categories = :id AND user_id = :user_id');
        $result = 'deleted';
    } else {
        $stmt = $pdo->prepare(
            'UPDATE categories SET status = \'inactive\' WHERE id_categories = :id AND user_id = :user_id'
        );
        $result = 'deactivated';
    }

    $stmt->execute(['id' => $id, 'user_id' => $userId]);
} catch (PDOException $e) {
    error_log('Erro ao eliminar/desativar categoria: ' . $e->getMessage());
    redirectToCategories('error=unknown');
}

redirectToCategories('success=' . $result);
