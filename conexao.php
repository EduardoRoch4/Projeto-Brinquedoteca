<?php
require_once 'config.php';

$host = 'localhost';
$db   = 'brinquedoteca2';
$user = 'root';    // Usuário padrão do XAMPP
$pass = '';        // Senha padrão (vazia)
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $conn = new PDO($dsn, $user, $pass, $options);
    registrarLog("Conexão com o banco de dados estabelecida com sucesso");
} catch (\PDOException $e) {
    registrarLog("Erro de conexão com o banco de dados: " . $e->getMessage());
    die("Erro de conexão: " . $e->getMessage());
}

return $conn;
?>