<?php /** @var string $protocolo @var string $base_url @var string $email */ ?>
<div class="lgpd-pub-card text-center">
    <h1 class="lgpd-pub-hero">Requisição recebida</h1>
    <p>A <?php echo htmlspecialchars((string) ($empresa ?? ''), ENT_QUOTES, 'UTF-8'); ?> registrou o seu pedido de direitos do titular.</p>
    <div class="border rounded-3 py-3 px-2 my-3" style="background:#f7fbf9;border-color:#cfe8d6!important;">
        <div class="small text-muted">Protocolo</div>
        <div class="fs-4 fw-bold" style="color:#0a5b30;letter-spacing:1px;">
            <?php echo htmlspecialchars((string) $protocolo, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    </div>
    <p class="small text-muted">
        Guarde este número. O prazo legal de atendimento é de até <strong>15 dias</strong>
        <?php if (!empty($email)): ?>
            e a resposta será enviada ao e-mail informado
            (<?php echo htmlspecialchars((string) $email, ENT_QUOTES, 'UTF-8'); ?>),
            salvo outro meio indicado.
        <?php endif; ?>.
        Poderemos contactá-lo para confirmar a identidade ou pedir esclarecimentos.
    </p>
    <a class="btn btn-lgpd" href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>">Voltar à página LGPD</a>
</div>
