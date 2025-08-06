<?php
session_start();
require_once 'conexao.php';

// Inicia a sessão se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'header.php';

// Função para formatar CPF
function formatarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if(strlen($cpf) != 11) return $cpf;
    return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
}

// Verifica se o ID foi passado
if(!isset($_GET['id'])) {
    $_SESSION['mensagem'] = ['tipo' => 'danger', 'texto' => 'ID do responsável não informado'];
    header("Location: listar_responsavel.php");
    exit;
}

$id = (int)$_GET['id'];

// Processa o formulário de edição
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dados = [
            'nome' => htmlspecialchars(trim($_POST['nome'])),
            'cpf' => preg_replace('/[^0-9]/', '', $_POST['cpf']),
            'sexo' => in_array($_POST['sexo'], ['M', 'F', 'O']) ? $_POST['sexo'] : 'O',
            'email' => filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ? $_POST['email'] : null,
            'data_nascimento' => $_POST['data_nascimento'],
            'id' => $id
        ];

        // Validações
        if(empty($dados['nome'])) throw new Exception("Nome é obrigatório");
        if(empty($dados['cpf']) || strlen($dados['cpf']) != 11) throw new Exception("CPF inválido - deve ter 11 dígitos");

        $sql = "UPDATE responsavel SET 
                nome = :nome, 
                CPF = :cpf, 
                sexo = :sexo, 
                email = :email, 
                data_nascimento = :data_nascimento 
                WHERE ID_responsavel = :id";

        $stmt = $conn->prepare($sql);
        if($stmt->execute($dados)) {
            $_SESSION['mensagem'] = ['tipo' => 'success', 'texto' => 'Responsável atualizado com sucesso!'];
            header("Location: listar_responsavel.php");
            exit;
        } else {
            throw new Exception("Falha ao executar a atualização no banco de dados");
        }
    } catch(Exception $e) {
        $_SESSION['mensagem'] = ['tipo' => 'danger', 'texto' => 'Erro: ' . $e->getMessage()];
    }
}

// Busca os dados do responsável
try {
    $stmt = $conn->prepare("SELECT * FROM responsavel WHERE ID_responsavel = ?");
    $stmt->execute([$id]);
    $responsavel = $stmt->fetch();

    if(!$responsavel) {
        $_SESSION['mensagem'] = ['tipo' => 'danger', 'texto' => 'Responsável não encontrado'];
        header("Location: listar_responsavel.php");
        exit;
    }
} catch(Exception $e) {
    $_SESSION['mensagem'] = ['tipo' => 'danger', 'texto' => 'Erro ao buscar responsável'];
    header("Location: listar_responsavel.php");
    exit;
}
?>

<div class="container">
    <h2><i class="glyphicon glyphicon-edit"></i> Editar Responsável</h2>
    
    <?php if(isset($_SESSION['mensagem'])): ?>
        <div class="alert alert-<?= $_SESSION['mensagem']['tipo'] ?>">
            <?= $_SESSION['mensagem']['texto'] ?>
        </div>
        <?php unset($_SESSION['mensagem']); ?>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Nome Completo*</label>
            <input type="text" name="nome" class="form-control" required 
                   value="<?= htmlspecialchars($responsavel['nome']) ?>">
        </div>
        
        <div class="form-group">
            <label>CPF*</label>
            <input type="text" name="cpf" class="form-control" required maxlength="14"
                  value="<?= formatarCPF($responsavel['CPF']) ?>"
                   oninput="formatarCPF(this)">
        </div>
        
        <div class="form-group">
            <label>Sexo*</label>
            <select name="sexo" class="form-control" required>
                <option value="M" <?= $responsavel['sexo'] == 'M' ? 'selected' : '' ?>>Masculino</option>
                <option value="F" <?= $responsavel['sexo'] == 'F' ? 'selected' : '' ?>>Feminino</option>
                <option value="O" <?= $responsavel['sexo'] == 'O' ? 'selected' : '' ?>>Outro</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>E-mail</label>
            <input type="email" name="email" class="form-control"
                   value="<?= htmlspecialchars($responsavel['email']) ?>">
        </div>
        
        <div class="form-group">
            <label>Data de Nascimento*</label>
            <input type="date" name="data_nascimento" class="form-control" required
                   value="<?= htmlspecialchars($responsavel['data_nascimento']) ?>">
        </div>
        
        <button type="submit" class="btn btn-primary">
            <i class="glyphicon glyphicon-floppy-disk"></i> Salvar Alterações
        </button>
        <a href="listar_responsavel.php" class="btn btn-default">Cancelar</a>
    </form>
</div>

<script>
function formatarCPF(campo) {
    // Remove tudo que não é número
    var valor = campo.value.replace(/\D/g, '');
    
    // Formata o CPF
    if(valor.length > 3) valor = valor.replace(/^(\d{3})/, '$1.');
    if(valor.length > 7) valor = valor.replace(/^(\d{3})\.(\d{3})/, '$1.$2.');
    if(valor.length > 11) valor = valor.replace(/^(\d{3})\.(\d{3})\.(\d{3})/, '$1.$2.$3-');
    
    // Atualiza o campo
    campo.value = valor.substring(0, 14);
}
</script>

<?php require_once 'footer.php'; ?>