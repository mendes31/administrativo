<?php
use App\adms\Helpers\CSRFHelper;
?>
<div class="container-fluid px-4">
    <div class="row justify-content-center align-items-center" style="min-height: 60vh;">
        <div class="col-md-6 col-lg-4">
            <div class="card mt-5">
                <div class="card-body">
                    <h4 class="card-title mb-4 text-center">Troca Obrigatória de Senha</h4>
                    <div class="alert alert-warning text-center" role="alert">
                        <strong>Troca de Senha Obrigatória</strong><br>
                        Por segurança, você deve definir uma nova senha antes de acessar o sistema.
                    </div>
                    <?php if (!empty($this->data['errors'])): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($this->data['errors'] as $error): ?>
                                <div><?= $error ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('form_force_password_change') ?>">
                        <div class="mb-3">
                            <label for="password" class="form-label">Nova Senha</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" required autofocus
                                       oninput="this.value = this.value.replace(/\s/g, '')" 
                                       onpaste="this.value = this.value.replace(/\s/g, '')"
                                       autocomplete="new-password">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="password_confirm" class="form-label">Confirme a Nova Senha</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password_confirm" name="confirm_password" required
                                       oninput="this.value = this.value.replace(/\s/g, '')" 
                                       onpaste="this.value = this.value.replace(/\s/g, '')"
                                       autocomplete="new-password">
                                <button class="btn btn-outline-secondary" type="button" id="togglePasswordConfirm">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Alterar Senha</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
window.onload = function() {
    var modal = document.getElementById('modalTrocaSenhaObrigatoria');
    if (modal) {
        modal.classList.add('show');
        modal.style.display = 'block';
        document.body.classList.add('modal-open');
    }
    // Bloqueia navegação
    document.querySelectorAll('a, button').forEach(function(el) {
        if (!el.closest('form')) el.onclick = function(e) { e.preventDefault(); };
    });
    
    // Verificar se o token CSRF foi gerado corretamente
    const csrfToken = document.querySelector('input[name="csrf_token"]');
    if (csrfToken && csrfToken.value) {
        console.log('Token CSRF gerado:', csrfToken.value);
    } else {
        console.error('Token CSRF não foi gerado!');
        // Tentar regenerar o token
        location.reload();
    }
};

// Prevenir submissão dupla do formulário
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
            }
        });
    }
});

// Toggle visualização de senha
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const passwordToggle = document.getElementById('togglePassword');
    const confirmInput = document.getElementById('password_confirm');
    const confirmToggle = document.getElementById('togglePasswordConfirm');

    function toggleVisibility(input, button) {
        if (!input || !button) return;
        const icon = button.querySelector('i');
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        if (icon) {
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        }
    }

    if (passwordToggle && passwordInput) {
        passwordToggle.addEventListener('click', function () {
            toggleVisibility(passwordInput, passwordToggle);
        });
    }

    if (confirmToggle && confirmInput) {
        confirmToggle.addEventListener('click', function () {
            toggleVisibility(confirmInput, confirmToggle);
        });
    }
});
</script> 