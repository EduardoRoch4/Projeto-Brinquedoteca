<?php
// Inicia a sessão
session_start();
require_once 'config.php';


// Limpa o arquivo de log se for a primeira execução
if (!isset($_GET['keep'])) {
    file_put_contents('debug.log', '');
}

// Registra informações da sessão
registrarLog("=== DEBUG DA SESSÃO ===");
registrarLog("Dados da sessão: " . print_r($_SESSION, true));

// Registra informações do usuário logado
if (isset($_SESSION['usuario_logado'])) {
    registrarLog("=== DEBUG DO USUÁRIO LOGADO ===");
    registrarLog("Dados do usuário: " . print_r($_SESSION['usuario_logado'], true));
    registrarLog("Nível de acesso: " . $_SESSION['usuario_logado']['nivel']);
    registrarLog("Tipo do nível: " . gettype($_SESSION['usuario_logado']['nivel']));
} else {
    registrarLog("=== AVISO: Nenhum usuário logado ===");
}

// Registra informações das constantes
registrarLog("=== DEBUG DAS CONSTANTES ===");
registrarLog("NIVEL_FUNCIONARIO = " . NIVEL_FUNCIONARIO . " (tipo: " . gettype(NIVEL_FUNCIONARIO) . ")");
registrarLog("NIVEL_GERENTE = " . NIVEL_GERENTE . " (tipo: " . gettype(NIVEL_GERENTE) . ")");
registrarLog("NIVEL_ADMIN = " . NIVEL_ADMIN . " (tipo: " . gettype(NIVEL_ADMIN) . ")");

// Verifica se o arquivo de log existe
if (file_exists('debug.log')) {
    $log_content = file_get_contents('debug.log');
    if (!empty($log_content)) {
        echo "<pre style='background-color: #f5f5f5; padding: 15px; border-radius: 5px;'>";
        echo htmlspecialchars($log_content);
        echo "</pre>";
    } else {
        echo "<p>O arquivo de log está vazio.</p>";
    }
} else {
    echo "<p>O arquivo de log não foi criado. Verifique as permissões do diretório.</p>";
}

// Adiciona um link para limpar o log
echo "<p><a href='debug.php?keep=1'>Atualizar Log</a> | <a href='debug.php'>Limpar Log</a></p>";
?> 