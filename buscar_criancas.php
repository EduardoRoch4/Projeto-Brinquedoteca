<?php
require_once 'config.php';
require_once 'conexao.php';

header('Content-Type: application/json');

if (!isset($_GET['id_responsavel'])) {
    echo json_encode([]);
    exit;
}

$id_responsavel = $_GET['id_responsavel'];

try {
    $conn = require 'conexao.php';
    
    $stmt = $conn->prepare("
        SELECT ID_crianca, nome_crianca 
        FROM crianca 
        WHERE ID_responsavel = ? 
        ORDER BY nome_crianca
    ");
    
    $stmt->execute([$id_responsavel]);
    $criancas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($criancas);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao buscar crianças: ' . $e->getMessage()]);
} 