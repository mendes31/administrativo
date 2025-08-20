<?php
use App\adms\Helpers\CSRFHelper;
?>
<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">
            <i class="fas fa-chart-line text-primary"></i>
            Dashboard TIA - Testes de Impacto às Atividades
        </h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-dashboard" class="text-decoration-none">LGPD</a>
            </li>
            <li class="breadcrumb-item">Dashboard TIA</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <!-- CARDS DE ESTATÍSTICAS -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total de Testes TIA
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $this->data['estatisticas']['total'] ?? 0; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-check fa-2x text-gray-300"></i>
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
                                Testes Concluídos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $this->data['estatisticas']['concluidos'] ?? 0; ?>
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
                                Em Andamento
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $this->data['estatisticas']['em_andamento'] ?? 0; ?>
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
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Necessitam AIPD
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $this->data['estatisticas']['necessitam_aipd'] ?? 0; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- GRÁFICOS E VISUALIZAÇÕES -->
    <div class="row mb-4">
        <!-- Gráfico de Risco por Departamento -->
        <div class="col-xl-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-pie me-2"></i>Risco por Departamento
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="riscoDepartamentoChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Gráfico de Status por Mês -->
        <div class="col-xl-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-line me-2"></i>Status por Mês (Últimos 12 meses)
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="statusMesChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- TABELAS DE DADOS -->
    <div class="row mb-4">
        <!-- Testes TIA Recentes -->
        <div class="col-xl-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-history me-2"></i>Testes TIA Recentes
                    </h6>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-tia" class="btn btn-sm btn-primary">
                        Ver Todos
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($this->data['tias_recentes'])): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Título</th>
                                        <th>Departamento</th>
                                        <th>Resultado</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($this->data['tias_recentes'] as $tia): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-tia-view/<?php echo $tia['id']; ?>" 
                                                   class="text-decoration-none">
                                                    <?php echo htmlspecialchars($tia['codigo']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars(substr($tia['titulo'], 0, 30)) . (strlen($tia['titulo']) > 30 ? '...' : ''); ?></td>
                                            <td><?php echo htmlspecialchars($tia['departamento_nome']); ?></td>
                                            <td>
                                                <?php
                                                $resultadoClass = match($tia['resultado']) {
                                                    'Baixo Risco' => 'success',
                                                    'Médio Risco' => 'warning',
                                                    'Alto Risco' => 'danger',
                                                    'Necessita AIPD' => 'dark',
                                                    default => 'secondary'
                                                };
                                                ?>
                                                <span class="badge bg-<?php echo $resultadoClass; ?>">
                                                    <?php echo htmlspecialchars($tia['resultado']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                $statusClass = match($tia['status']) {
                                                    'Em Andamento' => 'warning',
                                                    'Concluído' => 'success',
                                                    'Aprovado' => 'info',
                                                    default => 'secondary'
                                                };
                                                ?>
                                                <span class="badge bg-<?php echo $statusClass; ?>">
                                                    <?php echo htmlspecialchars($tia['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>Nenhum teste TIA encontrado</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Testes TIA Pendentes -->
        <div class="col-xl-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-warning">
                        <i class="fas fa-clock me-2"></i>Testes Pendentes
                    </h6>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-tia" class="btn btn-sm btn-warning">
                        Ver Todos
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($this->data['tias_pendentes'])): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Título</th>
                                        <th>Departamento</th>
                                        <th>Data Prevista</th>
                                        <th>Ação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($this->data['tias_pendentes'] as $tia): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-tia-view/<?php echo $tia['id']; ?>" 
                                                   class="text-decoration-none">
                                                    <?php echo htmlspecialchars($tia['codigo']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars(substr($tia['titulo'], 0, 30)) . (strlen($tia['titulo']) > 30 ? '...' : ''); ?></td>
                                            <td><?php echo htmlspecialchars($tia['departamento_nome']); ?></td>
                                            <td>
                                                <span class="text-<?php echo date('Y-m-d') > $tia['data_teste'] ? 'danger' : 'muted'; ?>">
                                                    <?php echo date('d/m/Y', strtotime($tia['data_teste'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-tia-edit/<?php echo $tia['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                            <p>Nenhum teste pendente</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- DEPARTAMENTOS ATIVOS -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-building me-2"></i>Departamentos com Atividades TIA
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($this->data['departamentos_ativos'])): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Departamento</th>
                                        <th>Total de Testes</th>
                                        <th>Em Andamento</th>
                                        <th>Concluídos</th>
                                        <th>Aprovados</th>
                                        <th>Progresso</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($this->data['departamentos_ativos'] as $dept): ?>
                                        <?php 
                                        $total = $dept['total_tias'];
                                        $concluidos = $dept['concluido'] + $dept['aprovado'];
                                        $progresso = $total > 0 ? round(($concluidos / $total) * 100) : 0;
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($dept['departamento']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo $total; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning"><?php echo $dept['em_andamento']; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success"><?php echo $dept['concluido']; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $dept['aprovado']; ?></span>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-success" 
                                                         role="progressbar" 
                                                         style="width: <?php echo $progresso; ?>%"
                                                         aria-valuenow="<?php echo $progresso; ?>" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                        <?php echo $progresso; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-building fa-3x mb-3"></i>
                            <p>Nenhum departamento com atividades TIA encontrado</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- AÇÕES RÁPIDAS -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-bolt me-2"></i>Ações Rápidas
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-tia-create" class="btn btn-success w-100">
                                <i class="fas fa-plus me-2"></i>Novo Teste TIA
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-tia" class="btn btn-primary w-100">
                                <i class="fas fa-list me-2"></i>Listar Testes
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-tia-export-pdf-list" class="btn btn-danger w-100">
                                <i class="fas fa-file-pdf me-2"></i>Exportar Lista PDF
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-workflow-report" class="btn btn-info w-100">
                                <i class="fas fa-chart-bar me-2"></i>Relatório Workflow
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dados para os gráficos
    const riscoDepartamentoData = <?php echo json_encode($this->data['risco_por_departamento']); ?>;
    const statusMesData = <?php echo json_encode($this->data['status_por_mes']); ?>;

    // Gráfico de Risco por Departamento
    if (riscoDepartamentoData.length > 0) {
        const ctx = document.getElementById('riscoDepartamentoChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: riscoDepartamentoData.map(item => item.departamento),
                datasets: [
                    {
                        label: 'Baixo Risco',
                        data: riscoDepartamentoData.map(item => item.baixo_risco),
                        backgroundColor: '#28a745',
                        borderColor: '#28a745',
                        borderWidth: 1
                    },
                    {
                        label: 'Médio Risco',
                        data: riscoDepartamentoData.map(item => item.medio_risco),
                        backgroundColor: '#ffc107',
                        borderColor: '#ffc107',
                        borderWidth: 1
                    },
                    {
                        label: 'Alto Risco',
                        data: riscoDepartamentoData.map(item => item.alto_risco),
                        backgroundColor: '#fd7e14',
                        borderColor: '#fd7e14',
                        borderWidth: 1
                    },
                    {
                        label: 'Necessita AIPD',
                        data: riscoDepartamentoData.map(item => item.necessita_aipd),
                        backgroundColor: '#dc3545',
                        borderColor: '#dc3545',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    // Gráfico de Status por Mês
    if (statusMesData.length > 0) {
        const ctx2 = document.getElementById('statusMesChart').getContext('2d');
        new Chart(ctx2, {
            type: 'line',
            data: {
                labels: statusMesData.map(item => {
                    const [year, month] = item.mes.split('-');
                    const monthNames = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 
                                      'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
                    return `${monthNames[parseInt(month) - 1]}/${year}`;
                }),
                datasets: [
                    {
                        label: 'Em Andamento',
                        data: statusMesData.map(item => item.em_andamento),
                        borderColor: '#ffc107',
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
                        tension: 0.1
                    },
                    {
                        label: 'Concluído',
                        data: statusMesData.map(item => item.concluido),
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        tension: 0.1
                    },
                    {
                        label: 'Aprovado',
                        data: statusMesData.map(item => item.aprovado),
                        borderColor: '#17a2b8',
                        backgroundColor: 'rgba(23, 162, 184, 0.1)',
                        tension: 0.1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
});
</script>
