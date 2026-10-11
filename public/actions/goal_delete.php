<?php
require_once __DIR__ . '/../../includes/session.php';
startSecureSession();

require_once __DIR__ . '/../../config/database.php';

/* Redireciona sempre para a secção dos objetivos, com uma query extra (erro ou sucesso) */
function redirectToGoals(string $query): never
{
    header('Location: ../dashboard.php?section=goals&' . $query);
    exit;
}

/* Guarda de entrada */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboard.php?section=goals');
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
    redirectToGoals('error=notfound');
}

$id = (int) $idInput;

/* Marca como eliminado, só se for do utilizador e estiver ativo */
try {
    $stmt = $pdo->prepare(
        'UPDATE goals SET status = \'deleted\'
         WHERE id_goals = :id AND user_id = :user_id AND status = \'active\''
    );
    $stmt->execute(['id' => $id, 'user_id' => $userId]);
} catch (PDOException $e) {
    error_log('Erro ao eliminar objetivo: ' . $e->getMessage());
    redirectToGoals('error=unknown');
}

if ($stmt->rowCount() === 0) {
    redirectToGoals('error=notfound');
}

redirectToGoals('success=deleted');
