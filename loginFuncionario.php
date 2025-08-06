<?php
session_start();
require_once 'config.php';
require_once 'conexao.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    registrarLog("Tentativa de login para o email: " . $email);
    
    try {
        // Buscar usuário pelo email
        $stmt = $conn->prepare("SELECT * FROM funcionario WHERE email_funcionario = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();
        
        registrarLog("Usuário encontrado: " . print_r($usuario, true));
        
        // Verifica a senha
        if ($usuario && password_verify($senha, $usuario['senha_login_hash'])) {
            // Login bem-sucedido
            registrarLog("Senha verificada com sucesso");
            
            // Armazena os dados na sessão
            $_SESSION['usuario_logado'] = [
                'id' => $usuario['ID_funcionario'],
                'nome' => $usuario['nome_funcionario'],
                'email' => $usuario['email_funcionario'],
                'nivel' => $usuario['nivel_acesso'] // Usa o valor direto do banco
            ];
            
            registrarLog("Dados armazenados na sessão: " . print_r($_SESSION['usuario_logado'], true));
            registrarLog("Nível de acesso armazenado: " . $_SESSION['usuario_logado']['nivel']);
            
            // Redireciona para a página inicial
            header('Location: index.php');
            exit;
        } else {
            registrarLog("Erro: Senha incorreta");
            $erro = "Senha incorreta";
        }
    } catch (PDOException $e) {
        registrarLog("Erro no login: " . $e->getMessage());
        $erro = "Erro ao realizar login. Por favor, tente novamente.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Brinquedoteca</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo img {
            max-width: 150px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="logo">
                <h2>Brinquedoteca</h2>
            </div>
            
            <?php if ($erro): ?>
                <div class="alert alert-danger"><?php echo $erro; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                
                <div class="mb-3">
                    <label for="senha" class="form-label">Senha</label>
                    <input type="password" class="form-control" id="senha" name="senha" required>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">Entrar</button>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>