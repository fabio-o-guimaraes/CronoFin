<?php
require_once __DIR__ . '/../../config/database.php';

/* Vai buscar os dados atuais do utilizador autenticado */
$stmt = $pdo->prepare('SELECT name, email, image FROM users WHERE id_users = :id');
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    echo '<p>Utilizador não encontrado.</p>';
    return;
}

/* Se houve erro no envio anterior, repõe o que o utilizador tinha escrito */
$formData = $_SESSION['profile_form_data'] ?? [];
unset($_SESSION['profile_form_data']);

$nameValue  = $formData['name']  ?? $user['name'];
$emailValue = $formData['email'] ?? $user['email'];

/* Mensagens de erro e de sucesso */
$errorMessages = [
    'empty'           => 'O nome e o email são obrigatórios.',
    'email'           => 'Email inválido.',
    'exists'          => 'Já existe uma conta registada com este email.',
    'currentrequired' => 'Para alterar a password, indica a password atual.',
    'currentwrong'    => 'A password atual está incorreta.',
    'passlength'      => 'A nova password deve ter pelo menos 8 caracteres.',
    'passmatch'       => 'As novas passwords não coincidem.',
    'imagetype'       => 'A fotografia deve ser um ficheiro JPG, PNG ou WEBP.',
    'imagesize'       => 'A fotografia não pode exceder 2 MB.',
    'imagesave'       => 'Não foi possível guardar a fotografia. Tenta novamente.',
];

$errorMessage = null;
if (isset($_GET['error'])) {
    $code = is_string($_GET['error']) ? $_GET['error'] : '';
    $errorMessage = $errorMessages[$code] ?? 'Ocorreu um erro. Tenta novamente.';
}

$success = isset($_GET['success']) && $_GET['success'] === 'updated';

?>

<section class="profile-section">
    <h1>Perfil</h1>

    <?php if ($errorMessage): ?>
        <div class="alert alert-error"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">Perfil atualizado com sucesso.</div>
    <?php endif; ?>

    <form action="actions/profile_update.php" method="POST" enctype="multipart/form-data" class="profile-form">

        <div class="form-group">
            <label>Fotografia atual</label>
            <?php if ($user['image']): ?>
                <img src="assets/uploads/profiles/<?= htmlspecialchars($user['image']) ?>" alt="Fotografia de perfil" class="profile-image">
            <?php else: ?>
                <p>Ainda não tens fotografia.</p>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="image">Nova fotografia</label>
            <input type="file" id="image" name="image" accept="image/jpeg, image/png, image/webp">
        </div>

        <div class="form-group">
            <label for="name">Nome</label>
            <input type="text" id="name" name="name" required autocomplete="name" value="<?= htmlspecialchars($nameValue) ?>">
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required autocomplete="email" value="<?= htmlspecialchars($emailValue) ?>">
        </div>

        <h2>Alterar password</h2>

        <div class="form-group">
            <label for="current_password">Password atual</label>
            <input type="password" id="current_password" name="current_password" autocomplete="current-password">
        </div>

        <div class="form-group">
            <label for="new_password">Nova password</label>
            <input type="password" id="new_password" name="new_password" minlength="8" autocomplete="new-password">
        </div>

        <div class="form-group">
            <label for="new_password_confirm">Confirmar nova password</label>
            <input type="password" id="new_password_confirm" name="new_password_confirm" minlength="8" autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primary">Guardar alterações</button>
    </form>
</section>