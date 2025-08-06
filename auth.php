<?php
// auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'verificar_permissoes.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_logado'])) {
    // Armazena a página que estava tentando acessar
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    
    // Mensagem de redirecionamento
    $_SESSION['mensagem'] = [
        'tipo' => 'warning',
        'texto' => 'Você precisa fazer login para acessar o sistema'
    ];
    
    // Redireciona para a página de login
    header("Location: loginFuncionario.php");
    exit;
}
?>