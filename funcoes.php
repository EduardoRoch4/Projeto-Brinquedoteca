<?php
// Funções auxiliares do sistema

/**
 * Verifica se o usuário está logado
 */
function verificarLogin() {
    if (!isset($_SESSION['usuario_logado'])) {
        header('Location: loginFuncionario.php');
        exit;
    }
}

/**
 * Formata um valor monetário
 */
function formatarMoeda($valor) {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

/**
 * Formata uma data
 */
function formatarData($data) {
    return date('d/m/Y', strtotime($data));
}

/**
 * Formata uma data e hora
 */
function formatarDataHora($data) {
    return date('d/m/Y H:i', strtotime($data));
}

/**
 * Limpa e sanitiza uma string
 */
function limparString($string) {
    return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
}

/**
 * Verifica se uma data é válida
 */
function dataValida($data) {
    $data = str_replace('/', '-', $data);
    return date('Y-m-d', strtotime($data)) == $data;
}

/**
 * Calcula a diferença em dias entre duas datas
 */
function calcularDiferencaDias($data1, $data2) {
    $data1 = new DateTime($data1);
    $data2 = new DateTime($data2);
    $diferenca = $data1->diff($data2);
    return $diferenca->days;
}

/**
 * Gera um token único
 */
function gerarToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Verifica se uma string é um JSON válido
 */
function isJson($string) {
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}

/**
 * Redireciona com mensagem
 */
function redirecionarComMensagem($url, $mensagem, $tipo = 'success') {
    $_SESSION['mensagem'] = [
        'texto' => $mensagem,
        'tipo' => $tipo
    ];
    header("Location: $url");
    exit;
}

/**
 * Exibe mensagem e limpa da sessão
 */
function exibirMensagem() {
    if (isset($_SESSION['mensagem'])) {
        $mensagem = $_SESSION['mensagem'];
        unset($_SESSION['mensagem']);
        return "<div class='alert alert-{$mensagem['tipo']}'>{$mensagem['texto']}</div>";
    }
    return '';
}
?> 