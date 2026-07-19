<?php

declare(strict_types=1);

/**
 * @var string $base_url
 */
$base_url = rtrim((string) ($base_url ?? ''), '/');
?>
<div class="vp-card vp-empty">
    <h2 class="h5 mb-2">Vaga não encontrada</h2>
    <p class="mb-3">Esta vaga não está disponível, não foi publicada ou o prazo de inscrição encerrou.</p>
    <a class="vp-link" href="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>">Ver vagas abertas</a>
</div>
