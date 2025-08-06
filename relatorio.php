<?php
session_start();
require_once 'conexao.php';
require_once 'header.php';



$conn = require 'conexao.php';
$relatorio = '';
$dados = [];

// Processa o tipo de relatório solicitado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tipo_relatorio'])) {
    $tipo = $_POST['tipo_relatorio'];
    $data_inicio = $_POST['data_inicio'] ?? null;
    $data_fim = $_POST['data_fim'] ?? null;
    
    try {
        switch ($tipo) {
            case 'itens_mais_emprestados':
                $sql = "SELECT i.nome_item, COUNT(e.ID_emprestimo) as total_emprestimos
                        FROM item i
                        LEFT JOIN emprestimo e ON i.ID_item = e.ID_item
                        WHERE i.ativo = 1";
                
                if ($data_inicio && $data_fim) {
                    $sql .= " AND e.data_emprestimo BETWEEN :data_inicio AND :data_fim";
                }
                
                $sql .= " GROUP BY i.ID_item
                          ORDER BY total_emprestimos DESC
                          LIMIT 10";
                
                $stmt = $conn->prepare($sql);
                if ($data_inicio && $data_fim) {
                    $stmt->bindParam(':data_inicio', $data_inicio);
                    $stmt->bindParam(':data_fim', $data_fim);
                }
                $stmt->execute();
                $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $relatorio = 'itens_mais_emprestados';
                break;
                
            case 'usuarios_ativos':
                $sql = "SELECT r.nome as 'Responsável', COUNT(e.ID_emprestimo) as 'Total de Empréstimos'
                        FROM responsavel r
                        LEFT JOIN emprestimo e ON r.ID_responsavel = e.ID_responsavel
                        WHERE 1=1";
                
                if ($data_inicio && $data_fim) {
                    $sql .= " AND e.data_emprestimo BETWEEN :data_inicio AND :data_fim";
                }
                
                $sql .= " GROUP BY r.ID_responsavel
                          ORDER BY COUNT(e.ID_emprestimo) DESC
                          LIMIT 10";
                
                $stmt = $conn->prepare($sql);
                if ($data_inicio && $data_fim) {
                    $stmt->bindParam(':data_inicio', $data_inicio);
                    $stmt->bindParam(':data_fim', $data_fim);
                }
                $stmt->execute();
                $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $relatorio = 'usuarios_ativos';
                break;
                
            case 'status_itens':
                $sql = "SELECT 
                            tipo_item,
                            COUNT(*) as total,
                            SUM(CASE WHEN quantidade_disponivel > 0 THEN 1 ELSE 0 END) as disponiveis,
                            SUM(CASE WHEN quantidade_disponivel <= 0 THEN 1 ELSE 0 END) as indisponiveis
                        FROM item
                        WHERE ativo = 1
                        GROUP BY tipo_item";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $relatorio = 'status_itens';
                break;
                
            case 'emprestimos_periodo':
                if (!$data_inicio || !$data_fim) {
                    throw new Exception("Para este relatório, é necessário informar o período");
                }
                
                $sql = "SELECT 
                            DATE_FORMAT(data_emprestimo, '%Y-%m-%d') as dia,
                            COUNT(*) as total_emprestimos
                        FROM emprestimo
                        WHERE data_emprestimo BETWEEN :data_inicio AND :data_fim
                        GROUP BY dia
                        ORDER BY dia";
                
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':data_inicio', $data_inicio);
                $stmt->bindParam(':data_fim', $data_fim);
                $stmt->execute();
                $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $relatorio = 'emprestimos_periodo';
                break;
        }
        
    } catch (PDOException $e) {
        $_SESSION['mensagem'] = ['tipo' => 'danger', 'texto' => 'Erro ao gerar relatório: ' . $e->getMessage()];
    } catch (Exception $e) {
        $_SESSION['mensagem'] = ['tipo' => 'warning', 'texto' => $e->getMessage()];
    }
}
?>




