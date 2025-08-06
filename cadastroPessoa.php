<?php
session_start();
require_once 'conexao.php';

// Inicia a sessão se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'funcoes_pessoa.php'; // Arquivo renomeado para refletir melhor seu propósito
require_once 'header.php';

$conn = require 'conexao.php';

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addnew'])) {
    try {
        // Prepara e valida os dados
        $dados = [
            'nome' => htmlspecialchars(trim($_POST['nome'])),
            'cpf' => preg_replace('/[^0-9]/', '', $_POST['cpf']),
            'sexo' => in_array($_POST['sexo'], ['M', 'F', 'O']) ? $_POST['sexo'] : 'O',
            'email' => filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ? $_POST['email'] : null,
            'data_nascimento' => $_POST['data_nascimento'],
            'data_cadastro' => date('Y-m-d H:i:s'),
            'configura_crianca' => isset($_POST['configura_crianca']) ? 1 : 0,
            'observacoes' => isset($_POST['observacoes']) ? $_POST['observacoes'] : null
        ];

        // Validações básicas
        if(empty($dados['nome'])) {
            throw new Exception("O nome é obrigatório");
        }
        
        // Verifica se é criança ou responsável
        if($dados['configura_crianca']) {
            // CADASTRO DE CRIANÇA
            
            // Validações específicas para criança
            if(empty($dados['data_nascimento'])) {
                throw new Exception("Data de nascimento é obrigatória para crianças");
            }
            
            // Verifica se foi selecionado um responsável
            if(empty($_POST['id_responsavel'])) {
                // Salva os dados na sessão e redireciona para seleção de responsável
                $_SESSION['dados_crianca_temp'] = $dados;
                header('Location: selecionar_responsavel.php');
                exit;
            }
            
            $id_responsavel = (int)$_POST['id_responsavel'];
            
            // Verifica se o responsável existe
            $stmt = $conn->prepare("SELECT 1 FROM responsavel WHERE ID_responsavel = ?");
            $stmt->execute([$id_responsavel]);
            
            if(!$stmt->fetch()) {
                throw new Exception("Responsável selecionado não encontrado");
            }
            
            // Cadastra apenas na tabela crianca
            $resultado = cadastrarCrianca($conn, $dados, $id_responsavel);
            $mensagem_sucesso = 'Criança cadastrada com sucesso!';
            
            // Remove dados temporários se existirem
            if(isset($_SESSION['dados_crianca_temp'])) {
                unset($_SESSION['dados_crianca_temp']);
            }
        } else {
            // CADASTRO DE RESPONSÁVEL
            
            // Validações específicas para responsável
            if(strlen($dados['cpf']) != 11) {
                throw new Exception("CPF deve conter 11 dígitos");
            }
            
            if(empty($dados['data_nascimento'])) {
                throw new Exception("Data de nascimento é obrigatória");
            }
            
            $resultado = cadastrarResponsavel($conn, $dados);
            $mensagem_sucesso = 'Responsável cadastrado com sucesso!';
        }

        if($resultado === true) {
            $_SESSION['mensagem'] = [
                'tipo' => 'success',
                'texto' => $mensagem_sucesso
            ];
            header('Location: listar_responsavel.php');
            exit;
        } else {
            throw new Exception($resultado);
        }
    } catch (Exception $e) {
        $erro = [
            'tipo' => 'danger',
            'texto' => 'Erro: ' . $e->getMessage()
        ];
    }
}

// Recupera dados temporários se existirem (para caso de redirecionamento)
if(isset($_SESSION['dados_crianca_temp'])) {
    $dados_temp = $_SESSION['dados_crianca_temp'];
    $_POST = array_merge($_POST, $dados_temp);
    $_POST['configura_crianca'] = 1;
}
?>

