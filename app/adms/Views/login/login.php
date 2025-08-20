<?php

use App\adms\Helpers\CSRFHelper;

// Exibir mensagem de erro vinda da query string
if (!empty($_GET['error'])) {
    echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($_GET['error']) . '</div>';
}
?>

<div class="col-lg-5">
    <div class="card shadow-lg border-0 rounded-lg mt-5">

        <div class="text-center mt-4">
            <img src="/administrativo2/public/adms/image/logo/Logo-Tiaraju.png" alt="Logo Tiaraju" style="max-width: 200px;">
        </div>

        <div class="card-header">
            <h3 class="text-center font-weight-light my-4">Login</h3>
        </div>

        <div class="card-body">
            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php // Inclui o arquivo que exibe mensagens de sucesso e erro
            include './app/adms/Views/partials/alerts.php';
            ?>

            <form action="" method="POST">
                <!-- Campo oculto para o token CSRF para proteger o formulário contra ataques de falsificação de solicitação entre sites -->
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_login'); ?>">


                <!-- Campo usuário -->
                <div class="form-floating mb-3">
                    <input type="text" name="username" class="form-control" id="username" placeholder="Digite seu usuário" value="<?php echo $this->data['form']['username'] ?? ''; ?>">
                    <label for="username">Usuário</label>
                </div>

                <!-- Campo para a senha do usuário -->
                <div class="form-floating mb-3">
                    <input type="password" name="password" class="form-control" id="password" placeholder="Digite sua senha." value="<?php echo $this->data['form']['password'] ?? ''; ?>">
                    <label for="password">Senha</label>
                </div>

                <div class="d-flex align-items-center justify-content-between mt-4 mb-0">
                    <a href="<?php echo $_ENV['URL_ADM']; ?>forgot-password" class="small text-decoration-none">Esqueceu a Senha?</a>
                    <!-- Botão para submeter o formulário -->
                    <button type="submit" class="btn btn-primary btn-sm">Acessar</button>
                </div>
            </form>

        </div>

        <div class="card-footer text-center py-3">
            <div class="small">
                <a href="<?php echo $_ENV['URL_ADM']; ?>new-user" class="text-decoration-none">Cadastrar</a>
            </div>
        </div>

    </div>
</div>