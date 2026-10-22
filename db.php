<?php
// Configurações da Base de Dados (Supabase PostgreSQL)
$host = 'db.yhonxysuusxuvjkcpize.supabase.co';
$port = '5432';
$db   = 'postgres';
$user = 'postgres';
$pass = '621695545@ETPC#';

$dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Falha na ligação à base de dados: ' . $e->getMessage()
    ]);
    exit;
}

/**
 * Função utilitária para executar queries SQL em segurança
 */
function dbQuery($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}
