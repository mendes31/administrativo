<?php
require __DIR__ . '/_view_scope.php';
/** @var string $base_url */
/** @var string $protocol */
/** @var string $password */
?>
<div class="canal-card">
    <div class="canal-header">
        <h1><i class="fas fa-check-circle text-success me-2"></i>Denúncia registrada</h1>
        <p class="text-muted mb-0">Guarde os dados abaixo. Eles <strong>não serão exibidos novamente</strong>.</p>
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
        <strong>Guarde protocolo e senha agora.</strong> Eles não serão exibidos novamente.
        Sem esses dados não será possível acompanhar a denúncia nem receber retornos do comitê.
    </div>

    <div class="alert alert-info small mb-3">
        <i class="fas fa-info-circle me-1"></i>
        Os retornos do comitê ficam disponíveis somente na consulta por protocolo.
        <?php if (!empty($reporter_inactivity_enabled)): ?>
        Quando o comitê solicitar informações, responda em até
        <strong><?php echo (int)($reporter_inactivity_days ?? 15); ?> dia(s)</strong>.
        A falta de retorno poderá ser considerada pelo comitê na decisão de encerramento.
        <?php endif; ?>
        Se você perder protocolo ou senha, registre uma <strong>nova denúncia</strong> e informe no relato
        que se trata de um novo cadastro devido à perda das credenciais anteriores.
    </div>

    <div class="canal-actions-grid">
        <button type="button" class="btn btn-canal-primary" onclick="window.print()">
            <i class="fas fa-print me-1"></i>Imprimir
        </button>
        <a href="<?php echo htmlspecialchars($base_url . 'acompanhar', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-canal-outline">
            Ir para acompanhamento
        </a>
        <a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary">Início</a>
    </div>
</div>
