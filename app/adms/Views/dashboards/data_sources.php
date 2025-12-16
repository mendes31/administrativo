<?php
$dashboard = $this->data['dashboard'] ?? [];
$reports = $this->data['reports'] ?? [];
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0">
                <i class="fas fa-database"></i>
                Fontes de Dados - <?= htmlspecialchars($dashboard['name'] ?? '') ?>
            </h1>
            <div>
                <a href="<?= $_ENV['URL_ADM'] ?>view-dashboard/<?= $dashboard['id'] ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Voltar ao Dashboard
                </a>
            </div>
        </div>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <?php if (empty($reports)): ?>
        <div class="alert alert-warning">
            <i class="fas fa-info-circle"></i>
            Nenhum relatório vinculado a este dashboard.
        </div>
    <?php else: ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    Abaixo estão as fontes de dados (relatórios dinâmicos) utilizadas por este dashboard.
                </div>
            </div>
        </div>

        <div class="row">
            <?php foreach ($reports as $report): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-bar"></i>
                                    <?= htmlspecialchars($report['name'] ?? '') ?>
                                </h5>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($report['description'])): ?>
                                <p class="text-muted small"><?= nl2br(htmlspecialchars($report['description'])) ?></p>
                            <?php endif; ?>

                            <div class="mb-2">
                                <small class="text-muted">
                                    <i class="fas fa-database"></i>
                                    <?= htmlspecialchars($report['data_source'] ?? 'SQL Personalizado') ?>
                                </small>
                            </div>
                        </div>
                        <div class="card-footer bg-light d-flex justify-content-between">
                            <a href="<?= $_ENV['URL_ADM'] ?>view-dynamic-report/<?= $report['id'] ?>?dashboard_id=<?= $dashboard['id'] ?>"
                               class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> Abrir Relatório
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>




