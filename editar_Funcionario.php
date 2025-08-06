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

// Processa a exclusão do funcionário via POST
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir'])) {
    try {
        $id = (int)$_POST['excluir'];
        
        $stmt = $conn->prepare("DELETE FROM funcionario WHERE ID_funcionario = ?");
        $stmt->execute([$id]);
        
        $_SESSION['mensagem'] = ['tipo' => 'success', 'texto' => 'Funcionário excluído com sucesso!'];
        header("Location: listaFuncionarios.php");
        exit;
    } catch(Exception $e) {
        $_SESSION['mensagem'] = ['tipo' => 'danger', 'texto' => 'Erro ao excluir: ' . $e->getMessage()];
        header("Location: listaFuncionarios.php");
        exit;
    }
}

// Verifica se o ID foi passado para edição
if(!isset($_GET['id'])) {
    $_SESSION['mensagem'] = ['tipo' => 'danger', 'texto' => 'ID do funcionário não informado'];
    header("Location: listaFuncionarios.php");
    exit;
}

$id = (int)$_GET['id'];

// Processa o formulário de edição
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'])) {
    try {
        $dados = [
            'nome' => htmlspecialchars(trim($_POST['nome'])),
            'cpf' => preg_replace('/[^0-9]/', '', $_POST['cpf']),
            'cargo' => htmlspecialchars(trim($_POST['cargo'])),
            'nivel_acesso' => $_POST['nivel_acesso'],
            'id' => $id
        ];

        // Validações básicas
        if(empty($dados['nome'])) throw new Exception("Nome é obrigatório");
        if(strlen($dados['cpf']) != 11) throw new Exception("CPF inválido");

        $sql = "UPDATE funcionario SET 
                nome_funcionario = :nome, 
                cpf = :cpf, 
                cargo_funcionario = :cargo,
                nivel_acesso = :nivel_acesso 
                WHERE ID_funcionario = :id";

        $stmt = $conn->prepare($sql);
        $stmt->execute($dados);
        
        $_SESSION['mensagem'] = ['tipo' => 'success', 'texto' => 'Funcionário atualizado com sucesso!'];
        header("Location: listaFuncionarios.php");
        exit;
    } catch(Exception $e) {
        $_SESSION['mensagem'] = ['tipo' => 'danger', 'texto' => 'Erro: ' . $e->getMessage()];
    }
}

// Busca os dados do funcionário
try {
    $stmt = $conn->prepare("SELECT * FROM funcionario WHERE ID_funcionario = ?");
    $stmt->execute([$id]);
    $funcionario = $stmt->fetch();

    if(!$funcionario) {
        $_SESSION['mensagem'] = ['tipo' => 'danger', 'texto' => 'Funcionário não encontrado'];
        header("Location: listaFuncionarios.php");
        exit;
    }
} catch(Exception $e) {
    $_SESSION['mensagem'] = ['tipo' => 'danger', 'texto' => 'Erro ao buscar funcionário'];
    header("Location: listaFuncionarios.php");
    exit;
}
?>

<div class="container">
    <h2><i class="glyphicon glyphicon-edit"></i> Editar Funcionário</h2>
    
    <?php if(isset($_SESSION['mensagem'])): ?>
        <div class="alert alert-<?= $_SESSION['mensagem']['tipo'] ?>">
            <?= $_SESSION['mensagem']['texto'] ?>
        </div>
        <?php unset($_SESSION['mensagem']); ?>
    <?php endif; ?>

    <form method="POST" id="formEditar">
        <div class="form-group">
            <label>Nome Completo*</label>
            <input type="text" name="nome" class="form-control" required 
                   value="<?= htmlspecialchars($funcionario['nome_funcionario']) ?>">
        </div>
        
        <div class="form-group">
            <label>CPF*</label>
            <input type="text" name="cpf" class="form-control" required maxlength="14"
                  value="<?= formatarCPF($funcionario['cpf']) ?>"
                   oninput="formatarCPF(this)">
        </div>
        
        <div class="form-group">
            <label>Cargo*</label>
            <input type="text" name="cargo" class="form-control" required
                   value="<?= htmlspecialchars($funcionario['cargo_funcionario']) ?>">
        </div>
        
        <div class="form-group">
            <label>Nível de Acesso*</label>
            <select name="nivel_acesso" class="form-control" required>
                <option value="funcionario" <?= $funcionario['nivel_acesso'] == 'funcionario' ? 'selected' : '' ?>>Funcionário</option>
                <option value="gerente" <?= $funcionario['nivel_acesso'] == 'gerente' ? 'selected' : '' ?>>Gerente</option>
                <option value="admin" <?= $funcionario['nivel_acesso'] == 'admin' ? 'selected' : '' ?>>Administrador</option>
            </select>
        </div>
        
        <input type="hidden" name="id" value="<?= $funcionario['ID_funcionario'] ?>">
        
        <button type="submit" class="btn btn-primary">
            <i class="glyphicon glyphicon-floppy-disk"></i> Salvar Alterações
        </button>
        <a href="listaFuncionarios.php" class="btn btn-default">Cancelar</a>
        <button type="button" class="btn btn-danger" onclick="confirmarExclusao()">
            <i class="glyphicon glyphicon-trash"></i> Excluir Funcionário
        </button>
    </form>
    
    <!-- Formulário oculto para exclusão -->
    <form method="POST" id="formExcluir" style="display:none;">
        <input type="hidden" name="excluir" value="<?= $funcionario['ID_funcionario'] ?>">
    </form>
</div>

<script>
function formatarCPF(campo) {
    var valor = campo.value.replace(/\D/g, '');
    if(valor.length > 3) valor = valor.replace(/^(\d{3})/, '$1.');
    if(valor.length > 7) valor = valor.replace(/^(\d{3})\.(\d{3})/, '$1.$2.');
    if(valor.length > 11) valor = valor.replace(/^(\d{3})\.(\d{3})\.(\d{3})/, '$1.$2.$3-');
    campo.value = valor.substring(0, 14);
}

function confirmarExclusao() {
    if(confirm('Tem certeza que deseja excluir este funcionário?\nEsta ação não pode ser desfeita.')) {
        document.getElementById('formExcluir').submit();
    }
}
</script>

<?php require_once 'footer.php'; ?>