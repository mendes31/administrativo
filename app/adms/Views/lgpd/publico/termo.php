<?php
/** @var array|null $termo @var bool $has_termo @var string $empty_hint @var string $base_url */
$h = static fn (mixed $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
?>
<div class="lgpd-pub-card">
    <p class="mb-3">
        <a href="<?php echo $h($base_url); ?>" class="text-decoration-none" style="color:#00995D;font-weight:600;">
            ← Voltar à página LGPD
        </a>
    </p>
    <h1 class="lgpd-pub-hero"><?php echo $h($termo['titulo'] ?? $title ?? 'Documento'); ?></h1>
    <?php if (!empty($termo['versao'])): ?>
        <p class="text-muted small mb-3">Versão <?php echo $h($termo['versao']); ?></p>
    <?php endif; ?>

    <?php if (!empty($has_termo)): ?>
        <div class="lgpd-term-body">
            <?php echo $termo['conteudo']; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-warning mb-0">
            <strong>Conteúdo ainda não disponível.</strong>
            <p class="small mb-0 mt-1"><?php echo $empty_hint ?? ''; ?></p>
        </div>
    <?php endif; ?>
</div>