<div class="container">
    <?php 
    // Exibe mensagens de erro/sucesso
    if(isset($_SESSION['mensagem'])) {
        echo '<div class="alert alert-'.$_SESSION['mensagem']['tipo'].'">'.$_SESSION['mensagem']['texto'].'</div>';
        unset($_SESSION['mensagem']);
    }
    if(isset($erro)) {
        echo '<div class="alert alert-'.$erro['tipo'].'">'.$erro['texto'].'</div>';
    }
    ?>
    
    <div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3><i class="fas fa-chart-bar"></i> Cadastro de pessoa</h3>
                        <a href="index.php" class="btn btn-light">
                            <i class="fas fa-home"></i> Voltar ao Início
                        </a>
                    </div>
                </div>
                
                <form method="POST" id="formCadastro" onsubmit="return validarFormulario()">
                    <div class="form-group">
                        <label for="nome">Nome Completo*</label>
                        <input type="text" id="nome" name="nome" class="form-control" required 
                               value="<?= isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : '' ?>">
                    </div>
                    
                    <div class="form-group" id="cpf-container">
                        <label for="cpf">CPF* (apenas números)</label>
                        <input type="text" id="cpf" name="cpf" class="form-control" maxlength="11" 
                               value="<?= isset($_POST['cpf']) ? htmlspecialchars($_POST['cpf']) : '' ?>"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    </div>
                    
                    <div class="form-group">
                        <label for="sexo">Sexo*</label>
                        <select id="sexo" name="sexo" class="form-control" required>
                            <option value="M" <?= (isset($_POST['sexo']) && $_POST['sexo'] === 'M') ? 'selected' : '' ?>>Masculino</option>
                            <option value="F" <?= (isset($_POST['sexo']) && $_POST['sexo'] === 'F') ? 'selected' : '' ?>>Feminino</option>
                            <option value="O" <?= (isset($_POST['sexo']) && $_POST['sexo'] === 'O') ? 'selected' : '' ?>>Outro</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" class="form-control"
                               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="data_nascimento">Data de Nascimento*</label>
                        <input type="date" id="data_nascimento" name="data_nascimento" class="form-control" required
                               value="<?= isset($_POST['data_nascimento']) ? htmlspecialchars($_POST['data_nascimento']) : '' ?>"
                               max="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div class="form-group" id="observacoes-container" style="display: none;">
                        <label for="observacoes">Observações sobre a criança</label>
                        <textarea id="observacoes" name="observacoes" class="form-control"><?= isset($_POST['observacoes']) ? htmlspecialchars($_POST['observacoes']) : '' ?></textarea>
                    </div>
                    
                    <div class="form-group" id="responsavel-container" style="display: none;">
                        <label for="id_responsavel">Responsável*</label>
                        <select id="id_responsavel" name="id_responsavel" class="form-control">
                            <option value="">Selecione um responsável</option>
                            <?php
                            $responsaveis = $conn->query("SELECT ID_responsavel, nome, CPF FROM responsavel ORDER BY nome");
                            while($resp = $responsaveis->fetch(PDO::FETCH_ASSOC)) {
                                $selected = (isset($_POST['id_responsavel']) && $_POST['id_responsavel'] == $resp['ID_responsavel']) ? 'selected' : '';
                                echo '<option value="'.$resp['ID_responsavel'].'" '.$selected.'>'.
                                     htmlspecialchars($resp['nome']).' (CPF: '.mask($resp['CPF'], '###.###.###-##').')</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="configura_crianca" value="1" id="configura_crianca"
                                  <?= isset($_POST['configura_crianca']) ? 'checked' : '' ?>> Esta pessoa é uma criança
                        </label>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; margin-top: 20px;">
                        <button type="submit" name="addnew" class="btn btn-primary">
                            <i class="glyphicon glyphicon-floppy-disk"></i> Cadastrar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Validação do formulário
function validarFormulario() {
    const isCrianca = document.getElementById('configura_crianca').checked;
    
    if(!isCrianca) {
        const cpf = document.getElementById('cpf').value;
        if(cpf.length !== 11) {
            alert('CPF deve conter 11 dígitos');
            return false;
        }
    } else {
        const responsavel = document.getElementById('id_responsavel').value;
        if(!responsavel) {
            alert('Selecione um responsável para a criança');
            return false;
        }
    }
    
    return true;
}

// Mostrar/ocultar campos específicos
document.getElementById('configura_crianca').addEventListener('change', function() {
    const isCrianca = this.checked;
    
    // Mostrar/ocultar campos
    document.getElementById('observacoes-container').style.display = isCrianca ? 'block' : 'none';
    document.getElementById('responsavel-container').style.display = isCrianca ? 'block' : 'none';
    
    // CPF não obrigatório para crianças
    document.getElementById('cpf').required = !isCrianca;
    
    // Atualizar rótulo do CPF
    document.querySelector('#cpf-container label').textContent = 
        isCrianca ? 'CPF (opcional)' : 'CPF* (apenas números)';
});

// Verificar estado inicial ao carregar
window.addEventListener('load', function() {
    const isCrianca = document.getElementById('configura_crianca').checked;
    if(isCrianca) {
        document.getElementById('observacoes-container').style.display = 'block';
        document.getElementById('responsavel-container').style.display = 'block';
        document.getElementById('cpf').required = false;
        document.querySelector('#cpf-container label').textContent = 'CPF (opcional)';
    }
});
</script>

<?php 
// Função para mascara de CPF
function mask($val, $mask) {
    $maskared = '';
    $k = 0;
    for($i = 0; $i <= strlen($mask)-1; $i++) {
        if($mask[$i] == '#') {
            if(isset($val[$k]))
                $maskared .= $val[$k++];
        } else {
            if(isset($mask[$i]))
                $maskared .= $mask[$i];
        }
    }
    return $maskared;
}

require_once 'footer.php';
?>