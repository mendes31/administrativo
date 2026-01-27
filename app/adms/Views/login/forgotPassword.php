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
                    <input type="text" name="email" class="form-control" id="email"
                        placeholder="Informe o e-mail ou CPF cadastrado"
                        value="<?php echo htmlspecialchars($this->data['form']['email'] ?? ''); ?>">
                    <label for="email">E-mail ou CPF</label>
                </div>

                <div class="mb-3">
                    <label class="form-label d-block">Como deseja receber o link de recuperação?</label>
                    <?php
                        $deliveryMethod = $this->data['form']['delivery_method'] ?? 'email';
                    ?>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="delivery_method" id="delivery_email"
                            value="email" <?php echo $deliveryMethod === 'email' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="delivery_email">
                            Receber por <strong>e-mail</strong>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="delivery_method" id="delivery_whatsapp"
                            value="whatsapp" <?php echo $deliveryMethod === 'whatsapp' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="delivery_whatsapp">
                            Receber por <strong>WhatsApp</strong> (usará o celular cadastrado)
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="delivery_method" id="delivery_both"
                            value="both" <?php echo $deliveryMethod === 'both' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="delivery_both">
                            Receber em <strong>e-mail e WhatsApp</strong>
                        </label>
                    </div>
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