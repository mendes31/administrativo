<?php
use App\adms\Helpers\FormatHelper;
$cal = $this->data['calibration'] ?? [];
$statusLabels = [
    'draft' => ['label' => 'Rascunho', 'color' => 'secondary'],
    'open' => ['label' => 'Aberta', 'color' => 'success'],
    'locked' => ['label' => 'Travada', 'color' => 'dark'],
];
$st = $statusLabels[$cal['status'] ?? ''] ?? ['label' => $cal['status'] ?? '-', 'color' => 'secondary'];
$cycleId = (int) ($cal['performance_cycle_id'] ?? 0);
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Calibração</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>list-performance-calibrations" class="text-decoration-none">Calibração</a></li>
            <li class="breadcrumb-item">Visualizar</li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span><i class="fas fa-balance-scale me-2"></i><?= htmlspecialchars($cal['cycle_name'] ?? '') ?></span>
            <div>
                <?php if (in_array('NineBoxMatrix', $this->data['buttonPermission'] ?? [], true)) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>nine-box-matrix?performance_cycle_id=<?= $cycleId ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-th me-1"></i>Nine Box do ciclo
                    </a>
                <?php } ?>
                <?php if (in_array('ListPerformanceReviews', $this->data['buttonPermission'] ?? [], true)) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-performance-reviews?performance_cycle_id=<?= $cycleId ?>" class="btn btn-sm btn-outline-secondary">
                        Avaliações (<?= (int) ($this->data['reviews_count'] ?? 0) ?>)
                    </a>
                <?php } ?>
                <?php if (in_array('UpdatePerformanceCalibration', $this->data['buttonPermission'] ?? [], true) && ($cal['status'] ?? '') !== 'locked') { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>update-performance-calibration/<?= (int) $cal['id'] ?>" class="btn btn-sm btn-warning">Editar</a>
                <?php } ?>
                <?php
                $log_resumo = $this->data['log_resumo'] ?? [];
                $log_btn_class = 'btn btn-outline-info btn-sm';
                include __DIR__ . '/../partials/button_log_alteracoes.php';
                ?>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-3"><div class="text-muted small">Status</div><span class="badge bg-<?= $st['color'] ?>"><?= $st['label'] ?></span></div>
                <div class="col-md-3"><div class="text-muted small">Ano</div><?= (int) ($cal['cycle_year'] ?? 0) ?></div>
                <div class="col-md-3"><div class="text-muted small">Período do ciclo</div>
                    <?= htmlspecialchars(FormatHelper::formatDate($cal['period_start'] ?? '')) ?> —
                    <?= htmlspecialchars(FormatHelper::formatDate($cal['period_end'] ?? '')) ?>
                </div>
                <div class="col-md-3"><div class="text-muted small">Criado por</div><?= htmlspecialchars($cal['created_by_name'] ?? '-') ?></div>
                <?php if (!empty($cal['session_notes'])): ?>
                    <div class="col-12"><div class="text-muted small">Notas</div><?= nl2br(htmlspecialchars($cal['session_notes'])) ?></div>
                <?php endif; ?>
                <?php if (($cal['status'] ?? '') === 'locked'): ?>
                    <div class="col-12 text-muted small">
                        Travada em <?= htmlspecialchars(FormatHelper::formatDateTime($cal['locked_at'] ?? '')) ?>
                        por <?= htmlspecialchars($cal['locked_by_name'] ?? '-') ?>
                    </div>
                <?php endif; ?>
            </div>
            <h5 class="mb-3">Avaliações do ciclo</h5>
            <?php if (empty($this->data['reviews'])): ?>
                <div class="alert alert-info">Nenhuma avaliação vinculada a este ciclo ainda.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead><tr><th>Colaborador</th><th>Tipo</th><th>Nota</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($this->data['reviews'] as $review): ?>
                                <tr>
                                    <td><?= htmlspecialchars($review['employee_name'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($review['review_type'] ?? '') ?></td>
                                    <td><?= $review['overall_score'] !== null ? htmlspecialchars((string) $review['overall_score']) : '—' ?></td>
                                    <td><?= htmlspecialchars($review['status'] ?? '') ?></td>
                                    <td><a href="<?php echo $_ENV['URL_ADM']; ?>view-performance-review/<?= (int) $review['id'] ?>">ver</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
