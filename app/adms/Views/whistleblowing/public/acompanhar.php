<?php
require __DIR__ . '/_view_scope.php';
/** @var string $base_url */
/** @var string|null $error */
/** @var string $csrf_token */
?>
<div class="canal-card">
    <div class="canal-header">
        <h1><i class="fas fa-search me-2"></i>Acompanhar denúncia</h1>
        <p class="text-muted mb-0">Informe o protocolo e a senha recebidos no momento do registro.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="anon-badge mb-3">
        <strong><i class="fas fa-key me-1"></i> Protocolo e senha</strong>
        <ul class="mb-0 small">
            <li>Os retornos do comitê ficam disponíveis <strong>somente nesta consulta</strong> — não enviamos respostas por e-mail ou telefone.</li>
            <li>Se você <strong>perdeu o protocolo ou a senha</strong>, não é possível recuperar o acesso. Registre uma <a href="<?php echo htmlspecialchars($base_url . 'registrar', ENT_QUOTES, 'UTF-8'); ?>">nova denúncia</a> e informe no relato que se trata de recadastro por perda das credenciais.</li>
        </ul>
    </div>

    <form class="canal-form" method="post" action="<?php echo htmlspecialchars($base_url . 'consultar', ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)($csrf_token ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

        <div class="canal-field">
            <label class="form-label fw-semibold">Protocolo</label>
            <input type="text" name="protocol" class="form-control font-monospace text-uppercase"
                placeholder="CD-2026-XXXX-XXXX" required autocomplete="off" autocapitalize="characters" spellcheck="false">
        </div>

        <div class="canal-field">
            <label class="form-label fw-semibold">Senha</label>
            <input type="text" name="password" class="form-control font-monospace text-uppercase"
                placeholder="Senha de 6 caracteres" required autocomplete="off" maxlength="12" autocapitalize="characters" spellcheck="false">
        </div>

        <div class="canal-form-actions">
            <button type="submit" class="btn btn-canal-primary">
                <i class="fas fa-unlock me-1"></i>Consultar
            </button>
            <a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary">Voltar</a>
        </div>
    </form>
</div>
