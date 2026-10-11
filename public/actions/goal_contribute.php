<?php
require_once __DIR__ . '/../../includes/session.php';
startSecureSession();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/format.php';
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

/* Recolhe os dados do form */
$idInput     = is_string($_POST['id'] ?? null) ? $_POST['id'] : '';
$action      = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
$amountInput = is_string($_POST['amount'] ?? null) ? $_POST['amount'] : '';

if (!ctype_digit($idInput)) {
    redirectToGoals('error=notfound');
}

$id = (int) $idInput;

if (!in_array($action, ['add', 'withdraw'], true)) {
    redirectToGoals('error=unknown');
}

/* Validar valor */
$amount = parseAmount($amountInput);

if ($amount === null || $amount <= 0) {
    redirectToGoals('error=amount');
}

if ($amount > MAX_AMOUNT) {
    redirectToGoals('error=toolarge');
}

/* O objetivo tem de existir, ser do utilizador e estar ativo; traz também a meta e o valor reservado */
$stmt = $pdo->prepare(
    'SELECT g.target, COALESCE(SUM(c.value), 0) AS reserved
     FROM goals g
     LEFT JOIN contributions c ON c.goal_id = g.id_goals
     WHERE g.id_goals = :id AND g.user_id = :user_id AND g.status = \'active\'
     GROUP BY g.id_goals, g.target'
);
$stmt->execute(['id' => $id, 'user_id' => $userId]);
$goal = $stmt->fetch();

if (!$goal) {
    redirectToGoals('error=notfound');
}

$target   = round((float) $goal['target'], 2);
$reserved = round((float) $goal['reserved'], 2);

/* Regras: adicionar (respeita a meta e o saldo) ou retirar (respeita o reservado) */
if ($action === 'add') {
    if (round($reserved + $amount, 2) > $target) {
        redirectToGoals('error=exceedtarget');
    }

    if ($amount > getAvailableBalance($pdo, $userId)) {
        redirectToGoals('error=nobalance');
    }

    $contribution = $amount;
    $result       = 'contributed';
} else {
    if ($amount > $reserved) {
        redirectToGoals('error=exceedreserved');
    }

    $contribution = -$amount;
    $result       = 'withdrawn';
}

/* Gravar a contribuição (as retiradas são registos negativos) */
try {
    $stmt = $pdo->prepare(
        'INSERT INTO contributions (value, date, goal_id) VALUES (:value, :date, :goal_id)'
    );
    $stmt->execute([
        'value'   => number_format($contribution, 2, '.', ''),
        'date'    => date('Y-m-d'),
        'goal_id' => $id,
    ]);
} catch (PDOException $e) {
    error_log('Erro ao registar contribuição: ' . $e->getMessage());
    redirectToGoals('error=unknown');
}

redirectToGoals('success=' . $result);
