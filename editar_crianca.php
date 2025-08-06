<?php
require_once 'conexao.php';
require_once 'header.php';


// Verifica se o ID foi passado
if(!isset($_GET['id'])) {
    $_SESSION['mensagem'] = ['tipo' => 'danger', 'texto' => 'ID da criança não informado'];
    header("Location: listar_responsavel.php");
    exit;
}

$id = (int)$_GET['id'];

// Processa o formulário de edição
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dados = [
            'nome_crianca' => htmlspecialchars(trim($_POST['nome'])),
            'sexo' => in_array($_POST['sexo'], ['M', 'F', 'O']) ? $_POST['sexo'] : 'O',
            'data_nascimento' => $_POST['data_nascimento'],
            'observacoes' => $_POST['observacoes'],
            'id_responsavel' => isset($_POST['id_responsavel']) ? (int)$_POST['id_responsavel'] : null,
            'id' => $id
        ];

        // Validações
        if(empty($dados['nome_crianca'])) throw new Exception("Nome é obrigatório");

        $sql = "UPDATE crianca SET 
                nome_crianca = :nome_crianca, 
                sexo = :sexo, 
                data_nascimento = :data_nascimento, 
                observacoes = :observacoes, 
                ID_responsavel = :id_responsavel 
                WHERE ID_crianca = :id";

        $stmt = $conn->prepare($sql);
        $stmt->execute($dados);

        $_SESSION['mensagem'] = ['tipo' => 'success', 'texto' => 'Criança atualizada com sucesso!'];
        header("Location: listar_responsavel.php");
        exit;
    } catch(Exception $e) {
        $_SESSION['mensagem'] = ['tipo' => 'danger', 'texto' => 'Erro: ' . $e->getMessage()];
    }
}

// Busca os dados da criança
try {
    $stmt = $conn->prepare("SELECT * FROM crianca WHERE ID_crianca = ?");
    $stmt->execute([$id]);
    $crianca = $stmt->fetch();

    if(!$crianca) {
        $_SESSION['mensagem'] = ['tipo' => 'danger', 'texto' => 'Criança não encontrada'];
        header("Location: listar_responsavel.php");
        exit;
    }

    // Busca os responsáveis disponíveis
    $responsaveis = $conn->query("SELECT ID_responsavel, nome FROM responsavel ORDER BY nome");
} catch(Exception $e) {
    $_SESSION['mensagem'] = ['tipo' => 'danger', 'texto' => 'Erro ao buscar dados'];
    header("Location: listar_responsavel.php");
    exit;
}
?>

<div class="container">
    <h2><i class="glyphicon glyphicon-edit"></i> Editar Criança</h2>
    
    <?php if(isset($_SESSION['mensagem'])): ?>
        <div class="alert alert-<?= $_SESSION['mensagem']['tipo'] ?>">
            <?= $_SESSION['mensagem']['texto'] ?>
        </div>
        <?php unset($_SESSION['mensagem']); ?>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Nome da Criança*</label>
            <input type="text" name="nome" class="form-control" required 
                   value="<?= htmlspecialchars($crianca['nome_crianca']) ?>">
        </div>
        
        <div class="form-group">
            <label>Sexo*</label>
            <select name="sexo" class="form-control" required>
                <option value="M" <?= $crianca['sexo'] == 'M' ? 'selected' : '' ?>>Masculino</option>
                <option value="F" <?= $crianca['sexo'] == 'F' ? 'selected' : '' ?>>Feminino</option>
                <option value="O" <?= $crianca['sexo'] == 'O' ? 'selected' : '' ?>>Outro</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Data de Nascimento*</label>
            <input type="date" name="data_nascimento" class="form-control" required
                   value="<?= htmlspecialchars($crianca['data_nascimento']) ?>">
        </div>
        
        <div class="form-group">
            <label>Responsável</label>
            <select name="id_responsavel" class="form-control">
                <option value="">-- Selecione um responsável --</option>
                <?php while($resp = $responsaveis->fetch()): ?>
                    <option value="<?= $resp['ID_responsavel'] ?>" 
                        <?= $resp['ID_responsavel'] == $crianca['ID_responsavel'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($resp['nome']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Observações</label>
            <textarea name="observacoes" class="form-control"><?= htmlspecialchars($crianca['observacoes']) ?></textarea>
        </div>
        
        <button type="submit" class="btn btn-primary">
            <i class="glyphicon glyphicon-floppy-disk"></i> Salvar Alterações
        </button>
        <a href="listar_responsavel.php" class="btn btn-default">Cancelar</a>
    </form>
</div>

<?php require_once 'footer.php'; ?>