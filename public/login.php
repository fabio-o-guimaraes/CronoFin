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
    <title>CronoFin - Iniciar Sessão</title>

    <!-- Fontes -->
    <?php require __DIR__ . '/../includes/fonts.php'; ?>

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/pages/auth.css">
</head>

<body>
    <?php require_once '../includes/header.php';

    // Recupera os dados do formulário guardados antes de um eventual erro, e remove-os logo de seguida
    $formData = $_SESSION['form_data'] ?? [];
    unset($_SESSION['form_data']);
    ?>

    <main class="auth-main">
        <section class="auth-form-section">
            <h1>Iniciar Sessão</h1>

            <?php if (isset($_GET['success']) && $_GET['success'] === 'registered'): ?>
                <div class="auth-alert auth-alert-success">
                    Conta criada com sucesso! Inicia sessão para continuares.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="auth-alert auth-alert-error">
                    <?php
                    switch ($_GET['error']) {
                        case 'empty':
                            echo 'Por favor preenche todos os campos.';
                            break;
                        case 'invalid':
                            echo 'Email ou password incorretos.';
                            break;
                        default:
                            echo 'Ocorreu um erro. Tenta novamente.';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <form action="actions/login_process.php" method="POST" class="auth-form">

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required autocomplete="email" value="<?= htmlspecialchars($formData['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>

                <button type="submit" class="btn btn-primary">Iniciar Sessão</button>

            </form>

            <p class="auth-alt-link">Ainda não tens conta? <a href="register.php" class="link-on-light">Criar Conta</a></p>
        </section>
    </main>

    <?php require_once '../includes/footer.php'; ?>

    <script src="assets/js/main.js"></script>
</body>

</html>