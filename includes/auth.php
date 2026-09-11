<?php

/* =================================================================
    Verificação de autenticação
    Incluir este ficheiro no topo de todas as páginas da área privada
    ================================================================= */

require_once __DIR__ . '/session.php';
startSecureSession();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
