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

/* Validar tipo */
if (!in_array($type, ['income', 'expense'], true)) {
    redirectToMovements('error=type');
}

/* Validar valor: aceita "12,50" ou "12.50", sem separador de milhares */
$normalizedValue = str_replace(',', '.', str_replace([' ', "\u{00A0}"], '', $valueInput));

if (!preg_match('/^\d+(\.\d{1,2})?$/', $normalizedValue)) {
    redirectToMovements('error=value');
}

$value = (float) $normalizedValue;

if ($value <= 0 || $value > 99999999.99) {
    redirectToMovements('error=value');
}

/* Validar data */
$parsedDate = DateTime::createFromFormat('Y-m-d', $date);

if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date) {
    redirectToMovements('error=date');
}

if ($date > date('Y-m-d')) {
    redirectToMovements('error=future');
}

/* Validar categoria: existe, é visível para o utilizador e está ativa */
if (!ctype_digit($categoryInput)) {
    redirectToMovements('error=category');
}

$categoryId = (int) $categoryInput;

$stmt = $pdo->prepare(
    'SELECT id_categories FROM categories
     WHERE id_categories = :id AND status = \'active\' AND (user_id IS NULL OR user_id = :user_id)'
);
$stmt->execute(['id' => $categoryId, 'user_id' => $userId]);

if (!$stmt->fetch()) {
    redirectToMovements('error=category');
}

/* Validar descrição (opcional) */
if (mb_strlen($description) > 100) {
    redirectToMovements('error=length');
}

/* Gravar na base de dados */
try {
    $stmt = $pdo->prepare(
        'INSERT INTO movements (date, value, description, type, user_id, category_id)
         VALUES (:date, :value, :description, :type, :user_id, :category_id)'
    );
    $stmt->execute([
        'date'        => $date,
        'value'       => number_format($value, 2, '.', ''),
        'description' => $description === '' ? null : $description,
        'type'        => $type,
        'user_id'     => $userId,
        'category_id' => $categoryId,
    ]);
} catch (PDOException $e) {
    error_log('Erro ao criar movimento: ' . $e->getMessage());
    redirectToMovements('error=unknown');
}

unset($_SESSION['movement_form_data']);

redirectToMovements('success=created');
