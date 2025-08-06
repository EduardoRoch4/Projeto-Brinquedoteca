<?php
session_start();
require_once 'conexao.php';
require_once 'header.php';

// Verifica login (adicione sua verificação de login aqui)

$conn = require 'conexao.php';

// Processa filtros
$filtro_tipo = $_GET['tipo'] ?? '';
$filtro_disponivel = $_GET['disponivel'] ?? '';
$busca = trim($_GET['busca'] ?? '');
$mostrar_desativados = isset($_GET['mostrar_desativados']);

// Consulta SQL
$sql = "SELECT i.*, f.nome_fornecedor, a.nome_area 
        FROM item i
        LEFT JOIN fornecedor f ON i.ID_fornecedor = f.ID_fornecedor
        LEFT JOIN area_desenvolvimento a ON i.ID_area_desenvolvimento = a.ID_area
        WHERE 1=1";  // Removido o filtro de ativo

if (!$mostrar_desativados) {
    $sql .= " AND i.ativo = 1";
}

$params = [];

if (!empty($filtro_tipo)) {
    $sql .= " AND i.tipo_item = :tipo";
    $params[':tipo'] = $filtro_tipo;
}

if (!empty($filtro_disponivel)) {
    $sql .= ($filtro_disponivel === 'sim') 
        ? " AND i.quantidade_disponivel > 0" 
        : " AND i.quantidade_disponivel <= 0";
}

if (!empty($busca)) {
    $sql .= " AND (i.nome_item LIKE :busca OR i.marca_item LIKE :busca)";
    $params[':busca'] = "%$busca%";
}

$sql .= " ORDER BY i.nome_item";

try {
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['mensagem'] = ['tipo' => 'danger', 'texto' => 'Erro ao carregar itens: ' . $e->getMessage()];
    $itens = [];
}

// Processa "exclusão" (na verdade, desativação)
if (isset($_GET['excluir'])) {
    try {
        $id = (int)$_GET['excluir'];
        
        // Marca o item como inativo (ativo = 0)
        $stmt = $conn->prepare("UPDATE item SET ativo = 0 WHERE ID_item = ?");
        
        if ($stmt->execute([$id])) {
            $_SESSION['mensagem'] = ['tipo' => 'success', 'texto' => 'Item desativado com sucesso!'];
        } else {
            $_SESSION['mensagem'] = ['tipo' => 'danger', 'texto' => 'Falha ao desativar o item.'];
        }
        
        header('Location: gerencia_item.php' . ($mostrar_desativados ? '?mostrar_desativados=1' : ''));
        exit;
        
    } catch (PDOException $e) {
        $_SESSION['mensagem'] = ['tipo' => 'danger', 'texto' => 'Erro: ' . $e->getMessage()];
        header('Location: gerencia_item.php' . ($mostrar_desativados ? '?mostrar_desativados=1' : ''));
        exit;
    }
}

