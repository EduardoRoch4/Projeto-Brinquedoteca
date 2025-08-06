<?php
// verificar_permissoes.php
// A sessão é iniciada no config.php, não é necessário iniciar aqui
require_once 'config.php';
require_once 'funcoes.php';

/**
 * Verifica se o usuário tem permissão para acessar uma página
 */
function verificarAcessoPagina($pagina) {
    if (!isset($_SESSION['usuario_logado']['nivel'])) {
        header('Location: loginFuncionario.php');
        exit;
    }

    $permissoes_pagina = [
        // Páginas que qualquer usuário logado pode acessar
        'index.php' => ['funcionario', 'gerente', 'admin'],
        'controle_emprestimos.php' => ['funcionario', 'gerente', 'admin'],
        'cadastro_responsavel.php' => ['funcionario', 'gerente', 'admin'],
        
        // Páginas que apenas gerente e admin podem acessar
        'cadastro_brinquedo.php' => ['gerente', 'admin'],
        'relatorios.php' => ['gerente', 'admin'],
        
        // Páginas exclusivas do admin
        'cadastro_funcionario.php' => ['admin'],
        'gerenciar_usuarios.php' => ['admin'],
        'configuracoes.php' => ['admin'],
        'editar_funcionario.php' => ['admin'],
        'excluir_funcionario.php' => ['admin'],
        'gerenciar_fornecedores.php' => ['admin'],
        'cadastro_fornecedor.php' => ['admin'],
        'editar_fornecedor.php' => ['admin'],
        'excluir_fornecedor.php' => ['admin']
    ];

    if (!isset($permissoes_pagina[$pagina])) {
        return true; // Se a página não estiver na lista, permite acesso
    }

    $nivel_usuario = $_SESSION['usuario_logado']['nivel'] ?? '';
    return in_array($nivel_usuario, $permissoes_pagina[$pagina]);
}

/**
 * Verifica o nível de acesso do usuário
 */
function verificarNivelAcesso($nivel_requerido) {
    if (!isset($_SESSION['usuario_logado']['nivel'])) {
        return false;
    }

    $nivel_usuario = $_SESSION['usuario_logado']['nivel'];
    
    // Define a hierarquia de níveis
    $hierarquia = [
        'funcionario' => 1,
        'gerente' => 2,
        'admin' => 3
    ];

    // Obtém os valores numéricos dos níveis
    $nivel_usuario_valor = $hierarquia[$nivel_usuario] ?? 0;
    $nivel_requerido_valor = $hierarquia[$nivel_requerido] ?? 0;

    // Verifica se o nível do usuário é maior ou igual ao nível requerido
    return $nivel_usuario_valor >= $nivel_requerido_valor;
}

/**
 * Verifica se o usuário tem permissão para realizar uma ação
 */
function verificarPermissaoAcao($acao) {
    if (!isset($_SESSION['usuario_logado']['nivel'])) {
        return false;
    }

    $permissoes_acao = [
        // Ações que qualquer usuário logado pode realizar
        'realizar_emprestimo' => 'funcionario',
        'registrar_emprestimo' => 'funcionario',
        'registrar_devolucao' => 'funcionario',
        'cadastrar_responsavel' => 'funcionario',
        
        // Ações que gerente e admin podem realizar
        'gerenciar_emprestimos' => 'gerente',
        'cadastrar_brinquedo' => 'gerente',
        'editar_brinquedo' => 'gerente',
        'gerar_relatorios' => 'gerente',
        'gerenciar_itens' => 'gerente',
        
        // Ações exclusivas do admin
        'cadastrar_funcionario' => 'admin',
        'editar_funcionario' => 'admin',
        'excluir_funcionario' => 'admin',
        'gerenciar_usuarios' => 'admin',
        'configurar_sistema' => 'admin',
        'gerenciar_fornecedores' => 'admin',
        'cadastrar_fornecedor' => 'admin',
        'editar_fornecedor' => 'admin',
        'excluir_fornecedor' => 'admin',
        'gerenciar_areas' => 'admin',
        'cadastrar_area' => 'admin',
        'editar_area' => 'admin',
        'excluir_area' => 'admin'
    ];

    if (!isset($permissoes_acao[$acao])) {
        error_log("Ação não encontrada nas permissões: " . $acao);
        return false;
    }

    $nivel_requerido = $permissoes_acao[$acao];
    return verificarNivelAcesso($nivel_requerido);
}
?> 