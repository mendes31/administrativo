<?php

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\WhistleblowingPublicUrlHelper;
use App\adms\Models\Services\WhistleblowingSlaService;

$slaService = new WhistleblowingSlaService();

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
];
$activePreset = (string) ($this->data['filters']['preset'] ?? '');
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
        <div class="card-header hstack gap-2">
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
                    <a href="<?php echo $_ENV['URL_ADM']; ?>denuncias" class="ms-auto btn btn-sm btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Remover filtro
                    </a>
                </div>
            <?php endif; ?>

            <form method="get" class="row g-2 mb-3 align-items-end">
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
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fa fa-filter"></i> Filtrar</button>
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="include_archived" value="1" id="include_archived"
                            <?php echo (($this->data['filters']['include_archived'] ?? '0') === '1') ? 'checked' : ''; ?>>
                        <label class="form-check-label small" for="include_archived">Incluir arquivadas</label>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Protocolo</th>
                            <th>Classificação</th>
                            <th>Comitê</th>
                            <th>Risco</th>
                            <th>Status</th>
                            <th>Responsável</th>
                            <th style="min-width: 145px;">SLA 1ª resposta</th>
                            <th>Registrada em</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($this->data['reports'])): ?>
                            <tr><td colspan="9" class="text-center text-muted py-4">Nenhuma denúncia encontrada.</td></tr>
                        <?php else: ?>
                            <?php foreach ($this->data['reports'] as $r): ?>
                                <?php $sla = $slaService->progress($r); ?>
                                <tr>
                                    <td class="font-monospace fw-semibold"><?php echo htmlspecialchars((string)$r['protocol'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)$r['category'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($r['committee_name'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><span class="badge bg-<?php echo $riskBadges[$r['risk_level']] ?? 'secondary'; ?>"><?php echo htmlspecialchars((string)$r['risk_level'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                    <td><span class="badge bg-<?php echo $statusBadges[$r['status']] ?? 'secondary'; ?>"><?php echo htmlspecialchars((string)$r['status'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                    <td><?php echo htmlspecialchars((string)($r['assigned_name'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></td>
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

            <?php
            $pag = $this->data['pagination'] ?? [];
            if (($pag['last_page'] ?? 1) > 1):
            ?>
            <nav>
                <ul class="pagination pagination-sm justify-content-center mb-0">
                    <?php for ($p = 1; $p <= (int)$pag['last_page']; $p++): ?>
                        <li class="page-item <?php echo ($p === (int)($pag['current_page'] ?? 1)) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $p; ?>&<?php echo http_build_query(array_filter($this->data['filters'] ?? [])); ?>"><?php echo $p; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>
