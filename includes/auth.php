<?php

/* =================================================================
    Verificação de autenticação - "camada 2"

    Usa a sessão já ligada pelo session.php (camada 1) e decide Quem
    pode ver a página: se não houver login (user_id), redireciona para
    o login. 
    * Incluir este ficheiro no topo de todas as páginas da
    área privada (dashboard, movements, goals, etc.)
    ================================================================= */

require_once __DIR__ . '/session.php';
startSecureSession();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
