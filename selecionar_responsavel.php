<?php
session_start();
require_once 'conexao.php';

// Inicia a sessão se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'header.php';

if(!isset($_SESSION['dados_crianca'])) {
    header('Location: cadastro.php');
    exit;
}

$conn = require 'conexao.php';
$stmt = $conn->query("SELECT ID_responsavel, nome, CPF FROM responsavel");
?>

<div class="container">
    <h2>Selecione um Responsável</h2>
    <form method="POST" action="finalizar_cadastro_crianca.php">
        <div class="form-group">
            <label>Responsável*</label>
            <select name="id_responsavel" class="form-control" required>
                <option value="">Selecione um responsável</option>
                <?php while($resp = $stmt->fetch()): ?>
                    <option value="<?= $resp['ID_responsavel'] ?>">
                        <?= htmlspecialchars($resp['nome']) ?> (CPF: <?= $resp['CPF'] ?>)
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary">Continuar Cadastro</button>
        <a href="cadastro.php" class="btn btn-default">Cancelar</a>
    </form>
</div>

<?php require_once 'footer.php'; ?>