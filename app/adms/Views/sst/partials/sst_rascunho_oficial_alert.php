<?php

use App\adms\Models\Services\SstEsocialPolicy;

$sstRascunhoKind = $sstRascunhoKind ?? 'esocial';
$msg = $sstRascunhoKind === 'ppp'
    ? SstEsocialPolicy::avisoPpp()
    : SstEsocialPolicy::avisoFila();
?>
<div class="alert alert-warning" role="alert" data-adms-help-section="aviso-nao-oficial">
    <strong>Não oficial.</strong>
    <?= htmlspecialchars($msg) ?>
</div>
