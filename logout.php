<?php
// logout.php
session_start();
require_once 'config.php';

// Registra o logout
if (isset($_SESSION['usuario_logado'])) {
    registrarLog("Logout realizado por: " . $_SESSION['usuario_logado']['nome']);
}

// Limpa todos os dados da sessão
$_SESSION = array();

// Destrói a sessão
session_destroy();

// Redireciona para a página de login
header('Location: loginFuncionario.php');
exit;
?>