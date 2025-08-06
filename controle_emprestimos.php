<?php
// controle_emprestimos.php
session_start();
require_once 'config.php'; // Primeiro carrega as configurações
require_once 'conexao.php';
require_once 'verificar_permissoes.php'; // Depois carrega as verificações de permissão
require_once 'funcoes_email.php'; // Por fim carrega as funções de e-mail

// Verifica se o usuário está logado
verificarLogin();

// Verifica acesso à página
verificarAcessoPagina('controle_emprestimos.php');

// Verificação de configurações de e-mail
if (!defined('EMAIL_FROM_NAME') || !defined('EMAIL_FROM')) {
    die("Configurações de e-mail não definidas no config.php");
}

// Obter o ID do funcionário da sessão
$id_funcionario = $_SESSION['usuario_logado']['id'] ?? $_SESSION['id_funcionario'] ?? null;

if (!$id_funcionario) {
    die("Erro: ID do funcionário não encontrado na sessão.");
}

$erro = '';
$sucesso = '';

// Constantes para o sistema de multas
define('VALOR_MULTA_POR_DIA', 2.00); // R$ 2,00 por dia de atraso
define('DIAS_GRATUITOS', 7); // 7 dias de empréstimo gratuito

// Função para calcular multa
function calcularMulta($data_emprestimo, $data_devolucao) {
    $data_limite = date('Y-m-d', strtotime($data_emprestimo . ' + ' . DIAS_GRATUITOS . ' days'));
    
    if ($data_devolucao <= $data_limite) {
        return 0;
    }
    
    $dias_atraso = (strtotime($data_devolucao) - strtotime($data_limite)) / (60 * 60 * 24);
    return $dias_atraso * VALOR_MULTA_POR_DIA;
}

// Função para verificar se a criança pertence ao responsável
function verificarResponsavelCrianca($conn, $id_crianca, $id_responsavel) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM crianca 
        WHERE ID_crianca = ? AND ID_responsavel = ?
    ");
    $stmt->execute([$id_crianca, $id_responsavel]);
    $resultado = $stmt->fetch();
    return $resultado['total'] > 0;
}

require_once 'header.php';

$conn = require 'conexao.php';

