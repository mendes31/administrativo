<?php
require __DIR__ . '/_view_scope.php';
/** @var string $base_url */
/** @var string $message */
?>
<div class="canal-card">
    <div class="canal-header">
        <h1><i class="fas fa-exclamation-triangle me-2 text-warning"></i>Canal temporariamente indisponível</h1>
    </div>
    <div class="alert alert-warning mb-0">
        <?= htmlspecialchars((string)($message ?? ''), ENT_QUOTES, 'UTF-8') ?>
    </div>
    <p class="small text-muted mt-3 mb-0">
        Se precisar registrar um relato com urgência, utilize os canais alternativos divulgados pela sua empresa.
    </p>
</div>
