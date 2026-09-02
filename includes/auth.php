<?php

/**
 * Verificação de autenticação - CronoFin
 * Incluir este ficheiro no topo de todas as páginas da área privada
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
