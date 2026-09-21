<?php
require_once __DIR__ . '/../../includes/session.php';
startSecureSession();

require_once __DIR__ . '/../../config/database.php';

// Só processa se o formulário for submetido via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

// Recolhe os dados recebidos do formulário
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validação campos vazios
if ($email === '' || $password === '') {
    header('Location: ../login.php?error=empty');
    exit;
}

// Procurar o utilizador pelo email
$stmt = $pdo->prepare('SELECT id_users, name, email, password FROM users WHERE email = :email');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

// Verificar se o utilizador existe E se a password coincide com o hash guardado
if (!$user || !password_verify($password, $user['password'])) {
    header('Location: ../login.php?error=invalid');
    exit;
}

// Credenciais corretas - inicia a sessão do utilizador
$_SESSION['user_id'] = $user['id_users'];
$_SESSION['user_name'] = $user['name'];

header('Location: ../dashboard.php');
exit;
