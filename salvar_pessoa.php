<?php
require_once 'conexao.php';
require_once 'funcoes_pessoa.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dados = [
        'nome' => $_POST['nome'],
        'cpf' => $_POST['cpf'],
        'sexo' => $_POST['sexo'],
        'email' => $_POST['email'],
        'data_nascimento' => $_POST['data_nascimento'],
        'data_cadastro' => date("Y-m-d"),
        'configura_crianca' => 0
    ];

    $resultado = cadastrarPessoa($conn, $dados);

    if ($resultado === true) {
        echo "Cadastro realizado com sucesso!";
    } else {
        echo $resultado;
    }
} else {
    echo "Requisição inválida.";
}
?>
