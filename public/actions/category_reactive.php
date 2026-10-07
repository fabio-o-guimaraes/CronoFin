<?php
require_once __DIR__ . '/../../includes/session.php';
startSecureSession();

require_once __DIR__ . '/../../config/database.php';

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
$idInput = $_POST['id'] ?? '';

if (!is_string($idInput) || !ctype_digit($idInput)) {
    redirectToCategories('error=notfound');
}

$id = (int) $idInput;

/* Reativa só se for do utilizador e estiver inativa */
try {
    $stmt = $pdo->prepare(
        'UPDATE categories SET status = \'active\'
         WHERE id_categories = :id AND user_id = :user_id AND status = \'inactive\''
    );
    $stmt->execute(['id' => $id, 'user_id' => $userId]);
} catch (PDOException $e) {
    error_log('Erro ao reativar categoria: ' . $e->getMessage());
    redirectToCategories('error=unknown');
}

if ($stmt->rowCount() === 0) {
    redirectToCategories('error=notfound');
}

redirectToCategories('success=reactivated');
