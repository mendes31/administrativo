<?php
$summary = $this->data['summary'] ?? [];
$statusItems = [
    ['key' => 'dentro_do_prazo', 'label' => 'Dentro do Prazo', 'icon' => 'fas fa-clock', 'color' => 'text-success'],
    ['key' => 'proximo_vencimento', 'label' => 'Próximo do Vencimento', 'icon' => 'fas fa-exclamation-circle', 'color' => 'text-warning'],
    ['key' => 'vencido', 'label' => 'Vencido', 'icon' => 'fas fa-exclamation-triangle', 'color' => 'text-danger'],
    ['key' => 'agendado', 'label' => 'Agendado', 'icon' => 'fas fa-calendar-alt', 'color' => 'text-info'],
    ['key' => 'concluido', 'label' => 'Concluído', 'icon' => 'fas fa-check-circle', 'color' => 'text-secondary'],
];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Visão da Matriz de Treinamentos</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-trainings" class="text-decoration-none">Treinamentos</a>
            </li>
            <li class="breadcrumb-item">Matriz</li>
        </ol>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <h3 class="text-primary">
                        <i class="fas fa-users"></i>
                        <?php echo number_format($this->data['stats']['total_users'] ?? 0); ?>
                    </h3>
                    <p class="card-text mb-0">Total de Colaboradores</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <h3 class="text-info">
                        <i class="fas fa-briefcase"></i>
                        <?php echo number_format($this->data['stats']['total_positions'] ?? 0); ?>
                    </h3>
                    <p class="card-text mb-0">Total de Cargos</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h3 class="text-success">
                        <i class="fas fa-graduation-cap"></i>
                        <?php echo number_format($this->data['stats']['total_trainings'] ?? 0); ?>
                    </h3>
                    <p class="card-text mb-0">Total de Treinamentos</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <h3 class="text-warning">
                        <i class="fas fa-table"></i>
                        <?php echo number_format($this->data['stats']['total_matrix_entries'] ?? 0); ?>
                    </h3>
                    <p class="card-text mb-0">Vínculos na Matriz</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-light shadow">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>
                        Estatísticas por Status
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($statusItems as $item): ?>
                            <div class="col-md-4 col-lg-2 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="<?php echo $item['icon']; ?> <?php echo $item['color']; ?> fa-2x me-3"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h4 class="mb-0"><?php echo number_format($summary[$item['key']] ?? 0); ?></h4>
                                        <small class="text-muted"><?php echo $item['label']; ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-md-12">
            <div class="alert alert-info mb-0">
                <h6 class="mb-2"><i class="fas fa-info-circle me-2"></i>Sincronização automática</h6>
                <p class="mb-2">A matriz é atualizada automaticamente nas operações do módulo:</p>
                <ul class="mb-0">
                    <li>Cadastro ou edição de colaboradores (cargo, ativação/inativação)</li>
                    <li>Criação, edição, exclusão ou <strong>nova versão</strong> de treinamentos</li>
                    <li>Vínculo de cargos a treinamentos e vínculos individuais</li>
                    <li>Aplicação e conclusão de treinamentos</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between flex-wrap gap-2">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-trainings" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Voltar aos Treinamentos
                </a>
                <div>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>training-kpi-dashboard" class="btn btn-warning me-2">
                        <i class="fas fa-chart-line me-2"></i>Dashboard de KPIs
                    </a>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-training-status" class="btn btn-info me-2">
                        <i class="fas fa-chart-bar me-2"></i>Status dos Treinamentos
                    </a>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-trainings" class="btn btn-primary">
                        <i class="fas fa-list me-2"></i>Listar Treinamentos
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
