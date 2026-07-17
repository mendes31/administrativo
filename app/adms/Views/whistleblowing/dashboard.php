<?php
$stats = $this->data['stats'] ?? [];
$canListReports = in_array('WhistleblowingListReports', $this->data['buttonPermission'] ?? []);
$filters = is_array($this->data['filters'] ?? null) ? $this->data['filters'] : [];
$dateFrom = (string) ($filters['date_from'] ?? '');
$dateTo = (string) ($filters['date_to'] ?? '');
$periodParams = array_filter([
    'date_from' => $dateFrom,
    'date_to' => $dateTo,
], static fn (string $value): bool => $value !== '');
$periodQuery = http_build_query($periodParams);
$exportUrl = $_ENV['URL_ADM'] . 'whistleblowing-export-dashboard'
    . ($periodQuery !== '' ? '?' . $periodQuery : '');
$agingParams = array_merge(['preset' => 'aging'], $periodParams);
$agingUrl = $_ENV['URL_ADM'] . 'denuncias?' . http_build_query($agingParams);
$allReportsUrl = $_ENV['URL_ADM'] . 'denuncias'
    . ($periodQuery !== '' ? '?' . $periodQuery : '');

/**
 * Envolve o conteúdo do card em link para a listagem filtrada (quando o usuário pode listar).
 */
$cardLink = static function (string $preset, string $inner) use ($canListReports, $periodParams): string {
    if (!$canListReports) {
        return $inner;
    }
    $params = $periodParams;
    if ($preset !== '') {
        $params['preset'] = $preset;
    }
    $query = http_build_query($params);
    $href = $_ENV['URL_ADM'] . 'denuncias' . ($query !== '' ? '?' . $query : '');

    return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" class="text-decoration-none text-reset wb-card-link" title="Ver denúncias deste indicador">' . $inner . '</a>';
};
?>

<style>
.wb-card-link .card { transition: box-shadow .15s ease, transform .15s ease; }
.wb-card-link:hover .card { box-shadow: 0 .5rem 1rem rgba(0,0,0,.15) !important; transform: translateY(-2px); }
</style>

