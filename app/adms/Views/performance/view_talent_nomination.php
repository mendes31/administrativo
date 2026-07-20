<?php
use App\adms\Helpers\FormatHelper;
$n = $this->data['nomination'] ?? [];
$cycleId = (int) ($n['performance_cycle_id'] ?? 0);
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Nomeação HiPo</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>list-talent-nominations" class="text-decoration-none">Talent Pool</a></li>
            <li class="breadcrumb-item">Visualizar</li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span><i class="fas fa-star me-2"></i><?= htmlspecialchars($n['user_name'] ?? '') ?></span>
            <div>
                <?php if (in_array('NineBoxMatrix', $this->data['buttonPermission'] ?? [], true) && $cycleId > 0) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>nine-box-matrix?performance_cycle_id=<?= $cycleId ?>" class="btn btn-sm btn-outline-primary">9BOX do ciclo</a>
                <?php } ?>
                <?php if (in_array('ListPdiPlans', $this->data['buttonPermission'] ?? [], true)) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-pdi-plans?user_id=<?= (int) ($n['user_id'] ?? 0) ?>" class="btn btn-sm btn-outline-secondary">PDI</a>
                <?php } ?>
                <?php if (in_array('UpdateTalentNomination', $this->data['buttonPermission'] ?? [], true)) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>update-talent-nomination/<?= (int) $n['id'] ?>" class="btn btn-sm btn-warning">Editar</a>
                <?php } ?>
                <?php
                $log_resumo = $this->data['log_resumo'] ?? [];
                $log_btn_class = 'btn btn-outline-info btn-sm';
                include __DIR__ . '/../partials/button_log_alteracoes.php';
                ?>
            </div>
        </div>
        <div class="card-body row g-3">
            <div class="col-md-3"><div class="text-muted small">Ciclo</div><?= htmlspecialchars($n['cycle_name'] ?? '') ?></div>
            <div class="col-md-2"><div class="text-muted small">Box</div><?= $n['nine_box'] !== null ? (int) $n['nine_box'] : '—' ?></div>
            <div class="col-md-2"><div class="text-muted small">Status</div>
                <?= ($n['status'] ?? '') === 'active' ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-secondary">Removido</span>' ?>
            </div>
            <div class="col-md-3"><div class="text-muted small">Nomeado por</div><?= htmlspecialchars($n['nominated_by_name'] ?? '—') ?></div>
            <div class="col-md-2"><div class="text-muted small">Em</div><?= htmlspecialchars(FormatHelper::formatDateTime($n['created_at'] ?? '')) ?></div>
            <?php if (!empty($n['notes'])): ?>
                <div class="col-12"><div class="text-muted small">Notas</div><?= nl2br(htmlspecialchars($n['notes'])) ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>
