<?php
require_once __DIR__ . '/../../includes/session.php';
startSecureSession();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/finance.php';

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

/* O objetivo tem de existir, ser do utilizador e estar adquirido */
$stmt = $pdo->prepare(
    'SELECT id_goals FROM goals WHERE id_goals = :id AND user_id = :user_id AND status = \'acquired\''
);
$stmt->execute(['id' => $id, 'user_id' => $userId]);

if (!$stmt->fetch()) {
    redirectToGoals('error=notfound');
}

/* A despesa criada ao adquirir (pode não existir em dados antigos) */
$stmt = $pdo->prepare(
    'SELECT id_movements, value FROM movements WHERE goal_id = :id AND user_id = :user_id LIMIT 1'
);
$stmt->execute(['id' => $id, 'user_id' => $userId]);
$movement = $stmt->fetch();

$spent = $movement ? round((float) $movement['value'], 2) : 0.0;

/* Valor que voltaria a ficar reservado */
$stmt = $pdo->prepare('SELECT COALESCE(SUM(value), 0) FROM contributions WHERE goal_id = :id');
$stmt->execute(['id' => $id]);
$reserved = round((float) $stmt->fetchColumn(), 2);

/* Ao voltar a ativo, a despesa desaparece e as contribuições voltam a contar como reservadas */
$availableAfter = round(getAvailableBalance($pdo, $userId) + $spent - $reserved, 2);

if ($availableAfter < 0) {
    redirectToGoals('error=noreserve');
}

/* Tudo ou nada: objetivo volta a ativo e a despesa é apagada */
try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'UPDATE goals SET status = \'active\'
         WHERE id_goals = :id AND user_id = :user_id AND status = \'acquired\''
    );
    $stmt->execute(['id' => $id, 'user_id' => $userId]);

    if ($stmt->rowCount() === 0) {
        $pdo->rollBack();
        redirectToGoals('error=notfound');
    }

    if ($movement) {
        $stmt = $pdo->prepare(
            'DELETE FROM movements WHERE id_movements = :movement_id AND goal_id = :goal_id AND user_id = :user_id'
        );
        $stmt->execute([
            'movement_id' => $movement['id_movements'],
            'goal_id'     => $id,
            'user_id'     => $userId,
        ]);
    }

    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Erro ao voltar a ativar objetivo: ' . $e->getMessage());
    redirectToGoals('error=unknown');
}

redirectToGoals('success=reopened');
