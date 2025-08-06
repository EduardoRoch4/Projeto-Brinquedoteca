<?php
require_once 'config.php';

try {
    // Verifica a estrutura da tabela
    $stmt = $conn->query("DESCRIBE funcionario");
    echo "<h2>Estrutura da tabela funcionario:</h2>";
    echo "<pre>";
    while ($row = $stmt->fetch()) {
        print_r($row);
    }
    echo "</pre>";

    // Verifica os dados de um funcionário
    $stmt = $conn->query("SELECT ID_funcionario, nome_funcionario, email_funcionario, nivel_acesso FROM funcionario LIMIT 1");
    echo "<h2>Dados de um funcionário:</h2>";
    echo "<pre>";
    while ($row = $stmt->fetch()) {
        print_r($row);
    }
    echo "</pre>";

} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?> 