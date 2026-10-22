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

/* O objetivo tem de estar adquirido, ser do utilizador e ter a despesa associada */
$stmt = $pdo->prepare(
    'SELECT m.id_movements, m.category_id
     FROM goals g
     JOIN movements m ON m.goal_id = g.id_goals
     WHERE g.id_goals = :id AND g.user_id = :user_id AND g.status = \'acquired\''
);
$stmt->execute(['id' => $id, 'user_id' => $userId]);
$current = $stmt->fetch();

if (!$current) {
    redirectToGoals('error=notfound');
}

/* Recolhe os dados do form */
$name          = is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '';
$spentInput    = is_string($_POST['spent'] ?? null) ? trim($_POST['spent']) : '';
$date          = is_string($_POST['date'] ?? null) ? $_POST['date'] : '';
$categoryInput = is_string($_POST['category'] ?? null) ? $_POST['category'] : '';
$description   = is_string($_POST['description'] ?? null) ? trim($_POST['description']) : '';

/* Guarda os dados para repor no form se houver erro */
$_SESSION['goal_acquired_form_data'] = [
    'name'        => $name,
    'spent'       => $spentInput,
    'date'        => $date,
    'category'    => $categoryInput,
    'description' => $description,
];

/* Em caso de erro, volta ao formulário de edição deste objetivo */
$editQuery = 'editacquired=' . $id . '&';

/* Validar nome */
if ($name === '') {
    redirectToGoals($editQuery . 'error=name');
}

if (mb_strlen($name) > 100) {
    redirectToGoals($editQuery . 'error=namelength');
}

/* Validar valor gasto */
$spent = parseAmount($spentInput);

if ($spent === null || $spent <= 0) {
    redirectToGoals($editQuery . 'error=amount');
}

if ($spent > MAX_AMOUNT) {
    redirectToGoals($editQuery . 'error=toolarge');
}

/* Validar data */
$parsedDate = DateTime::createFromFormat('Y-m-d', $date);

if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date) {
    redirectToGoals($editQuery . 'error=date');
}

if ($date > date('Y-m-d')) {
    redirectToGoals($editQuery . 'error=future');
}

/* Validar descrição: se ficar vazia, assume o nome do objetivo */
if (mb_strlen($description) > 100) {
    redirectToGoals($editQuery . 'error=desclength');
}

if ($description === '') {
    $description = $name;
}

/* Validar categoria: é do utilizador e está ativa (ou é a que a despesa já tinha) */
if (!ctype_digit($categoryInput)) {
    redirectToGoals($editQuery . 'error=category');
}

$categoryId = (int) $categoryInput;

$stmt = $pdo->prepare(
    'SELECT status FROM categories WHERE id_categories = :id AND user_id = :user_id'
);
$stmt->execute(['id' => $categoryId, 'user_id' => $userId]);
$category = $stmt->fetch();

if (!$category || ($category['status'] !== 'active' && $categoryId !== (int) $current['category_id'])) {
    redirectToGoals($editQuery . 'error=category');
}

/* Tudo ou nada: o nome do objetivo e a despesa */
try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'UPDATE goals SET name = :name WHERE id_goals = :id AND user_id = :user_id AND status = \'acquired\''
    );
    $stmt->execute(['name' => $name, 'id' => $id, 'user_id' => $userId]);

    $stmt = $pdo->prepare(
        'UPDATE movements
         SET date = :date, value = :value, description = :description, category_id = :category_id
         WHERE id_movements = :movement_id AND goal_id = :goal_id AND user_id = :user_id'
    );
    $stmt->execute([
        'date'        => $date,
        'value'       => number_format($spent, 2, '.', ''),
        'description' => $description,
        'category_id' => $categoryId,
        'movement_id' => $current['id_movements'],
        'goal_id'     => $id,
        'user_id'     => $userId,
    ]);

    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Erro ao editar objetivo adquirido: ' . $e->getMessage());
    redirectToGoals($editQuery . 'error=unknown');
}

unset($_SESSION['goal_acquired_form_data']);

redirectToGoals('success=acquiredupdated');
