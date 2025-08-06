<?php
session_start();

require_once 'config.php';
require_once 'verificar_permissoes.php';

// Verifica se o usuário está logado
verificarLogin();

// Obtém o nível de acesso do usuário
$nivel_acesso = $_SESSION['usuario_logado']['nivel'];

// Função para obter o nome do nível de acesso
function getNomeNivelAcesso($nivel) {
    if ($nivel === NIVEL_ADMIN) {
        return 'Administrador';
    } elseif ($nivel === NIVEL_GERENTE) {
        return 'Gerente';
    } elseif ($nivel === NIVEL_FUNCIONARIO) {
        return 'Funcionário';
    }
    return 'Desconhecido';
}

// Registra o nível de acesso para debug
registrarLog("Nível de acesso na sessão: " . $nivel_acesso);
registrarLog("Tipo do nível: " . gettype($nivel_acesso));

$nome_nivel = getNomeNivelAcesso($nivel_acesso);
registrarLog("Nome do nível retornado: " . $nome_nivel);

if (isset($_SESSION['mensagem'])) {
    // Mensagem tratada abaixo
}

$total_criancas = $conn->query("SELECT COUNT(*) FROM crianca")->fetchColumn();
$total_responsavel = $conn->query("SELECT COUNT(*) FROM responsavel")->fetchColumn();
$total_jogos = $conn->query("SELECT COUNT(*) FROM item")->fetchColumn();
$total_emprestimo = $conn->query("SELECT COUNT(*) FROM emprestimo WHERE data_devolucao IS NULL")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Brinquedoteca</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .dashboard-card {
            transition: transform 0.3s;
            margin-bottom: 20px;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        .card-icon {
            font-size: 2rem;
            margin-bottom: 15px;
        }
        .welcome-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="welcome-section">
            <h1 class="mb-3">Bem-vindo(a), <?php echo htmlspecialchars($_SESSION['usuario_logado']['nome']); ?>!</h1>
            <h2 class="h4 mb-4">Sistema de Gerenciamento da Brinquedoteca</h2>
            <p class="mb-0">Nível de Acesso: <?php echo getNomeNivelAcesso($nivel_acesso); ?></p>
        </div>

        <div class="row">
            <!-- Gerenciar Pessoas - Acesso para todos -->
            <div class="col-md-4">
                <div class="card dashboard-card">
                    <div class="card-body text-center">
                        <i class="fas fa-users card-icon text-primary"></i>
                        <h5 class="card-title">Gerenciar Pessoas</h5>
                        <p class="card-text">Cadastro e gerenciamento de responsáveis e crianças.</p>
                        <a href="listar_responsavel.php" class="btn btn-primary">Acessar</a>
                    </div>
                </div>
            </div>

            <!-- Controle de Empréstimos - Acesso para todos -->
            <div class="col-md-4">
                <div class="card dashboard-card">
                    <div class="card-body text-center">
                        <i class="fas fa-exchange-alt card-icon text-info"></i>
                        <h5 class="card-title">Controle de Empréstimos</h5>
                        <p class="card-text">Gerenciar empréstimos e devoluções.</p>
                        <a href="controle_emprestimos.php" class="btn btn-info">Acessar</a>
                    </div>
                </div>
            </div>

            <!-- Relatórios - Acesso para todos -->
            <div class="col-md-4">
                <div class="card dashboard-card">
                    <div class="card-body text-center">
                        <i class="fas fa-chart-bar card-icon text-success"></i>
                        <h5 class="card-title">Relatórios</h5>
                        <p class="card-text">Visualizar relatórios e estatísticas.</p>
                        <a href="relatorio.php" class="btn btn-success">Acessar</a>
                    </div>
                </div>
            </div>

            <!-- Catálogo - Acesso para todos -->
            <div class="col-md-4">
                <div class="card dashboard-card">
                    <div class="card-body text-center">
                        <i class="fas fa-book card-icon text-info"></i>
                        <h5 class="card-title">Catálogo</h5>
                        <p class="card-text">Visualizar todos os itens disponíveis.</p>
                        <a href="catalogo.php" class="btn btn-info">Acessar</a>
                    </div>
                </div>
            </div>

            <?php if ($nivel_acesso === NIVEL_GERENTE || $nivel_acesso === NIVEL_ADMIN): ?>
            <!-- Gerenciar Itens - Acesso para Gerente e Admin -->
            <div class="col-md-4">
                <div class="card dashboard-card">
                    <div class="card-body text-center">
                        <i class="fas fa-gamepad card-icon text-warning"></i>
                        <h5 class="card-title">Gerenciar Itens</h5>
                        <p class="card-text">Cadastro e controle de jogos e brinquedos.</p>
                        <a href="gerencia_item.php" class="btn btn-warning">Acessar</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($nivel_acesso === NIVEL_ADMIN): ?>
            <!-- Gerenciar Funcionários - Acesso apenas para Admin -->
            <div class="col-md-4">
                <div class="card dashboard-card">
                    <div class="card-body text-center">
                        <i class="fas fa-user-tie card-icon text-danger"></i>
                        <h5 class="card-title">Gerenciar Funcionários</h5>
                        <p class="card-text">Cadastro e controle de funcionários.</p>
                        <a href="listaFuncionarios.php" class="btn btn-danger">Acessar</a>
                    </div>
                </div>
            </div>

            <!-- Fornecedores - Acesso apenas para Admin -->
            <div class="col-md-4">
                <div class="card dashboard-card">
                    <div class="card-body text-center">
                        <i class="fas fa-truck card-icon text-secondary"></i>
                        <h5 class="card-title">Fornecedores</h5>
                        <p class="card-text">Gerenciar cadastro de fornecedores.</p>
                        <a href="listaFornecedores.php" class="btn btn-secondary">Acessar</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="row mt-4">
            <div class="col-12 text-center">
                <a href="logout.php" class="btn btn-outline-secondary">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
