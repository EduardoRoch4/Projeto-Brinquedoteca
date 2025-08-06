<?php
// config.php - Arquivo de configuração principal do sistema

// 0. Definição do ambiente (desenvolvimento/produção)
define('AMBIENTE_DEV', true); // Alterar para false em produção

// 1. Configurações de exibição de erros
if (AMBIENTE_DEV) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// Carregar funções auxiliares
require_once 'funcoes.php';

// 2. Carregar dependências manualmente (PHPMailer)
$phpmailerPath = __DIR__ . '/vendor/PHPMailer/phpmailer/src/';
if (!file_exists($phpmailerPath . 'PHPMailer.php')) {
    die("Erro: Arquivos do PHPMailer não encontrados em: " . $phpmailerPath);
}

require $phpmailerPath . 'PHPMailer.php';
require $phpmailerPath . 'SMTP.php';
require $phpmailerPath . 'Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// 3. Configurações básicas do sistema
define('SISTEMA_NOME', 'BrinquedoTech');
define('SISTEMA_VERSAO', '1.0.0');
define('BASE_URL', 'http://localhost/' . rawurlencode('BRINQUEDOTECA DOMINGO') . '/');

// 4. Configurações de sessão segura
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start([
        'name' => 'BrinquedotecaSessao',
        'cookie_lifetime' => 86400, // 1 dia
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'cookie_httponly' => true,
        'use_strict_mode' => true,
        'sid_length' => 128,
        'sid_bits_per_character' => 6,
        'gc_maxlifetime' => 14400 // 4 horas
    ]);
}

// 5. Configurações de banco de dados MySQL
define('DB_HOST', 'localhost');
define('DB_NAME', 'brinquedoteca2');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATION', 'utf8mb4_unicode_ci');

// 6. Configurações de e-mail
define('EMAIL_FROM', 'nao-responder@brinquedotech.com');
define('EMAIL_FROM_NAME', 'BrinquedoTech');
define('EMAIL_REPLY_TO', 'contato@brinquedotech.com');
define('EMAIL_HOST', 'smtp.brinquedotech.com');
define('EMAIL_USERNAME', 'contato@brinquedotech.com');
define('EMAIL_PASSWORD', 'SenhaEmail123!');
define('EMAIL_PORT', 587);
define('EMAIL_SECURE', 'tls');
define('EMAIL_DEBUG', 0); // 0 = off, 1 = client messages, 2 = client and server messages

// 7. Configurações de multas e prazos
define('DIAS_EMPRESTIMO_PADRAO', 7);
define('VALOR_MULTA_DIARIA', 3.00);
define('DIAS_ALERTA_ATRASO', 3);

// 8. Níveis de acesso
define('NIVEL_ADMIN', 'admin');      // Nível mais alto
define('NIVEL_GERENTE', 'gerente');    // Nível intermediário
define('NIVEL_FUNCIONARIO', 'funcionario'); // Nível mais baixo

// Log para debug das constantes
registrarLog("=== DEBUG DAS CONSTANTES EM CONFIG.PHP ===");
registrarLog("NIVEL_FUNCIONARIO = " . NIVEL_FUNCIONARIO . " (tipo: " . gettype(NIVEL_FUNCIONARIO) . ")");
registrarLog("NIVEL_GERENTE = " . NIVEL_GERENTE . " (tipo: " . gettype(NIVEL_GERENTE) . ")");
registrarLog("NIVEL_ADMIN = " . NIVEL_ADMIN . " (tipo: " . gettype(NIVEL_ADMIN) . ")");

// 9. Funções de segurança
function sanitizarEntrada($dados) {
    if (is_array($dados)) {
        return array_map('sanitizarEntrada', $dados);
    }
    return htmlspecialchars(trim($dados), ENT_QUOTES, 'UTF-8');
}

// 10. Conexão com o banco de dados (PDO)
function conectarBancoDados() {
    try {
        $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
        $opcoes = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opcoes);
        $pdo->exec("SET NAMES '".DB_CHARSET."' COLLATE '".DB_COLLATION."'");
        $pdo->exec("SET SQL_MODE = 'STRICT_TRANS_TABLES'");
        
        return $pdo;
    } catch (PDOException $e) {
        $errorInfo = [
            'message' => $e->getMessage(),
            'dsn' => $dsn,
            'user' => DB_USER,
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ];
        
        registrarLog("Erro de conexão com o banco: " . print_r($errorInfo, true));
        
        if (AMBIENTE_DEV) {
            die("<h2>Erro de Banco de Dados</h2><pre>".print_r($errorInfo, true)."</pre>");
        }
        
        die("Erro ao conectar com o banco de dados. Por favor, tente novamente mais tarde.");
    }
}

// 11. Autoloader para classes
spl_autoload_register(function ($classe) {
    $diretorios = [
        __DIR__ . '/classes/',
        __DIR__ . '/models/',
        __DIR__ . '/controllers/'
    ];
    
    foreach ($diretorios as $diretorio) {
        $arquivo = $diretorio . str_replace('\\', '/', $classe) . '.php';
        if (file_exists($arquivo)) {
            require $arquivo;
            return;
        }
    }
    
    if (AMBIENTE_DEV) {
        registrarLog("Classe não encontrada: " . $classe);
    }
});

// 12. Tratamento de erros
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function($exception) {
    registrarLog("Exceção não capturada: " . $exception->getMessage());
    
    if (AMBIENTE_DEV) {
        echo "<h1>Erro do Sistema</h1>";
        echo "<div style='background:#fdd; padding:20px; margin:10px; border:2px solid red;'>";
        echo "<p><strong>Mensagem:</strong> " . $exception->getMessage() . "</p>";
        echo "<p><strong>Arquivo:</strong> " . $exception->getFile() . " (linha " . $exception->getLine() . ")</p>";
        echo "<pre>" . $exception->getTraceAsString() . "</pre>";
        echo "</div>";
        exit;
    }
    
    header('Location: ' . BASE_URL . 'erro.php');
    exit;
});

// 13. Configuração de fuso horário
date_default_timezone_set('America/Sao_Paulo');

// 14. CORS
header("Access-Control-Allow-Origin: " . BASE_URL);
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

// 15. Inicialização do sistema
$_SESSION['mensagem'] = $_SESSION['mensagem'] ?? null;

try {
    $conn = conectarBancoDados();
} catch (Exception $e) {
    if (AMBIENTE_DEV) {
        die("<h2>Falha na inicialização do sistema</h2><p>" . $e->getMessage() . "</p>");
    }
    die("Sistema temporariamente indisponível. Por favor, tente novamente mais tarde.");
}

// Função para registrar logs
function registrarLog($mensagem) {
    $data = date('Y-m-d H:i:s');
    $log = "[$data] $mensagem\n";
    
    // Define o diretório de logs
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    // Define o arquivo de log
    $logFile = $logDir . '/sistema.log';
    
    // Adiciona a mensagem ao arquivo de log
    file_put_contents($logFile, $log, FILE_APPEND);
}
?>