<?php
use App\adms\Helpers\FormatHelper;
$p = $this->data['promotion'] ?? [];
$statusLabels = ['draft' => 'Rascunho', 'approved' => 'Aprovada', 'applied' => 'Aplicada', 'cancelled' => 'Cancelada'];
?>
<div class="container-fluid px-4">
    <h2 class="mt-3">Promoção</h2>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex justify-content-between flex-wrap gap-2">
            <span><?= htmlspecialchars($p['user_name'] ?? '') ?></span>
            <div>
                <?php if (in_array('UpdateCareerPromotion', $this->data['buttonPermission'] ?? [], true) && ($p['status'] ?? '') !== 'applied') { ?>
                    <a class="btn btn-sm btn-warning" href="<?php echo $_ENV['URL_ADM']; ?>update-career-promotion/<?= (int) $p['id'] ?>">Editar / Aplicar</a>
                <?php } ?>
                <?php $log_resumo = $this->data['log_resumo'] ?? []; $log_btn_class = 'btn btn-outline-info btn-sm'; include __DIR__ . '/../partials/button_log_alteracoes.php'; ?>
            </div>
        </div>
        <div class="card-body row g-3">
            <div class="col-md-3"><div class="text-muted small">De</div><?= htmlspecialchars($p['from_position_name'] ?? '—') ?></div>
            <div class="col-md-3"><div class="text-muted small">Para</div><?= htmlspecialchars($p['to_position_name'] ?? '') ?></div>
            <div class="col-md-2"><div class="text-muted small">Data</div><?= htmlspecialchars(FormatHelper::formatDate($p['effective_date'] ?? '')) ?></div>
            <div class="col-md-2"><div class="text-muted small">Status</div><?= htmlspecialchars($statusLabels[$p['status'] ?? ''] ?? '') ?></div>
            <div class="col-md-2"><div class="text-muted small">Trilha</div><?= htmlspecialchars($p['track_name'] ?? '—') ?></div>
            <?php if (!empty($p['notes'])): ?><div class="col-12"><?= nl2br(htmlspecialchars($p['notes'])) ?></div><?php endif; ?>
        </div>
    </div>
</div>
