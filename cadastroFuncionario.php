<?php
require_once 'conexao.php';
require_once 'header.php';

// Obtém a conexão
$conn = require 'conexao.php';

// Verifica se já existem funcionários cadastrados
$stmt = $conn->query("SELECT COUNT(*) as total FROM funcionario");
$totalFuncionario = $stmt->fetch()['total'];

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addnew'])) {
    try {
        // Prepara os dados
        $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);
        $cep = preg_replace('/[^0-9]/', '', $_POST['cep']);

        $dados = [
            'nome_funcionario' => htmlspecialchars(trim($_POST['nome_funcionario'])),
            'cpf' => $cpf,
            'email_funcionario' => filter_var($_POST['email_funcionario'], FILTER_VALIDATE_EMAIL) ? $_POST['email_funcionario'] : null,
            'cep' => $cep,
            'cargo' => htmlspecialchars(trim($_POST['cargo'])),
            'senha_login_hash' => password_hash($_POST['senha_login_hash'], PASSWORD_DEFAULT),
            'data_admissao' => $_POST['data_admissao'],
            'nivel_acesso' => $_POST['nivel_acesso']
        ];
        
        // Validações básicas (mantidas iguais)
        if(empty($dados['nome_funcionario'])) {
            throw new Exception("O nome é obrigatório");
        }
        
        if(strlen($dados['cpf']) !== 11) {
            throw new Exception("CPF deve conter 11 dígitos");
        }

        if(empty($dados['cargo'])) {
            throw new Exception("O cargo é obrigatório");
        }

        if(empty($_POST['senha_login_hash']) || strlen($_POST['senha_login_hash']) < 8) {
            throw new Exception("A senha é obrigatória e deve ter no mínimo 8 caracteres");
        }

        if(empty($dados['data_admissao'])) {
            throw new Exception("A data de admissão é obrigatória");
        }

        // Verifica se CPF já existe
        $stmt = $conn->prepare("SELECT ID_funcionario FROM funcionario WHERE cpf = ?");
        $stmt->execute([$dados['cpf']]);
        if($stmt->fetch()) {
            throw new Exception("CPF já cadastrado no sistema");
        }

        // Verifica se email já existe
$stmt = $conn->prepare("SELECT ID_funcionario FROM funcionario WHERE email_funcionario = ?");
$stmt->execute([$dados['email_funcionario']]);
if($stmt->fetch()) {
    throw new Exception("Email já cadastrado no sistema");
}

        // Inicia transação
        $conn->beginTransaction();

