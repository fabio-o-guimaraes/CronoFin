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

/* Recolhe os dados do form */
$name        = is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '';
$targetInput = is_string($_POST['target'] ?? null) ? trim($_POST['target']) : '';

/* Guarda os dados para repor no form se houver erro */
$_SESSION['goal_form_data'] = [
    'name'   => $name,
    'target' => $targetInput,
];

/* Validar nome */
if ($name === '') {
    redirectToGoals('error=name');
}

if (mb_strlen($name) > 100) {
    redirectToGoals('error=namelength');
}

/* Validar meta */
$target = parseAmount($targetInput);

if ($target === null || $target <= 0) {
    redirectToGoals('error=target');
}

if ($target > MAX_AMOUNT) {
    redirectToGoals('error=toolarge');
}

/* Gravar na base de dados */
try {
    $stmt = $pdo->prepare(
        'INSERT INTO goals (name, target, status, user_id) VALUES (:name, :target, \'active\', :user_id)'
    );
    $stmt->execute([
        'name'    => $name,
        'target'  => number_format($target, 2, '.', ''),
        'user_id' => $userId,
    ]);
} catch (PDOException $e) {
    error_log('Erro ao criar objetivo: ' . $e->getMessage());
    redirectToGoals('error=unknown');
}

unset($_SESSION['goal_form_data']);

redirectToGoals('success=created');
