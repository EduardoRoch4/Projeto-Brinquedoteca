<?php 
require_once 'conexao.php';
require_once 'header.php';

// Inicia sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper functions
function formatSexo($sexo) {
    if ($sexo === 'M') {
        return 'Masculino';
    } elseif ($sexo === 'F') {
        return 'Feminino';
    } else {
        return 'Outro';
    }
}

function formatDate($date, $format = 'd/m/Y') {
    if (empty($date) || $date === '0000-00-00') {
        return 'N/A';
    }
    
    try {
        $dateObj = new DateTime($date);
        return $dateObj->format($format);
    } catch (Exception $e) {
        return 'Data inválida';
    }
}

function mask($val, $mask) {
    $masked = '';
    $k = 0;
    for ($i = 0; $i < strlen($mask); $i++) {
        if ($mask[$i] == '#') {
            if (isset($val[$k])) {
                $masked .= $val[$k++];
            }
        } else {
            if (isset($mask[$i])) {
                $masked .= $mask[$i];
            }
        }
    }
    return $masked;
}

// Processa a exclusão se solicitada
if(isset($_POST['delete'])){
    try {
        $sql = "DELETE FROM responsavel WHERE ID_responsavel = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $_POST['userid'], PDO::PARAM_INT);
        
        if($stmt->execute()){
            $_SESSION['mensagem'] = [
                'tipo' => 'success',
                'texto' => 'Responsável removido com sucesso!'
            ];
            header('Location: listar_responsavel.php');
            exit;
        }
    } catch(PDOException $e) {
        $_SESSION['mensagem'] = [
            'tipo' => 'danger',
            'texto' => 'Erro ao remover responsável: ' . $e->getMessage()
        ];
    }
}

// Consulta responsáveis
try {
    $sql_responsaveis = "SELECT r.*, 
                        (SELECT COUNT(*) FROM crianca c WHERE c.ID_responsavel = r.ID_responsavel) as total_criancas
                        FROM responsavel r ORDER BY nome ASC";
    $responsaveis = $conn->query($sql_responsaveis)->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $_SESSION['mensagem'] = [
        'tipo' => 'danger',
        'texto' => 'Erro no banco de dados: ' . $e->getMessage()
    ];
}
?>

<div class="container">
    <!-- Botões de navegação -->
    <div class="d-flex justify-content-between mb-4">
        <button class="btn btn-secondary" onclick="window.location.href='index.php'">
            <i class="fas fa-home"></i> Voltar à Página Inicial
        </button>
        <div>
            <button class="btn btn-primary mr-2" onclick="window.location.href='cadastroPessoa.php'">
                <i class="fas fa-plus"></i> Novo Responsável
            </button>
            <button class="btn btn-success" onclick="window.location.href='listar_crianca.php'">
                <i class="fas fa-child"></i> Ver Crianças
            </button>
        </div>
    </div>

    <!-- Mensagens de feedback -->
    <?php if(isset($_SESSION['mensagem'])): ?>
        <div class="alert alert-<?= $_SESSION['mensagem']['tipo'] ?> alert-dismissible fade show">
            <?= $_SESSION['mensagem']['texto'] ?>
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
        <?php unset($_SESSION['mensagem']); ?>
    <?php endif; ?>

    <!-- Lista de Responsáveis -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h3><i class="fas fa-user"></i> Responsáveis Cadastrados</h3>
        </div>
        <div class="card-body">
            <?php if(!empty($responsaveis)): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>Nome</th>
                                <th>CPF</th>
                                <th>Sexo</th>
                                <th>E-mail</th>
                                <th>Data Nasc.</th>
                                <th>Crianças Vinculadas</th>
                                <th width="120px">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($responsaveis as $responsavel): ?>
                            <tr>
                                <td><?= htmlspecialchars($responsavel['nome']) ?></td>
                                <td><?= mask($responsavel['CPF'], '###.###.###-##') ?></td>
                                <td><?= formatSexo($responsavel['sexo']) ?></td>
                                <td><?= htmlspecialchars($responsavel['email']) ?></td>
                                <td><?= formatDate($responsavel['data_nascimento']) ?></td>
                                <td class="text-center"><?= $responsavel['total_criancas'] ?></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="editar_responsavel.php?id=<?= $responsavel['ID_responsavel'] ?>" 
                                           class="btn btn-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="userid" value="<?= $responsavel['ID_responsavel'] ?>">
                                            <button type="submit" name="delete" class="btn btn-danger" title="Excluir"
                                                    onclick="return confirm('Tem certeza que deseja excluir este responsável? Todas as crianças vinculadas serão desassociadas.');">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">Nenhum responsável cadastrado encontrado.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
require_once 'footer.php';