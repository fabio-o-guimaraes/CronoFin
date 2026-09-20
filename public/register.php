<?php
require_once '../includes/session.php';
startSecureSession();

// Se já estiver autenticado redireciona para o index
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>CronoFin - Criar Conta</title>

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/pages/auth.css">
</head>

<body>
    <?php require_once '../includes/header.php'; ?>

    <main class="auth-main">
        <section class="auth-form-section">
            <h1>Criar Conta</h1>

            <form action="actions/register_process.php" method="POST" class="auth-form" enctype="multipart/form-data">
                <!-- enctype="multipart/form-data" obrigatório porque há um input type="file". Sem uploads de ficheiros, este atributo não seria necessário. -->

                <div class="form-group">
                    <label for="name">Nome</label>
                    <input type="text" id="name" name="name" required autocomplete="name">
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required autocomplete="email">
                </div>

                <div class="form-group">
                    <label for="image">Fotografia de perfil (opcional)</label>
                    <input type="file" id="image" name="image" accept="image/jpeg, image/png, image/webp">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
                </div>

                <div class="form-group">
                    <label for="password_confirm">Confirmar Password</label>
                    <input type="password" id="password_confirm" name="password_confirm" required minlength="8" autocomplete="new-password">
                </div>

                <div class="form-group form-group-checkbox">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">Li e aceito os <a href="terms.php" class="link-on-light">Termos e Condições</a></label>
                </div>

                <button type="submit" class="btn btn-primary">Criar Conta</button>

            </form>

            <p class="auth-alt-link">Já tens conta? <a href="login.php" class="link-on-light">Iniciar Sessão</a></p>
        </section>
    </main>

    <?php require_once '../includes/footer.php'; ?>

    <script src="assets/js/main.js"></script>
</body>

</html>