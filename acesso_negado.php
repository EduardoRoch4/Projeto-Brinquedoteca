<?php
session_start();
require_once 'header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Acesso Negado</h4>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-lock fa-4x text-danger mb-3"></i>
                        <h5>Você não tem permissão para acessar esta página</h5>
                        <p class="text-muted">
                            <?= isset($_SESSION['mensagem']['texto']) ? $_SESSION['mensagem']['texto'] : 'Por favor, entre em contato com o administrador do sistema.' ?>
                        </p>
                    </div>
                    <div class="text-center">
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-home"></i> Voltar ao Início
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
// Limpa a mensagem após exibir
unset($_SESSION['mensagem']);
require_once 'footer.php'; 
?> 