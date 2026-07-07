<?php
require __DIR__ . '/_view_scope.php';
/** @var string $base_url */
/** @var string|null $error */
/** @var string $csrf_token */
?>
<div class="canal-card">
    <div class="canal-header">
        <h1><i class="fas fa-search me-2"></i>Acompanhar denúncia</h1>
        <p class="text-muted">Informe o protocolo e a senha recebidos no momento do registro.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" action="<?php echo htmlspecialchars($base_url . 'consultar', ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)($csrf_token ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

        <div class="mb-3">
            <label class="form-label fw-semibold">Protocolo</label>
            <input type="text" name="protocol" class="form-control font-monospace text-uppercase"
                placeholder="CD-2026-XXXX-XXXX" required autocomplete="off">
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Senha</label>
            <input type="text" name="password" class="form-control font-monospace text-uppercase"
                placeholder="Senha de 6 caracteres" required autocomplete="off" maxlength="12">
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-canal-primary">
                <i class="fas fa-unlock me-1"></i>Consultar
            </button>
            <a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary">Voltar</a>
        </div>
    </form>
</div>
