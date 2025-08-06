<?php
if (!function_exists('validarCNPJ')) {
    function validarCNPJ($cnpj) {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        return strlen($cnpj) === 14;
    }
}

if (!function_exists('cadastrarEndereco')) {
    function cadastrarEndereco($conn, $cep) {
        try {
            // Verifica se o CEP já existe
            $stmt = $conn->prepare("SELECT ID_endereco FROM endereco WHERE cep = ?");
            $stmt->execute([$cep]);
            $endereco = $stmt->fetch();
            
            if(!$endereco) {
                // Se não existe, cadastra um novo endereço com apenas o CEP
                $stmt = $conn->prepare("INSERT INTO endereco (cep) VALUES (?)");
                $stmt->execute([$cep]);
                return $conn->lastInsertId();
            }
            return $endereco['ID_endereco'];
        } catch (PDOException $e) {
            error_log("Erro ao cadastrar endereço: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('cadastrarFornecedor')) {
    function cadastrarFornecedor($conn, $dados) {
        try {
            // Primeiro cadastra o endereço (CEP)
            $id_endereco = cadastrarEndereco($conn, $dados['cep']);
            if(!$id_endereco) {
                throw new Exception("Erro ao cadastrar endereço");
            }

            

            // Cadastra o fornecedor
            $sql = "INSERT INTO fornecedor (
                      nome_fornecedor, 
                      cnpj, 
                      email, 
                      cep, 
                      data_cadastro
                    ) VALUES (
                      :nome_fornecedor, 
                      :cnpj, 
                      :email, 
                      :cep, 
                      :data_cadastro
                    )";

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':nome_fornecedor', $dados['nome_fornecedor']);
            $stmt->bindParam(':cnpj', $dados['cnpj']);
            $stmt->bindParam(':email', $dados['email']);
            $stmt->bindParam(':cep', $id_endereco);
            $stmt->bindParam(':data_cadastro', $dados['data_cadastro']);
                        
            return $stmt->execute();

        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                return "Erro: CNPJ já cadastrado no sistema.";
            }
            return "Erro ao cadastrar fornecedor: " . $e->getMessage();
        }
    }
}
?>