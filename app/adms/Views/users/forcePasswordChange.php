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
                            <input type="password" class="form-control" id="password" name="password" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label for="password_confirm" class="form-label">Confirme a Nova Senha</label>
                            <input type="password" class="form-control" id="password_confirm" name="confirm_password" required>
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
};
</script> 