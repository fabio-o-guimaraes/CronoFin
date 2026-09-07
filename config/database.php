<?php

/**
 * Conexão à base de dados
 * Usa PDO com prepared statements para evitar SQL Injection
 */

$host = 'localhost';
$dbname = 'cronofin';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // lança exceções em erros
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // devolve arrays associativos
    PDO::ATTR_EMULATE_PREPARES   => false,                   // usa prepared statements reais
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    error_log("Erro BD: " . $e->getMessage()); // detalhe fica só no log do servidor
    die("Erro ao ligar à base de dados. Tente novamente mais tarde."); // mensagem genérica ao utilizador
}
