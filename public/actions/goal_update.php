<?php
require_once __DIR__ . '/../../includes/session.php';
startSecureSession();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/format.php';

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

/* O objetivo tem de existir, ser do utilizador e estar ativo; traz o valor reservado */
$stmt = $pdo->prepare(
    'SELECT COALESCE(SUM(c.value), 0) AS reserved
     FROM goals g
     LEFT JOIN contributions c ON c.goal_id = g.id_goals
     WHERE g.id_goals = :id AND g.user_id = :user_id AND g.status = \'active\'
     GROUP BY g.id_goals'
);
$stmt->execute(['id' => $id, 'user_id' => $userId]);
$goal = $stmt->fetch();

if (!$goal) {
    redirectToGoals('error=notfound');
}

$reserved = round((float) $goal['reserved'], 2);

/* Recolhe os dados do form */
$name        = is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '';
$targetInput = is_string($_POST['target'] ?? null) ? trim($_POST['target']) : '';

/* Guarda os dados para repor no form se houver erro */
$_SESSION['goal_form_data'] = [
    'name'   => $name,
    'target' => $targetInput,
];

/* Em caso de erro, volta ao modo de edição deste objetivo */
$editQuery = 'edit=' . $id . '&';

/* Validar nome */
if ($name === '') {
    redirectToGoals($editQuery . 'error=name');
}

if (mb_strlen($name) > 100) {
    redirectToGoals($editQuery . 'error=namelength');
}

/* Validar meta */
$target = parseAmount($targetInput);

if ($target === null || $target <= 0) {
    redirectToGoals($editQuery . 'error=target');
}

if ($target > MAX_AMOUNT) {
    redirectToGoals($editQuery . 'error=toolarge');
}

/* A meta nunca pode ficar abaixo do que já está reservado */
if ($target < $reserved) {
    redirectToGoals($editQuery . 'error=belowreserved');
}

/* Gravar na base de dados */
try {
    $stmt = $pdo->prepare(
        'UPDATE goals SET name = :name, target = :target
         WHERE id_goals = :id AND user_id = :user_id AND status = \'active\''
    );
    $stmt->execute([
        'name'    => $name,
        'target'  => number_format($target, 2, '.', ''),
        'id'      => $id,
        'user_id' => $userId,
    ]);
} catch (PDOException $e) {
    error_log('Erro ao atualizar objetivo: ' . $e->getMessage());
    redirectToGoals($editQuery . 'error=unknown');
}

unset($_SESSION['goal_form_data']);

redirectToGoals('success=updated');
