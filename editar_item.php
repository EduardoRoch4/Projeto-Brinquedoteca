<?php
session_start();
require_once 'conexao.php';

// Verifica se o ID do item foi passado
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['mensagem'] = [
        'tipo' => 'danger',
        'texto' => 'ID do item inválido!'
    ];
    header('Location: gerencia_item.php');
    exit;
}

$id_item = (int)$_GET['id'];
$conn = require 'conexao.php';
$erro = null;
$item = null;

// Busca os dados do item
try {
    $stmt = $conn->prepare("SELECT * FROM item WHERE ID_item = ?");
    $stmt->execute([$id_item]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        $_SESSION['mensagem'] = [
            'tipo' => 'danger',
            'texto' => 'Item não encontrado!'
        ];
        header('Location: gerencia_item.php');
        exit;
    }
} catch (PDOException $e) {
    $erro = "Erro ao carregar item: " . $e->getMessage();
}

// Buscar fornecedores para o select
$fornecedores = [];
try {
    $stmt = $conn->query("SELECT ID_fornecedor, nome_fornecedor FROM fornecedor ORDER BY nome_fornecedor");
    $fornecedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao carregar fornecedores: " . $e->getMessage();
}

// Buscar áreas de desenvolvimento para o select
$areas = [];
try {
    $stmt = $conn->query("SELECT ID_area, nome_area FROM area_desenvolvimento ORDER BY nome_area");
    $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao carregar áreas de desenvolvimento: " . $e->getMessage();
}

