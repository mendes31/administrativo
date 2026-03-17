<?php

use App\adms\Helpers\CSRFHelper;

// Modal de troca obrigatória de senha
if (!empty($_SESSION['force_password_change'])): ?>
    <div class="modal fade" id="modalTrocaSenhaObrigatoria" tabindex="-1" aria-labelledby="modalTrocaSenhaObrigatoriaLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-warning">
            <h5 class="modal-title" id="modalTrocaSenhaObrigatoriaLabel">Troca de Senha Obrigatória</h5>
          </div>
          <div class="modal-body">
            Por segurança, você deve definir uma nova senha antes de acessar o sistema.
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Entendi</button>
          </div>
        </div>
      </div>
    </div>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        var modal = new bootstrap.Modal(document.getElementById('modalTrocaSenhaObrigatoria'));
        modal.show();
      });
    </script>
<?php unset($_SESSION['force_password_change']); endif; ?>



<div class="container-fluid px-4">

    <div class="mb-1 d-flex flex-column flex-sm-row gap-2">
        <div>
            <h2 class="mt-3">Usuários</h2>
            <?php if (isset($this->data['user_info'])): ?>
                <div class="alert alert-info py-2 px-3 mb-0 border-0" style="background-color: #e3f2fd; border-left: 4px solid #2196f3 !important;">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-user-circle text-primary me-2 fs-5"></i>
                        <div>
                            <strong class="text-primary">Usuário Selecionado:</strong>
                            <span class="fw-bold text-dark"><?php echo htmlspecialchars($this->data['user_info']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="badge bg-secondary ms-2">ID: <?php echo (int)($this->data['user_info']['id'] ?? 0); ?></span>
                            <span class="text-muted ms-2"><?php echo htmlspecialchars($this->data['user_info']['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <ol class="breadcrumb mb-3 mt-0 mt-sm-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>list-users" class="text-decoration-none">Usuários</a></li>
            <li class="breadcrumb-item">Editar Senha</li>
        </ol>

    </div>

    <div class="card mb-4 border-light shadow">

        <div class="card-header hstack gap-2">

            <span>Editar</span>

            <span class="ms-auto d-sm-flex flex-row">
            <?php
            if (empty($_GET['force'])) { // Só exibe botões se não for troca obrigatória
                if (in_array('ListUsers', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}list-users' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-list'></i> Listar</a> ";
                }

                $id = ($this->data['form']['id'] ?? '');
                if (in_array('ViewUser', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}view-user/$id' class='btn btn-primary btn-sm me-1 mb-1'><i class='fa-regular fa-eye'></i> Visualizar</a> ";
                }
            }
            ?>
            </span>

        </div>

        <div class="card-body">

            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <form action="" method="POST" class="row g-3">

                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_update_password_user'); ?>">

                <input type="hidden" name="id" id="id" value="<?php echo $this->data['form']['id'] ?? ''; ?>">

                <input type="hidden" name="email" id="email" value="<?php echo $this->data['form']['email'] ?? ''; ?>">

                <div class="col-md-6">
                    <label for="password" class="form-label">Senha</label>
                    <input type="password" name="password" class="form-control" id="password" placeholder="Senha minímo 6 caracteres e deve conter letra, número e caractere especial." value="<?php echo $this->data['form']['password'] ?? ''; ?>"
                       oninput="this.value = this.value.replace(/\s/g, '')" 
                       onpaste="this.value = this.value.replace(/\s/g, '')"
                       autocomplete="new-password">
                </div>

                <div class="col-md-6">
                    <label for="confirm_password" class="form-label">Confirmar a Senha</label>
                    <input type="password" name="confirm_password" class="form-control" id="confirm_password" placeholder="Confirmar a senha" value="<?php echo $this->data['form']['confirm_password'] ?? ''; ?>"
                       oninput="this.value = this.value.replace(/\s/g, '')" 
                       onpaste="this.value = this.value.replace(/\s/g, '')"
                       autocomplete="new-password">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Gerar senha automática</label><br>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="gerar_senha" name="gerar_senha" value="1"
                            <?php echo !empty($this->data['form']['gerar_senha']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="gerar_senha">
                            Usar data de nascimento como senha provisória (ddmmaaaa)
                        </label>
                    </div>
                    <div class="form-text">
                        Quando marcado, a senha digitada acima será ignorada e a senha provisória será gerada a partir da data de nascimento do usuário.
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Modificar senha no próximo logon</label><br>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="modificar_senha_proximo_logon" name="modificar_senha_proximo_logon" value="Sim"
                            <?php echo (isset($this->data['form']['modificar_senha_proximo_logon']) && $this->data['form']['modificar_senha_proximo_logon'] === 'Sim') ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="modificar_senha_proximo_logon">
                            Exigir troca de senha após o próximo acesso
                        </label>
                    </div>
                    <div class="form-text">
                        Quando marcado, o usuário será obrigado a definir uma nova senha no próximo login.
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Notificar usuário</label>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="enviar_notificacao_email" name="enviar_notificacao_email" value="1"
                            <?php echo !empty($this->data['form']['enviar_notificacao_email']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="enviar_notificacao_email">
                            Enviar por e-mail
                        </label>
                    </div>
                    <div class="form-check form-switch mt-1">
                        <input class="form-check-input" type="checkbox" id="enviar_notificacao_whatsapp" name="enviar_notificacao_whatsapp" value="1"
                            <?php echo !empty($this->data['form']['enviar_notificacao_whatsapp']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="enviar_notificacao_whatsapp">
                            Enviar por WhatsApp
                        </label>
                    </div>
                    <div class="form-text">
                        Marque ao menos uma opção para enviar a notificação ao usuário.
                    </div>
                </div>

                <div class="col-md-4">
                    <label for="tipo_mensagem" class="form-label">Tipo de mensagem</label>
                    <select name="tipo_mensagem" id="tipo_mensagem" class="form-select">
                        <?php
                        $tipoMensagem = $this->data['form']['tipo_mensagem'] ?? 'unlock';
                        ?>
                        <option value="unlock" <?php echo ($tipoMensagem === 'unlock') ? 'selected' : ''; ?>>
                            Senha provisória / desbloqueio
                        </option>
                        <option value="welcome" <?php echo ($tipoMensagem === 'welcome') ? 'selected' : ''; ?>>
                            Boas-vindas (acesso inicial)
                        </option>
                    </select>
                    <div class="form-text">
                        Define se o texto será de boas-vindas para primeiro acesso ou de desbloqueio com senha provisória.
                    </div>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-warning btn-sm">Salvar</button>
                </div>

            </form>

        </div>
    </div>

</div>