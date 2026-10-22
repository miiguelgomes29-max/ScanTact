<?php
define('DB_TYPE', 'mysql');         // 'mysql' para MySQL/MariaDB ou 'pgsql' para PostgreSQL (Supabase Direct)
define('DB_HOST', '127.0.0.1');     // Endereço do servidor
define('DB_PORT', '3306');          // Porta: 3306 para MySQL, 5432 para PostgreSQL
define('DB_NAME', 'gestao_contactos'); // Nome da base de dados
define('DB_USER', 'root');          // Utilizador da base de dados
define('DB_PASS', '');              // Palavra-passe
define('DB_CHARSET', 'utf8mb4');    // Codificação de caracteres

function getDBConnection() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            if (DB_TYPE === 'pgsql') {
                $dsn = sprintf("pgsql:host=%s;port=%s;dbname=%s", DB_HOST, DB_PORT, DB_NAME);
            } else {
                $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=%s", DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
            }

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

        } catch (PDOException $e) {
            error_log("Erro na ligação à Base de Dados: " . $e->getMessage());
            
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Não foi possível estabelecer ligação à base de dados. Verifique os dados em db.php."
            ]);
            exit;
        }
    }

    return $pdo;
}

function dbQuery($sql, $params = []) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}
?>