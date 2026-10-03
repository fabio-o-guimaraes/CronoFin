<?php
require_once __DIR__ . '/../../includes/session.php';
startSecureSession();

require_once __DIR__ . '/../../config/database.php';

/* Redireciona sempre para a secção do perfil, com uma query extra (erro ou sucesso) */
function redirectToProfile(string $query): void
{
    header('Location: ../dashboard.php?section=profile&' . $query);
    exit;
}

/* Guarda de entrada */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboard.php?section=profile');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

/* Recolhe os dados do form */
$name                   = trim($_POST['name'] ?? '');
$email                  = trim($_POST['email'] ?? '');
$currentPassword        = $_POST['current_password'] ?? '';
$newPassword            = $_POST['new_password'] ?? '';
$newPasswordConfirm     = $_POST['new_password_confirm'] ?? '';

/* Guarda nome e email para repor no form se houver erro */
$_SESSION['profile_form_data'] = [
    'name'  => $name,
    'email' => $email,
];

/* Validar nome e email */
if ($name === '' || $email === '') {
    redirectToProfile('error=empty');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirectToProfile('error=email');
}

/* Email já usado por outro utilizador */
$stmt = $pdo->prepare('SELECT id_users FROM users WHERE email = :email AND id_users != :id');
$stmt->execute(['email' => $email, 'id' => $userId]);

if ($stmt->fetch()) {
    redirectToProfile('error=exists');
}

/* Ir buscar os dados atuais (password e imagem) */
$stmt = $pdo->prepare('SELECT password, image FROM users WHERE id_users = :id');
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: ../login.php');
    exit;
}

/* Password (só se o utilizador quiser mudar) */
$passwordToSave = $user['password'];

$wantsPasswordChange = $currentPassword !== '' || $newPassword !== '' || $newPasswordConfirm !== '';

if ($wantsPasswordChange) {
    if ($currentPassword === '') {
        redirectToProfile('error=currentrequired');
    }

    if (!password_verify($currentPassword, $user['password'])) {
        redirectToProfile('error=currentwrong');
    }

    if (strlen($newPassword) < 8) {
        redirectToProfile('error=passlength');
    }

    if ($newPassword !== $newPasswordConfirm) {
        redirectToProfile('error=passmatch');
    }

    $passwordToSave = password_hash($newPassword, PASSWORD_DEFAULT);
}

/* Imagem */
$imageToSave  = $user['image']; // por defeito, mantém a imagem atual
$newImageName = null;
$uploadDir    = __DIR__ . '/../assets/uploads/profiles/';

$uploadError = $_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE;

if ($uploadError !== UPLOAD_ERR_NO_FILE) {
    if ($uploadError !== UPLOAD_ERR_OK) {
        redirectToProfile('error=imagesave');
    }

    // Tipo real do ficheiro -> extensão segura
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    $fileType = mime_content_type($_FILES['image']['tmp_name']);

    if (!array_key_exists($fileType, $extensions)) {
        redirectToProfile('error=imagetype');
    }

    if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
        redirectToProfile('error=imagesize');
    }

    $newImageName = 'user_' . bin2hex(random_bytes(8)) . '.' . $extensions[$fileType];

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newImageName)) {
        redirectToProfile('error=imagesave');
    }

    $imageToSave = $newImageName;
}

/* Gravar na base de dados */
try {
    $stmt = $pdo->prepare(
        'UPDATE users SET name = :name, email = :email, password = :password, image = :image WHERE id_users = :id'
    );
    $stmt->execute([
        'name'     => $name,
        'email'    => $email,
        'password' => $passwordToSave,
        'image'    => $imageToSave,
        'id'       => $userId,
    ]);
} catch (PDOException $e) {
    error_log('Erro ao atualizar perfil: ' . $e->getMessage());

    // Se a imagem nova já tinha sido guardada, apaga-a
    if ($newImageName !== null) {
        unlink($uploadDir . $newImageName);
    }

    redirectToProfile('error=unknown');
}

/* Limpeza e sessão */
/* com a BD atualizada é seguro apagar a imagem antiga */
if ($newImageName !== null && $user['image']) {
    $oldPath = $uploadDir . basename($user['image']);
    if (is_file($oldPath)) {
        unlink($oldPath);
    }
}

/* O header lê estes valores da sessão, por isso têm de ficar atualizados */
$_SESSION['user_name']  = $name;
$_SESSION['user_email'] = $email;

unset($_SESSION['profile_form_data']);

redirectToProfile('success=updated');
