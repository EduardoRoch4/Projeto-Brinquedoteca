<?php
session_start();
require_once 'conexao.php';

// Verifica se o header.php já carrega o Bootstrap/jQuery, se não, adicione:
if (!isset($header_loaded)) {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sistema Brinquedoteca</title>
        <!-- Bootstrap 4.6 CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    </head>
    <body>
    <?php
}

// Inicia a sessão se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = require 'conexao.php';
$erro = null;

// Verificar e inserir áreas de desenvolvimento pré-definidas se não existirem
$areas_pre_definidas = ['Memória', 'Atenção', 'Linguagem', 'Raciocínio', 'Percepção', 'Resolução de Problemas'];

try {
    foreach ($areas_pre_definidas as $area) {
        // Verificar se a área já existe
        $stmt = $conn->prepare("SELECT ID_area FROM area_desenvolvimento WHERE nome_area = ?");
        $stmt->execute([$area]);
        
        if (!$stmt->fetch()) {
            // Se não existir, inserir
            $insert = $conn->prepare("INSERT INTO area_desenvolvimento (nome_area) VALUES (?)");
            $insert->execute([$area]);
        }
    }
} catch (PDOException $e) {
    $erro = "Erro ao verificar/inserir áreas pré-definidas: " . $e->getMessage();
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




if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addnew'])) {
    try {
        // Validações básicas
        if(empty($_POST['nome_item'])) {
            throw new Exception("O nome do item é obrigatório");
        }
        
        if(!isset($_POST['tipo_item'])) {
            throw new Exception("O tipo do item é obrigatório");
        }
        
        if(empty($_POST['ID_fornecedor'])) {
            throw new Exception("O fornecedor é obrigatório");
        }
        
        // Processar upload da imagem
        $imagem_path = null;
        if(isset($_FILES['imagem_path']) && $_FILES['imagem_path']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/itens/';
            if(!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $extensao = pathinfo($_FILES['imagem_path']['name'], PATHINFO_EXTENSION);
            $nomeUnico = uniqid('item_') . '.' . $extensao;
            $destino = $uploadDir . $nomeUnico;
            
            // Verificar se é uma imagem válida
            $check = getimagesize($_FILES['imagem_path']['tmp_name']);
            if($check === false) {
                throw new Exception("O arquivo enviado não é uma imagem válida");
            }
            
            // Mover o arquivo para o diretório de uploads
            if(move_uploaded_file($_FILES['imagem_path']['tmp_name'], $destino)) {
                $imagem_path = $destino;
            } else {
                throw new Exception("Erro ao salvar a imagem no servidor");
            }
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

        // Prepara os dados para inserção
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
            'imagem_path' => $imagem_path
        ];

        // Query SQL atualizada para incluir a imagem
        $sql = "INSERT INTO item (
                    nome_item, tipo_item, descricao, marca_item, numero_serie, 
                    numero_NF, data_aquisicao, valor_aquisicao, quantidade_total, 
                    quantidade_disponivel, ID_fornecedor, ID_area_desenvolvimento, ativo, imagem_path
                ) VALUES (
                    :nome_item, :tipo_item, :descricao, :marca_item, :numero_serie, 
                    :numero_NF, :data_aquisicao, :valor_aquisicao, :quantidade_total, 
                    :quantidade_disponivel, :ID_fornecedor, :ID_area_desenvolvimento, :ativo, :imagem_path
                )";
        
        $stmt = $conn->prepare($sql);
        
        if($stmt->execute($dados)) {
            $_SESSION['mensagem'] = [
                'tipo' => 'success',
                'texto' => 'Item cadastrado com sucesso!'
            ];
            header('Location: gerencia_item.php');
            exit;
        } else {
            // Se falhar, remove a imagem que foi enviada (se houver)
            if($imagem_path && file_exists($imagem_path)) {
                unlink($imagem_path);
            }
            $errorInfo = $stmt->errorInfo();
            throw new Exception("Erro ao cadastrar no banco de dados: " . $errorInfo[2]);
        }
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}
?>  

<div class="container">
    <!-- Seções de mensagens de alerta -->
    <?php if(isset($_SESSION['mensagem'])): ?>
        <div class="alert alert-<?= $_SESSION['mensagem']['tipo'] ?>">
            <?= $_SESSION['mensagem']['texto'] ?>
        </div>
        <?php unset($_SESSION['mensagem']); ?>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['mensagem_area'])): ?>
        <div class="alert alert-<?= $_SESSION['mensagem_area']['tipo'] ?>">
            <?= $_SESSION['mensagem_area']['texto'] ?>
        </div>
        <?php unset($_SESSION['mensagem_area']); ?>
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
                    <h3>Cadastrar Novo Jogo/Brinquedo</h3>
                </div>
                <div class="card-body">
                    <form method="POST" id="formItem" enctype="multipart/form-data">
                        <!-- Informações Básicas -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nome_item">Nome do Item*</label>
                                    <input type="text" id="nome_item" name="nome_item" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tipo_item">Tipo do Item*</label>
                                    <select id="tipo_item" name="tipo_item" class="form-control" required>
                                        <option value="">Selecione...</option>
                                        <option value="Jogo">Jogo</option>
                                        <option value="Brinquedo">Brinquedo</option>
                                        <option value="Material">Material</option>
                                        <option value="Equipamento">Equipamento</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="marca_item">Marca/Fabricante</label>
                                    <input type="text" id="marca_item" name="marca_item" class="form-control">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="numero_serie">Número de Série</label>
                                    <input type="text" id="numero_serie" name="numero_serie" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="descricao">Descrição</label>
                            <textarea id="descricao" name="descricao" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <!-- Quantidade e Status -->
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="quantidade_total">Quantidade Total*</label>
                                    <input type="number" id="quantidade_total" name="quantidade_total" class="form-control" min="1" value="1" required>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="quantidade_disponivel">Quantidade Disponível*</label>
                                    <input type="number" id="quantidade_disponivel" name="quantidade_disponivel" class="form-control" min="0" value="1" required>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="ativo">Status</label>
                                    <select id="ativo" name="ativo" class="form-control">
                                        <option value="1" selected>Ativo</option>
                                        <option value="0">Inativo</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="imagem_path">Imagem do Item</label>
                                    <input type="file" id="imagem_path" name="imagem_path" class="form-control-file" accept="image/*">
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
                                            <option value="<?= $fornecedor['ID_fornecedor'] ?>">
                                                <?= htmlspecialchars($fornecedor['nome_fornecedor']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                              <div class="col-md-6">
                            <div class="form-group">
                                <label for="ID_area_desenvolvimento">Área de Desenvolvimento</label>
                                <div class="input-group">
                                    <select id="ID_area_desenvolvimento" name="ID_area_desenvolvimento" class="form-control">
                                        <option value="">Selecione uma área...</option>
                                        <?php foreach($areas as $area): ?>
                                            <option value="<?= $area['ID_area'] ?>">
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
                                    <input type="date" id="data_aquisicao" name="data_aquisicao" class="form-control">
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="valor_aquisicao">Valor de Aquisição (R$)</label>
                                    <input type="text" id="valor_aquisicao" name="valor_aquisicao" class="form-control money">
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="numero_NF">Número da Nota Fiscal</label>
                                    <input type="text" id="numero_NF" name="numero_NF" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mt-4">
                            <button type="submit" name="addnew" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Cadastrar Item
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

<!-- Modal Nova Área - ESTRUTURA CORRETA -->
<div class="modal fade" id="modalNovaArea" tabindex="-1" role="dialog" aria-labelledby="modalNovaAreaLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalNovaAreaLabel">Cadastrar Nova Área</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="nova_area_input">Nome da Área*</label>
                    <input type="text" class="form-control" id="nova_area_input" name="nova_area" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnSalvarNovaArea">Salvar</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript CORRETO - deve vir ANTES do footer -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    console.log('Documento pronto - jQuery funcionando'); // Verifique no console F12
    
    // 1. ABRIR MODAL - FORMA GARANTIDA
    $(document).on('click', '#btnAbrirModalArea', function() {
        console.log('Botão Nova clicado - Deve abrir modal');
        $('#modalNovaArea').modal('show');
    });

    // 2. SALVAR NOVA ÁREA
    $('#btnSalvarNovaArea').click(function() {
        var nomeArea = $('#nova_area_input').val().trim();
        
        if(!nomeArea) {
            alert('Por favor, informe o nome da área');
            return;
        }
        
        console.log('Tentando salvar área:', nomeArea); // Debug
        
        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: {
                add_area: 1,
                nova_area: nomeArea
            },
            dataType: 'json',
            success: function(response) {
                console.log('Resposta do servidor:', response); // Debug
                if(response.success) {
                    // Adiciona a nova área ao select
                    $('#ID_area_desenvolvimento').append(
                        $('<option>', {
                            value: response.id,
                            text: response.nome,
                            selected: true
                        })
                    );
                    
                    $('#modalNovaArea').modal('hide');
                    $('#nova_area_input').val('');
                    alert(response.message);
                } else {
                    alert('Erro: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro na requisição:', status, error);
                alert('Erro ao comunicar com o servidor');
            }
        });
    });
});
</script>

<?php 
require_once 'footer.php';
?>