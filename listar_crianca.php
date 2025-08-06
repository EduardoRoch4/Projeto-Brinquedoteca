<?php 
require_once 'conexao.php';
require_once 'header.php';

// Inicia sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Funções auxiliares
function formatSexo($sexo) {
    $map = ['M' => 'Masculino', 'F' => 'Feminino', 'O' => 'Outro'];
    return $map[$sexo] ?? 'Não informado';
}

function formatDate($date, $format = 'd/m/Y') {
    if (empty($date) || $date === '0000-00-00') return 'N/A';
    try {
        return (new DateTime($date))->format($format);
    } catch (Exception $e) {
        return 'Data inválida';
    }
}

// Processa exclusão
if (isset($_POST['delete'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM crianca WHERE ID_crianca = ?");
        if ($stmt->execute([$_POST['userid']])) {
            $_SESSION['mensagem'] = ['tipo' => 'success', 'texto' => 'Criança removida com sucesso!'];
            header('Location: listar_crianca.php');
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['mensagem'] = ['tipo' => 'danger', 'texto' => 'Erro ao remover: ' . $e->getMessage()];
    }
}

// Consulta dados
try {
    // Verifica se a tabela existe
    $tabelaExiste = $conn->query("SHOW TABLES LIKE 'crianca'")->rowCount() > 0;
    
    if (!$tabelaExiste) {
        throw new Exception("Tabela 'crianca' não encontrada no banco de dados");
    }

    // Consulta responsáveis para o formulário
    $responsaveis = $conn->query("SELECT ID_responsavel, nome FROM responsavel ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

    // Consulta crianças com JOIN para o nome do responsável
    $sql = "SELECT c.*, r.nome as nome_responsavel 
            FROM crianca c 
            LEFT JOIN responsavel r ON c.ID_responsavel = r.ID_responsavel
            ORDER BY c.nome_crianca";
    
    $criancas = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error = $e->getMessage();
    echo "<!-- Erro: $error -->";
    $_SESSION['mensagem'] = ['tipo' => 'danger', 'texto' => $error];
    $criancas = [];
}
?>
<style>
/* Estilos modernos para a tabela */
.card {
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
    border: none;
}

.card-header {
    border-radius: 10px 10px 0 0 !important;
    padding: 1.25rem 1.5rem;
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
}

.card-header h4 {
    margin-bottom: 0;
    font-weight: 600;
}

.table-responsive {
    border-radius: 0 0 10px 10px;
    overflow: hidden;
}

.table {
    margin-bottom: 0;
    width: 100%;
}

.table thead th {
    background-color: #f8f9fc;
    color: #5a5c69;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #e3e6f0;
    padding: 1rem;
}

.table tbody td {
    padding: 1rem;
    vertical-align: middle;
    border-top: 1px solid #e3e6f0;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

.btn-group-sm > .btn, .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    border-radius: 0.2rem;
}

.btn-warning {
    background-color: #f6c23e;
    border-color: #f6c23e;
    color: #fff;
}

.btn-warning:hover {
    background-color: #dda20a;
    border-color: #dda20a;
}

.btn-danger {
    background-color: #e74a3b;
    border-color: #e74a3b;
}

.btn-danger:hover {
    background-color: #be2617;
    border-color: #be2617;
}

.alert {
    border-radius: 0.35rem;
    padding: 1rem 1.25rem;
}

/* Estilos para os botões de navegação */
.nav-buttons {
    margin-bottom: 1.5rem;
}

.btn-secondary {
    background-color: #858796;
    border-color: #858796;
}

.btn-secondary:hover {
    background-color: #6c757d;
    border-color: #6c757d;
}

.btn-primary {
    background-color: #4e73df;
    border-color: #4e73df;
}

.btn-primary:hover {
    background-color: #2e59d9;
    border-color: #2653d4;
}

/* Efeitos de transição suave */
.btn, .form-control, .alert {
    transition: all 0.3s ease;
}

/* Responsividade melhorada */
@media (max-width: 768px) {
    .table-responsive {
        border: 1px solid #e3e6f0;
        border-radius: 0.35rem;
    }
    
    .table thead {
        display: none;
    }
    
    .table tbody tr {
        display: block;
        margin-bottom: 1rem;
        border: 1px solid #e3e6f0;
        border-radius: 0.35rem;
    }
    
    .table tbody td {
        display: block;
        text-align: right;
        padding-left: 50%;
        position: relative;
        border-top: none;
    }
    
    .table tbody td::before {
        content: attr(data-label);
        position: absolute;
        left: 1rem;
        width: calc(50% - 1rem);
        padding-right: 1rem;
        font-weight: 600;
        text-align: left;
        color: #5a5c69;
    }
    
    .table tbody td:first-child {
        border-top: none;
    }
    
    .table tbody td:last-child {
        border-bottom: none;
    }
}
</style>



<div class="container">
    <!-- Cabeçalho e Mensagens -->
    <div class="d-flex justify-content-between mb-4">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-home"></i> Voltar
        </a>
        <a href="cadastroPessoa.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nova Criança
        </a>
    </div>

    <?php if (isset($_SESSION['mensagem'])): ?>
        <div class="alert alert-<?= $_SESSION['mensagem']['tipo'] ?> alert-dismissible fade show">
            <?= $_SESSION['mensagem']['texto'] ?>
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
        <?php unset($_SESSION['mensagem']); ?>
    <?php endif; ?>

    <!-- Listagem de Crianças -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4><i class="fas fa-child"></i> Crianças Cadastradas</h4>
        </div>
        
        <div class="card-body">
            <?php if (!empty($criancas)): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead class="thead-dark">
                            <tr>
                                <th>Nome</th>
                                <th>Sexo</th>
                                <th>Nascimento</th>
                                <th>Responsável</th>
                                <th>Observações</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($criancas as $c): ?>
                            <tr>
                                <td><?= htmlspecialchars($c['nome_crianca']) ?></td>
                                <td><?= formatSexo($c['sexo']) ?></td>
                                <td><?= formatDate($c['data_nascimento']) ?></td>
                                <td><?= htmlspecialchars($c['nome_responsavel'] ?? 'Não informado') ?></td>
                                <td><?= nl2br(htmlspecialchars($c['observacoes'] ?? 'Nenhuma')) ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="editar_crianca.php?id=<?= $c['ID_crianca'] ?>" class="btn btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="userid" value="<?= $c['ID_crianca'] ?>">
                                            <button type="submit" name="delete" class="btn btn-danger"
                                                    onclick="return confirm('Tem certeza?')">
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
                <div class="alert alert-info">
                    Nenhuma criança cadastrada. 
                    <a href="cadastro_crianca.php" class="alert-link">Clique aqui para cadastrar uma nova criança.</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>