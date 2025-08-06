<?php
require_once 'conexao.php';
require_once 'header.php';

// Funções de formatação
function formatarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}

// Obtém a conexão
$conn = require 'conexao.php';

// Consulta os funcionários ajustada para a nova estrutura
$sql = "SELECT 
            ID_funcionario,
            cargo_funcionario as cargo,
            nome_funcionario as nome,
            cpf,
            email_funcionario,
            nivel_acesso
        FROM funcionario
        ORDER BY nome_funcionario";
$stmt = $conn->prepare($sql);
$stmt->execute();
$funcionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3><i class="fas fa-users"></i> Lista de Funcionários</h3>
                        <div>
                            <a href="index.php" class="btn btn-light mr-2">
                                <i class="fas fa-home"></i> Voltar ao Início
                            </a>
                            <a href="cadastroFuncionario.php" class="btn btn-success">
                                <i class="fas fa-plus"></i> Novo Funcionário
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Nome</th>
                                    <th>CPF</th>
                                    <th>E-mail</th>
                                    <th>Cargo</th>
                                    <th>Nível de Acesso</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($funcionarios)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Nenhum funcionário cadastrado</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($funcionarios as $funcionario): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($funcionario['nome']) ?></td>
                                        <td><?= formatarCPF($funcionario['cpf']) ?></td>
                                        <td><?= htmlspecialchars($funcionario['email_funcionario']) ?></td>
                                        <td><?= htmlspecialchars($funcionario['cargo']) ?></td>
                                        <td>
                                            <?php
                                            switch($funcionario['nivel_acesso']) {
                                                case 1:
                                                    echo '<span class="badge bg-info">Funcionário</span>';
                                                    break;
                                                case 2:
                                                    echo '<span class="badge bg-warning">Gerente</span>';
                                                    break;
                                                case 3:
                                                    echo '<span class="badge bg-danger">Administrador</span>';
                                                    break;
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <a href="editar_Funcionario.php?id=<?= $funcionario['ID_funcionario'] ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i> Editar
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmarExclusao(id) {
    if(confirm('Tem certeza que deseja excluir este funcionário?')) {
        window.location.href = 'excluir_funcionario.php?id=' + id;
    }
}
</script>

<?php 
require_once 'footer.php';
?>