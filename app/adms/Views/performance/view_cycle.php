<?php
use App\adms\Helpers\FormatHelper;

$cycle = $this->data['cycle'] ?? [];
$statusLabels = [
    'draft' => ['label' => 'Rascunho', 'color' => 'secondary'],
    'open' => ['label' => 'Aberto', 'color' => 'success'],
    'closed' => ['label' => 'Fechado', 'color' => 'dark'],
];
$st = $statusLabels[$cycle['status'] ?? ''] ?? ['label' => $cycle['status'] ?? '-', 'color' => 'secondary'];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Visualizar Ciclo</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-performance-cycles" class="text-decoration-none">Ciclos</a>
            </li>
            <li class="breadcrumb-item">Visualizar</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-calendar-alt me-2"></i><?= htmlspecialchars($cycle['name'] ?? '') ?></span>
            <div>
                <?php if (in_array('ListPerformanceCycles', $this->data['buttonPermission'] ?? [], true)) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-performance-cycles" class="btn btn-sm btn-secondary">
                        <i class="fas fa-list me-1"></i>Listar
                    </a>
                <?php } ?>
                <?php if (in_array('UpdatePerformanceCycle', $this->data['buttonPermission'] ?? [], true)) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>update-performance-cycle/<?= (int) ($cycle['id'] ?? 0) ?>" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit me-1"></i>Editar
                    </a>
                <?php } ?>
                <?php
                $log_resumo = $this->data['log_resumo'] ?? [];
                $log_btn_class = 'btn btn-outline-info btn-sm';
                include __DIR__ . '/../partials/button_log_alteracoes.php';
                ?>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="text-muted small">Status</div>
                    <div><span class="badge bg-<?= $st['color'] ?>"><?= $st['label'] ?></span></div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Ano</div>
                    <div><?= (int) ($cycle['year'] ?? 0) ?></div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Criado por</div>
                    <div><?= htmlspecialchars($cycle['created_by_name'] ?? '-') ?></div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted small">Período</div>
                    <div>
                        <?= htmlspecialchars(FormatHelper::formatDate($cycle['period_start'] ?? '')) ?>
                        —
                        <?= htmlspecialchars(FormatHelper::formatDate($cycle['period_end'] ?? '')) ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Metas vinculadas</div>
                    <div>
                        <?= (int) ($this->data['goals_count'] ?? 0) ?>
                        <?php if (in_array('ListPerformanceGoals', $this->data['buttonPermission'] ?? [], true)
                            && (int) ($this->data['goals_count'] ?? 0) > 0) { ?>
                            <a href="<?php echo $_ENV['URL_ADM']; ?>list-performance-goals?performance_cycle_id=<?= (int) $cycle['id'] ?>"
                               class="ms-1 small">ver metas</a>
                        <?php } ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Avaliações vinculadas</div>
                    <div><?= (int) ($this->data['reviews_count'] ?? 0) ?></div>
                </div>
                <?php if (!empty($cycle['description'])): ?>
                    <div class="col-12">
                        <div class="text-muted small">Descrição</div>
                        <div><?= nl2br(htmlspecialchars($cycle['description'])) ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
