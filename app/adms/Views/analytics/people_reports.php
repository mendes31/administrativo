<?php
use App\adms\Helpers\FormatHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Relatórios de RH</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>people-analytics" class="text-decoration-none">People Analytics</a>
            </li>
            <li class="breadcrumb-item">Relatórios</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span><i class="fas fa-file-alt me-2"></i>Relatórios de Gestão de Pessoas</span>
            <span class="ms-auto">
                <a href="<?php echo $_ENV['URL_ADM']; ?>people-analytics" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Voltar
                </a>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Selecione o relatório que deseja visualizar ou exportar.
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card border-primary">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-users text-primary me-2"></i>Headcount
                            </h5>
                            <p class="card-text">Relatório de quantidade de colaboradores por departamento, cargo e período.</p>
                            <button class="btn btn-primary btn-sm" disabled>
                                <i class="fas fa-download me-1"></i>Gerar Relatório
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card border-warning">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-exchange-alt text-warning me-2"></i>Turnover
                            </h5>
                            <p class="card-text">Análise de rotatividade de pessoal e taxa de desligamentos.</p>
                            <button class="btn btn-warning btn-sm" disabled>
                                <i class="fas fa-download me-1"></i>Gerar Relatório
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card border-success">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-chart-line text-success me-2"></i>Desempenho
                            </h5>
                            <p class="card-text">Relatório consolidado de avaliações de desempenho.</p>
                            <button class="btn btn-success btn-sm" disabled>
                                <i class="fas fa-download me-1"></i>Gerar Relatório
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card border-info">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-graduation-cap text-info me-2"></i>Treinamentos
                            </h5>
                            <p class="card-text">Relatório de treinamentos realizados e pendentes.</p>
                            <button class="btn btn-info btn-sm" disabled>
                                <i class="fas fa-download me-1"></i>Gerar Relatório
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-warning mt-4">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Em desenvolvimento:</strong> Os relatórios estarão disponíveis em breve.
            </div>
        </div>
    </div>
</div>