<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="index.php" class="btn btn-light">
                            <i class="fas fa-home"></i> Voltar ao Início
                        </a>
                    </div>
                </div>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3><i class="fas fa-chart-bar"></i> Relatórios do Sistema</h3>
                </div>
                <div class="card-body">
                    <?php if(isset($_SESSION['mensagem'])): ?>
                        <div class="alert alert-<?= $_SESSION['mensagem']['tipo'] ?>">
                            <?= $_SESSION['mensagem']['texto'] ?>
                        </div>
                        <?php unset($_SESSION['mensagem']); ?>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header bg-secondary text-white">
                                    <h4>Selecione o Relatório</h4>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="form-group">
                                            <label for="tipo_relatorio">Tipo de Relatório:</label>
                                            <select class="form-control" id="tipo_relatorio" name="tipo_relatorio" required>
                                                <option value="">Selecione...</option>
                                                <option value="itens_mais_emprestados" <?= $relatorio === 'itens_mais_emprestados' ? 'selected' : '' ?>>Itens Mais Emprestados</option>
                                                <option value="usuarios_ativos" <?= $relatorio === 'usuarios_ativos' ? 'selected' : '' ?>>Responsáveis Mais Ativos</option>
                                                <option value="status_itens" <?= $relatorio === 'status_itens' ? 'selected' : '' ?>>Status dos Itens</option>
                                                <option value="emprestimos_periodo" <?= $relatorio === 'emprestimos_periodo' ? 'selected' : '' ?>>Empréstimos por Período</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="data_inicio">Data Início:</label>
                                            <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?= $_POST['data_inicio'] ?? '' ?>">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="data_fim">Data Fim:</label>
                                            <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?= $_POST['data_fim'] ?? '' ?>">
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary btn-block">
                                            <i class="fas fa-chart-line"></i> Gerar Relatório
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h4>Resultado do Relatório</h4>
                                </div>
                                <div class="card-body">
                                    <?php if ($relatorio && !empty($dados)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered">
                                                <thead class="thead-dark">
                                                    <tr>
                                                        <?php
                                                        // Cabeçalhos dinâmicos baseados no tipo de relatório
                                                        switch ($relatorio) {
                                                            case 'itens_mais_emprestados':
                                                                echo '<th>Item</th><th>Total de Empréstimos</th>';
                                                                break;
                                                            case 'usuarios_ativos':
                                                                echo '<th>Responsável</th><th>Total de Empréstimos</th>';
                                                                break;
                                                            case 'status_itens':
                                                                echo '<th>Tipo de Item</th><th>Total</th><th>Disponíveis</th><th>Indisponíveis</th>';
                                                                break;
                                                            case 'emprestimos_periodo':
                                                                echo '<th>Data</th><th>Total de Empréstimos</th>';
                                                                break;
                                                        }
                                                        ?>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($dados as $linha): ?>
                                                        <tr>
                                                            <?php foreach ($linha as $valor): ?>
                                                                <td><?= htmlspecialchars($valor) ?></td>
                                                            <?php endforeach; ?>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <!-- Botão para exportar para Excel -->
                                        <form method="POST" action="exportar_relatorio.php" class="mt-3">
                                            <input type="hidden" name="tipo_relatorio" value="<?= $relatorio ?>">
                                            <input type="hidden" name="data_inicio" value="<?= $_POST['data_inicio'] ?? '' ?>">
                                            <input type="hidden" name="data_fim" value="<?= $_POST['data_fim'] ?? '' ?>">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-file-excel"></i> Exportar para Excel
                                            </button>
                                        </form>
                                        
                                    <?php elseif ($relatorio && empty($dados)): ?>
                                        <div class="alert alert-warning">
                                            Nenhum dado encontrado para os critérios selecionados.
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            Selecione um tipo de relatório e clique em "Gerar Relatório" para visualizar os dados.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Script para mostrar/ocultar campos de data conforme necessário
document.getElementById('tipo_relatorio').addEventListener('change', function() {
    const tipo = this.value;
    const dataFields = document.querySelectorAll('[name="data_inicio"], [name="data_fim"]');
    
    // Mostra campos de data apenas para relatórios que precisam
    if (tipo === 'emprestimos_periodo' || tipo === 'itens_mais_emprestados' || tipo === 'usuarios_ativos') {
        dataFields.forEach(field => {
            field.closest('.form-group').style.display = 'block';
            if (tipo === 'emprestimos_periodo') {
                field.required = true;
            } else {
                field.required = false;
            }
        });
    } else {
        dataFields.forEach(field => {
            field.closest('.form-group').style.display = 'none';
            field.required = false;
        });
    }
});

// Dispara o evento ao carregar a página
window.addEventListener('load', function() {
    document.getElementById('tipo_relatorio').dispatchEvent(new Event('change'));
});
</script>

<?php 
require_once 'footer.php';
?>