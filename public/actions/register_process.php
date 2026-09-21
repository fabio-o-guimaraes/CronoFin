<?php
require_once __DIR__ . '/../../includes/session.php';
startSecureSession();

require_once __DIR__ . '/../../config/database.php';

// Só processa se o formulário for submetido via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../register.php');
    exit;
}

// Recolhe e limpa os dados recebidos do formulário
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$passwordConfirm = $_POST['password_confirm'] ?? '';
$termsAccepted = isset($_POST['terms']);

// Validação campos vazios
if ($name === '' || $email === '' || $password === '' || $passwordConfirm === '') {
    header('Location: ../register.php?error=empty');
    exit;
}

if (!$termsAccepted) {
    header('Location: ../register.php?error=terms');
    exit;
}

// Formato de email válido
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../register.php?error=email');
    exit;
}

// Tamanho mínimo de password
if (strlen($password) < 8) {
    header('Location: ../register.php?error=passlength');
    exit;
}

// Validação das passwords
if ($password !== $passwordConfirm) {
    header('Location: ../register.php?error=passmatch');
    exit;
}

// Verificar se email já existe
$stmt = $pdo->prepare('SELECT id_users FROM users WHERE email = :email');
$stmt->execute(['email' => $email]);

if ($stmt->fetch()) {
    header('Location: ../register.php?error=exists');
    exit;
}

// Hash da password
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// Tratamento da foto de perfil (campo opcional)
$imageFileName = null; // fica null se não for enviada nenhuma foto

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {

    // Validação do tipo de ficheiro
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $fileType = mime_content_type($_FILES['image']['tmp_name']);

    if (!in_array($fileType, $allowedTypes)) {
        header('Location: ../register.php?error=imagetype');
        exit;
    }

    // Limite de tamanho: 2 MB
    if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
        header('Location: ../register.php?error=imagesize');
        exit;
    }

    // Gera um nome de ficheiro único, para nunca haver conflitos entre utilizadores
    $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $imageFileName = uniqid('user_', true) . '.' . $extension;

    $destination = __DIR__ . '/../assets/uploads/profiles/' . $imageFileName;
    move_uploaded_file($_FILES['image']['tmp_name'], $destination);
}

// Inserir o novo utilizador na base de dados
$stmt = $pdo->prepare('INSERT INTO users (name, email, password, image) VALUES (:name, :email, :password, :image)');
$stmt->execute([
    'name' => $name,
    'email' => $email,
    'password' => $passwordHash,
    'image' => $imageFileName,
]);

// Registo concluído - encaminha para o login
header('Location: ../login.php?success=registered');
exit;
