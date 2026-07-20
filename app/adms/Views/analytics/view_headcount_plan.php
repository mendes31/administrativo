<?php
$p = $this->data['plan'] ?? [];
$statusLabels = ['draft' => 'Rascunho', 'active' => 'Ativo', 'closed' => 'Fechado'];
$gap = (int)($p['gap'] ?? 0);
$gapClass = $gap > 0 ? 'text-danger' : ($gap < 0 ? 'text-warning' : 'text-success');
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Linha de Quadro</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>list-headcount-plans" class="text-decoration-none">Quadro</a></li>
            <li class="breadcrumb-item">Detalhe</li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card border-light shadow mb-4">
        <div class="card-header d-flex justify-content-between">
            <span><?= htmlspecialchars(($p['department_name'] ?? '') . ' · ' . ($p['position_name'] ?? 'Área')) ?></span>
            <?php if (in_array('UpdateHeadcountPlan', $this->data['buttonPermission'] ?? [], true)): ?>
                <a href="<?= $_ENV['URL_ADM'] ?>update-headcount-plan/<?= (int)$p['id'] ?>" class="btn btn-sm btn-primary">Editar</a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Período</dt>
                <dd class="col-sm-9"><?= (int)($p['period_month'] ?? 0) ?>/<?= (int)($p['period_year'] ?? 0) ?></dd>
                <dt class="col-sm-3">Status</dt>
                <dd class="col-sm-9"><?= htmlspecialchars($statusLabels[$p['status'] ?? ''] ?? ($p['status'] ?? '')) ?></dd>
                <dt class="col-sm-3">Planejado</dt>
                <dd class="col-sm-9"><?= (int)($p['planned_count'] ?? 0) ?></dd>
                <dt class="col-sm-3">Efetivo (agora)</dt>
                <dd class="col-sm-9"><?= (int)($p['actual_count'] ?? 0) ?></dd>
                <dt class="col-sm-3">Gap</dt>
                <dd class="col-sm-9 <?= $gapClass ?>"><strong><?= $gap ?></strong> (planejado − efetivo)</dd>
                <dt class="col-sm-3">Observações</dt>
                <dd class="col-sm-9"><?= nl2br(htmlspecialchars((string)($p['notes'] ?? '—'))) ?></dd>
                <dt class="col-sm-3">Criado por</dt>
                <dd class="col-sm-9"><?= htmlspecialchars($p['created_by_name'] ?? '') ?></dd>
            </dl>
        </div>
    </div>
</div>
