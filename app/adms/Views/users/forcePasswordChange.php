<?php
use App\adms\Helpers\CSRFHelper;
$policy = $this->data['password_policy'] ?? null;
?>
<div class="d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="col-lg-6 col-md-8 col-sm-11">
        <div class="card shadow-lg border-0 rounded-lg">
            <div class="card-header bg-success text-white py-2">
                <h3 class="text-center font-weight-light my-1">Troca Obrigatória de Senha</h3>
            </div>
            <div class="card-body py-3 px-4">
                <?php include './app/adms/Views/partials/alerts.php'; ?>

                <div class="alert alert-warning small mb-3" role="alert">
                    Por segurança, você deve definir uma nova senha antes de acessar o sistema.
                </div>

                <?php if (!empty($policy)): ?>
                    <div class="alert alert-light border small mb-3">
                        <strong>Requisitos mínimos da senha</strong>
                        <ul class="mb-0 mt-2">
                            <li>Mínimo de <?php echo (int)($policy->comprimento_minimo ?? 0); ?> caracteres.</li>
                            <?php if (!empty($policy->min_maiusculas) && (int)$policy->min_maiusculas > 0): ?>
                                <li>Pelo menos <?php echo (int)$policy->min_maiusculas; ?> letra(s) maiúscula(s).</li>
                            <?php endif; ?>
                            <?php if (!empty($policy->min_minusculas) && (int)$policy->min_minusculas > 0): ?>
                                <li>Pelo menos <?php echo (int)$policy->min_minusculas; ?> letra(s) minúscula(s).</li>
                            <?php endif; ?>
                            <?php if (!empty($policy->min_digitos) && (int)$policy->min_digitos > 0): ?>
                                <li>Pelo menos <?php echo (int)$policy->min_digitos; ?> dígito(s).</li>
                            <?php endif; ?>
                            <?php if (!empty($policy->min_nao_alfanumericos) && (int)$policy->min_nao_alfanumericos > 0): ?>
                                <li>Pelo menos <?php echo (int)$policy->min_nao_alfanumericos; ?> caractere(s) especial(is).</li>
                            <?php endif; ?>
                            <?php if (!empty($policy->historico_senhas) && (int)$policy->historico_senhas > 0): ?>
                                <li>Não pode coincidir com as últimas <?php echo (int)$policy->historico_senhas; ?> senha(s) utilizadas.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (!empty($this->data['errors'])): ?>
                    <div class="alert alert-danger small">
                        <?php foreach ($this->data['errors'] as $error): ?>
                            <div><?php echo htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_force_password_change'); ?>">
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
                    <button type="submit" class="btn btn-success w-100">Alterar Senha</button>
                </form>
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