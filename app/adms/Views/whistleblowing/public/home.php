<?php
require __DIR__ . '/_view_scope.php';
/** @var string $base_url */
?>
<div class="canal-card">
    <div class="canal-header">
        <h1><i class="fas fa-shield-alt me-2 text-primary"></i>Canal de Denúncias</h1>
        <p class="text-muted mb-0">Canal seguro e anônimo para relatos de condutas inadequadas</p>
    </div>

    <div class="anon-badge mb-3">
        <strong><i class="fas fa-user-secret me-1"></i> Garantias de anonimato</strong>
        <ul class="mb-0">
            <li>Não é necessário login, CPF, matrícula ou e-mail.</li>
            <li>A denúncia <strong>não é vinculada</strong> ao seu usuário do portal.</li>
            <li>O sistema <strong>não registra endereço IP</strong> nem dados que identifiquem o denunciante.</li>
            <li>O acompanhamento é feito apenas com <strong>protocolo e senha</strong> gerados após o envio.</li>
            <li>O conteúdo é armazenado de forma <strong>criptografada</strong>.</li>
        </ul>
    </div>

    <div class="anon-badge mb-3">
        <strong><i class="fas fa-lock me-1"></i> Confidencialidade e uso responsável</strong>
        <p class="small mb-2 mt-2">
            Canal para irregularidades, condutas antiéticas, assédio, discriminação, fraudes,
            violações de normas, riscos e desvios de qualidade envolvendo colaboradores ou terceiros.
        </p>
        <ul class="mb-0 small">
            <li>Informações tratadas com <strong>sigilo</strong>; proteção contra <strong>retaliação</strong>.</li>
            <li>Pode ser <strong>anônimo</strong> ou com identificação voluntária (LGPD).</li>
            <li>Relate com <strong>boa-fé</strong> e fatos verdadeiros — acusações de má-fé podem ser apuradas.</li>
            <li>Retornos do comitê somente na <strong>consulta por protocolo</strong> (não por e-mail ou telefone).</li>
        </ul>
    </div>

    <div class="canal-actions-grid">
        <a href="<?php echo htmlspecialchars($base_url . 'registrar', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-canal-primary btn-lg">
            <i class="fas fa-plus-circle me-2"></i>Registrar nova denúncia
        </a>
        <a href="<?php echo htmlspecialchars($base_url . 'acompanhar', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-canal-outline btn-lg">
            <i class="fas fa-search me-2"></i>Acompanhar denúncia existente
        </a>
    </div>
</div>
