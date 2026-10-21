<?php
require_once __DIR__ . '/../../includes/session.php';
startSecureSession();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/format.php';
require_once __DIR__ . '/../../includes/category_icons.php';

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

/* O objetivo tem de existir, ser do utilizador e estar ativo */
$stmt = $pdo->prepare(
    'SELECT name FROM goals WHERE id_goals = :id AND user_id = :user_id AND status = \'active\''
);
$stmt->execute(['id' => $id, 'user_id' => $userId]);
$goal = $stmt->fetch();

if (!$goal) {
    redirectToGoals('error=notfound');
}

/* Recolhe os dados do form */
$spentInput    = is_string($_POST['spent'] ?? null) ? trim($_POST['spent']) : '';
$date          = is_string($_POST['date'] ?? null) ? $_POST['date'] : '';
$categoryInput = is_string($_POST['category'] ?? null) ? $_POST['category'] : '';
$newCategory   = is_string($_POST['new_category'] ?? null) ? trim($_POST['new_category']) : '';
$description   = is_string($_POST['description'] ?? null) ? trim($_POST['description']) : '';

/* Guarda os dados para repor no form se houver erro */
$_SESSION['goal_acquire_form_data'] = [
    'spent'        => $spentInput,
    'date'         => $date,
    'category'     => $categoryInput,
    'new_category' => $newCategory,
    'description'  => $description,
];

/* Em caso de erro, volta ao formulário de aquisição deste objetivo */
$acquireQuery = 'acquire=' . $id . '&';

/* Validar valor gasto */
$spent = parseAmount($spentInput);

if ($spent === null || $spent <= 0) {
    redirectToGoals($acquireQuery . 'error=amount');
}

if ($spent > MAX_AMOUNT) {
    redirectToGoals($acquireQuery . 'error=toolarge');
}

/* Validar data */
$parsedDate = DateTime::createFromFormat('Y-m-d', $date);

if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date) {
    redirectToGoals($acquireQuery . 'error=date');
}

if ($date > date('Y-m-d')) {
    redirectToGoals($acquireQuery . 'error=future');
}

/* Validar descrição: se ficar vazia, assume o nome do objetivo */
if (mb_strlen($description) > 100) {
    redirectToGoals($acquireQuery . 'error=desclength');
}

if ($description === '') {
    $description = $goal['name'];
}

/* Validar categoria: uma nova (que ainda não existe) ou uma existente e ativa */
$categoryId = 0;

if ($newCategory !== '') {
    if (mb_strlen($newCategory) > 100) {
        redirectToGoals($acquireQuery . 'error=catlength');
    }

    $stmt = $pdo->prepare(
        'SELECT id_categories FROM categories
         WHERE name = :name AND user_id = :user_id'
    );
    $stmt->execute(['name' => $newCategory, 'user_id' => $userId]);

    if ($stmt->fetch()) {
        redirectToGoals($acquireQuery . 'error=catexists');
    }
} else {
    if (!ctype_digit($categoryInput)) {
        redirectToGoals($acquireQuery . 'error=category');
    }

    $categoryId = (int) $categoryInput;

    $stmt = $pdo->prepare(
        'SELECT id_categories FROM categories
         WHERE id_categories = :id AND status = \'active\' AND user_id = :user_id'
    );
    $stmt->execute(['id' => $categoryId, 'user_id' => $userId]);

    if (!$stmt->fetch()) {
        redirectToGoals($acquireQuery . 'error=category');
    }
}

/* Gravar tudo ou nada: objetivo -> (categoria nova) -> movimento */
try {
    $pdo->beginTransaction();

    /* 1. Passa o objetivo a adquirido (só se ainda estiver ativo) */
    $stmt = $pdo->prepare(
        'UPDATE goals SET status = \'acquired\'
         WHERE id_goals = :id AND user_id = :user_id AND status = \'active\''
    );
    $stmt->execute(['id' => $id, 'user_id' => $userId]);

    if ($stmt->rowCount() === 0) {
        $pdo->rollBack();
        redirectToGoals('error=notfound');
    }

    /* 2. Cria a categoria nova, se foi pedida */
    if ($newCategory !== '') {
        $stmt = $pdo->prepare(
            'INSERT INTO categories (name, icon, status, user_id)
             VALUES (:name, :icon, \'active\', :user_id)'
        );
        $stmt->execute([
            'name'    => $newCategory,
            'icon'    => DEFAULT_CATEGORY_ICON,
            'user_id' => $userId,
        ]);

        $categoryId = (int) $pdo->lastInsertId();
    }

    /* 3. Regista a despesa, ligada ao objetivo */
    $stmt = $pdo->prepare(
        'INSERT INTO movements (date, value, description, type, user_id, category_id, goal_id)
         VALUES (:date, :value, :description, \'expense\', :user_id, :category_id, :goal_id)'
    );
    $stmt->execute([
        'date'        => $date,
        'value'       => number_format($spent, 2, '.', ''),
        'description' => $description,
        'user_id'     => $userId,
        'category_id' => $categoryId,
        'goal_id'     => $id,
    ]);

    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Erro ao adquirir objetivo: ' . $e->getMessage());
    redirectToGoals($acquireQuery . 'error=unknown');
}

unset($_SESSION['goal_acquire_form_data']);

redirectToGoals('success=acquired');
