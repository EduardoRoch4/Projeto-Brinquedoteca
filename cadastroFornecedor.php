<?php
session_start();
require_once 'conexao.php';

// Inicia a sessão se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


require_once 'header.php';

$conn = require 'conexao.php';
$erro = null;

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addnew'])) {
    try {
        // Limpa e valida os dados
        $cnpj = preg_replace('/[^0-9]/', '', $_POST['cnpj']);
        $cep = preg_replace('/[^0-9]/', '', $_POST['cep']);
        
        // Validações
        if(empty($_POST['nome'])) {
            throw new Exception("O nome é obrigatório");
        }
        
        if(strlen($cnpj) !== 14) {
            throw new Exception("CNPJ deve conter exatamente 14 dígitos");
        }

        if(strlen($cep) !== 8) {
            throw new Exception("CEP deve conter exatamente 8 dígitos");
        }

        // Prepara os dados para inserção
        $dados = [
            'nome_fornecedor' => htmlspecialchars(trim($_POST['nome'])),
            'cnpj' => $cnpj,
            'email' => filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ? $_POST['email'] : null,
            'cep' => $cep,
            'data_cadastro' => date('Y-m-d H:i:s')
        ];

        // Query SQL
        $sql = "INSERT INTO fornecedor (nome_fornecedor, cnpj, email, data_cadastro) 
                VALUES (:nome_fornecedor, :cnpj, :email, :data_cadastro)";
        
        $stmt = $conn->prepare($sql);
        
        if($stmt->execute([
            ':nome_fornecedor' => $dados['nome_fornecedor'],
            ':cnpj' => $dados['cnpj'],
            ':email' => $dados['email'],
            ':data_cadastro' => $dados['data_cadastro']
        ])) {
            $_SESSION['mensagem'] = [
                'tipo' => 'success',
                'texto' => 'Fornecedor cadastrado com sucesso!'
            ];
            header('Location: listaFornecedores.php');
            exit;
        } else {
            throw new Exception("Erro ao cadastrar no banco de dados");
        }
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}
?>

<div class="container">
    <?php if(isset($_SESSION['mensagem'])): ?>
        <div class="alert alert-<?= $_SESSION['mensagem']['tipo'] ?>">
            <?= $_SESSION['mensagem']['texto'] ?>
        </div>
        <?php unset($_SESSION['mensagem']); ?>
    <?php endif; ?>
    
    <?php if($erro): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($erro) ?>
        </div>
    <?php endif; ?>

    <!-- Botões de navegação -->
    <div class="row mb-4">
        <div class="col">
            <a href="index.php" class="btn btn-default" style="margin-right: 10px;">
                <i class="glyphicon glyphicon-home"></i> Voltar à Página Inicial
            </a>
            <a href="listaFornecedores.php" class="btn btn-info">
                <i class="glyphicon glyphicon-list"></i> Voltar para Lista de Fornecedores
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h3>Cadastrar Novo Fornecedor</h3>
                </div>
                <div class="card-body">
                    <form method="POST" id="formFornecedor">
                        <!-- Campos do formulário -->
                        <div class="form-group">
                            <label for="nome">Nome/Razão Social*</label>
                            <input type="text" id="nome" name="nome" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="cnpj">CNPJ*</label>
                            <input type="text" id="cnpj" name="cnpj" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">E-mail</label>
                            <input type="email" id="email" name="email" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="cep">CEP*</label>
                            <input type="text" id="cep" name="cep" class="form-control" required>
                        </div>
                        
                        <button type="submit" name="addnew" class="btn btn-primary">Cadastrar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Formatação e validação em tempo real
document.getElementById('cnpj').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if(value.length > 14) value = value.substring(0, 14);
    
    // Formatação do CNPJ
    value = value.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
    e.target.value = value;
});

document.getElementById('cep').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if(value.length > 8) value = value.substring(0, 8);
    value = value.replace(/(\d{5})(\d{3})/, '$1-$2');
    e.target.value = value;
});

// Validação no envio
document.getElementById('formFornecedor').addEventListener('submit', function(e) {
    let valid = true;
    
    // Validação simples - pode ser expandida
    const campos = [
        {id: 'nome', msg: 'Nome é obrigatório'},
        {id: 'cnpj', msg: 'CNPJ inválido', val: v => v.replace(/\D/g, '').length === 14},
        {id: 'cep', msg: 'CEP inválido', val: v => v.replace(/\D/g, '').length === 8}
    ];
    
    campos.forEach(campo => {
        const el = document.getElementById(campo.id);
        const value = el.value;
        
        if(!value || (campo.val && !campo.val(value))) {
            alert(campo.msg);
            el.focus();
            valid = false;
            return false;
        }
    });
    
    if(!valid) {
        e.preventDefault();
    }
});
</script>

<?php 
require_once 'footer.php';
?>