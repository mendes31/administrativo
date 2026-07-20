<?php
$filters = $this->data['filters'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Pesquisas Pulse / eNPS</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item">People Analytics</li>
            <li class="breadcrumb-item">Pesquisas</li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card border-light shadow mb-4">
        <div class="card-header d-flex justify-content-between">
            <span><i class="fas fa-poll me-2"></i>Campanhas</span>
            <?php if (in_array('CreatePulseCampaign', $this->data['buttonPermission'] ?? [], true)): ?>
                <a href="<?= $_ENV['URL_ADM'] ?>create-pulse-campaign" class="btn btn-sm btn-success"><i class="fas fa-plus me-1"></i>Nova</a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <form class="row g-2 mb-3" method="get">
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Status</option>
                        <?php foreach (['draft'=>'Rascunho','open'=>'Aberta','closed'=>'Fechada'] as $k=>$l): ?>
                            <option value="<?= $k ?>" <?= ($filters['status'] ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="campaign_type" class="form-select form-select-sm">
                        <option value="">Tipo</option>
                        <option value="enps" <?= ($filters['campaign_type'] ?? '') === 'enps' ? 'selected' : '' ?>>eNPS</option>
                        <option value="pulse" <?= ($filters['campaign_type'] ?? '') === 'pulse' ? 'selected' : '' ?>>Pulse</option>
                    </select>
                </div>
                <div class="col-md-2"><button class="btn btn-sm btn-primary">Filtrar</button></div>
            </form>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead><tr><th>Nome</th><th>Tipo</th><th>Status</th><th>Criado por</th><th></th></tr></thead>
                    <tbody>
                    <?php if (empty($this->data['campaigns'])): ?>
                        <tr><td colspan="5" class="text-muted">Nenhuma campanha.</td></tr>
                    <?php else: foreach ($this->data['campaigns'] as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($c['campaign_type'] ?? '') ?></td>
                            <td><?= htmlspecialchars($c['status'] ?? '') ?></td>
                            <td><?= htmlspecialchars($c['created_by_name'] ?? '') ?></td>
                            <td class="text-nowrap">
                                <a href="<?= $_ENV['URL_ADM'] ?>view-pulse-campaign/<?= (int)$c['id'] ?>">ver</a>
                                <?php if (($c['status'] ?? '') === 'open' && in_array('RespondPulseCampaign', $this->data['buttonPermission'] ?? [], true)): ?>
                                    · <a href="<?= $_ENV['URL_ADM'] ?>respond-pulse-campaign/<?= (int)$c['id'] ?>">responder</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <?= $this->data['pagination'] ?? '' ?>
        </div>
    </div>
</div>
