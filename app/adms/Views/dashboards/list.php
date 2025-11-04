<?php
$this->layout('layouts/main', ['pageTitle' => 'Dashboards de KPI']); ?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Dashboards de KPI</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>dashboard">Home</a></li>
        <li class="breadcrumb-item active">Dashboards de KPI</li>
    </ol>

    <?= $_SESSION['msg'] ?? ''; unset($_SESSION['msg']); ?>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-tachometer-alt me-1"></i>
            Meus Dashboards
            <a href="<?= $_ENV['URL_ADM'] ?>create-kpi-dashboard" class="btn btn-success btn-sm float-end">
                <i class="fas fa-plus"></i> Novo Dashboard
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($dashboards)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    Nenhum dashboard encontrado. <a href="<?= $_ENV['URL_ADM'] ?>create-kpi-dashboard">Criar novo dashboard</a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($dashboards as $dashboard): ?>
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                <?= htmlspecialchars($dashboard['name'] ?? '') ?>
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <small class="text-muted">
                                                    <?= htmlspecialchars($dashboard['description'] ?? 'Sem descrição') ?>
                                                </small>
                                            </div>
                                            <div class="mt-2">
                                                <span class="badge bg-info"><?= $dashboard['widget_count'] ?? 0 ?> widgets</span>
                                                <?php if ($dashboard['is_public']): ?>
                                                    <span class="badge bg-success">Público</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Privado</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    Criado por: <?= htmlspecialchars($dashboard['creator_name'] ?? '') ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-chart-area fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <a href="<?= $_ENV['URL_ADM'] ?>view-kpi-dashboard?id=<?= $dashboard['id'] ?>" 
                                           class="btn btn-primary btn-sm w-100">
                                            <i class="fas fa-eye"></i> Visualizar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