// Processa o formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar'])) {
    try {
        // Validações básicas
        if (empty($_POST['nome_item'])) {
            throw new Exception("O nome do item é obrigatório");
        }
        
        if (!isset($_POST['tipo_item'])) {
            throw new Exception("O tipo do item é obrigatório");
        }
        
        if (empty($_POST['ID_fornecedor'])) {
            throw new Exception("O fornecedor é obrigatório");
        }
        
        // Limpa e formata os dados
        $valor_aquisicao = isset($_POST['valor_aquisicao']) ? 
            str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor_aquisicao']) : 
            null;
        
        $numero_NF = isset($_POST['numero_NF']) ? preg_replace('/[^0-9]/', '', $_POST['numero_NF']) : null;
        
        // Verifica se a área de desenvolvimento existe
        $ID_area_desenvolvimento = null;
        if (!empty($_POST['ID_area_desenvolvimento'])) {
            $stmt = $conn->prepare("SELECT ID_area FROM area_desenvolvimento WHERE ID_area = ?");
            $stmt->execute([$_POST['ID_area_desenvolvimento']]);
            if ($stmt->fetch()) {
                $ID_area_desenvolvimento = (int)$_POST['ID_area_desenvolvimento'];
            }
        }

        // Prepara os dados para atualização
        $dados = [
            'nome_item' => htmlspecialchars(trim($_POST['nome_item'])),
            'tipo_item' => $_POST['tipo_item'],
            'descricao' => isset($_POST['descricao']) ? htmlspecialchars(trim($_POST['descricao'])) : null,
            'marca_item' => isset($_POST['marca_item']) ? htmlspecialchars(trim($_POST['marca_item'])) : null,
            'numero_serie' => isset($_POST['numero_serie']) ? htmlspecialchars(trim($_POST['numero_serie'])) : null,
            'numero_NF' => $numero_NF,
            'data_aquisicao' => isset($_POST['data_aquisicao']) ? $_POST['data_aquisicao'] : null,
            'valor_aquisicao' => $valor_aquisicao ? (float)$valor_aquisicao : null,
            'quantidade_total' => isset($_POST['quantidade_total']) ? (int)$_POST['quantidade_total'] : 1,
            'quantidade_disponivel' => isset($_POST['quantidade_disponivel']) ? (int)$_POST['quantidade_disponivel'] : 1,
            'ID_fornecedor' => (int)$_POST['ID_fornecedor'],
            'ID_area_desenvolvimento' => $ID_area_desenvolvimento,
            'ativo' => isset($_POST['ativo']) ? 1 : 0,
            'ID_item' => $id_item
        ];

        // Query SQL de atualização
        $sql = "UPDATE item SET
                    nome_item = :nome_item,
                    tipo_item = :tipo_item,
                    descricao = :descricao,
                    marca_item = :marca_item,
                    numero_serie = :numero_serie,
                    numero_NF = :numero_NF,
                    data_aquisicao = :data_aquisicao,
                    valor_aquisicao = :valor_aquisicao,
                    quantidade_total = :quantidade_total,
                    quantidade_disponivel = :quantidade_disponivel,
                    ID_fornecedor = :ID_fornecedor,
                    ID_area_desenvolvimento = :ID_area_desenvolvimento,
                    ativo = :ativo
                WHERE ID_item = :ID_item";
        
        $stmt = $conn->prepare($sql);
        
        if ($stmt->execute($dados)) {
            $_SESSION['mensagem'] = [
                'tipo' => 'success',
                'texto' => 'Item atualizado com sucesso!'
            ];
            header('Location: gerencia_item.php');
            exit;
        } else {
            throw new Exception("Erro ao atualizar no banco de dados");
        }
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

require_once 'header.php';
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

    <div class="row">
        <div class="col-md-10 offset-md-1">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3><i class="fas fa-edit"></i> Editar Item: <?= htmlspecialchars($item['nome_item']) ?></h3>
                </div>
                <div class="card-body">
                    <form method="POST" id="formItem">
                        <!-- Informações Básicas -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nome_item">Nome do Item*</label>
                                    <input type="text" id="nome_item" name="nome_item" class="form-control" 
                                           value="<?= htmlspecialchars($item['nome_item']) ?>" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tipo_item">Tipo do Item*</label>
                                    <select id="tipo_item" name="tipo_item" class="form-control" required>
                                        <option value="">Selecione...</option>
                                        <option value="Jogo" <?= $item['tipo_item'] === 'Jogo' ? 'selected' : '' ?>>Jogo</option>
                                        <option value="Brinquedo" <?= $item['tipo_item'] === 'Brinquedo' ? 'selected' : '' ?>>Brinquedo</option>
                                        <option value="Material" <?= $item['tipo_item'] === 'Material' ? 'selected' : '' ?>>Material</option>
                                        <option value="Equipamento" <?= $item['tipo_item'] === 'Equipamento' ? 'selected' : '' ?>>Equipamento</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="marca_item">Marca/Fabricante</label>
                                    <input type="text" id="marca_item" name="marca_item" class="form-control"
                                           value="<?= htmlspecialchars($item['marca_item']) ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="numero_serie">Número de Série</label>
                                    <input type="text" id="numero_serie" name="numero_serie" class="form-control"
                                           value="<?= htmlspecialchars($item['numero_serie']) ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="descricao">Descrição</label>
                            <textarea id="descricao" name="descricao" class="form-control" rows="3"><?= htmlspecialchars($item['descricao']) ?></textarea>
                        </div>
                        
                        <!-- Quantidade e Status -->
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="quantidade_total">Quantidade Total*</label>
                                    <input type="number" id="quantidade_total" name="quantidade_total" 
                                           class="form-control" min="1" value="<?= $item['quantidade_total'] ?>" required>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="quantidade_disponivel">Quantidade Disponível*</label>
                                    <input type="number" id="quantidade_disponivel" name="quantidade_disponivel" 
                                           class="form-control" min="0" value="<?= $item['quantidade_disponivel'] ?>" required>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="ativo">Status</label>
                                    <select id="ativo" name="ativo" class="form-control">
                                        <option value="1" <?= $item['ativo'] == 1 ? 'selected' : '' ?>>Ativo</option>
                                        <option value="0" <?= $item['ativo'] == 0 ? 'selected' : '' ?>>Inativo</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Imagem Atual:</label>
                                    <?php if (!empty($item['imagem_path'])): ?>
                                        <div>
                                            <img src="<?= htmlspecialchars($item['imagem_path']) ?>" class="img-thumbnail" style="max-height: 100px;">
                                            <a href="remover_imagem.php?id=<?= $item['ID_item'] ?>" class="btn btn-sm btn-danger ml-2">
                                                <i class="fas fa-trash"></i> Remover
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted">Nenhuma imagem cadastrada</p>
                                    <?php endif; ?>
                                    <input type="file" id="nova_imagem" name="nova_imagem" class="form-control-file mt-2">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Informações de Aquisição -->
                        <h4 class="mt-4 mb-3">Informações de Aquisição</h4>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="ID_fornecedor">Fornecedor*</label>
                                    <select id="ID_fornecedor" name="ID_fornecedor" class="form-control" required>
                                        <option value="">Selecione um fornecedor...</option>
                                        <?php foreach($fornecedores as $fornecedor): ?>
                                            <option value="<?= $fornecedor['ID_fornecedor'] ?>" 
                                                <?= $item['ID_fornecedor'] == $fornecedor['ID_fornecedor'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($fornecedor['nome_fornecedor']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="ID_area_desenvolvimento">Área de Desenvolvimento</label>
                                    <select id="ID_area_desenvolvimento" name="ID_area_desenvolvimento" class="form-control">
                                        <option value="">Selecione uma área...</option>
                                        <?php foreach($areas as $area): ?>
                                            <option value="<?= $area['ID_area'] ?>" 
                                                <?= $item['ID_area_desenvolvimento'] == $area['ID_area'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($area['nome_area']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="data_aquisicao">Data de Aquisição</label>
                                    <input type="date" id="data_aquisicao" name="data_aquisicao" class="form-control"
                                           value="<?= $item['data_aquisicao'] ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="valor_aquisicao">Valor de Aquisição (R$)</label>
                                    <input type="text" id="valor_aquisicao" name="valor_aquisicao" class="form-control money"
                                           value="<?= $item['valor_aquisicao'] ? 'R$ ' . number_format($item['valor_aquisicao'], 2, ',', '.') : '' ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="numero_NF">Número da Nota Fiscal</label>
                                    <input type="text" id="numero_NF" name="numero_NF" class="form-control"
                                           value="<?= htmlspecialchars($item['numero_NF']) ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mt-4">
                            <button type="submit" name="editar" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Salvar Alterações
                            </button>
                            <a href="gerencia_item.php" class="btn btn-secondary btn-lg">
                                <i class="fas fa-arrow-left"></i> Voltar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Formatação do valor monetário
document.getElementById('valor_aquisicao').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    value = (value / 100).toFixed(2) + '';
    value = value.replace(".", ",");
    value = value.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
    value = value.replace(/(\d)(\d{3}),/g, "$1.$2,");
    e.target.value = value ? 'R$ ' + value : '';
});

// Validação no envio
document.getElementById('formItem').addEventListener('submit', function(e) {
    let valid = true;
    
    const campos = [
        {id: 'nome_item', msg: 'Nome do item é obrigatório'},
        {id: 'tipo_item', msg: 'Tipo do item é obrigatório'},
        {id: 'ID_fornecedor', msg: 'Fornecedor é obrigatório'},
        {id: 'quantidade_total', msg: 'Quantidade total deve ser pelo menos 1', val: v => parseInt(v) >= 1},
        {id: 'quantidade_disponivel', msg: 'Quantidade disponível não pode ser negativa', val: v => parseInt(v) >= 0}
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
    
    // Valida quantidade disponível <= quantidade total
    const qtdTotal = parseInt(document.getElementById('quantidade_total').value);
    const qtdDisp = parseInt(document.getElementById('quantidade_disponivel').value);
    
    if(qtdDisp > qtdTotal) {
        alert('Quantidade disponível não pode ser maior que a quantidade total');
        document.getElementById('quantidade_disponivel').focus();
        valid = false;
    }
    
    if(!valid) {
        e.preventDefault();
    }
});
</script>

<?php 
require_once 'footer.php';
?>