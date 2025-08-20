<?php

use App\adms\Helpers\CSRFHelper;

?>

<div class="col-lg-5">
    <div class="card shadow-lg border-0 rounded-lg mt-5">
        <div class="card-header">
            <h3 class="text-center font-weight-light my-4">Recuperar Senha</h3>
        </div>
        <div class="card-body">

            <?php
            // Inclui o arquivo que exibe mensagens de sucesso e erro
            include './app/adms/Views/partials/alerts.php';
            ?>

            <form method="POST" action="">

                <!-- Campo oculto para o token CSRF para proteger o formulário contra ataques de falsificação de solicitação entre sites -->
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_forgot_password'); ?>">

                <div class="form-floating mb-3">
                    <input type="email" name="email" class="form-control" id="email"
                        placeholder="Digite o e-mail cadastrado" value="<?php echo $this->data['form']['email'] ?? ''; ?>">
                    <label for="email">E-mail</label>
                </div>

                <div class="d-flex align-items-center justify-content-between mt-4 mb-0">
                    <button type="submit" class="btn btn-primary btn-sm">Recuperar</button>
                </div>

            </form>
        </div>

        <div class="card-footer text-center py-3">
            <div class="small">
                <a href="<?php echo $_ENV['URL_ADM']; ?>login" class="text-decoration-none">Clique aqui</a> para acessar.
            </div>
        </div>

    </div>
</div>