// Processar devolução
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['devolver'])) {
    $id_emprestimo = $_POST['id_emprestimo'];
    $data_devolucao = date('Y-m-d');
    
    try {
        // Buscar dados do empréstimo
        $stmt = $conn->prepare("
            SELECT e.*, i.nome_item, i.quantidade_disponivel, i.quantidade_total,
                   c.nome_crianca, c.ID_crianca,
                   f.nome_funcionario, f.ID_funcionario,
                   r.nome as nome_responsavel, r.email
            FROM emprestimo e
            JOIN item i ON e.ID_item = i.ID_item
            JOIN crianca c ON e.ID_crianca = c.ID_crianca
            JOIN funcionario f ON e.ID_funcionario_entrega = f.ID_funcionario
            JOIN responsavel r ON e.ID_responsavel = r.ID_responsavel
            WHERE e.ID_emprestimo = ?
        ");
        $stmt->execute([$id_emprestimo]);
        $emprestimo = $stmt->fetch();
        
        if ($emprestimo) {
            // Calcular multa
            $valor_multa = calcularMulta($emprestimo['data_emprestimo'], $data_devolucao);
            
            // Atualizar empréstimo com o ID do funcionário que está realizando a devolução
            $stmt = $conn->prepare("
                UPDATE emprestimo 
                SET data_devolucao = ?, 
                    situacao = 'Devolvido',
                    ID_funcionario_devolucao = ?
                WHERE ID_emprestimo = ?
            ");
            $stmt->execute([$data_devolucao, $_SESSION['usuario_logado']['id'], $id_emprestimo]);
            
            // Registrar multa se houver
            if ($valor_multa > 0) {
                $stmt = $conn->prepare("
                    INSERT INTO multa (ID_emprestimo, valor_multa, data_pagamento, situacao)
                    VALUES (?, ?, ?, 'Pendente')
                ");
                $stmt->execute([$id_emprestimo, $valor_multa, $data_devolucao]);
            }

            // Registrar dano se houver
            if (isset($_POST['tem_dano']) && $_POST['tem_dano'] == '1') {
                $descricao_dano = $_POST['descricao_dano'] ?? '';
                $valor_reparo = $_POST['valor_reparo'] ?? 0;
                $observacoes = $_POST['observacoes_dano'] ?? '';
 
                // Registrar o dano
                $stmt = $conn->prepare("
                    INSERT INTO registro_dano (
                        ID_item, 
                        ID_emprestimo,
                        ID_funcionario_registro,
                        descricao_dano, 
                        data_ocorrencia,
                        situacao
                    ) VALUES (?, ?, ?, ?, ?, 'Registrado')
                ");
                $stmt->execute([
                    $emprestimo['ID_item'],
                    $id_emprestimo,
                    $_SESSION['usuario_logado']['id'], // Usa o ID do funcionário logado
                    $descricao_dano,
                    $data_devolucao
                ]);

                // Registrar o valor do reparo na tabela multa
                if ($valor_reparo > 0) {
                    $stmt = $conn->prepare("
                        INSERT INTO multa (ID_emprestimo, valor_multa, data_geracao, situacao)
                        VALUES (?, ?, ?, 'Pendente')
                    ");
                    $stmt->execute([$id_emprestimo, $valor_reparo, $data_devolucao]);
                }

                // Atualizar mensagem de sucesso para incluir informação sobre o dano
                $sucesso .= " Danos registrados.";
            }
            
            // Buscar quantidade do empréstimo
            $stmt = $conn->prepare("
                SELECT quantidade 
                FROM item_emprestimo 
                WHERE ID_emprestimo = ?
            ");
            $stmt->execute([$id_emprestimo]);
        $item_emprestimo = $stmt->fetch();
        
            if ($item_emprestimo) {
                // Atualizar quantidade disponível do item
                $stmt = $conn->prepare("
                    UPDATE item 
                    SET quantidade_disponivel = quantidade_disponivel + ? 
                    WHERE ID_item = ?
                ");
                $stmt->execute([$item_emprestimo['quantidade'], $emprestimo['ID_item']]);
            }
            
            // Enviar email de confirmação
            if (isset($emprestimo['email']) && !empty($emprestimo['email'])) {
                if ($valor_multa > 0) {
                    $assunto = "Devolução com Multa - Brinquedoteca";
                    $mensagem = "Olá {$emprestimo['nome_responsavel']},\n\n";
                    $mensagem .= "Confirmamos a devolução do item: {$emprestimo['nome_item']}\n";
                    $mensagem .= "Data do empréstimo: " . date('d/m/Y', strtotime($emprestimo['data_emprestimo'])) . "\n";
                    $mensagem .= "Data da devolução: " . date('d/m/Y', strtotime($data_devolucao)) . "\n";
                    $mensagem .= "Valor da multa: R$ " . number_format($valor_multa, 2, ',', '.') . "\n\n";
                    $mensagem .= "Por favor, compareça à brinquedoteca para regularizar a situação.\n\n";
                    $mensagem .= "Atenciosamente,\nEquipe da Brinquedoteca";
                } else {
                    $assunto = "Devolução Confirmada - Brinquedoteca";
                    $mensagem = "Olá {$emprestimo['nome_responsavel']},\n\n";
                    $mensagem .= "Confirmamos a devolução do item: {$emprestimo['nome_item']}\n";
                    $mensagem .= "Data do empréstimo: " . date('d/m/Y', strtotime($emprestimo['data_emprestimo'])) . "\n";
                    $mensagem .= "Data da devolução: " . date('d/m/Y', strtotime($data_devolucao)) . "\n\n";
                    $mensagem .= "Obrigado por utilizar nossos serviços!\n\n";
                    $mensagem .= "Atenciosamente,\nEquipe da Brinquedoteca";
                }
                
                enviarEmail($emprestimo['email'], $emprestimo['nome_responsavel'], $assunto, $mensagem);
            }
            
            $sucesso = "Devolução registrada com sucesso!" . ($valor_multa > 0 ? " Multa de R$ " . number_format($valor_multa, 2, ',', '.') . " aplicada." : "");
        }
    } catch (Exception $e) {
        $erro = "Erro ao processar devolução: " . $e->getMessage();
    }
}

// Processar envio de cobranças
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_cobrancas'])) {
    // Verifica permissão para gerenciar empréstimos (apenas gerentes e admins)
    if (!verificarPermissaoAcao('gerenciar_emprestimos')) {
        $erro = "Você não tem permissão para enviar cobranças.";
    } else {
    try {
        if (!isset($_POST['emails']) || empty($_POST['emails'])) {
            throw new Exception("Nenhum destinatário selecionado para envio");
        }

        $emails = $_POST['emails'];
        $enviados = 0;
        $erros = 0;
        $assunto = "Lembrete de Devolução Atrasada - BrinquedoTech";
        
        // Carrega o template de e-mail
        $mensagem_template = file_get_contents('../../templates/email_cobranca.html');
        
        foreach ($emails as $email) {
            try {
                // Obter dados do empréstimo associado ao e-mail
                $stmt = $conn->prepare("SELECT e.ID_emprestimo, r.nome, i.nome_item, e.data_limite_devolucao 
                                      FROM emprestimo e
                                      JOIN responsavel r ON e.id_responsavel = r.id_responsavel
                                      JOIN item_emprestimo ie ON e.ID_emprestimo = ie.ID_emprestimo
                                      JOIN item i ON ie.ID_item = i.ID_item
                                      WHERE r.email = ? AND e.situacao = 'Emprestado'");
                $stmt->execute([$email]);
                $emprestimo = $stmt->fetch();
                
                if ($emprestimo) {
                    // Personalizar mensagem
                    $mensagem = str_replace(
                        ['{NOME}', '{BRINQUEDO}', '{DATA_DEVOLUCAO}'],
                        [
                            $emprestimo['nome'],
                            $emprestimo['nome_item'],
                            date('d/m/Y', strtotime($emprestimo['data_limite_devolucao']))
                        ],
                        $mensagem_template
                    );
                    
                    // Enviar e-mail (usando PHPMailer ou função mail())
                        $enviado = enviarEmail($email, $emprestimo['nome_responsavel'], $assunto, $mensagem);
                    
                    if ($enviado) {
                        // Registrar no banco de dados
                        $stmt = $conn->prepare("INSERT INTO historico_cobrancas 
                                              (ID_emprestimo, email, data_envio, status) 
                                              VALUES (?, ?, NOW(), 'Enviado')");
                        $stmt->execute([$emprestimo['ID_emprestimo'], $email]);
                        $enviados++;
                    } else {
                        $erros++;
                    }
                }
            } catch (PDOException $e) {
                $erros++;
                error_log("Erro ao enviar e-mail para $email: " . $e->getMessage());
            }
        }
        
        $_SESSION['mensagem'] = ['tipo' => 'success', 'texto' => "Cobranças enviadas: $enviados com sucesso, $erros falhas"];
        header('Location: controle_emprestimos.php');
        exit;
        
    } catch (Exception $e) {
        $erro = "Erro ao processar cobranças: " . $e->getMessage();
        }
    }
}

// Processar exclusão de empréstimos devolvidos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_devolvidos'])) {
    try {
        // Verifica permissão para gerenciar empréstimos (apenas gerentes e admins)
        if (!verificarPermissaoAcao('gerenciar_emprestimos')) {
            throw new Exception("Você não tem permissão para excluir empréstimos.");
        }

        // Inicia a transação
        $conn->beginTransaction();

        // Primeiro, exclui os registros da tabela item_emprestimo
        $stmt = $conn->prepare("
            DELETE ie FROM item_emprestimo ie
            INNER JOIN emprestimo e ON ie.ID_emprestimo = e.ID_emprestimo
            WHERE e.situacao = 'Devolvido'
        ");
        $stmt->execute();

        // Depois, exclui os empréstimos devolvidos
        $stmt = $conn->prepare("
            DELETE FROM emprestimo 
            WHERE situacao = 'Devolvido'
        ");
        $stmt->execute();

        // Confirma a transação
        $conn->commit();

        $sucesso = "Empréstimos devolvidos excluídos com sucesso!";
    } catch (Exception $e) {
        // Desfaz a transação em caso de erro
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $erro = "Erro ao excluir empréstimos: " . $e->getMessage();
    }
}

// Buscar dados para os formulários
$itens = $responsaveis = $criancas = $funcionarios = [];
try {
    // Itens disponíveis
    $stmt = $conn->query("SELECT ID_item, nome_item, quantidade_disponivel FROM item WHERE quantidade_disponivel > 0 AND ativo = 1 ORDER BY nome_item");
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Responsáveis
    $stmt = $conn->query("SELECT ID_responsavel, nome, email FROM responsavel ORDER BY nome");
    $responsaveis = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Crianças
    $stmt = $conn->query("SELECT ID_crianca, nome_crianca FROM crianca ORDER BY nome_crianca");
    $criancas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Funcionários
    $stmt = $conn->query("SELECT ID_funcionario, nome_funcionario as nome FROM funcionario WHERE ativo = 1 ORDER BY nome_funcionario");
    $funcionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $erro = "Erro ao carregar dados: " . $e->getMessage();
}

// Processar novo empréstimo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['emprestar'])) {
    try {
        // Verificar permissão
        if (!verificarPermissaoAcao('registrar_emprestimo')) {
            throw new Exception("Você não tem permissão para registrar empréstimos.");
        }

        $id_funcionario = $_SESSION['usuario_logado']['id'];
        
        // Validar dados do formulário
        $id_crianca = $_POST['id_crianca'] ?? null;
        $id_item = $_POST['id_item'] ?? null;
        $id_responsavel = $_POST['id_responsavel'] ?? null;
        $prazo_emprestimo = $_POST['prazo_emprestimo'] ?? 7;
        $quantidade = $_POST['quantidade'] ?? 1;
        $observacoes = $_POST['observacoes_emprestimo'] ?? '';
        
        if (!$id_crianca || !$id_item || !$id_responsavel || !$prazo_emprestimo) {
            throw new Exception("Todos os campos são obrigatórios");
        }

        // Validar prazo máximo
        if ($prazo_emprestimo > 30) {
            throw new Exception("O prazo máximo de empréstimo é de 30 dias");
        }

        // Calcular data limite de devolução
        $data_limite = date('Y-m-d', strtotime("+{$prazo_emprestimo} days"));

        // Verificar se a criança pertence ao responsável selecionado
        if (!verificarResponsavelCrianca($conn, $id_crianca, $id_responsavel)) {
            throw new Exception("A criança selecionada não pertence ao responsável informado. Por favor, selecione o responsável correto da criança.");
        }

        // Verificar se a quantidade solicitada está disponível
        $stmt = $conn->prepare("SELECT quantidade_disponivel FROM item WHERE ID_item = ?");
        $stmt->execute([$id_item]);
        $item = $stmt->fetch();

        if (!$item) {
            throw new Exception("Item não encontrado");
        }

        if ($quantidade > $item['quantidade_disponivel']) {
            throw new Exception("Quantidade solicitada ({$quantidade}) é maior que a quantidade disponível ({$item['quantidade_disponivel']})");
        }

        // Iniciar transação
        $conn->beginTransaction();

        // Registrar o empréstimo
        $sql = "INSERT INTO emprestimo (
            ID_crianca, 
            ID_funcionario_entrega,
            data_emprestimo,
            data_limite_devolucao,
            situacao,
            ID_item,
            ID_responsavel,
            observacoes_emprestimo
        ) VALUES (?, ?, NOW(), ?, 'Emprestado', ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $id_crianca,
            $id_funcionario,
            $data_limite,
            $id_item,
            $id_responsavel,
            $observacoes
        ]);

        $id_emprestimo = $conn->lastInsertId();

        // Registrar quantidade do item emprestado
        $sql = "INSERT INTO item_emprestimo (
            ID_emprestimo,
            ID_item,
            quantidade
        ) VALUES (?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id_emprestimo, $id_item, $quantidade]);

        // Atualizar quantidade disponível do item
        $sql = "UPDATE item 
               SET quantidade_disponivel = quantidade_disponivel - ? 
               WHERE ID_item = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$quantidade, $id_item]);

        // Commit da transação
        $conn->commit();

        $sucesso = "Empréstimo registrado com sucesso!";
        
        // Redirecionar após sucesso
        header("Location: controle_emprestimos.php?sucesso=1");
        exit;
        
    } catch (Exception $e) {
        // Rollback em caso de erro
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $erro = "Erro ao registrar empréstimo: " . $e->getMessage();
    }
}

// Buscar empréstimos ativos
try {
    $stmt = $conn->query("
        SELECT e.*, i.nome_item, c.nome_crianca, r.nome as nome_responsavel,
               CASE 
                   WHEN e.data_devolucao IS NULL AND DATEDIFF(CURRENT_DATE, e.data_emprestimo) > " . DIAS_GRATUITOS . "
                   THEN DATEDIFF(CURRENT_DATE, e.data_emprestimo) - " . DIAS_GRATUITOS . "
                   ELSE 0
               END as dias_atraso,
               CASE 
                   WHEN e.data_devolucao IS NULL AND DATEDIFF(CURRENT_DATE, e.data_emprestimo) > " . DIAS_GRATUITOS . "
                   THEN (DATEDIFF(CURRENT_DATE, e.data_emprestimo) - " . DIAS_GRATUITOS . ") * " . VALOR_MULTA_POR_DIA . "
                   ELSE 0
               END as multa_atual
        FROM emprestimo e
        JOIN item i ON e.ID_item = i.ID_item
        JOIN crianca c ON e.ID_crianca = c.ID_crianca
        JOIN responsavel r ON c.ID_responsavel = r.ID_responsavel
        WHERE e.situacao = 'Emprestado'
        ORDER BY e.data_emprestimo DESC
    ");
    $emprestimos = $stmt->fetchAll();
    } catch (Exception $e) {
    $erro = "Erro ao buscar empréstimos: " . $e->getMessage();
}

// No início do arquivo, após o processamento de devolução
if (isset($_POST['enviar_email'])) {
    $id_emprestimo = $_POST['id_emprestimo'];
    $assunto = $_POST['assunto_email'];
    $mensagem = $_POST['mensagem_email'];

    // Buscar dados do empréstimo
    $stmt = $conn->prepare("
        SELECT e.*, i.nome_item, i.quantidade_disponivel, i.quantidade_total,
               c.nome_crianca, c.ID_crianca,
               f.nome_funcionario, f.ID_funcionario,
               r.nome as nome_responsavel, r.email
        FROM emprestimo e
        JOIN item i ON e.ID_item = i.ID_item
        JOIN crianca c ON e.ID_crianca = c.ID_crianca
        JOIN funcionario f ON e.ID_funcionario_entrega = f.ID_funcionario
        JOIN responsavel r ON e.ID_responsavel = r.ID_responsavel
        WHERE e.ID_emprestimo = ?
    ");
    $stmt->execute([$id_emprestimo]);
    $emprestimo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($emprestimo && !empty($emprestimo['email'])) {
        // Não precisamos incluir novamente, pois já está no config.php
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // Configurações do servidor
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'brinquedoteca41@gmail.com';
            $mail->Password = 'guwhoothorgycpso';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';

            // Remetente e destinatário
            $mail->setFrom('brinquedoteca41@gmail.com', 'Brinquedoteca');
            $mail->addAddress($emprestimo['email'], $emprestimo['nome_responsavel']);

            // Conteúdo
            $mail->isHTML(true);
            $mail->Subject = $assunto;
            
            // Template HTML do email
            $mensagem_html = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #f8f9fa; padding: 20px; text-align: center; }
                    .content { padding: 20px; }
                    .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Brinquedoteca</h2>
                    </div>
                    <div class='content'>
                        " . nl2br($mensagem) . "
                    </div>
                    <div class='footer'>
                        <p>Este é um email automático, por favor não responda.</p>
                    </div>
                </div>
            </body>
            </html>";

            $mail->Body = $mensagem_html;
            $mail->AltBody = strip_tags($mensagem); // Versão texto plano para clientes que não suportam HTML

            $mail->send();
            $sucesso = "Email enviado com sucesso para " . $emprestimo['email'];
        } catch (Exception $e) {
            $erro = "Erro ao enviar email: {$mail->ErrorInfo}";
            error_log("Erro ao enviar email: " . $mail->ErrorInfo);
        }
    } else {
        $erro = "Email do responsável não encontrado ou inválido.";
    }
}

?>
<div class="container mt-4">
    <!-- Botão Voltar -->
    <a href="index.php" class="btn btn-secondary mb-4">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>

    <h2 class="mb-4">Controle de Empréstimos</h2>
    
    <?php if ($erro): ?>
        <div class="alert alert-danger"><?php echo $erro; ?></div>
    <?php endif; ?>
    
    <?php if ($sucesso): ?>
        <div class="alert alert-success"><?php echo $sucesso; ?></div>
    <?php endif; ?>

    <!-- Formulário de Novo Empréstimo -->
    <?php if (verificarPermissaoAcao('realizar_emprestimo')): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h4>Novo Empréstimo</h4>
        </div>
        <div class="card-body">
            <form method="POST" action="">
        <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="id_item">Item</label>
                            <select class="form-control" id="id_item" name="id_item" required>
                                <option value="">Selecione um item</option>
                                <?php foreach ($itens as $item): ?>
                                    <option value="<?= $item['ID_item'] ?>">
                                        <?= htmlspecialchars($item['nome_item']) ?> 
                                        (Disponível: <?= $item['quantidade_disponivel'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="id_responsavel">Responsável</label>
                            <select class="form-control" id="id_responsavel" name="id_responsavel" required onchange="carregarCriancas(this.value)">
                                <option value="">Selecione um responsável</option>
                                <?php foreach ($responsaveis as $responsavel): ?>
                                    <option value="<?= $responsavel['ID_responsavel'] ?>">
                                        <?= htmlspecialchars($responsavel['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="id_crianca">Criança</label>
                            <select class="form-control" id="id_crianca" name="id_crianca" required>
                                <option value="">Selecione uma criança</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="quantidade">Quantidade</label>
                            <input type="number" class="form-control" id="quantidade" name="quantidade" min="1" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="prazo_emprestimo">Prazo do Empréstimo (dias)</label>
                            <input type="number" class="form-control" id="prazo_emprestimo" name="prazo_emprestimo" min="1" max="30" value="7" required>
                            <small class="form-text text-muted">Máximo de 30 dias</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="observacoes_emprestimo">Observações</label>
                            <textarea class="form-control" id="observacoes_emprestimo" name="observacoes_emprestimo" rows="1"></textarea>
                        </div>
                    </div>
                </div>
                <button type="submit" name="emprestar" class="btn btn-primary">Registrar Empréstimo</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
                            
    <!-- Lista de Empréstimos Ativos -->
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Empréstimos Ativos</h4>
                                    <?php if (verificarPermissaoAcao('gerenciar_emprestimos')): ?>
                                        <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir todos os empréstimos devolvidos? Esta ação não pode ser desfeita.');">
                                            <button type="submit" name="excluir_devolvidos" class="btn btn-danger">
                                                <i class="fas fa-trash"></i> Excluir Empréstimos Devolvidos
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
        <div class="card-body">
                                <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                            <th>Item</th>
                            <th>Criança</th>
                                                    <th>Responsável</th>
                                                    <th>Data Empréstimo</th>
                            <th>Prazo Devolução</th>
                            <th>Status</th>
                                                    <th>Multa</th>
                            <?php if (verificarPermissaoAcao('registrar_devolucao')): ?>
                                                    <th>Ações</th>
                            <?php endif; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                        <?php
                        try {
                            $stmt = $conn->query("
                                SELECT e.*, i.nome_item, c.nome_crianca, r.nome as nome_responsavel,
                                       CASE 
                                           WHEN e.data_devolucao IS NULL AND DATEDIFF(CURRENT_DATE, e.data_emprestimo) > " . DIAS_GRATUITOS . "
                                           THEN DATEDIFF(CURRENT_DATE, e.data_emprestimo) - " . DIAS_GRATUITOS . "
                                           ELSE 0
                                       END as dias_atraso,
                                       CASE 
                                           WHEN e.data_devolucao IS NULL AND DATEDIFF(CURRENT_DATE, e.data_emprestimo) > " . DIAS_GRATUITOS . "
                                           THEN (DATEDIFF(CURRENT_DATE, e.data_emprestimo) - " . DIAS_GRATUITOS . ") * " . VALOR_MULTA_POR_DIA . "
                                           ELSE 0
                                       END as multa_atual
                                FROM emprestimo e
                                JOIN item i ON e.ID_item = i.ID_item
                                JOIN crianca c ON e.ID_crianca = c.ID_crianca
                                JOIN responsavel r ON c.ID_responsavel = r.ID_responsavel
                                WHERE e.situacao = 'Emprestado'
                                ORDER BY e.data_emprestimo DESC
                            ");
                            while ($emprestimo = $stmt->fetch(PDO::FETCH_ASSOC)):
                                                ?>
                                                    <tr>
                                <td><?= htmlspecialchars($emprestimo['nome_item']) ?></td>
                                <td><?= htmlspecialchars($emprestimo['nome_crianca']) ?></td>
                                <td><?= htmlspecialchars($emprestimo['nome_responsavel']) ?></td>
                                <td><?= date('d/m/Y', strtotime($emprestimo['data_emprestimo'])) ?></td>
                                <td><?= date('d/m/Y', strtotime($emprestimo['data_emprestimo'] . ' + ' . DIAS_GRATUITOS . ' days')) ?></td>
                                <td>
                                    <?php if ($emprestimo['dias_atraso'] > 0): ?>
                                        <span class="status-atrasado">
                                            <i class="fas fa-exclamation-circle"></i> Atrasado
                                        </span>
                                    <?php else: ?>
                                        <span class="status-no-prazo">
                                            <i class="fas fa-check-circle"></i> No prazo
                                        </span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                    <?php if ($emprestimo['multa_atual'] > 0): ?>
                                        <span class="multa">
                                            R$ <?php echo number_format($emprestimo['multa_atual'], 2, ',', '.'); ?>
                                        </span>
                                    <?php else: ?>
                                        -
                                                            <?php endif; ?>
                                                        </td>
                                <?php if (verificarPermissaoAcao('registrar_devolucao')): ?>
                                <td>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="id_emprestimo" value="<?= $emprestimo['ID_emprestimo'] ?>">
                                        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalDano<?= $emprestimo['ID_emprestimo'] ?>">
                                            <i class="fas fa-exclamation-triangle"></i> Registrar Dano
                                        </button>
                                        <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalEmail<?= $emprestimo['ID_emprestimo'] ?>">
                                            <i class="fas fa-envelope"></i> Enviar Aviso
                                        </button>
                                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalDevolucao<?= $emprestimo['ID_emprestimo'] ?>">
                                            <i class="fas fa-check"></i> Devolver
                                        </button>
                                    </form>
                                </td>
                                <?php endif; ?>

                                <!-- Modal de Envio de Email -->
                                <div class="modal fade" id="modalEmail<?= $emprestimo['ID_emprestimo'] ?>" tabindex="-1" aria-labelledby="modalEmailLabel<?= $emprestimo['ID_emprestimo'] ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="modalEmailLabel<?= $emprestimo['ID_emprestimo'] ?>">Enviar Aviso por Email</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="POST" action="">
                                                    <input type="hidden" name="id_emprestimo" value="<?= $emprestimo['ID_emprestimo'] ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label for="assunto_email" class="form-label">Assunto</label>
                                                        <input type="text" class="form-control" id="assunto_email" name="assunto_email" 
                                                               value="Aviso de Devolução - Brinquedoteca" required>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="mensagem_email" class="form-label">Mensagem</label>
                                                        <textarea class="form-control" id="mensagem_email" name="mensagem_email" rows="5" required>Olá <?= $emprestimo['nome_responsavel'] ?>,

Lembramos que o item "<?= $emprestimo['nome_item'] ?>" emprestado em <?= date('d/m/Y', strtotime($emprestimo['data_emprestimo'])) ?> deve ser devolvido até <?= date('d/m/Y', strtotime($emprestimo['data_limite_devolucao'])) ?>.

Por favor, compareça à brinquedoteca para realizar a devolução.

Atenciosamente,
Equipe da Brinquedoteca</textarea>
                                                    </div>
                                                    
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" name="enviar_email" class="btn btn-primary">Enviar Email</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal de Devolução -->
                                <div class="modal fade" id="modalDevolucao<?= $emprestimo['ID_emprestimo'] ?>" tabindex="-1" aria-labelledby="modalDevolucaoLabel<?= $emprestimo['ID_emprestimo'] ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="modalDevolucaoLabel<?= $emprestimo['ID_emprestimo'] ?>">Confirmar Devolução</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="POST" action="">
                                                    <input type="hidden" name="id_emprestimo" value="<?= $emprestimo['ID_emprestimo'] ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label for="data_devolucao" class="form-label">Data de Devolução</label>
                                                        <input type="datetime-local" class="form-control" id="data_devolucao" name="data_devolucao" 
                                                               value="<?= date('Y-m-d\TH:i') ?>" required>
                                                    </div>
                                                    
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" name="devolver" class="btn btn-primary">Confirmar Devolução</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal de Registro de Dano -->
                                <div class="modal fade" id="modalDano<?= $emprestimo['ID_emprestimo'] ?>" tabindex="-1" aria-labelledby="modalDanoLabel<?= $emprestimo['ID_emprestimo'] ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="modalDanoLabel<?= $emprestimo['ID_emprestimo'] ?>">Registrar Dano</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="POST" action="">
                                                    <input type="hidden" name="id_emprestimo" value="<?= $emprestimo['ID_emprestimo'] ?>">
                                                    <input type="hidden" name="tem_dano" value="1">
                                                    
                                                    <div class="mb-3">
                                                        <label for="data_devolucao" class="form-label">Data de Devolução</label>
                                                        <input type="datetime-local" class="form-control" id="data_devolucao" name="data_devolucao" 
                                                               value="<?= date('Y-m-d\TH:i') ?>" required>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="descricao_dano" class="form-label">Descrição do Dano</label>
                                                        <textarea class="form-control" id="descricao_dano" name="descricao_dano" rows="3" required></textarea>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="valor_reparo" class="form-label">Valor do Reparo</label>
                                                        <input type="number" class="form-control" id="valor_reparo" name="valor_reparo" min="0">
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="observacoes_dano" class="form-label">Observações</label>
                                                        <textarea class="form-control" id="observacoes_dano" name="observacoes_dano" rows="2"></textarea>
                                                    </div>
                                                    
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" name="devolver" class="btn btn-primary">Confirmar Devolução com Dano</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </tr>
                        <?php
                            endwhile;
                        } catch (PDOException $e) {
                            echo "<tr><td colspan='7' class='text-danger'>Erro ao carregar empréstimos: " . $e->getMessage() . "</td></tr>";
                        }
                        ?>
                                            </tbody>
                                        </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function carregarCriancas(idResponsavel) {
    if (!idResponsavel) {
        document.getElementById('id_crianca').innerHTML = '<option value="">Selecione uma criança</option>';
        return;
    }

    fetch('buscar_criancas.php?id_responsavel=' + idResponsavel)
        .then(response => response.json())
        .then(criancas => {
            const select = document.getElementById('id_crianca');
            select.innerHTML = '<option value="">Selecione uma criança</option>';
            
            criancas.forEach(crianca => {
                const option = document.createElement('option');
                option.value = crianca.ID_crianca;
                option.textContent = crianca.nome_crianca;
                select.appendChild(option);
            });
        })
        .catch(error => console.error('Erro ao carregar crianças:', error));
}
</script>
</body>
</html>