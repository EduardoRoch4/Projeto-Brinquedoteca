<?php
session_start();
require_once 'conexao.php';
// Inicia a sessão se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'funcoes_pessoa.php';

// Verifica se existem dados da criança e um responsável selecionado
if(!isset($_SESSION['dados_crianca']) || !isset($_POST['id_responsavel'])) {
    $_SESSION['mensagem'] = ['tipo' => 'danger', 'texto' => 'Dados incompletos para cadastro.'];
    header('Location: cadastro.php');
    exit;
}

$conn = require 'conexao.php';
$dados = $_SESSION['dados_crianca'];
$id_responsavel = (int)$_POST['id_responsavel'];

try {
    // Verifica se o responsável existe
    $stmt = $conn->prepare("SELECT 1 FROM responsavel WHERE ID_responsavel = ?");
    $stmt->execute([$id_responsavel]);
    
    if(!$stmt->fetch()) {
        throw new Exception("Responsável não encontrado no sistema.");
    }

    // Cadastra a criança
    $resultado = cadastrarCrianca($conn, $dados, $id_responsavel);
    
    if($resultado === true) {
        // Limpa os dados da sessão e redireciona com mensagem de sucesso
        unset($_SESSION['dados_crianca']);
        $_SESSION['mensagem'] = [
            'tipo' => 'success', 
            'texto' => 'Criança cadastrada com sucesso!'
        ];
        header('Location: listar_criancas.php'); // Redireciona para a lista de crianças
        exit;
    } else {
        throw new Exception($resultado);
    }
} catch (PDOException $e) {
    // Erros específicos do banco de dados
    $_SESSION['mensagem'] = [
        'tipo' => 'danger',
        'texto' => 'Erro no banco de dados: ' . $e->getMessage()
    ];
    header('Location: cadastro.php');
    exit;
} catch (Exception $e) {
    // Outros erros
    $_SESSION['mensagem'] = [
        'tipo' => 'danger',
        'texto' => $e->getMessage()
    ];
    header('Location: selecionar_responsavel.php'); // Volta para seleção de responsável
    exit;
}