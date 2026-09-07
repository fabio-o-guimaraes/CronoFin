<?php

/**
 * Logout
 * Destrói a sessão ativa e envia o utilizador para a página inicial
 */

require_once __DIR__ . '/../includes/session.php';
startSecureSession();

// Limpa todas as variáveis de sessão
$_SESSION = [];

// Destrói o cookie de sessão no browser
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destrói a sessão no servidor
session_destroy();

header('Location: index.php');
exit;
