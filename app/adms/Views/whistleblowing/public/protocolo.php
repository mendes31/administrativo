<?php
require __DIR__ . '/_view_scope.php';
/** @var string $base_url */
/** @var string $protocol */
/** @var string $password */
?>
<div class="canal-card">
    <div class="canal-header">
        <h1><i class="fas fa-check-circle text-success me-2"></i>Denúncia registrada</h1>
        <p class="text-muted">Guarde os dados abaixo. Eles <strong>não serão exibidos novamente</strong>.</p>
    </div>

    <div class="protocol-box">
        <div class="mb-3">
            <div class="text-muted small text-uppercase fw-semibold">Protocolo</div>
            <div class="code"><?php echo htmlspecialchars((string)($protocol ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
        <div>
            <div class="text-muted small text-uppercase fw-semibold">Senha de acompanhamento</div>
            <div class="code"><?php echo htmlspecialchars((string)($password ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
    </div>

    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-1"></i>
        Sem o protocolo e a senha não será possível acompanhar sua denúncia.
        Anote ou imprima esta página agora.
    </div>

    <div class="d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-canal-primary" onclick="window.print()">
            <i class="fas fa-print me-1"></i>Imprimir
        </button>
        <a href="<?php echo htmlspecialchars($base_url . 'acompanhar', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-canal-outline">
            Ir para acompanhamento
        </a>
        <a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary">Início</a>
    </div>
</div>
