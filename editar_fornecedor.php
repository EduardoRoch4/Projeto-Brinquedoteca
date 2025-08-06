<?php
require_once 'conexao.php';
require_once 'header.php';

// Obtém a conexão
$conn = require 'conexao.php';

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    try {
        $cnpj = preg_replace('/[^0-9]/', '', $_POST['cnpj']);
        $cep = preg_replace('/[^0-9]/', '', $_POST['cep']);
        
        // Validações básicas
        if(empty(trim($_POST['nome']))) {
            throw new Exception("O nome é obrigatório");
        }
        
        if(strlen($cnpj) !== 14) {
            throw new Exception("CNPJ deve conter exatamente 14 dígitos");
        }
        
        if(strlen($cep) !== 8) {
            throw new Exception("CEP deve conter exatamente 8 dígitos");
        }

        // Inicia a transação
        $conn->beginTransaction();

        try {
            // 1. Verifica/Cadastra o endereço
            $stmt = $conn->prepare("SELECT ID_endereco FROM endereco WHERE cep = ?");
            $stmt->execute([$cep]);
            $endereco = $stmt->fetch();
            
            if(!$endereco) {
                $stmt = $conn->prepare("INSERT INTO endereco (cep) VALUES (?)");
                if(!$stmt->execute([$cep])) {
                    throw new Exception("Erro ao cadastrar novo endereço");
                }
                $id_endereco = $conn->lastInsertId();
            } else {
                $id_endereco = $endereco['ID_endereco'];
            }

            // 2. Atualiza fornecedor
            $dados = [
                'nome_fornecedor' => htmlspecialchars(trim($_POST['nome'])),
                'cnpj' => $cnpj,
                'email' => filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ? $_POST['email'] : null,
                'ID_endereco' => $id_endereco,
                'id' => $_POST['id_fornecedor']
            ];

            $sql = "UPDATE fornecedor SET 
                    nome_fornecedor = :nome_fornecedor,
                    cnpj = :cnpj,
                    email = :email,
                    ID_endereco = :ID_endereco
                    WHERE ID_fornecedor = :id";

            $stmt = $conn->prepare($sql);
            
            if(!$stmt->execute($dados)) {
                throw new Exception("Erro ao atualizar fornecedor");
            }

            // Confirma a transação
            $conn->commit();

            $_SESSION['mensagem'] = [
                'tipo' => 'success',
                'texto' => 'Fornecedor atualizado com sucesso!'
            ];
            header('Location: listaFornecedores.php');
            exit;

        } catch (Exception $e) {
            // Se houver erro, desfaz a transação
            $conn->rollBack();
            throw $e;
        }

    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

// Consulta o fornecedor com o endereço
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$sql = "SELECT f.*, e.cep 
        FROM fornecedor f 
        LEFT JOIN endereco e ON f.ID_endereco = e.ID_endereco 
        WHERE f.ID_fornecedor = :id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();

if($stmt->rowCount() < 1){
    $_SESSION['mensagem'] = [
        'tipo' => 'danger',
        'texto' => 'Fornecedor não encontrado!'
    ];
    header('Location: listaFornecedores.php');
    exit;
}

$fornecedor = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="container">
    <?php if(isset($erro)): ?>
        <div class="alert alert-danger"><?= $erro ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-6 col-md-offset-3">
            <div class="box">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3><i class="glyphicon glyphicon-edit"></i> Editar Fornecedor</h3>
                    <button class="btn btn-default" onclick="window.location.href='index.php'">
                        <i class="glyphicon glyphicon-home"></i> Voltar à Página Inicial
                    </button>
                </div>
                
                 <form method="POST">
                    <input type="hidden" name="id_fornecedor" value="<?= $fornecedor['ID_fornecedor'] ?>">
                    
                    <div class="form-group">
                        <label for="nome">Nome/Razão Social*</label>
                        <input type="text" id="nome" name="nome" class="form-control" required 
                               value="<?= isset($fornecedor['nome_fornecedor']) ? htmlspecialchars($fornecedor['nome_fornecedor']) : '' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="cnpj">CNPJ*</label>
                        <input type="text" id="cnpj" name="cnpj" class="form-control" required
                               value="<?= isset($fornecedor['cnpj']) ? htmlspecialchars($fornecedor['cnpj']) : '' ?>"
                               oninput="formatarCNPJ(this)" maxlength="18">
                        <small class="form-text text-muted">Formato: 00.000.000/0000-00</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" class="form-control"
                               value="<?= isset($fornecedor['email']) ? htmlspecialchars($fornecedor['email']) : '' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="cep">CEP*</label>
                        <input type="text" id="cep" name="cep" class="form-control" required
                               value="<?= isset($fornecedor['cep']) ? htmlspecialchars($fornecedor['cep']) : '' ?>"
                               oninput="formatarCEP(this)" maxlength="9">
                        <small class="form-text text-muted">Formato: 00000-000</small>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; margin-top: 20px;">
                        <button type="submit" name="update" class="btn btn-primary">
                            <i class="glyphicon glyphicon-floppy-disk"></i> Salvar Alterações
                        </button>
                        <a href="listaFornecedores.php" class="btn btn-default">
                            <i class="glyphicon glyphicon-list"></i> Voltar à Lista
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Função para formatar CNPJ
function formatarCNPJ(input) {
    let value = input.value.replace(/\D/g, '');
    if(value.length > 14) value = value.substring(0, 14);
    
    if(value.length > 12) {
        value = value.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5');
    } else if(value.length > 8) {
        value = value.replace(/^(\d{2})(\d{3})(\d{3})(\d{0,4})$/, '$1.$2.$3/$4');
    } else if(value.length > 5) {
        value = value.replace(/^(\d{2})(\d{3})(\d{0,3})$/, '$1.$2.$3');
    } else if(value.length > 2) {
        value = value.replace(/^(\d{2})(\d{0,3})$/, '$1.$2');
    }
    input.value = value;
}

// Função para formatar CEP
function formatarCEP(input) {
    let value = input.value.replace(/\D/g, '');
    if(value.length > 8) value = value.substring(0, 8);
    
    if(value.length > 5) {
        value = value.replace(/^(\d{5})(\d{0,3})$/, '$1-$2');
    }
    input.value = value;
}

// Formata os campos quando a página carrega
document.addEventListener('DOMContentLoaded', function() {
    // Mantém as outras formatações
    formatarCNPJ(document.getElementById('cnpj'));
    formatarCEP(document.getElementById('cep'));
});
</script>

<?php 
require_once 'footer.php';
?>