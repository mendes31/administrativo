<?php
$categories = $this->data['categories'] ?? [];
$reports = $this->data['reports'] ?? [];
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">
                    <i class="fas fa-chart-line"></i> Meus Relatórios
                </h1>
                <?php if (in_array('DynamicReportBuilder', $this->data['buttonPermission'] ?? [])): ?>
                    <a href="<?= $_ENV['URL_ADM'] ?>dynamic-report-builder" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Criar Novo Relatório
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (empty($reports)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-chart-bar fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">Nenhum relatório encontrado</h5>
                        <p class="text-muted">Comece criando seu primeiro relatório dinâmico!</p>
                        <?php if (in_array('DynamicReportBuilder', $this->data['buttonPermission'] ?? [])): ?>
                            <a href="<?= $_ENV['URL_ADM'] ?>dynamic-report-builder" class="btn btn-primary mt-3">
                                <i class="fas fa-plus"></i> Criar Meu Primeiro Relatório
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($categories as $categoryName => $categoryReports): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <h4 class="mb-3">
                        <i class="fas fa-folder"></i> <?= htmlspecialchars($categoryName) ?>
                        <span class="badge bg-secondary"><?= count($categoryReports) ?></span>
                    </h4>
                </div>
            </div>

            <div class="row">
                <?php foreach ($categoryReports as $report): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <?php
                                        $icon = match($report['visualization_type']) {
                                            'table' => 'fa-table',
                                            'bar_chart' => 'fa-chart-bar',
                                            'line_chart' => 'fa-chart-line',
                                            'pie_chart' => 'fa-chart-pie',
                                            'donut_chart' => 'fa-chart-pie',
                                            'area_chart' => 'fa-chart-area',
                                            'column_chart' => 'fa-chart-column',
                                            default => 'fa-chart-bar'
                                        };
                                        ?>
                                        <i class="fas <?= $icon ?>"></i>
                                        <?= htmlspecialchars($report['name']) ?>
                                    </h5>
                                    <?php if ($report['is_favorite']): ?>
                                        <i class="fas fa-star text-warning"></i>
                                    <?php endif; ?>
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
                                
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-user"></i> 
                                        <?= htmlspecialchars($report['creator_name'] ?? 'Desconhecido') ?>
                                    </small>
                                </div>
                                
                                <?php if ($report['is_public']): ?>
                                    <span class="badge bg-info">
                                        <i class="fas fa-share-alt"></i> Público
                                    </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($report['refresh_interval'])): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-sync"></i> Auto-atualiza (<?= $report['refresh_interval'] ?>s)
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer bg-light">
                                <div class="d-flex justify-content-between">
                                    <a href="<?= $_ENV['URL_ADM'] ?>view-dynamic-report/<?= $report['id'] ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> Visualizar
                                    </a>
                                    
                                    <div class="btn-group">
                                        <?php if ($report['created_by'] == ($_SESSION['user_id'] ?? 0)): ?>
                                            <a href="<?= $_ENV['URL_ADM'] ?>dynamic-report-builder?id=<?= $report['id'] ?>" 
                                               class="btn btn-sm btn-secondary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?= $_ENV['URL_ADM'] ?>delete-dynamic-report/<?= $report['id'] ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Tem certeza que deseja excluir este relatório?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

