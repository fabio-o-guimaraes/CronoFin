<?php
require_once __DIR__ . '/../../includes/session.php';
startSecureSession();

require_once __DIR__ . '/../../config/database.php';

/* Redireciona sempre para a secção dos movimentos, com uma query extra (erro ou sucesso) */
function redirectToMovements(string $query): never
{
    header('Location: ../dashboard.php?section=movements&' . $query);
    exit;
}

/* Guarda de entrada */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboard.php?section=movements');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

/* Recolhe o id */
$idInput = is_string($_POST['id'] ?? null) ? $_POST['id'] : '';

if (!ctype_digit($idInput)) {
    redirectToMovements('error=notfound');
}

$id = (int) $idInput;

/* Elimina só se for do utilizador */
try {
    $stmt = $pdo->prepare('DELETE FROM movements WHERE id_movements = :id AND user_id = :user_id');
    $stmt->execute(['id' => $id, 'user_id' => $userId]);
} catch (PDOException $e) {
    error_log('Erro ao eliminar movimento: ' . $e->getMessage());
    redirectToMovements('error=unknown');
}

if ($stmt->rowCount() === 0) {
    redirectToMovements('error=notfound');
}

redirectToMovements('success=deleted');
