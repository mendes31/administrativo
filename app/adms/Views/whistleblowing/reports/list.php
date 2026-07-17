<?php

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\WhistleblowingPublicUrlHelper;
use App\adms\Models\Services\WhistleblowingSlaService;

$slaService = new WhistleblowingSlaService();
$reporterInactivityService = new \App\adms\Models\Services\WhistleblowingReporterInactivityService();
$showReporterInactivity = (new \App\adms\Models\Repository\WhistleblowingConfigRepository())->isReporterInactivityEnabled();

$statusBadges = [
    'Recebida' => 'secondary',
    'Em triagem' => 'info',
    'Em análise' => 'primary',
    'Comitê' => 'warning',
    'Investigação' => 'warning',
    'Providências' => 'info',
    'Encerrada' => 'success',
];

$riskBadges = [
    'Baixo' => 'secondary',
    'Médio' => 'primary',
    'Alto' => 'warning',
    'Crítico' => 'danger',
];

$presetLabels = [
    'triagem' => 'Pendentes triagem',
    'investigacao' => 'Em investigação',
    'criticas' => 'Críticas abertas',
    'sla-vencido' => 'SLA 1ª resposta vencido',
    'sla-encerramento-vencido' => 'SLA de encerramento vencido',
    'retorno-denunciante-vencido' => 'Retorno do denunciante vencido',
    'aging' => 'Aging — denúncias abertas por tempo sem atualização',
];
$activePreset = (string) ($this->data['filters']['preset'] ?? '');
$showAging = $activePreset === 'aging';
$showClosureSla = $slaService->defaultClosureSlaLabel() !== null;
$publicFilters = [];
foreach (['preset', 'search', 'status', 'category', 'risk_level', 'assigned_user_id', 'date_from', 'date_to', 'include_archived'] as $filterKey) {
    $value = $this->data['filters'][$filterKey] ?? '';
    if (is_scalar($value) && (string) $value !== '' && (string) $value !== '0') {
        $publicFilters[$filterKey] = (string) $value;
    }
}
$withoutPreset = $publicFilters;
unset($withoutPreset['preset']);
$removePresetUrl = $_ENV['URL_ADM'] . 'denuncias'
    . ($withoutPreset !== [] ? '?' . http_build_query($withoutPreset) : '');
$activeFilterCount = count($publicFilters);
?>

