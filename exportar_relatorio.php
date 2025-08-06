<?php
session_start();
require_once 'conexao.php';


$conn = require 'conexao.php';

// Processa os dados do formulário
$tipo = $_POST['tipo_relatorio'] ?? '';
$data_inicio = $_POST['data_inicio'] ?? null;
$data_fim = $_POST['data_fim'] ?? null;

try {
    // Inicializa a variável $stmt
    $stmt = null;
    $sql = '';
    
    // Consulta os dados
    switch ($tipo) {
        case 'itens_mais_emprestados':
            $sql = "SELECT i.nome_item as 'Item', COUNT(e.ID_emprestimo) as 'Total de Empréstimos'
                    FROM item i
                    LEFT JOIN emprestimo e ON i.ID_item = e.ID_item
                    WHERE i.ativo = 1";
            
            if ($data_inicio && $data_fim) {
                $sql .= " AND e.data_emprestimo BETWEEN :data_inicio AND :data_fim";
            }
            
            $sql .= " GROUP BY i.ID_item
                      ORDER BY COUNT(e.ID_emprestimo) DESC
                      LIMIT 10";
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
            break;
            
        case 'status_itens':
            $sql = "SELECT 
                        tipo_item as 'Tipo de Item',
                        COUNT(*) as 'Total',
                        SUM(CASE WHEN quantidade_disponivel > 0 THEN 1 ELSE 0 END) as 'Disponíveis',
                        SUM(CASE WHEN quantidade_disponivel <= 0 THEN 1 ELSE 0 END) as 'Indisponíveis'
                    FROM item
                    WHERE ativo = 1
                    GROUP BY tipo_item";
            break;
            
        case 'emprestimos_periodo':
            if (!$data_inicio || !$data_fim) {
                throw new Exception("Para este relatório, é necessário informar o período");
            }
            
            $sql = "SELECT 
                        DATE_FORMAT(data_emprestimo, '%Y-%m-%d') as 'Data',
                        COUNT(*) as 'Total de Empréstimos'
                    FROM emprestimo
                    WHERE data_emprestimo BETWEEN :data_inicio AND :data_fim
                    GROUP BY DATE_FORMAT(data_emprestimo, '%Y-%m-%d')
                    ORDER BY Data";
            break;
            
        default:
            throw new Exception("Tipo de relatório inválido");
    }
    
    // Prepara e executa a consulta
    if (!empty($sql)) {
        $stmt = $conn->prepare($sql);
        
        // Bind dos parâmetros quando necessário
        if (($tipo === 'itens_mais_emprestados' || $tipo === 'usuarios_ativos' || $tipo === 'emprestimos_periodo') 
            && $data_inicio && $data_fim) {
            $stmt->bindParam(':data_inicio', $data_inicio);
            $stmt->bindParam(':data_fim', $data_fim);
        }
        
        $stmt->execute();
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Configurações para o arquivo Excel
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="relatorio_'.date('Ymd_His').'_'.$tipo.'.xls"');
        
        // Gera o conteúdo do Excel
        echo '<table border="1">';
        
        // Cabeçalhos
        if (!empty($dados)) {
            echo '<tr>';
            foreach (array_keys($dados[0]) as $cabecalho) {
                echo '<th>'.htmlspecialchars($cabecalho).'</th>';
            }
            echo '</tr>';
            
            // Dados
            foreach ($dados as $linha) {
                echo '<tr>';
                foreach ($linha as $valor) {
                    echo '<td>'.htmlspecialchars($valor).'</td>';
                }
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="'.count(array_keys($dados[0] ?? [])).'">Nenhum dado encontrado</td></tr>';
        }
        
        echo '</table>';
        exit;
    }
    
} catch (PDOException $e) {
    $_SESSION['mensagem'] = ['tipo' => 'danger', 'texto' => 'Erro ao exportar relatório: ' . $e->getMessage()];
} catch (Exception $e) {
    $_SESSION['mensagem'] = ['tipo' => 'warning', 'texto' => $e->getMessage()];
}

// Redireciona de volta se houver erro
header('Location: relatorio.php');
exit;
?>