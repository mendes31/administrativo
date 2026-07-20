<?php
use App\adms\Helpers\CSRFHelper;
$filters = $this->data['filters'] ?? [];
$statusLabels = ['draft' => 'Rascunho', 'active' => 'Ativo', 'closed' => 'Fechado'];
$months = [1=>'Jan',2=>'Fev',3=>'Mar',4=>'Abr',5=>'Mai',6=>'Jun',7=>'Jul',8=>'Ago',9=>'Set',10=>'Out',11=>'Nov',12=>'Dez'];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Planejamento de Quadro</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item">People Analytics</li>
            <li class="breadcrumb-item">Quadro</li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card border-light shadow mb-4">
        <div class="card-header d-flex justify-content-between">
            <span><i class="fas fa-users-cog me-2"></i>Linhas de headcount</span>
            <?php if (in_array('CreateHeadcountPlan', $this->data['buttonPermission'] ?? [], true)): ?>
                <a href="<?= $_ENV['URL_ADM'] ?>create-headcount-plan" class="btn btn-sm btn-success"><i class="fas fa-plus me-1"></i>Nova</a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <form class="row g-2 mb-3" method="get">
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Status</option>
                        <?php foreach ($statusLabels as $k => $l): ?>
                            <option value="<?= $k ?>" <?= ($filters['status'] ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="department_id" class="form-select form-select-sm">
                        <option value="">Departamento</option>
                        <?php foreach (($this->data['departments'] ?? []) as $d): ?>
                            <option value="<?= (int)$d['id'] ?>" <?= (string)($filters['department_id'] ?? '') === (string)$d['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="number" name="period_year" class="form-control form-control-sm" placeholder="Ano"
                           value="<?= htmlspecialchars((string)($filters['period_year'] ?? '')) ?>">
                </div>
                <div class="col-md-2">
                    <select name="period_month" class="form-select form-select-sm">
                        <option value="">Mês</option>
                        <?php foreach ($months as $n => $l): ?>
                            <option value="<?= $n ?>" <?= (string)($filters['period_month'] ?? '') === (string)$n ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2"><button class="btn btn-sm btn-primary">Filtrar</button></div>
            </form>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                    <tr>
                        <th>Período</th><th>Departamento</th><th>Cargo</th>
                        <th class="text-end">Planejado</th><th class="text-end">Efetivo</th><th class="text-end">Gap</th>
                        <th>Status</th><th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($this->data['plans'])): ?>
                        <tr><td colspan="8" class="text-muted">Nenhuma linha de quadro.</td></tr>
                    <?php else: foreach ($this->data['plans'] as $p):
                        $gap = (int)($p['gap'] ?? 0);
                        $gapClass = $gap > 0 ? 'text-danger' : ($gap < 0 ? 'text-warning' : 'text-success');
                        ?>
                        <tr>
                            <td><?= (int)$p['period_month'] ?>/<?= (int)$p['period_year'] ?></td>
                            <td><?= htmlspecialchars($p['department_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($p['position_name'] ?? '— (área)') ?></td>
                            <td class="text-end"><?= (int)$p['planned_count'] ?></td>
                            <td class="text-end"><?= (int)($p['actual_count'] ?? 0) ?></td>
                            <td class="text-end <?= $gapClass ?>"><?= $gap ?></td>
                            <td><?= htmlspecialchars($statusLabels[$p['status'] ?? ''] ?? ($p['status'] ?? '')) ?></td>
                            <td class="text-nowrap">
                                <a href="<?= $_ENV['URL_ADM'] ?>view-headcount-plan/<?= (int)$p['id'] ?>">ver</a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <?= $this->data['pagination'] ?? '' ?>
            <p class="small text-muted mb-0 mt-2">Gap = planejado − efetivo (positivo = abaixo do plano).</p>
        </div>
    </div>
</div>
