<?php
function cadastrarResponsavel($conn, $dados) {
    try {
        $sql = "INSERT INTO responsavel (
            nome, 
            CPF, 
            sexo, 
            email, 
            data_nascimento, 
            data_cadastro
        ) VALUES (
            :nome, 
            :cpf, 
            :sexo, 
            :email, 
            :data_nascimento, 
            :data_cadastro
        )";
        
        $stmt = $conn->prepare($sql);
        
        $stmt->execute([
            ':nome' => $dados['nome'],
            ':cpf' => $dados['cpf'],
            ':sexo' => $dados['sexo'],
            ':email' => $dados['email'],
            ':data_nascimento' => $dados['data_nascimento'],
            ':data_cadastro' => $dados['data_cadastro']
        ]);
        
        return true;
    } catch (PDOException $e) {
        if($e->errorInfo[1] == 1062) {
            return "CPF ou e-mail já cadastrado no sistema";
        }
        return "Erro ao cadastrar responsável: " . $e->getMessage();
    }
}

function cadastrarCrianca($conn, $dados, $id_responsavel) {
    try {
        $sql = "INSERT INTO crianca (
            nome_crianca, 
            data_nascimento, 
            sexo, 
            data_cadastro,
            observacoes,
            ID_responsavel
        ) VALUES (
            :nome, 
            :data_nascimento, 
            :sexo, 
            :data_cadastro,
            :observacoes,
            :id_responsavel
        )";
        
        $stmt = $conn->prepare($sql);
        
        $stmt->execute([
            ':nome' => $dados['nome'],
            ':data_nascimento' => $dados['data_nascimento'],
            ':sexo' => $dados['sexo'],
            ':data_cadastro' => $dados['data_cadastro'],
            ':observacoes' => $dados['observacoes'],
            ':id_responsavel' => $id_responsavel
        ]);
        
        return true;
    } catch (PDOException $e) {
        return "Erro ao cadastrar criança: " . $e->getMessage();
    }
}
?>