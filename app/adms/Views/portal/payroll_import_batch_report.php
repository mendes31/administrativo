<?php
$urlAdm = rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/';
$batch = $this->data['batch'] ?? [];
$batchId = (int)($this->data['batch_id'] ?? 0);
$rows = $this->data['report_rows'] ?? [];
$kpi = $this->data['kpi'] ?? ['total' => 0, 'notified' => 0, 'viewed' => 0, 'downloaded' => 0, 'signed' => 0, 'pending' => 0, 'na_ciencia' => 0];
$labels = $this->data['type_labels'] ?? [];
$auditUrl = (string)($this->data['audit_url'] ?? '');
$dt = (string)($batch['document_type'] ?? '');
$fn = (string)($batch['original_filename'] ?? '');
$y = (int)($batch['reference_year'] ?? 0);
$mo = $batch['reference_month'] ?? null;
$ref = $mo !== null && $mo !== '' ? str_pad((string)(int)$mo, 2, '0', STR_PAD_LEFT) . '/' . $y : (string)$y;
$csvUrl = $urlAdm . 'payroll-import-batch-report/' . $batchId . '?export=csv';

$fmt = static function (?string $mysql): string {
    if ($mysql === null || $mysql === '') {
        return '—';
    }
    $t = strtotime($mysql);

    return $t !== false ? date('d/m/Y H:i:s', $t) : htmlspecialchars($mysql);
};
?>
<div class="container-fluid px-3 px-md-4">
    <div class="mb-1 d-flex flex-column flex-md-row gap-2 align-items-md-center">
        <h2 class="mt-3 mb-0">Relatório do lote de importação</h2>
        <ol class="breadcrumb mb-0 mt-2 mt-md-3 ms-md-auto small">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($urlAdm) ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($urlAdm) ?>import-payroll-documents" class="text-decoration-none">Importar RH</a></li>
            <li class="breadcrumb-item active">Relatório lote #<?= $batchId ?></li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="card border-0 shadow-sm mb-3 mt-3 rounded-3">
        <div class="card-header bg-primary text-white d-flex flex-wrap align-items-center gap-2 py-2">
            <span class="fw-semibold"><i class="fas fa-file-import me-2"></i>Lote #<?= $batchId ?> — <?= htmlspecialchars(mb_strlen($fn) > 60 ? mb_substr($fn, 0, 57) . '…' : $fn) ?></span>
            <div class="ms-md-auto d-flex flex-wrap gap-2">
                <a href="<?= htmlspecialchars($csvUrl) ?>" class="btn btn-light btn-sm"><i class="fas fa-file-csv me-1"></i> CSV</a>
                <a href="<?= htmlspecialchars($auditUrl) ?>" class="btn btn-outline-light btn-sm"><i class="fas fa-list-alt me-1"></i> Trilha de auditoria</a>
                <a href="<?= htmlspecialchars($urlAdm) ?>import-payroll-documents" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
            </div>
        </div>
        <div class="card-body small">
            <div class="row g-2">
                <div class="col-md-3"><span class="text-muted">Tipo</span><br><strong><?= htmlspecialchars($labels[$dt] ?? $dt) ?></strong></div>
                <div class="col-md-2"><span class="text-muted">Referência</span><br><strong><?= htmlspecialchars($ref) ?></strong></div>
                <div class="col-md-3"><span class="text-muted">Importado em</span><br><strong><?= $fmt((string)($batch['created_at'] ?? '')) ?></strong></div>
                <div class="col-md-4"><span class="text-muted">Por</span><br><strong><?= htmlspecialchars((string)($batch['created_by_name'] ?? '—')) ?></strong></div>
            </div>
            <p class="text-muted mt-3 mb-0">
                <strong>Notificado</strong> reflete criação da <strong>notificação interna</strong> do portal (publicação). Visualizado/Baixado usam eventos registados ao abrir o PDF (inline vs download).
            </p>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm h-100 bg-primary text-white">
                <div class="card-body py-3 text-center">
                    <div class="fs-4 fw-bold"><?= (int)$kpi['total'] ?></div>
                    <div class="small opacity-90">Documentos no lote</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm h-100 bg-info text-white">
                <div class="card-body py-3 text-center">
                    <div class="fs-4 fw-bold"><?= (int)$kpi['notified'] ?></div>
                    <div class="small opacity-90">Notificados (interno)</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm h-100 bg-success text-white">
                <div class="card-body py-3 text-center">
                    <div class="fs-4 fw-bold"><?= (int)$kpi['viewed'] ?></div>
                    <div class="small opacity-90">Visualizaram</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm h-100 bg-secondary text-white">
                <div class="card-body py-3 text-center">
                    <div class="fs-4 fw-bold"><?= (int)$kpi['downloaded'] ?></div>
                    <div class="small opacity-90">Baixaram</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm h-100 bg-success bg-opacity-75 text-white">
                <div class="card-body py-3 text-center">
                    <div class="fs-4 fw-bold"><?= (int)$kpi['signed'] ?></div>
                    <div class="small opacity-90">Ciência OK</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm h-100 bg-warning text-dark">
                <div class="card-body py-3 text-center">
                    <div class="fs-4 fw-bold"><?= (int)$kpi['pending'] ?></div>
                    <div class="small">Pend. ciência</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-bottom py-2 d-flex flex-wrap align-items-center gap-2">
            <span class="fw-semibold small text-uppercase text-muted" style="letter-spacing:.04em;">Status por colaborador</span>
            <input type="search" id="filterPayrollReportUser" class="form-control form-control-sm ms-md-auto" style="max-width:280px;" placeholder="Filtrar por nome ou e-mail" autocomplete="off">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle" id="tablePayrollBatchReport">
                    <thead class="table-light">
                        <tr class="small">
                            <th class="ps-3">Colaborador</th>
                            <th>Disponibilizado</th>
                            <th>Notificado</th>
                            <th>Visualizou</th>
                            <th>Baixou</th>
                            <th>Ciência</th>
                            <th class="pe-3">Estado doc.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r) {
                            $filterHaystack = strtolower($r['owner_name'] . ' ' . $r['owner_email']);
                            $req = !empty($r['requires_signature']);
                            $st = (string)($r['signature_status'] ?? '');
                            if (!$req) {
                                $cienciaBadge = '<span class="badge text-bg-secondary">N/A</span>';
                                $cienciaDate = '—';
                            } elseif ($st === 'signed') {
                                $cienciaBadge = '<span class="badge text-bg-success">SIM</span>';
                                $cienciaDate = $fmt($r['signed_at']);
                            } elseif ($st === 'pending') {
                                $cienciaBadge = '<span class="badge text-bg-warning text-dark">PEND.</span>';
                                $cienciaDate = '—';
                            } else {
                                $cienciaBadge = '<span class="badge text-bg-light text-secondary border">' . htmlspecialchars($st) . '</span>';
                                $cienciaDate = '—';
                            }
                            $sv = (string)($r['status_version'] ?? '');
                            ?>
                            <tr data-filter="<?= htmlspecialchars($filterHaystack, ENT_QUOTES, 'UTF-8') ?>">
                                <td class="ps-3 small">
                                    <div class="fw-semibold"><?= htmlspecialchars($r['owner_name']) ?></div>
                                    <div class="text-muted" style="font-size:.72rem;"><?= htmlspecialchars($r['owner_email']) ?></div>
                                </td>
                                <td class="small text-nowrap"><?= $fmt($r['published_at'] !== '' ? (string)$r['published_at'] : null) ?></td>
                                <td class="small">
                                    <?php if (!empty($r['notified'])) { ?>
                                        <span class="badge text-bg-info">SIM</span>
                                        <div class="text-muted" style="font-size:.7rem;"><?= $fmt($r['notified_at']) ?></div>
                                    <?php } else { ?>
                                        <span class="badge text-bg-secondary">NÃO</span>
                                    <?php } ?>
                                </td>
                                <td class="small">
                                    <?php if (!empty($r['viewed_at'])) { ?>
                                        <span class="badge text-bg-success">SIM</span>
                                        <div class="text-muted" style="font-size:.7rem;"><?= $fmt((string)$r['viewed_at']) ?></div>
                                    <?php } else { ?>
                                        <span class="badge text-bg-danger">NÃO</span>
                                    <?php } ?>
                                </td>
                                <td class="small">
                                    <?php if (!empty($r['downloaded_at'])) { ?>
                                        <span class="badge text-bg-success">SIM</span>
                                        <div class="text-muted" style="font-size:.7rem;"><?= $fmt((string)$r['downloaded_at']) ?></div>
                                    <?php } else { ?>
                                        <span class="badge text-bg-secondary">NÃO</span>
                                    <?php } ?>
                                </td>
                                <td class="small"><?php echo $cienciaBadge; ?><?php if ($cienciaDate !== '—') { ?><div class="text-muted mt-1" style="font-size:.7rem;"><?= $cienciaDate ?></div><?php } ?></td>
                                <td class="pe-3 small"><span class="badge text-bg-light text-secondary border"><?= htmlspecialchars($sv !== '' ? $sv : '—') ?></span></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var inp = document.getElementById('filterPayrollReportUser');
    var tbl = document.getElementById('tablePayrollBatchReport');
    if (!inp || !tbl) return;
    inp.addEventListener('input', function () {
        var q = (inp.value || '').toLowerCase().trim();
        tbl.querySelectorAll('tbody tr').forEach(function (tr) {
            var h = (tr.getAttribute('data-filter') || '');
            tr.style.display = (!q || h.indexOf(q) !== -1) ? '' : 'none';
        });
    });
})();
</script>