// Processa reativação
if (isset($_GET['reativar'])) {
    try {
        $id = (int)$_GET['reativar'];
        
        // Marca o item como ativo (ativo = 1)
        $stmt = $conn->prepare("UPDATE item SET ativo = 1 WHERE ID_item = ?");
        
        if ($stmt->execute([$id])) {
            $_SESSION['mensagem'] = ['tipo' => 'success', 'texto' => 'Item reativado com sucesso!'];
        } else {
            $_SESSION['mensagem'] = ['tipo' => 'danger', 'texto' => 'Falha ao reativar o item.'];
        }
        
        header('Location: gerencia_item.php' . ($mostrar_desativados ? '?mostrar_desativados=1' : ''));
        exit;
        
    } catch (PDOException $e) {
        $_SESSION['mensagem'] = ['tipo' => 'danger', 'texto' => 'Erro: ' . $e->getMessage()];
        header('Location: gerencia_item.php' . ($mostrar_desativados ? '?mostrar_desativados=1' : ''));
        exit;
    }
}
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3><i class="fas fa-boxes"></i> Lista de Itens</h3>
                        <div>
                            <a href="index.php" class="btn btn-light mr-2">
                                <i class="fas fa-home"></i> Voltar
                            </a>
                            <a href="cadastro_jogo_brinquedo.php" class="btn btn-success mr-2">
                                <i class="fas fa-plus"></i> Novo Item
                            </a>
                            <a href="gerencia_item.php?<?= $mostrar_desativados ? '' : 'mostrar_desativados=1' ?>" class="btn <?= $mostrar_desativados ? 'btn-warning' : 'btn-info' ?>">
                                <i class="fas <?= $mostrar_desativados ? 'fa-eye' : 'fa-eye-slash' ?>"></i>
                                <?= $mostrar_desativados ? 'Mostrar Ativos' : 'Mostrar Desativados' ?>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
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

                    <!-- Filtros -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="form-inline">
                                <div class="form-group mr-3">
                                    <label for="tipo" class="mr-2">Tipo:</label>
                                    <select id="tipo" name="tipo" class="form-control">
                                        <option value="">Todos</option>
                                        <option value="Jogo" <?= $filtro_tipo === 'Jogo' ? 'selected' : '' ?>>Jogos</option>
                                        <option value="Brinquedo" <?= $filtro_tipo === 'Brinquedo' ? 'selected' : '' ?>>Brinquedos</option>
                                        <option value="Material" <?= $filtro_tipo === 'Material' ? 'selected' : '' ?>>Materiais</option>
                                        <option value="Equipamento" <?= $filtro_tipo === 'Equipamento' ? 'selected' : '' ?>>Equipamentos</option>
                                    </select>
                                </div>
                                
                                <div class="form-group mr-3">
                                    <label for="disponivel" class="mr-2">Disponibilidade:</label>
                                    <select id="disponivel" name="disponivel" class="form-control">
                                        <option value="">Todos</option>
                                        <option value="sim" <?= $filtro_disponivel === 'sim' ? 'selected' : '' ?>>Disponíveis</option>
                                        <option value="nao" <?= $filtro_disponivel === 'nao' ? 'selected' : '' ?>>Indisponíveis</option>
                                    </select>
                                </div>
                                
                                <div class="form-group mr-3">
                                    <div class="input-group">
                                        <input type="text" name="busca" class="form-control" placeholder="Buscar..." value="<?= htmlspecialchars($busca) ?>">
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="submit">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <a href="gerencia_item.php" class="btn btn-secondary">
                                    <i class="fas fa-sync-alt"></i> Limpar
                                </a>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Tabela de itens -->
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead class="thead-dark">
                                <tr>
                                    <th width="120">Imagem</th>
                                    <th>Nome</th>
                                    <th>Tipo</th>
                                    <th>Marca</th>
                                    <th>Disponível</th>
                                    <th>Fornecedor</th>
                                    <th>Área</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($itens)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">
                                            <?= ($filtro_tipo || $filtro_disponivel || $busca) 
                                                ? 'Nenhum item encontrado com os filtros aplicados' 
                                                : 'Nenhum item cadastrado' ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($itens as $item): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($item['imagem_path']) && file_exists($item['imagem_path'])): ?>
                                                <img src="<?= htmlspecialchars($item['imagem_path']) ?>" 
                                                    alt="<?= htmlspecialchars($item['nome_item']) ?>" 
                                                    style="max-width: 100px; max-height: 100px; object-fit: cover;"
                                                    class="img-thumbnail">
                                            <?php else: ?>
                                                <div class="text-center text-muted" style="width: 100px; height: 100px; display: flex; align-items: center; justify-content: center; background: #f8f9fa;">
                                                    <i class="fas fa-image fa-2x" style="opacity: 0.3;"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($item['nome_item']) ?></td>
                                        <td><?= htmlspecialchars($item['tipo_item']) ?></td>
                                        <td><?= htmlspecialchars($item['marca_item'] ?? '-') ?></td>
                                        <td>
                                            <?php if ($item['quantidade_disponivel'] > 0): ?>
                                                <span class="badge badge-success"><?= $item['quantidade_disponivel'] ?>/<?= $item['quantidade_total'] ?></span>
                                            <?php else: ?>
                                                <span class="badge badge-danger"><?= $item['quantidade_disponivel'] ?>/<?= $item['quantidade_total'] ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($item['nome_fornecedor'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($item['nome_area'] ?? '-') ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <?php if ($item['ativo']): ?>
                                                    <a href="editar_item.php?id=<?= $item['ID_item'] ?>" class="btn btn-warning" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="gerencia_item.php?excluir=<?= $item['ID_item'] ?>" class="btn btn-danger" title="Desativar" onclick="return confirm('Tem certeza que deseja desativar este item?')">
                                                        <i class="fas fa-eye-slash"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="gerencia_item.php?reativar=<?= $item['ID_item'] ?>" class="btn btn-success" title="Reativar" onclick="return confirm('Tem certeza que deseja reativar este item?')">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
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
// Aplica filtros automaticamente ao mudar seleção
document.getElementById('tipo').addEventListener('change', function() {
    this.form.submit();
});

document.getElementById('disponivel').addEventListener('change', function() {
    this.form.submit();
});
</script>

<?php 
require_once 'footer.php';
?>