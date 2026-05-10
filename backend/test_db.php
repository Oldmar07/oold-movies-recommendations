<?php
// ============================================================
// Script de Teste de Ligação à Base de Dados (CineMatch)
// ============================================================

// Carrega as configurações
$config = require_once __DIR__ . '/config/database.php';

header('Content-Type: text/plain; charset=UTF-8');

echo "--- Teste de Ligação CineMatch ---\n";
echo "Host: " . $config['host'] . "\n";
echo "Base de Dados: " . $config['dbname'] . "\n";
echo "Utilizador: " . $config['user'] . "\n";
echo "---------------------------------\n";

try {
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
    
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    echo "✅ SUCESSO: A ligação foi estabelecida corretamente!\n";
    echo "Versão do Servidor: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
} catch (PDOException $e) {
    echo "❌ ERRO DE LIGAÇÃO: " . $e->getMessage() . "\n";
}