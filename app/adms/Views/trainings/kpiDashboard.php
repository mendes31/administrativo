<?php
use App\adms\Helpers\FormatHelper;
use App\adms\Models\Repository\TrainingUsersRepository;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Dashboard de KPIs - Treinamentos</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Treinamentos</li>
            <li class="breadcrumb-item">KPIs</li>
        </ol>
    </div>

    <?php $dashboard = $this->data['dashboard'] ?? []; ?>
    <?php $summary = $dashboard['summary'] ?? []; ?>
    <?php $statusCounts = $dashboard['statusCounts'] ?? []; ?>
    <?php $monthly = $dashboard['monthlyRealizations'] ?? []; ?>

    <?php
    // Fallback de segurança: se por algum motivo o controller não
    // tiver preenchido o array $dashboard, buscar direto do repositório.
    if (empty($summary) || empty($statusCounts)) {
        $repo = new TrainingUsersRepository();
        $summary      = $repo->getSummaryAll();
        $statusCounts = $repo->getStatusCounts();
        $monthly      = $repo->getMonthlyRealizations();
        
        // Atualizar o array $dashboard para que os gráficos recebam os dados
        $dashboard['summary'] = $summary;
        $dashboard['statusCounts'] = $statusCounts;
        $dashboard['monthlyRealizations'] = $monthly;
        
        // Carregar também os outros dados que podem estar faltando
        if (empty($dashboard['topPendingUsers'])) {
            $dashboard['topPendingUsers'] = $repo->getTopPendingUsers();
        }
        if (empty($dashboard['topCriticalTrainings'])) {
            $dashboard['topCriticalTrainings'] = $repo->getTopCriticalTrainings();
        }
    }
    ?>

    <!-- Cards de Resumo (alinhados com Status de Treinamentos por Colaborador) -->
    <div class="row mb-3">
        <div class="col-md-2 mb-3">
            <div class="card border-primary shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <h4 class="text-primary mb-1">
                        <i class="fas fa-users"></i>
                        <?= number_format($summary['todos'] ?? 0) ?>
                    </h4>
                    <div class="small text-muted">Todos</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card border-success shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <h4 class="text-success mb-1">
                        <i class="fas fa-check-circle"></i>
                        <?= number_format($summary['dentro_do_prazo'] ?? 0) ?>
                    </h4>
                    <div class="small text-muted">Dentro do Prazo</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card border-warning shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <h4 class="text-warning mb-1">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= number_format($summary['proximo_vencimento'] ?? 0) ?>
                    </h4>
                    <div class="small text-muted">Próximo do Vencimento</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card border-danger shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <h4 class="text-danger mb-1">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?= number_format($summary['vencido'] ?? 0) ?>
                    </h4>
                    <div class="small text-muted">Vencido</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card border-info shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <h4 class="text-info mb-1">
                        <i class="fas fa-calendar-alt"></i>
                        <?= number_format($summary['agendado'] ?? 0) ?>
                    </h4>
                    <div class="small text-muted">Agendado</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card border-secondary shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <h4 class="text-secondary mb-1">
                        <i class="fas fa-check"></i>
                        <?= number_format($summary['concluido'] ?? 0) ?>
                    </h4>
                    <div class="small text-muted">Concluído</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row mb-3">
        <!-- Gráfico de Status (Pizza) -->
        <div class="col-xl-6 col-lg-6 mb-3">
            <div class="card shadow mb-3 h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-pie me-2"></i>Distribuição por Status
                    </h6>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center" style="min-height: 150px;">
                    <canvas id="statusChart" style="max-width: 100%; height: 140px;"></canvas>
                </div>
            </div>
        </div>

        <!-- Gráfico de Realizações Mensais (Barras) -->
        <div class="col-xl-6 col-lg-6 mb-3">
            <div class="card shadow mb-3 h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-bar me-2"></i>Realizações por Mês
                    </h6>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center" style="min-height: 150px;">
                    <canvas id="monthlyChart" style="max-width: 100%; height: 140px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabelas de Dados -->
    <div class="row mb-4">
        <!-- Top Usuários com Pendências -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-users me-2"></i>Top 5 - Usuários com Mais Pendências
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0" style="table-layout: fixed;">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th class="col-nome">Colaborador</th>
                                    <th>Pendências</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $topPendingUsers = $dashboard['topPendingUsers'] ?? []; ?>
                                <?php if (!empty($topPendingUsers)): ?>
                                    <?php foreach ($topPendingUsers as $user): ?>
                                        <tr>
                                            <td><?= $user['user_id'] ?? $user['id'] ?? '-' ?></td>
                                            <td class="col-nome"><?= htmlspecialchars($user['name']) ?></td>
                                            <td>
                                                <span class="badge bg-warning text-dark">
                                                    <?= $user['pendentes'] ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">
                                            Nenhum usuário com pendências encontrado.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Treinamentos Críticos -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-exclamation-triangle me-2"></i>Top 5 - Treinamentos Críticos
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0" style="table-layout: fixed;">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th class="col-nome">Treinamento</th>
                                    <th>Pendentes</th>
                                    <th>Vencidos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $topCriticalTrainings = $dashboard['topCriticalTrainings'] ?? []; ?>
                                <?php if (!empty($topCriticalTrainings)): ?>
                                    <?php foreach ($topCriticalTrainings as $training): ?>
                                        <tr>
                                            <td><?= $training['training_id'] ?? $training['id'] ?? '-' ?></td>
                                            <td class="col-nome"><?= htmlspecialchars($training['training_name']) ?></td>
                                            <td>
                                                <span class="badge bg-warning text-dark">
                                                    <?= $training['pendentes'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-danger">
                                                    <?= $training['vencidos'] ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">
                                            Nenhum treinamento crítico encontrado.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Estatísticas por Departamento -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-building me-2"></i>Estatísticas por Departamento
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0" style="table-layout: fixed;">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th class="col-nome">Departamento</th>
                                    <th>Total</th>
                                    <th>Concluídos</th>
                                    <th>A Fazer (Dentro do Prazo)</th>
                                    <th>Pendentes (Próx. Vencimento)</th>
                                    <th>Vencidos</th>
                                    <th>Agendados</th>
                                    <th>% Conclusão</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $departmentStats = $dashboard['departmentStats'] ?? []; ?>
                                <?php if (!empty($departmentStats)): ?>
                                    <?php foreach ($departmentStats as $dept): ?>
                                        <?php 
                                        $total = $dept['total_vinculos'];
                                        $concluidos = $dept['concluidos'];
                                        $percentual = $total > 0 ? round(($concluidos / $total) * 100, 1) : 0;
                                        ?>
                                        <tr>
                                            <td><?= $dept['department_id'] ?? $dept['id'] ?? '-' ?></td>
                                            <td class="col-nome"><?= htmlspecialchars($dept['department_name']) ?></td>
                                            <td><?= $total ?></td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?= $concluidos ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">
                                                    <?= $dept['em_dia'] ?? 0 ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning text-dark">
                                                    <?= $dept['pendentes'] ?? 0 ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-danger">
                                                    <?= $dept['vencidos'] ?? 0 ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info text-dark">
                                                    <?= $dept['agendados'] ?? 0 ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-secondary" 
                                                         role="progressbar" 
                                                         style="width: <?= $percentual ?>%"
                                                         aria-valuenow="<?= $percentual ?>" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                        <?= $percentual ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">
                                            Nenhuma estatística por departamento encontrada.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Últimas Aplicações -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-history me-2"></i>Últimas Aplicações
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0" style="table-layout: fixed;">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th class="col-nome">Colaborador</th>
                                    <th>Treinamento</th>
                                    <th>Data Realização</th>
                                    <th>Data Agendada</th>
                                    <th>Status</th>
                                    <th>Nota</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $recentApplications = $dashboard['recentApplications'] ?? []; ?>
                                <?php if (!empty($recentApplications)): ?>
                                    <?php foreach ($recentApplications as $app): ?>
                                        <tr>
                                            <td><?= $app['id'] ?></td>
                                            <td class="col-nome"><?= htmlspecialchars($app['user_name']) ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($app['training_name']) ?></strong>
                                                <br><small class="text-muted"><?= htmlspecialchars($app['training_code']) ?></small>
                                            </td>
                                            <td>
                                                <?php if ($app['data_realizacao']): ?>
                                                    <?= FormatHelper::formatDate($app['data_realizacao']) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($app['data_agendada']): ?>
                                                    <?= FormatHelper::formatDate($app['data_agendada']) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($app['data_realizacao']): ?>
                                                    <span class="badge bg-secondary">Realizado</span>
                                                <?php elseif ($app['data_agendada']): ?>
                                                    <span class="badge bg-info text-dark">Agendado</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Pendente</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($app['nota']): ?>
                                                    <?= htmlspecialchars($app['nota']) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            Nenhuma aplicação recente encontrada.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts para os gráficos -->
<!-- Chart.js com fallback -->
<script src="<?php echo $_ENV['URL_ADM']; ?>public/adms/vendor/chartjs/chart.umd.min.js" 
        onerror="console.error('Erro ao carregar Chart.js local'); loadChartJsFallback();"></script>
<script>
// Fallback para Chart.js se CDN falhar
function loadChartJsFallback() {
    console.warn('Tentando carregar Chart.js de fallback...');
    const script = document.createElement('script');
    script.src = '<?php echo $_ENV['URL_ADM']; ?>public/adms/vendor/chartjs/chart.umd.min.js';
    script.onerror = function() {
        console.error('Erro ao carregar Chart.js local (fallback). Verifique o arquivo local.');
        document.getElementById('statusChart').parentElement.innerHTML = '<div class="alert alert-warning">Erro ao carregar biblioteca de gráficos local.</div>';
        document.getElementById('monthlyChart').parentElement.innerHTML = '<div class="alert alert-warning">Erro ao carregar biblioteca de gráficos local.</div>';
    };
    document.head.appendChild(script);
}

document.addEventListener('DOMContentLoaded', function() {
    // Aguardar Chart.js carregar (com timeout)
    let chartJsReady = false;
    let attempts = 0;
    const maxAttempts = 50; // 5 segundos
    
    function checkChartJs() {
        attempts++;
        if (typeof Chart !== 'undefined') {
            chartJsReady = true;
            initializeCharts();
        } else if (attempts < maxAttempts) {
            setTimeout(checkChartJs, 100);
        } else {
            console.error('Chart.js não carregou após 5 segundos. Tentando fallback...');
            loadChartJsFallback();
            setTimeout(function() {
                if (typeof Chart !== 'undefined') {
                    initializeCharts();
                } else {
                    console.error('Chart.js não está disponível. Gráficos não serão renderizados.');
                }
            }, 1000);
        }
    }
    
    function initializeCharts() {
        // Verificar se Chart.js está disponível
        if (typeof Chart === 'undefined') {
            console.error('Chart.js não está disponível!');
            return;
        }
        
        console.log('Chart.js carregado, inicializando gráficos...');
        
        // Dados para os gráficos (garantir que sempre tenham dados)
        const statusData = <?= json_encode($statusCounts ?? []) ?>;
        const monthlyData = <?= json_encode($monthly ?? []) ?>;
        
        // Debug: verificar se os dados estão chegando
        console.log('Status Data:', statusData);
        console.log('Monthly Data:', monthlyData);
        
        // Verificar se os dados são válidos
        if (!statusData || typeof statusData !== 'object') {
            console.error('statusData inválido:', statusData);
            return;
        }
        
        if (!monthlyData || typeof monthlyData !== 'object') {
            console.warn('monthlyData inválido ou vazio:', monthlyData);
        }

        // Gráfico de Status (Pizza)
        const statusCtx = document.getElementById('statusChart');
        if (statusCtx) {
            try {
                const statusChart = new Chart(statusCtx.getContext('2d'), {
                    type: 'pie',
                    data: {
                        labels: [
                            'Dentro do Prazo (A fazer)',
                            'Próx. do Vencimento',
                            'Vencido',
                            'Agendado',
                            'Concluído'
                        ],
                        datasets: [{
                            data: [
                                statusData.em_dia || 0,
                                statusData.proximo_vencimento || 0,
                                statusData.vencido || 0,
                                statusData.agendado || 0,
                                statusData.concluido || 0
                            ],
                            backgroundColor: [
                                '#198754', // em dia
                                '#ffc107', // próximo vencimento
                                '#dc3545', // vencido
                                '#0d6efd', // agendado
                                '#6c757d'  // concluído
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        aspectRatio: 1.2,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
                console.log('Gráfico de Status criado com sucesso!');
            } catch (error) {
                console.error('Erro ao criar gráfico de Status:', error);
            }
        } else {
            console.error('Elemento statusChart não encontrado!');
        }

        // Gráfico de Realizações Mensais (Barras)
        const monthlyCtx = document.getElementById('monthlyChart');
        if (monthlyCtx) {
            try {
                const months = Object.keys(monthlyData || {});
                const values = Object.values(monthlyData || {});
                
                // Se não houver dados, criar um array vazio para evitar erro
                if (months.length === 0) {
                    console.warn('Nenhum dado mensal encontrado para o gráfico');
                }
                
                const monthlyChart = new Chart(monthlyCtx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: months.length > 0 ? months.map(month => {
                            const [year, monthNum] = month.split('-');
                            const monthNames = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 
                                              'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
                            return `${monthNames[parseInt(monthNum) - 1]}/${year.slice(2)}`;
                        }) : ['Sem dados'],
                        datasets: [{
                            label: 'Realizações',
                            data: values.length > 0 ? values : [0],
                            backgroundColor: '#007bff',
                            borderColor: '#0056b3',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        aspectRatio: 1.2,
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
                                display: false
                            }
                        }
                    }
                });
                console.log('Gráfico Mensal criado com sucesso!');
            } catch (error) {
                console.error('Erro ao criar gráfico Mensal:', error);
            }
        } else {
            console.error('Elemento monthlyChart não encontrado!');
        }
    }
    
    // Iniciar verificação do Chart.js
    checkChartJs();
});
</script> 