// 1. Primeiro cadastra o endereço (apenas com CEP)
if(!empty($dados['cep'])) {
    $stmtEndereco = $conn->prepare("
        INSERT INTO endereco (cep) VALUES (?)
    ");
    
    if(!$stmtEndereco->execute([$dados['cep']])) {
        throw new Exception("Erro ao cadastrar endereço");
    }
    $id_endereco = $conn->lastInsertId();
} else {
    throw new Exception("CEP é obrigatório");
}

// 2. Agora cadastra o funcionário com a referência ao endereço
$stmtFuncionario = $conn->prepare("
    INSERT INTO funcionario (
        nome_funcionario, 
        cpf, 
        email_funcionario, 
        ID_endereco,
        cargo_funcionario, 
        senha_login_hash,
        data_admissao,
        nivel_acesso
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

$stmtFuncionario->execute([
    $dados['nome_funcionario'],
    $dados['cpf'],
    $dados['email_funcionario'],
    $id_endereco,
    $dados['cargo'],
    $dados['senha_login_hash'],
    $dados['data_admissao'],
    $dados['nivel_acesso']
]);

// Confirma a transação
$conn->commit();

        // Se for o primeiro cadastro, faz login automaticamente
        if($totalFuncionario == 0) {
            session_start();
            $_SESSION['usuario_logado'] = [
                'id' => $id_funcionario,
                'nome' => $dados['nome_funcionario'],
                'cargo' => $dados['cargo']
            ];
            header('Location: index.php');
        } else {
            $_SESSION['mensagem'] = [
                'tipo' => 'success',
                'texto' => 'Funcionário cadastrado com sucesso!'
            ];
            header('Location: listaFuncionarios.php');
        }
        exit;

    } catch (Exception $e) {
        // Desfaz a transação em caso de erro
        if($conn->inTransaction()) {
            $conn->rollBack();
        }
        $erro = [
            'tipo' => 'danger',
            'texto' => 'Erro no cadastro: ' . $e->getMessage()
        ];
        
        error_log("ERRO NO CADASTRO: " . $e->getMessage());
    }
}
?>
    
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3><i class="fas fa-chart-bar"></i> Cadastro de funcionário</h3>
                        <a href="index.php" class="btn btn-light">
                            <i class="fas fa-home"></i> Voltar ao Início
                        </a>
                    </div>
                </div>
            
            <?php if(isset($erro)): ?>
                <div class="alert alert-<?= $erro['tipo'] ?>">
                    <?= $erro['texto'] ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="formFuncionario">
                <div class="form-group">
                    <label for="nome_funcionario">Nome Completo*</label>
                    <input type="text" id="nome_funcionario" name="nome_funcionario" class="form-control" required 
                           value="<?= isset($_POST['nome_funcionario']) ? htmlspecialchars($_POST['nome_funcionario']) : '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="cpf">CPF*</label>
                    <input type="text" id="cpf" name="cpf" class="form-control" required
                           value="<?= isset($_POST['cpf']) ? htmlspecialchars($_POST['cpf']) : '' ?>"
                           oninput="formatarCPF(this)" maxlength="14" placeholder="000.000.000-00">
                </div>
                
                <div class="form-group">
                    <label for="email_funcionario">E-mail</label>
                    <input type="email" id="email_funcionario" name="email_funcionario" class="form-control"
                           value="<?= isset($_POST['email_funcionario']) ? htmlspecialchars($_POST['email_funcionario']) : '' ?>">
                </div>
                
                
                <div class="form-group">
                    <label for="cep">CEP*</label>
                    <input type="text" id="cep" name="cep" class="form-control" required
                           value="<?= isset($_POST['cep']) ? htmlspecialchars($_POST['cep']) : '' ?>"
                           oninput="formatarCEP(this)" maxlength="9" placeholder="00000-000">
                    <small class="text-muted">O endereço será preenchido automaticamente</small>
                </div>
                
                <div class="form-group">
                    <label for="cargo">Cargo*</label>
                    <input type="text" id="cargo" name="cargo" class="form-control" required
                           value="<?= isset($_POST['cargo']) ? htmlspecialchars($_POST['cargo']) : '' ?>">
                </div>

                <div class="form-group">
                    <label for="senha_login_hash">Senha*</label>
                    <input type="password" id="senha_login_hash" name="senha_login_hash" class="form-control" required>
                    <small class="text-muted">Mínimo de 8 caracteres</small>
                </div>

                <div class="form-group">
                    <label for="data_admissao">Data de Admissão*</label>
                    <input type="date" id="data_admissao" name="data_admissao" class="form-control" required
                           value="<?= isset($_POST['data_admissao']) ? htmlspecialchars($_POST['data_admissao']) : date('Y-m-d') ?>">
                </div>

                <div class="form-group">
                    <label for="nivel_acesso">Nível de Acesso*</label>
                    <select id="nivel_acesso" name="nivel_acesso" class="form-control" required>
                        <option value="funcionario">Funcionário</option>
                        <option value="gerente">Gerente</option>
                        <option value="admin">Administrador</option>
                    </select>
                    <small class="text-muted">Selecione o nível de acesso do funcionário</small>
                </div>
                
                <div style="display: flex; justify-content: space-between; margin-top: 20px;">
                    <button type="submit" name="addnew" class="btn btn-primary">
                        <i class="glyphicon glyphicon-floppy-disk"></i> Cadastrar
                    </button>
                    <?php if($totalFuncionario > 0): ?>
                        <button type="button" class="btn btn-default" onclick="window.location.href='listaFuncionarios.php'">
                            <i class="glyphicon glyphicon-list"></i> Ver Funcionários
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Funções de formatação
function formatarCPF(input) {
    let value = input.value.replace(/\D/g, '');
    if(value.length > 11) value = value.substring(0, 11);
    
    if(value.length > 9) {
        value = value.replace(/^(\d{3})(\d{3})(\d{3})(\d{2})$/, '$1.$2.$3-$4');
    } else if(value.length > 6) {
        value = value.replace(/^(\d{3})(\d{3})(\d{0,3})$/, '$1.$2.$3');
    } else if(value.length > 3) {
        value = value.replace(/^(\d{3})(\d{0,3})$/, '$1.$2');
    }
    
    input.value = value;
}

function formatarCEP(input) {
    let value = input.value.replace(/\D/g, '');
    if(value.length > 8) value = value.substring(0, 8);
    
    if(value.length > 5) {
        value = value.replace(/^(\d{5})(\d{0,3})$/, '$1-$2');
    }
    
    input.value = value;
}

// Validação do formulário
document.getElementById('formFuncionario').addEventListener('submit', function(e) {
    const cpf = document.getElementById('cpf').value.replace(/\D/g, '');
    const cep = document.getElementById('cep').value.replace(/\D/g, '');
    const senha = document.getElementById('senha_login_hash').value;
    
    if(cpf.length !== 11) {
        alert('CPF deve conter 11 dígitos');
        e.preventDefault();
        return;
    }
    
    if(cep.length !== 8) {
        alert('CEP deve conter 8 dígitos');
        e.preventDefault();
        return;
    }
    
    if(senha.length < 8) {
        alert('A senha deve ter no mínimo 8 caracteres');
        e.preventDefault();
        return;
    }
});
</script>

<?php 
require_once 'footer.php';