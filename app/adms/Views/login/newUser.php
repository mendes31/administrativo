<?php

use App\adms\Helpers\CSRFHelper;

?>

<div class="col-lg-5">
    <div class="card shadow-lg border-0 rounded-lg mt-5">
        <div class="card-header">
            <h3 class="text-center font-weight-light my-4">Novo Usuário</h3>
        </div>
        <div class="card-body">

            <?php

            // Inclui o arquivo que exibe mensagens de sucesso e erro
            include './app/adms/Views/partials/alerts.php';

            ?>

            <form method="POST" action="">

                <!-- Campo oculto para o token CSRF para proteger o formulário contra ataques de falsificação de solicitação entre sites -->
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_new_user'); ?>">

                <div class="form-floating mb-3">
                    <input type="text" name="name" class="form-control" id="name"
                        placeholder="Nome completo" value="<?php echo $this->data['form']['name'] ?? ''; ?>">
                    <label for="email">Nome</label>
                </div>

                <div class="form-floating mb-3">
                    <input type="email" name="email" class="form-control" id="email"
                        placeholder="Melhor e-mail" value="<?php echo $this->data['form']['email'] ?? ''; ?>">
                    <label for="email">E-mail</label>
                </div>

                <div class="form-floating mb-3">
                    <input type="text" name="username" class="form-control" id="username"
                        placeholder="Cadastre um usuário dispovível." value="<?php echo $this->data['form']['username'] ?? ''; ?>">
                    <label for="username">Usuário</label>
                </div>

                <div class="form-floating mb-3">
                    <input type="password" name="password" class="form-control" id="password"
                        placeholder="Senha com mínimo 6 caracteres" value="<?php echo $this->data['form']['password'] ?? ''; ?>">
                    <label for="password">Senha</label>
                </div>

                <div class="form-floating mb-3">
                    <input type="password" name="confirm_password" class="form-control" id="confirm_password"
                        placeholder="Confirmar a Senha" value="<?php echo $this->data['form']['confirm_password'] ?? ''; ?>">
                    <label for="password">Confirmar a Senha</label>
                </div>

                <div class="d-flex align-items-center justify-content-between mt-4 mb-0">
                    <button type="submit" class="btn btn-primary btn-sm">Cadastrar</button>
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