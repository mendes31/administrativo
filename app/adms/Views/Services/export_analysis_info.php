<?php
use App\adms\Helpers\FormatHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Exportar Análise Completa do Projeto</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Exportar Análise</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header bg-primary text-white">
            <span><i class="fas fa-file-pdf me-2"></i>Análise Completa do Projeto - PDF</span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            
            <div class="alert alert-info">
                <h5><i class="fas fa-info-circle me-2"></i>Informações sobre a Análise</h5>
                <p>Este documento contém uma análise completa do projeto, incluindo:</p>
                <ul>
                    <li><strong>Métricas do Projeto:</strong> Estatísticas de código, linhas, arquivos</li>
                    <li><strong>17+ Módulos Implementados:</strong> Descrição detalhada de cada módulo</li>
                    <li><strong>Análise por Complexidade:</strong> Classificação e tempo estimado</li>
                    <li><strong>Estimativas de Desenvolvimento:</strong> Cenários realistas e otimistas</li>
                    <li><strong>Comparação com Sistemas Comerciais:</strong> Benchmarking de mercado</li>
                    <li><strong>Investimento Estimado:</strong> Custos e horas de desenvolvimento</li>
                </ul>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card border-primary">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-download me-2"></i>Download do PDF</h5>
                            <p class="card-text">Clique no botão abaixo para gerar e baixar o PDF com a análise completa do projeto.</p>
                            <a href="<?php echo $_ENV['URL_ADM']; ?>export-analysis-pdf" class="btn btn-primary btn-lg">
                                <i class="fas fa-file-pdf me-2"></i>Gerar e Baixar PDF
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-info">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-chart-bar me-2"></i>Estatísticas Rápidas</h5>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-code me-2 text-primary"></i><strong>556 Controllers</strong> implementados</li>
                                <li><i class="fas fa-database me-2 text-success"></i><strong>107 Repositories</strong> para acesso a dados</li>
                                <li><i class="fas fa-file-alt me-2 text-warning"></i><strong>367 Views</strong> com interface moderna</li>
                                <li><i class="fas fa-table me-2 text-info"></i><strong>150+ Tabelas</strong> no banco de dados</li>
                                <li><i class="fas fa-file-code me-2 text-danger"></i><strong>170.000+ linhas</strong> de código PHP</li>
                                <li><i class="fas fa-cube me-2 text-secondary"></i><strong>17+ Módulos</strong> funcionais completos</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <h5><i class="fas fa-clock me-2"></i>Tempo Estimado de Desenvolvimento</h5>
                <div class="row">
                    <div class="col-md-4">
                        <div class="card text-center border-success">
                            <div class="card-body">
                                <h6 class="text-success">Cenário Realista</h6>
                                <h3>15.5-20 meses</h3>
                                <small class="text-muted">1 desenvolvedor full-time</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center border-info">
                            <div class="card-body">
                                <h6 class="text-info">Cenário Otimista</h6>
                                <h3>12.5-17 meses</h3>
                                <small class="text-muted">1 desenvolvedor full-time</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center border-warning">
                            <div class="card-body">
                                <h6 class="text-warning">Com Time</h6>
                                <h3>4-7 meses</h3>
                                <small class="text-muted">3-4 desenvolvedores</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

