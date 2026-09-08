<?php

/**
 * Sessão segura
 * Centraliza o início de sessão com configurações de segurança.
 * Incluir este ficheiro em qualquer lugar que precise de $_SESSION
 * (auth.php, header.php, login.php, logout.php, etc.)
 */

function startSecureSession()
{
    if (session_status() === PHP_SESSION_NONE) {
        // Impede acesso ao cookie via JavaScript (protege contra XSS)
        ini_set('session.cookie_httponly', 1);

        // Impede que o PHP aceite IDs de sessão não gerados por ele (protege contra session fixation)
        ini_set('session.use_strict_mode', 1);

        // Impede que o cookie seja enviado em pedidos originados noutro site (protege contra CSRF)
        ini_set('session.cookie_samesite', 'Strict');

        // NOTA: session.cookie_secure fica desativado propositadamente -
        // exige HTTPS, que não existe no XAMPP local. Ativar quando o site for publicado:

        // ini_set('session.cookie_secure', 1);

        session_start();
    }
}
