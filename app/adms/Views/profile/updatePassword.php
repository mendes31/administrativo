<?php
// Verificar se o usuário está logado
if (empty($_SESSION['user_id'])) {
    header('Location: ' . $_ENV['URL_ADM'] . 'login');
    exit;
}

use App\adms\Helpers\CSRFHelper;
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Alterar Senha</h1>
    
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>profile">Meu Perfil</a></li>
        <li class="breadcrumb-item active">Alterar Senha</li>
    </ol>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-key me-1"></i>
                    Alterar Senha
                </div>
                <div class="card-body">
                    <?php include './app/adms/Views/partials/alerts.php'; ?>

                    <form action="" method="POST" class="row g-3">
                        <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_update_password_profile'); ?>">

                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-1"></i>
                                <strong>Requisitos da senha:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Mínimo de 6 caracteres</li>
                                    <li>Pelo menos uma letra</li>
                                    <li>Pelo menos um número</li>
                                    <li>Pelo menos um caractere especial</li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="password" class="form-label">Nova Senha</label>
                            <div class="input-group">
                                <input type="password" 
                                       name="password" 
                                       class="form-control <?php echo isset($this->data['errors']['password']) ? 'is-invalid' : ''; ?>" 
                                       id="password" 
                                       placeholder="Digite sua nova senha">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye" id="eyeIcon"></i>
                                </button>
                            </div>
                            <?php if (isset($this->data['errors']['password'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $this->data['errors']['password']; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label for="confirm_password" class="form-label">Confirmar Nova Senha</label>
                            <div class="input-group">
                                <input type="password" 
                                       name="confirm_password" 
                                       class="form-control <?php echo isset($this->data['errors']['confirm_password']) ? 'is-invalid' : ''; ?>" 
                                       id="confirm_password" 
                                       placeholder="Confirme sua nova senha">
                                <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                    <i class="fas fa-eye" id="eyeConfirmIcon"></i>
                                </button>
                            </div>
                            <?php if (isset($this->data['errors']['confirm_password'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $this->data['errors']['confirm_password']; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save me-1"></i>
                                Alterar Senha
                            </button>
                            <a href="<?php echo $_ENV['URL_ADM']; ?>profile" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>
                                Voltar ao Perfil
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Card com informações de segurança -->
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-shield-alt me-1"></i>
                    Dicas de Segurança
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li><i class="fas fa-check text-success me-2"></i>Use uma senha única para cada conta</li>
                        <li><i class="fas fa-check text-success me-2"></i>Evite informações pessoais óbvias</li>
                        <li><i class="fas fa-check text-success me-2"></i>Não compartilhe sua senha com ninguém</li>
                        <li><i class="fas fa-check text-success me-2"></i>Altere sua senha regularmente</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle para mostrar/ocultar senha
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');

    togglePassword.addEventListener('click', function() {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        eyeIcon.classList.toggle('fa-eye');
        eyeIcon.classList.toggle('fa-eye-slash');
    });

    // Toggle para mostrar/ocultar confirmação de senha
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const confirmPassword = document.getElementById('confirm_password');
    const eyeConfirmIcon = document.getElementById('eyeConfirmIcon');

    toggleConfirmPassword.addEventListener('click', function() {
        const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
        confirmPassword.setAttribute('type', type);
        eyeConfirmIcon.classList.toggle('fa-eye');
        eyeConfirmIcon.classList.toggle('fa-eye-slash');
    });

    // Validação em tempo real para confirmação de senha
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');

    function validatePasswordMatch() {
        if (confirmPasswordInput.value && passwordInput.value !== confirmPasswordInput.value) {
            confirmPasswordInput.setCustomValidity('As senhas não coincidem');
        } else {
            confirmPasswordInput.setCustomValidity('');
        }
    }

    passwordInput.addEventListener('input', validatePasswordMatch);
    confirmPasswordInput.addEventListener('input', validatePasswordMatch);
});
</script>
