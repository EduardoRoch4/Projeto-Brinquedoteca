<?php 
require_once 'conexao.php';
require_once 'header.php';

// Obtém a conexão PDO
$conn = require 'conexao.php';

echo "<div class='container'>";

// Adicionando os botões de navegação no topo da página
echo "<div style='margin-bottom: 20px;'>";
echo "<a href='index.php' class='btn btn-default' style='margin-right: 10px;'>";
echo "<i class='glyphicon glyphicon-home'></i> Voltar à Página Inicial";
echo "</a>";
echo "<a href='cadastroFornecedor.php' class='btn btn-success'>";
echo "<i class='glyphicon glyphicon-plus'></i> Novo Fornecedor";
echo "</a>";
echo "</div>";

// Processa a exclusão se solicitada
if(isset($_POST['delete'])){
    try {
        $sql = "DELETE FROM fornecedor WHERE ID_fornecedor = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $_POST['fornecedorid'], PDO::PARAM_INT);
        
        if($stmt->execute()){
            $_SESSION['mensagem'] = [
                'tipo' => 'success',
                'texto' => 'Fornecedor removido com sucesso!'
            ];
            header('Location: listaFornecedores.php');
            exit;
        }
    } catch(PDOException $e) {
        $_SESSION['mensagem'] = [
            'tipo' => 'danger',
            'texto' => 'Erro ao remover: ' . $e->getMessage()
        ];
    }
}

// Exibe mensagens
if(isset($_SESSION['mensagem'])) {
    echo '<div class="alert alert-'.$_SESSION['mensagem']['tipo'].'">'.$_SESSION['mensagem']['texto'].'</div>';
    unset($_SESSION['mensagem']);
}

// Consulta todos os fornecedores cadastrados
try {
    $sql = "SELECT * FROM fornecedor ORDER BY nome_fornecedor ASC";
    $result = $conn->query($sql); 

    if($result && $result->rowCount() > 0) { 
        ?>
        <h2><i class="glyphicon glyphicon-briefcase"></i> Fornecedores Cadastrados</h2>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Nome/Razão Social</th>
                    <th>CNPJ</th>
                    <th>E-mail</th>
                    <th>Data Cadastro</th>
                    <th width="120px">Ações</th>
                </tr>
            </thead>
            <tbody>
        <?php
        while($row = $result->fetch(PDO::FETCH_ASSOC)){ 
            echo "<form action='' method='POST'>";
            echo "<input type='hidden' value='".$row['ID_fornecedor']."' name='fornecedorid' />";
            echo "<tr>";
            echo "<td>".htmlspecialchars($row['nome_fornecedor'])."</td>";
            echo "<td>".mask($row['cnpj'], '##.###.###/####-##')."</td>";
            echo "<td>".htmlspecialchars($row['email'] ?? 'N/A')."</td>";
            echo "<td>".date('d/m/Y H:i', strtotime($row['data_cadastro']))."</td>";
            echo "<td>
                    <div class='btn-group' role='group'>
                        <a href='editar_fornecedor.php?id=".$row['ID_fornecedor']."' 
                           class='btn btn-sm btn-info' 
                           data-toggle='tooltip' 
                           data-placement='top' 
                           title='Editar fornecedor'
                           style='margin-right: 5px; transition: all 0.3s ease;'>
                            <i class='glyphicon glyphicon-edit'></i> Editar
                        </a>
                        <button type='submit' 
                                name='delete' 
                                value='Delete' 
                                class='btn btn-sm btn-danger' 
                                data-toggle='tooltip' 
                                data-placement='top' 
                                title='Excluir fornecedor'
                                onclick=\"return confirm('Tem certeza que deseja excluir o fornecedor ".htmlspecialchars($row['nome_fornecedor'])."?\\nEsta ação não poderá ser desfeita.');\"
                                style='transition: all 0.3s ease;'>
                            <i class='glyphicon glyphicon-trash'></i> Excluir
                        </button>
                    </div>
                  </td>";
            echo "</tr>";
            echo "</form>";
        }
        ?>
            </tbody>
        </table>
    <?php 
    } else {
        echo "<div class='alert alert-info'>Nenhum fornecedor cadastrado encontrado.</div>";
    }
} catch(PDOException $e) {
    echo "<div class='alert alert-danger'>Erro ao carregar fornecedores: " . $e->getMessage() . "</div>";
}
?> 
</div>

<?php 
// Função para máscara de CNPJ, CEP e Telefone
function mask($val, $mask) {
    if(empty($val)) return '';
    
    $val = preg_replace('/[^0-9]/', '', $val);
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

echo '<script>
$(document).ready(function(){
    // Inicializa os tooltips
    $("[data-toggle=\'tooltip\']").tooltip();
    
    // Adiciona efeito hover nos botões
    $(".btn").hover(
        function() {
            $(this).css("transform", "scale(1.05)");
        },
        function() {
            $(this).css("transform", "scale(1)");
        }
    );
});
</script>';
?>