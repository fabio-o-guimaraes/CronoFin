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

/* O movimento tem de existir e ser do utilizador */
$stmt = $pdo->prepare(
    'SELECT category_id FROM movements WHERE id_movements = :id AND user_id = :user_id'
);
$stmt->execute(['id' => $id, 'user_id' => $userId]);
$current = $stmt->fetch();

if (!$current) {
    redirectToMovements('error=notfound');
}

/* Recolhe os dados do form */
$type          = is_string($_POST['type'] ?? null) ? $_POST['type'] : '';
$valueInput    = is_string($_POST['value'] ?? null) ? trim($_POST['value']) : '';
$date          = is_string($_POST['date'] ?? null) ? $_POST['date'] : '';
$categoryInput = is_string($_POST['category'] ?? null) ? $_POST['category'] : '';
$description   = is_string($_POST['description'] ?? null) ? trim($_POST['description']) : '';

/* Guarda os dados para repor no form se houver erro */
$_SESSION['movement_form_data'] = [
    'type'        => $type,
    'value'       => $valueInput,
    'date'        => $date,
    'category'    => $categoryInput,
    'description' => $description,
];

/* Em caso de erro, volta ao modo de edição deste movimento */
$editQuery = 'edit=' . $id . '&';

/* Validar tipo */
if (!in_array($type, ['income', 'expense'], true)) {
    redirectToMovements($editQuery . 'error=type');
}

/* Validar valor: aceita "12,50" ou "12.50", sem separador de milhares */
$normalizedValue = str_replace(',', '.', str_replace([' ', "\u{00A0}"], '', $valueInput));

if (!preg_match('/^\d+(\.\d{1,2})?$/', $normalizedValue)) {
    redirectToMovements($editQuery . 'error=value');
}

$value = (float) $normalizedValue;

if ($value <= 0) {
    redirectToMovements($editQuery . 'error=value');
}

if ($value > 99999999.99) {
    redirectToMovements($editQuery . 'error=toolarge');
}

/* Validar data */
$parsedDate = DateTime::createFromFormat('Y-m-d', $date);

if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date) {
    redirectToMovements($editQuery . 'error=date');
}

if ($date > date('Y-m-d')) {
    redirectToMovements($editQuery . 'error=future');
}

/* Validar categoria: visível para o utilizador e ativa (ou a que o movimento já tinha) */
if (!ctype_digit($categoryInput)) {
    redirectToMovements($editQuery . 'error=category');
}

$categoryId = (int) $categoryInput;

$stmt = $pdo->prepare(
    'SELECT status FROM categories
     WHERE id_categories = :id AND (user_id IS NULL OR user_id = :user_id)'
);
$stmt->execute(['id' => $categoryId, 'user_id' => $userId]);
$category = $stmt->fetch();

if (!$category || ($category['status'] !== 'active' && $categoryId !== (int) $current['category_id'])) {
    redirectToMovements($editQuery . 'error=category');
}

/* Validar descrição (opcional) */
if (mb_strlen($description) > 100) {
    redirectToMovements($editQuery . 'error=length');
}

/* Gravar na base de dados */
try {
    $stmt = $pdo->prepare(
        'UPDATE movements
         SET date = :date, value = :value, description = :description, type = :type, category_id = :category_id
         WHERE id_movements = :id AND user_id = :user_id'
    );
    $stmt->execute([
        'date'        => $date,
        'value'       => number_format($value, 2, '.', ''),
        'description' => $description === '' ? null : $description,
        'type'        => $type,
        'category_id' => $categoryId,
        'id'          => $id,
        'user_id'     => $userId,
    ]);
} catch (PDOException $e) {
    error_log('Erro ao atualizar movimento: ' . $e->getMessage());
    redirectToMovements($editQuery . 'error=unknown');
}

unset($_SESSION['movement_form_data']);

redirectToMovements('success=updated');