<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3 mobile-hide-page-title"><i class="fas fa-chart-pie me-2"></i>Dashboard — Canal de Denúncias</h2>
        <div class="ms-auto hstack gap-2">
            <?php if (in_array('WhistleblowingExportDashboard', $this->data['buttonPermission'] ?? [])): ?>
            <a href="<?= htmlspecialchars($exportUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-success">
                <i class="fas fa-file-excel me-1"></i>Exportar Excel
            </a>
            <?php endif; ?>
            <ol class="breadcrumb mb-3 mobile-hide-breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item">Canal de Denúncias</li>
        </ol>
        </div>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="card border-light shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1" for="dashboard-date-from">Registradas de</label>
                    <input type="date" name="date_from" id="dashboard-date-from" class="form-control form-control-sm"
                        value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1" for="dashboard-date-to">Registradas até</label>
                    <input type="date" name="date_to" id="dashboard-date-to" class="form-control form-control-sm"
                        value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-filter me-1"></i>Aplicar período
                    </button>
                </div>
                <?php if ($periodQuery !== ''): ?>
                <div class="col-md-auto">
                    <a href="<?= $_ENV['URL_ADM'] ?>denuncias-dashboard" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-times me-1"></i>Limpar período
                    </a>
                </div>
                <?php endif; ?>
                <div class="col-md ms-md-auto">
                    <p class="small text-muted text-md-end mb-1">
                        O período considera a data de registro da denúncia e também é aplicado à exportação.
                    </p>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <?php echo $cardLink('', '
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3"><i class="fas fa-folder text-primary fa-2x"></i></div>
                    <div><h6 class="text-muted mb-1">Total ativas</h6><h3 class="mb-0 fw-bold">' . (int)($stats['total'] ?? 0) . '</h3></div>
                </div>
            </div>'); ?>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <?php echo $cardLink('triagem', '
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-warning bg-opacity-10 p-3 me-3"><i class="fas fa-hourglass-half text-warning fa-2x"></i></div>
                    <div><h6 class="text-muted mb-1">Pendentes triagem</h6><h3 class="mb-0 fw-bold">' . (int)($stats['pending'] ?? 0) . '</h3></div>
                </div>
            </div>'); ?>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <?php echo $cardLink('criticas', '
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-danger bg-opacity-10 p-3 me-3"><i class="fas fa-exclamation-triangle text-danger fa-2x"></i></div>
                    <div><h6 class="text-muted mb-1">Críticas abertas</h6><h3 class="mb-0 fw-bold">' . (int)($stats['critical'] ?? 0) . '</h3></div>
                </div>
            </div>'); ?>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <?php echo $cardLink('sla-vencido', '
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-danger bg-opacity-10 p-3 me-3"><i class="fas fa-clock text-danger fa-2x"></i></div>
                    <div><h6 class="text-muted mb-1">SLA 1ª resposta (' . htmlspecialchars((string)($this->data['sla_label'] ?? '72h'), ENT_QUOTES, 'UTF-8') . ')</h6><h3 class="mb-0 fw-bold text-danger">' . (int)($stats['sla_overdue'] ?? 0) . '</h3><small class="text-muted">sem resposta</small></div>
                </div>
            </div>'); ?>
        </div>
        <?php if (!empty($this->data['sla_closure_label'])): ?>
        <div class="col-xl-3 col-md-6 mb-3">
            <?php echo $cardLink('sla-encerramento-vencido', '
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-danger bg-opacity-10 p-3 me-3"><i class="fas fa-calendar-times text-danger fa-2x"></i></div>
                    <div><h6 class="text-muted mb-1">SLA encerramento (' . htmlspecialchars((string)$this->data['sla_closure_label'], ENT_QUOTES, 'UTF-8') . ')</h6><h3 class="mb-0 fw-bold text-danger">' . (int)($stats['sla_closure_overdue'] ?? 0) . '</h3><small class="text-muted">ainda abertas</small></div>
                </div>
            </div>'); ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($this->data['reporter_inactivity_enabled'])): ?>
        <div class="col-xl-3 col-md-6 mb-3">
            <?php echo $cardLink('retorno-denunciante-vencido', '
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-danger bg-opacity-10 p-3 me-3"><i class="fas fa-user-clock text-danger fa-2x"></i></div>
                    <div><h6 class="text-muted mb-1">Retorno do denunciante (' . (int)($this->data['reporter_inactivity_days'] ?? 15) . ' dias)</h6><h3 class="mb-0 fw-bold text-danger">' . (int)($stats['reporter_response_overdue'] ?? 0) . '</h3><small class="text-muted">prazo vencido</small></div>
                </div>
            </div>'); ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <?php echo $cardLink('investigacao', '
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3"><i class="fas fa-search text-info fa-2x"></i></div>
                    <div><h6 class="text-muted mb-1">Em investigação</h6><h3 class="mb-0 fw-bold">' . (int)($stats['investigation'] ?? 0) . '</h3></div>
                </div>
            </div>'); ?>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card border-light shadow h-100">
                <div class="card-header fw-semibold">Tempo médio de resposta</div>
                <div class="card-body"><h2 class="mb-0"><?= number_format((float)($stats['avg_response_hours'] ?? 0), 1) ?> h</h2></div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card border-light shadow h-100">
                <div class="card-header fw-semibold">Tempo médio de encerramento</div>
                <div class="card-body"><h2 class="mb-0"><?= number_format((float)($stats['avg_closure_hours'] ?? 0), 1) ?> h</h2></div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card border-light shadow">
                <div class="card-header">Por status</div>
                <table class="table table-sm mb-0">
                    <?php foreach ($stats['by_status'] ?? [] as $row): ?>
                        <tr><td><?= htmlspecialchars((string)($row['label'] ?? '')) ?></td><td class="text-end"><?= (int)($row['total'] ?? 0) ?></td></tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-light shadow">
                <div class="card-header">Por classificação</div>
                <table class="table table-sm mb-0">
                    <?php foreach ($stats['by_category'] ?? [] as $row): ?>
                        <tr><td><?= htmlspecialchars((string)($row['label'] ?? '')) ?></td><td class="text-end"><?= (int)($row['total'] ?? 0) ?></td></tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-light shadow">
                <div class="card-header">Por risco</div>
                <table class="table table-sm mb-0">
                    <?php foreach ($stats['by_risk'] ?? [] as $row): ?>
                        <tr><td><?= htmlspecialchars((string)($row['label'] ?? '')) ?></td><td class="text-end"><?= (int)($row['total'] ?? 0) ?></td></tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-light shadow mb-4">
        <div class="card-header fw-semibold hstack gap-2">
            <span>Aging — denúncias paradas (dias sem atualização)</span>
            <?php if ($canListReports): ?>
                <a href="<?= htmlspecialchars($agingUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary ms-auto">
                    <i class="fas fa-list me-1"></i>Ver na listagem
                </a>
            <?php endif; ?>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-striped mb-0">
                <thead>
                    <tr>
                        <th>Protocolo</th><th>Status</th><th>Risco</th><th>Classificação</th><th>Comitê</th>
                        <th class="text-end">Dias parado</th><th class="text-end">Dias aberta</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['aging'] ?? [] as $row): ?>
                    <tr>
                        <td><a href="<?php echo $_ENV['URL_ADM']; ?>view-denuncia/<?= (int)($row['id'] ?? 0) ?>"><?= htmlspecialchars((string)($row['protocol'] ?? '')) ?></a></td>
                        <td><?= htmlspecialchars((string)($row['status'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($row['risk_level'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($row['category'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($row['committee_name'] ?? '—')) ?></td>
                        <td class="text-end fw-semibold <?= (int)($row['days_idle'] ?? 0) >= 7 ? 'text-danger' : '' ?>"><?= (int)($row['days_idle'] ?? 0) ?></td>
                        <td class="text-end"><?= (int)($row['days_open'] ?? 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($stats['aging'])): ?>
                    <tr><td colspan="7" class="text-muted text-center py-3">Nenhuma denúncia aberta no momento.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card border-light shadow">
        <div class="card-header hstack">
            <span>Denúncias recentes</span>
            <a href="<?= htmlspecialchars($allReportsUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary ms-auto">Ver todas</a>
        </div>
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead><tr><th>Protocolo</th><th>Classificação</th><th>Status</th><th>Data</th></tr></thead>
                <tbody>
                    <?php foreach ($stats['recent'] ?? [] as $r): ?>
                        <tr>
                            <td><a href="<?php echo $_ENV['URL_ADM']; ?>view-denuncia/<?= (int)$r['id'] ?>"><?= htmlspecialchars((string)$r['protocol']) ?></a></td>
                            <td><?= htmlspecialchars((string)$r['category']) ?></td>
                            <td><?= htmlspecialchars((string)$r['status']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime((string)$r['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
