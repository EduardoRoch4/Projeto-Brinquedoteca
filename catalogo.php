<?php
session_start();
require_once 'config.php';
require_once 'verificar_permissoes.php';

// Verifica se o usuário está logado
verificarLogin();

try {
    // Log para debug
    registrarLog("Iniciando busca de itens no catálogo");
    
    // Buscar todos os itens
    $stmt = $conn->query("
        SELECT i.*, 
               a.nome_area as categoria_nome,
               f.nome_fornecedor as fornecedor_nome
        FROM item i
        LEFT JOIN area_desenvolvimento a ON i.ID_area_desenvolvimento = a.ID_area
        LEFT JOIN fornecedor f ON i.ID_fornecedor = f.ID_fornecedor
        WHERE i.ativo = 1
        ORDER BY i.nome_item ASC
    ");
    
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Log para debug
    registrarLog("Número de itens encontrados: " . count($itens));
    
} catch (PDOException $e) {
    registrarLog("Erro ao buscar itens: " . $e->getMessage());
    registrarLog("Query que causou o erro: SELECT i.*, a.nome_area as categoria_nome, f.nome_fornecedor as fornecedor_nome FROM item i LEFT JOIN area_desenvolvimento a ON i.ID_area_desenvolvimento = a.ID_area LEFT JOIN fornecedor f ON i.ID_fornecedor = f.ID_fornecedor WHERE i.ativo = 1 ORDER BY i.nome_item ASC");
    die("Erro ao carregar o catálogo. Por favor, tente novamente mais tarde.");
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Itens - Brinquedoteca</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .catalogo-card {
            transition: transform 0.3s;
            height: 100%;
            margin-bottom: 20px;
        }
        .catalogo-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .item-imagem {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }
        .categoria-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .quantidade-badge {
            position: absolute;
            top: 10px;
            left: 10px;
        }
        .card-body {
            display: flex;
            flex-direction: column;
        }
        .card-text {
            flex-grow: 1;
        }
        .filtro-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Catálogo de Itens</h1>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>

        <!-- Seção de Filtros -->
        <div class="filtro-section">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="filtroCategoria">Área de Desenvolvimento</label>
                        <select class="form-control" id="filtroCategoria">
                            <option value="">Todas as áreas</option>
                            <?php
                            $areas = $conn->query("SELECT * FROM area_desenvolvimento ORDER BY nome_area")->fetchAll();
                            foreach ($areas as $area) {
                                echo "<option value='{$area['ID_area']}'>{$area['nome_area']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="filtroDisponibilidade">Disponibilidade</label>
                        <select class="form-control" id="filtroDisponibilidade">
                            <option value="">Todos</option>
                            <option value="disponivel">Disponíveis</option>
                            <option value="indisponivel">Indisponíveis</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="busca">Buscar</label>
                        <input type="text" class="form-control" id="busca" placeholder="Nome do item...">
                    </div>
                </div>
            </div>
        </div>

        <!-- Grid de Itens -->
        <div class="row" id="gridItens">
            <?php if (empty($itens)): ?>
                <div class="col-12 text-center">
                    <p class="text-muted">Nenhum item encontrado.</p>
                </div>
            <?php else: ?>
                <?php foreach ($itens as $item): ?>
                    <div class="col-md-4 col-lg-3">
                        <div class="card catalogo-card">
                            <?php if (!empty($item['imagem_path'])): ?>
                                <img src="<?php echo htmlspecialchars($item['imagem_path']); ?>" 
                                     class="card-img-top item-imagem" 
                                     alt="<?php echo htmlspecialchars($item['nome_item']); ?>">
                            <?php else: ?>
                                <div class="card-img-top item-imagem bg-light d-flex align-items-center justify-content-center">
                                    <i class="fas fa-image fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            
                            <span class="badge bg-primary categoria-badge">
                                <?php echo htmlspecialchars($item['categoria_nome'] ?? 'Sem área'); ?>
                            </span>
                            
                            <span class="badge <?php echo $item['quantidade_disponivel'] > 0 ? 'bg-success' : 'bg-danger'; ?> quantidade-badge">
                                <?php echo $item['quantidade_disponivel']; ?> disponível(is)
                            </span>

                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($item['nome_item']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($item['descricao'] ?? 'Sem descrição'); ?></p>
                                
                                <div class="mt-auto">
                                    <small class="text-muted">
                                        Tipo: <?php echo htmlspecialchars($item['tipo_item']); ?><br>
                                        Fornecedor: <?php echo htmlspecialchars($item['fornecedor_nome'] ?? 'Não especificado'); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Função para filtrar os itens
        function filtrarItens() {
            const categoria = document.getElementById('filtroCategoria').value;
            const disponibilidade = document.getElementById('filtroDisponibilidade').value;
            const busca = document.getElementById('busca').value.toLowerCase();
            
            const cards = document.querySelectorAll('.catalogo-card');
            
            cards.forEach(card => {
                const cardCategoria = card.querySelector('.categoria-badge').textContent.trim();
                const cardQuantidade = parseInt(card.querySelector('.quantidade-badge').textContent);
                const cardTitulo = card.querySelector('.card-title').textContent.toLowerCase();
                
                let mostrar = true;
                
                // Filtro de categoria
                if (categoria && cardCategoria !== categoria) {
                    mostrar = false;
                }
                
                // Filtro de disponibilidade
                if (disponibilidade === 'disponivel' && cardQuantidade <= 0) {
                    mostrar = false;
                } else if (disponibilidade === 'indisponivel' && cardQuantidade > 0) {
                    mostrar = false;
                }
                
                // Filtro de busca
                if (busca && !cardTitulo.includes(busca)) {
                    mostrar = false;
                }
                
                card.closest('.col-md-4').style.display = mostrar ? 'block' : 'none';
            });
        }

        // Adicionar eventos de filtro
        document.getElementById('filtroCategoria').addEventListener('change', filtrarItens);
        document.getElementById('filtroDisponibilidade').addEventListener('change', filtrarItens);
        document.getElementById('busca').addEventListener('input', filtrarItens);
    </script>
</body>
</html> 