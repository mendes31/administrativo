<?php
use App\adms\Helpers\CSRFHelper;

// Extrair dados passados pelo controller
if (isset($this->data['estatisticas'])) {
    extract($this->data['estatisticas']);
} else {
    // Fallback caso os dados não estejam disponíveis
    $estatisticas = [];
    $total = 0;
    $aprovados = 0;
    $em_revisao = 0;
    $por_status = [];
    $por_mes = [];
}
?>

<div class="container-fluid px-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>lgpd-dashboard">Dashboard LGPD</a></li>
            <li class="breadcrumb-item active" aria-current="page">Dashboard RIPD</li>
        </ol>
    </nav>

    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-chart-bar me-2"></i>Dashboard RIPD</h2>
        <div>
            <a href="<?= $_ENV['URL_ADM'] ?>lgpd-ripd-export-pdf-list" class="btn btn-info me-2" target="_blank">
                <i class="fas fa-file-pdf me-1"></i>Exportar Lista PDF
            </a>
            <a href="<?= $_ENV['URL_ADM'] ?>lgpd-ripd" class="btn btn-primary me-2">
                <i class="fas fa-list me-1"></i>Listar RIPDs
            </a>
            <a href="<?= $_ENV['URL_ADM'] ?>lgpd-ripd-create" class="btn btn-success">
                <i class="fas fa-plus me-1"></i>Novo RIPD
            </a>
        </div>
    </div>

    <!-- Cards de Estatísticas -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total de RIPDs
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= $total ?? 0 ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                RIPDs Aprovados
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= $aprovados ?? 0 ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Em Revisão
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= $em_revisao ?? 0 ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Em Rascunho
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= ($total ?? 0) - (($aprovados ?? 0) + ($em_revisao ?? 0)) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-edit fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Gráfico de Status -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Distribuição por Status</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfico de Evolução Mensal -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Evolução Mensal</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Status Detalhado -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Status Detalhado</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Quantidade</th>
                            <th>Percentual</th>
                            <th>Barra de Progresso</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total = $total ?? 0;
                        if ($total > 0 && isset($por_status)):
                            foreach ($por_status as $status):
                                $percentual = round(($status['total'] / $total) * 100, 1);
                                $statusClass = match($status['status']) {
                                    'Aprovado' => 'success',
                                    'Em Revisão' => 'warning',
                                    'Rejeitado' => 'danger',
                                    default => 'secondary'
                                };
                        ?>
                        <tr>
                            <td>
                                <span class="badge bg-<?= $statusClass ?>"><?= $status['status'] ?></span>
                            </td>
                            <td><?= $status['total'] ?></td>
                            <td><?= $percentual ?>%</td>
                            <td>
                                <div class="progress">
                                    <div class="progress-bar bg-<?= $statusClass ?>" 
                                         role="progressbar" 
                                         style="width: <?= $percentual ?>%" 
                                         aria-valuenow="<?= $percentual ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php 
                            endforeach;
                        else:
                        ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">
                                <i class="fas fa-info-circle me-2"></i>
                                Nenhum RIPD encontrado para gerar estatísticas
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Ações Rápidas -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Ações Rápidas</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?= $_ENV['URL_ADM'] ?>lgpd-ripd-create" class="btn btn-success btn-lg">
                            <i class="fas fa-plus me-2"></i>Criar Novo RIPD
                        </a>
                        <a href="<?= $_ENV['URL_ADM'] ?>lgpd-ripd" class="btn btn-primary">
                            <i class="fas fa-list me-2"></i>Ver Todos os RIPDs
                        </a>
                        <a href="<?= $_ENV['URL_ADM'] ?>lgpd-aipd" class="btn btn-info">
                            <i class="fas fa-link me-2"></i>Gerenciar AIPDs
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informações</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>Sobre o RIPD</h6>
                        <p class="mb-0">
                            O Relatório de Impacto à Proteção de Dados (RIPD) é o documento final 
                            que consolida toda a avaliação de impacto e é exigido pela LGPD.
                        </p>
                    </div>
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Lembrete</h6>
                        <p class="mb-0">
                            RIPDs aprovados não podem ser excluídos. Para alterações, 
                            crie uma nova versão.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Gráfico de Status
const statusCtx = document.getElementById('statusChart').getContext('2d');
const statusChart = new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: [
            <?php 
            if (isset($por_status)):
                foreach ($por_status as $status):
                    echo "'" . addslashes($status['status']) . "',";
                endforeach;
            endif;
            ?>
        ],
        datasets: [{
            data: [
                            <?php 
            if (isset($por_status)):
                foreach ($por_status as $status):
                    echo $status['total'] . ",";
                endforeach;
            endif;
            ?>
            ],
            backgroundColor: [
                '#28a745', // Aprovado - Verde
                '#ffc107', // Em Revisão - Amarelo
                '#dc3545', // Rejeitado - Vermelho
                '#6c757d'  // Rascunho - Cinza
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Gráfico de Evolução Mensal
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
const monthlyChart = new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: [
            <?php 
            if (isset($por_mes)):
                foreach ($por_mes as $mes):
                    echo "'" . date('M/Y', strtotime($mes['mes'] . '-01')) . "',";
                endforeach;
            endif;
            ?>
        ],
        datasets: [{
            label: 'RIPDs Criados',
            data: [
                            <?php 
            if (isset($por_mes)):
                foreach ($por_mes as $mes):
                    echo $mes['total'] . ",";
                endforeach;
            endif;
            ?>
            ],
            borderColor: '#007bff',
            backgroundColor: 'rgba(0, 123, 255, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>
