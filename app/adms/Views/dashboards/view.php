<?php
$this->layout('layouts/main', ['pageTitle' => $dashboard['name'] ?? 'Dashboard KPI']); ?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <div>
            <h1><?= htmlspecialchars($dashboard['name'] ?? 'Dashboard') ?></h1>
            <p class="text-muted"><?= htmlspecialchars($dashboard['description'] ?? '') ?></p>
        </div>
        <div>
            <a href="<?= $_ENV['URL_ADM'] ?>list-kpi-dashboards" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <div id="kpi-dashboard-container" 
         data-dashboard-id="<?= $dashboard['id'] ?>"
         data-refresh-interval="<?= $dashboard['refresh_interval'] ?? 0 ?>">
        
        <div class="row" id="widgets-container">
            <?php if (empty($widgets)): ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Nenhum widget configurado neste dashboard.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($widgets as $widget): ?>
                    <?php
                    $sizeClass = match($widget['size'] ?? 'medium') {
                        'small' => 'col-xl-3 col-lg-4 col-md-6',
                        'medium' => 'col-xl-4 col-lg-6 col-md-6',
                        'large' => 'col-xl-6 col-lg-6 col-md-12',
                        'full' => 'col-12',
                        default => 'col-xl-4 col-lg-6 col-md-6'
                    };
                    
                    $colorClass = match($widget['color_scheme'] ?? 'primary') {
                        'success' => 'border-left-success',
                        'danger' => 'border-left-danger',
                        'warning' => 'border-left-warning',
                        'info' => 'border-left-info',
                        default => 'border-left-primary'
                    };
                    ?>
                    
                    <div class="<?= $sizeClass ?> mb-4">
                        <div class="card <?= $colorClass ?> shadow h-100 kpi-widget" 
                             data-widget-id="<?= $widget['id'] ?>"
                             data-widget-type="<?= $widget['widget_type'] ?>"
                             data-report-id="<?= $widget['report_id'] ?? '' ?>">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-<?= $widget['color_scheme'] ?? 'primary' ?> text-uppercase mb-1">
                                            <?= htmlspecialchars($widget['title'] ?? '') ?>
                                        </div>
                                        
                                        <?php if (in_array($widget['widget_type'], ['number', 'gauge'])): ?>
                                            <div class="h3 mb-0 font-weight-bold text-gray-800" id="widget-value-<?= $widget['id'] ?>">
                                                <i class="fas fa-spinner fa-spin"></i> Carregando...
                                            </div>
                                            <?php if ($widget['target_value']): ?>
                                                <div class="mt-2">
                                                    <small class="text-muted">Meta: <?= number_format((float)$widget['target_value'], 2, ',', '.') ?></small>
                                                    <div class="progress mt-1" style="height: 5px;">
                                                        <div class="progress-bar" role="progressbar" 
                                                             id="widget-progress-<?= $widget['id'] ?>"
                                                             style="width: 0%"></div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        
                                        <?php elseif (strpos($widget['widget_type'], 'chart_') === 0): ?>
                                            <canvas id="widget-chart-<?= $widget['id'] ?>" style="max-height: 250px;"></canvas>
                                        
                                        <?php elseif ($widget['widget_type'] === 'table'): ?>
                                            <div class="table-responsive" id="widget-table-<?= $widget['id'] ?>">
                                                <i class="fas fa-spinner fa-spin"></i> Carregando...
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($widget['icon']): ?>
                                        <div class="col-auto">
                                            <i class="<?= htmlspecialchars($widget['icon']) ?> fa-2x text-gray-300"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="<?= $_ENV['URL_ADM'] ?>public/adms/js/kpi-dashboard.js"></script>

