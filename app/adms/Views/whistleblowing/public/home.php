<?php
require __DIR__ . '/_view_scope.php';
/** @var string $base_url */
?>
<div class="canal-card">
    <div class="canal-header">
        <h1><i class="fas fa-shield-alt me-2 text-primary"></i>Canal de Denúncias</h1>
        <p class="text-muted mb-0">Canal seguro e anônimo para relatos de condutas inadequadas</p>
    </div>

    <div class="anon-badge">
        <strong><i class="fas fa-user-secret me-1"></i> Garantias de anonimato</strong>
        <ul>
            <li>Não é necessário login, CPF, matrícula ou e-mail.</li>
            <li>A denúncia <strong>não é vinculada</strong> ao seu usuário do portal.</li>
            <li>O sistema <strong>não registra endereço IP</strong> nem dados que identifiquem o denunciante.</li>
            <li>O acompanhamento é feito apenas com <strong>protocolo e senha</strong> gerados após o envio.</li>
            <li>O conteúdo é armazenado de forma <strong>criptografada</strong>.</li>
        </ul>
    </div>

    <div class="d-grid gap-3">
        <a href="<?php echo htmlspecialchars($base_url . 'registrar', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-canal-primary btn-lg">
            <i class="fas fa-plus-circle me-2"></i>Registrar nova denúncia
        </a>
        <a href="<?php echo htmlspecialchars($base_url . 'acompanhar', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-canal-outline btn-lg">
            <i class="fas fa-search me-2"></i>Acompanhar denúncia existente
        </a>
    </div>
</div>