<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3 mobile-hide-page-title"><i class="fas fa-shield-alt me-2"></i>Canal de Denúncias</h2>
        <ol class="breadcrumb mb-3 ms-auto mobile-hide-breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item">Denúncias</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex flex-wrap align-items-center gap-2">
            <span>Listar denúncias</span>
            <span class="ms-auto d-flex flex-wrap gap-1">
                <?php if (in_array('WhistleblowingDashboard', $this->data['buttonPermission'] ?? [])): ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>denuncias-dashboard" class="btn btn-outline-secondary btn-sm"><i class="fas fa-chart-pie me-1"></i>Dashboard</a>
                <?php endif; ?>
                <a href="<?php echo htmlspecialchars(WhistleblowingPublicUrlHelper::baseUrl(), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-external-link-alt me-1"></i>Canal público
                </a>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <?php if ($activePreset !== '' && isset($presetLabels[$activePreset])): ?>
                <div class="alert alert-info py-2 small d-flex align-items-center gap-2 mb-3">
                    <i class="fas fa-filter"></i>
                    <span>Filtro rápido do dashboard: <strong><?php echo htmlspecialchars($presetLabels[$activePreset], ENT_QUOTES, 'UTF-8'); ?></strong></span>
                    <a href="<?php echo htmlspecialchars($removePresetUrl, ENT_QUOTES, 'UTF-8'); ?>" class="ms-auto btn btn-sm btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Remover filtro
                    </a>
                </div>
            <?php endif; ?>

            <button class="btn btn-outline-primary w-100 d-md-none mb-3 d-flex align-items-center justify-content-between"
                type="button" data-bs-toggle="collapse" data-bs-target="#reportFilters"
                aria-expanded="<?= $activeFilterCount > 0 ? 'true' : 'false' ?>" aria-controls="reportFilters">
                <span><i class="fas fa-filter me-2"></i>Filtros</span>
                <span class="d-flex align-items-center gap-2">
                    <?php if ($activeFilterCount > 0): ?>
                        <span class="badge text-bg-primary"><?= $activeFilterCount ?> ativo<?= $activeFilterCount === 1 ? '' : 's' ?></span>
                    <?php endif; ?>
                    <i class="fas fa-chevron-down filter-collapse-icon"></i>
                </span>
            </button>

            <div class="collapse d-md-block <?= $activeFilterCount > 0 ? 'show' : '' ?>" id="reportFilters">
            <form method="get" class="row g-2 mb-3 align-items-end report-filters-form">
                <?php if ($activePreset !== ''): ?>
                    <input type="hidden" name="preset" value="<?php echo htmlspecialchars($activePreset, ENT_QUOTES, 'UTF-8'); ?>">
                <?php endif; ?>
                <div class="col-md-2">
                    <label class="form-label small">Protocolo</label>
                    <input type="text" name="search" class="form-control form-control-sm" value="<?php echo htmlspecialchars((string)($this->data['filters']['search'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($this->data['statuses'] as $st): ?>
                            <option value="<?php echo htmlspecialchars($st, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($this->data['filters']['status'] ?? '') === $st) ? 'selected' : ''; ?>><?php echo htmlspecialchars($st, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Classificação</label>
                    <select name="category" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        <?php foreach ($this->data['categories'] as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($this->data['filters']['category'] ?? '') === $cat) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Risco</label>
                    <select name="risk_level" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($this->data['risk_levels'] as $risk): ?>
                            <option value="<?php echo htmlspecialchars($risk, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($this->data['filters']['risk_level'] ?? '') === $risk) ? 'selected' : ''; ?>><?php echo htmlspecialchars($risk, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Responsável</label>
                    <select name="assigned_user_id" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($this->data['users'] as $u): ?>
                            <option value="<?php echo (int)$u['id']; ?>" <?php echo ((string)($this->data['filters']['assigned_user_id'] ?? '') === (string)$u['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars((string)$u['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="include_archived" value="1" id="include_archived"
                            <?php echo (($this->data['filters']['include_archived'] ?? '0') === '1') ? 'checked' : ''; ?>>
                        <label class="form-check-label small" for="include_archived">Incluir arquivadas</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Registradas de</label>
                    <input type="date" name="date_from" class="form-control form-control-sm"
                        value="<?php echo htmlspecialchars((string)($this->data['filters']['date_from'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Registradas até</label>
                    <input type="date" name="date_to" class="form-control form-control-sm"
                        value="<?php echo htmlspecialchars((string)($this->data['filters']['date_to'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fa fa-filter"></i> Filtrar</button>
                </div>
            </form>
            </div>

            <div class="table-responsive d-none d-lg-block">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Protocolo</th>
                            <th>Classificação</th>
                            <th>Comitê</th>
                            <th>Risco</th>
                            <th>Status</th>
                            <th>Resultado</th>
                            <?php if ($showAging): ?><th>Dias parado</th><?php endif; ?>
                            <th style="min-width: 145px;">SLA 1ª resposta</th>
                            <?php if ($showClosureSla): ?><th style="min-width: 145px;">SLA encerramento</th><?php endif; ?>
                            <?php if ($showReporterInactivity): ?><th style="min-width: 145px;">Retorno denunciante</th><?php endif; ?>
                            <th>Registrada em</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($this->data['reports'])): ?>
                            <tr><td colspan="<?php echo 9 + ($showAging ? 1 : 0) + ($showClosureSla ? 1 : 0) + ($showReporterInactivity ? 1 : 0); ?>" class="text-center text-muted py-4">Nenhuma denúncia encontrada.</td></tr>
                        <?php else: ?>
                            <?php foreach ($this->data['reports'] as $r): ?>
                                <?php $sla = $slaService->progress($r); ?>
                                <?php $closureSla = $showClosureSla ? $slaService->closureProgress($r) : null; ?>
                                <?php $reporterResponse = $showReporterInactivity ? $reporterInactivityService->status($r) : null; ?>
                                <tr>
                                    <td class="font-monospace fw-semibold"><?php echo htmlspecialchars((string)$r['protocol'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)$r['category'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($r['committee_name'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><span class="badge bg-<?php echo $riskBadges[$r['risk_level']] ?? 'secondary'; ?>"><?php echo htmlspecialchars((string)$r['risk_level'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                    <td><span class="badge bg-<?php echo $statusBadges[$r['status']] ?? 'secondary'; ?>"><?php echo htmlspecialchars((string)$r['status'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                    <td>
                                        <?php if (($r['status'] ?? '') === 'Encerrada' && !empty($r['closure_outcome'])): ?>
                                            <span class="badge bg-dark"><?php echo htmlspecialchars((string)$r['closure_outcome'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($showAging): ?>
                                        <td class="fw-semibold <?php echo (int)($r['days_idle'] ?? 0) >= 7 ? 'text-danger' : ''; ?>">
                                            <?php echo (int)($r['days_idle'] ?? 0); ?>
                                        </td>
                                    <?php endif; ?>
                                    <td title="<?php echo htmlspecialchars($sla['title'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php if ($sla['available']): ?>
                                            <div class="small mb-1"><?php echo htmlspecialchars($sla['deadline'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div class="progress position-relative" style="height: 15px;" role="progressbar"
                                                aria-label="<?php echo htmlspecialchars($sla['label'], ENT_QUOTES, 'UTF-8'); ?>"
                                                aria-valuenow="<?php echo (int)$sla['percent']; ?>" aria-valuemin="0" aria-valuemax="100">
                                                <div class="progress-bar progress-bar-striped bg-<?php echo htmlspecialchars($sla['color'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    style="width: <?php echo (int)$sla['percent']; ?>%"></div>
                                                <span class="position-absolute w-100 text-center small fw-semibold"
                                                    style="font-size: .68rem; line-height: 15px; color: <?php echo $sla['percent'] >= 50 ? '#fff' : '#212529'; ?>;">
                                                    <?php echo (int)$sla['percent']; ?>%
                                                </span>
                                            </div>
                                            <div class="small text-<?php echo htmlspecialchars($sla['color'], ENT_QUOTES, 'UTF-8'); ?>" style="font-size: .68rem;">
                                                <?php echo htmlspecialchars($sla['label'], ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small">Não definido</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($showClosureSla && $closureSla !== null): ?>
                                    <td title="<?php echo htmlspecialchars($closureSla['title'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php if ($closureSla['available']): ?>
                                            <div class="small mb-1"><?php echo htmlspecialchars($closureSla['deadline'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div class="progress position-relative" style="height: 15px;" role="progressbar"
                                                aria-label="<?php echo htmlspecialchars($closureSla['label'], ENT_QUOTES, 'UTF-8'); ?>"
                                                aria-valuenow="<?php echo (int)$closureSla['percent']; ?>" aria-valuemin="0" aria-valuemax="100">
                                                <div class="progress-bar progress-bar-striped bg-<?php echo htmlspecialchars($closureSla['color'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    style="width: <?php echo (int)$closureSla['percent']; ?>%"></div>
                                                <span class="position-absolute w-100 text-center small fw-semibold"
                                                    style="font-size: .68rem; line-height: 15px; color: <?php echo $closureSla['percent'] >= 50 ? '#fff' : '#212529'; ?>;">
                                                    <?php echo (int)$closureSla['percent']; ?>%
                                                </span>
                                            </div>
                                            <div class="small text-<?php echo htmlspecialchars($closureSla['color'], ENT_QUOTES, 'UTF-8'); ?>" style="font-size: .68rem;">
                                                <?php echo htmlspecialchars($closureSla['label'], ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small">Não definido</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php endif; ?>
                                    <?php if ($showReporterInactivity && $reporterResponse !== null): ?>
                                    <td>
                                        <?php if ($reporterResponse['active']): ?>
                                            <span class="badge bg-<?php echo htmlspecialchars($reporterResponse['color'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars($reporterResponse['label'], ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                            <div class="small mt-1"><?php echo htmlspecialchars($reporterResponse['deadline'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        <?php else: ?>
                                            <span class="text-muted small">Sem pendência</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php endif; ?>
                                    <td><?php echo date('d/m/Y H:i', strtotime((string)$r['created_at'])); ?></td>
                                    <td class="text-center">
                                        <?php if (in_array('WhistleblowingViewReport', $this->data['buttonPermission'] ?? [])): ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>view-denuncia/<?php echo (int)$r['id']; ?>" class="btn btn-info btn-sm" title="Visualizar"><i class="fas fa-eye"></i></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-lg-none report-mobile-list">
                <?php if (empty($this->data['reports'])): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p class="mb-0">Nenhuma denúncia encontrada.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($this->data['reports'] as $r): ?>
                        <?php
                        $sla = $slaService->progress($r);
                        $closureSla = $showClosureSla ? $slaService->closureProgress($r) : null;
                        $reporterResponse = $showReporterInactivity ? $reporterInactivityService->status($r) : null;
                        ?>
                        <article class="card report-mobile-card border mb-3">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                                    <div class="min-width-0">
                                        <div class="small text-muted mb-1">Protocolo</div>
                                        <strong class="font-monospace text-break">
                                            <?php echo htmlspecialchars((string) $r['protocol'], ENT_QUOTES, 'UTF-8'); ?>
                                        </strong>
                                    </div>
                                    <?php if (in_array('WhistleblowingViewReport', $this->data['buttonPermission'] ?? [])): ?>
                                        <a href="<?php echo $_ENV['URL_ADM']; ?>view-denuncia/<?php echo (int) $r['id']; ?>"
                                            class="btn btn-info btn-sm flex-shrink-0">
                                            <i class="fas fa-eye me-1"></i>Abrir
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <span class="badge bg-<?php echo $statusBadges[$r['status']] ?? 'secondary'; ?>">
                                        <?php echo htmlspecialchars((string) $r['status'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                    <span class="badge bg-<?php echo $riskBadges[$r['risk_level']] ?? 'secondary'; ?>">
                                        Risco <?php echo htmlspecialchars((string) $r['risk_level'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                    <?php if (($r['status'] ?? '') === 'Encerrada' && !empty($r['closure_outcome'])): ?>
                                        <span class="badge bg-dark">
                                            <?php echo htmlspecialchars((string) $r['closure_outcome'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <dl class="row g-2 small mb-3 report-mobile-details">
                                    <dt class="col-5 text-muted fw-normal">Classificação</dt>
                                    <dd class="col-7 mb-0 text-end text-break">
                                        <?php echo htmlspecialchars((string) $r['category'], ENT_QUOTES, 'UTF-8'); ?>
                                    </dd>
                                    <dt class="col-5 text-muted fw-normal">Comitê</dt>
                                    <dd class="col-7 mb-0 text-end text-break">
                                        <?php echo htmlspecialchars((string) ($r['committee_name'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?>
                                    </dd>
                                    <dt class="col-5 text-muted fw-normal">Registrada em</dt>
                                    <dd class="col-7 mb-0 text-end">
                                        <?php echo date('d/m/Y H:i', strtotime((string) $r['created_at'])); ?>
                                    </dd>
                                    <?php if ($showAging): ?>
                                        <dt class="col-5 text-muted fw-normal">Dias parado</dt>
                                        <dd class="col-7 mb-0 text-end fw-semibold <?php echo (int) ($r['days_idle'] ?? 0) >= 7 ? 'text-danger' : ''; ?>">
                                            <?php echo (int) ($r['days_idle'] ?? 0); ?>
                                        </dd>
                                    <?php endif; ?>
                                </dl>

                                <div class="report-mobile-slas border-top pt-3">
                                    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                        <span class="small text-muted">SLA 1ª resposta</span>
                                        <?php if ($sla['available']): ?>
                                            <span class="small text-end">
                                                <strong class="text-<?php echo htmlspecialchars($sla['color'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php echo htmlspecialchars($sla['label'], ENT_QUOTES, 'UTF-8'); ?>
                                                </strong>
                                                <span class="d-block text-muted"><?php echo htmlspecialchars($sla['deadline'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            </span>
                                        <?php else: ?>
                                            <span class="small text-muted">Não definido</span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($showClosureSla && $closureSla !== null): ?>
                                        <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                            <span class="small text-muted">SLA encerramento</span>
                                            <?php if ($closureSla['available']): ?>
                                                <span class="small text-end">
                                                    <strong class="text-<?php echo htmlspecialchars($closureSla['color'], ENT_QUOTES, 'UTF-8'); ?>">
                                                        <?php echo htmlspecialchars($closureSla['label'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </strong>
                                                    <span class="d-block text-muted"><?php echo htmlspecialchars($closureSla['deadline'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                </span>
                                            <?php else: ?>
                                                <span class="small text-muted">Não definido</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($showReporterInactivity && $reporterResponse !== null): ?>
                                        <div class="d-flex align-items-start justify-content-between gap-2">
                                            <span class="small text-muted">Retorno denunciante</span>
                                            <?php if ($reporterResponse['active']): ?>
                                                <span class="small text-end">
                                                    <span class="badge bg-<?php echo htmlspecialchars($reporterResponse['color'], ENT_QUOTES, 'UTF-8'); ?>">
                                                        <?php echo htmlspecialchars($reporterResponse['label'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </span>
                                                    <span class="d-block text-muted mt-1"><?php echo htmlspecialchars($reporterResponse['deadline'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                </span>
                                            <?php else: ?>
                                                <span class="small text-muted">Sem pendência</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php
            $pag = $this->data['pagination'] ?? [];
            if (($pag['last_page'] ?? 1) > 1):
            ?>
            <nav>
                <ul class="pagination pagination-sm justify-content-center mb-0">
                    <?php for ($p = 1; $p <= (int)$pag['last_page']; $p++): ?>
                        <li class="page-item <?php echo ($p === (int)($pag['current_page'] ?? 1)) ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo htmlspecialchars(http_build_query(array_merge($publicFilters, ['page' => $p])), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $p; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.report-mobile-card {
    border-radius: 14px;
    overflow: hidden;
}
.report-mobile-card .min-width-0 {
    min-width: 0;
}
.report-mobile-details dt,
.report-mobile-details dd {
    padding-top: .15rem;
    padding-bottom: .15rem;
}
[aria-controls="reportFilters"] .filter-collapse-icon {
    transition: transform .2s ease;
}
[aria-controls="reportFilters"][aria-expanded="true"] .filter-collapse-icon {
    transform: rotate(180deg);
}
@media (max-width: 767.98px) {
    .container-fluid.px-4 {
        padding-left: .75rem !important;
        padding-right: .75rem !important;
    }
    .card-body {
        padding: 1rem;
    }
    .report-filters-form > [class*="col-"] {
        width: 100%;
    }
    .report-filters-form .form-control-sm,
    .report-filters-form .form-select-sm,
    .report-filters-form .btn-sm {
        min-height: 42px;
        font-size: 1rem;
    }
    .report-filters-form .form-check {
        margin-top: .5rem !important;
        margin-bottom: .5rem;
    }
}
</style>
