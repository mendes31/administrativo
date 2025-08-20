<?php
$config = $this->data['dashboard'] ?? [];
$statusCounts = $config['status_counts'] ?? [];
$monthly = $config['monthly_realizations'] ?? [];
$topUsers = $config['top_pending_users'] ?? [];
$topTrainings = $config['top_critical_trainings'] ?? [];
?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= number_format(($statusCounts['em_dia'] ?? 0) + ($statusCounts['concluido'] ?? 0)) ?></h4>
                            <div>Em dia / Concluídos</div>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= number_format($statusCounts['proximo_vencimento'] ?? 0) ?></h4>
                            <div>Próx. do Vencimento</div>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= number_format($statusCounts['vencido'] ?? 0) ?></h4>
                            <div>Vencidos</div>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-times-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-secondary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= number_format($statusCounts['pendente'] ?? 0) ?></h4>
                            <div>Pendentes</div>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header"><strong>Distribuição por Status</strong></div>
                <div class="card-body">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header"><strong>Realizações por Mês (últimos 12 meses)</strong></div>
                <div class="card-body">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header"><strong>Top 5 Colaboradores com Mais Pendências</strong></div>
                <div class="card-body">
                    <ul class="list-group">
                        <?php foreach ($topUsers as $user): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?= htmlspecialchars($user['name']) ?>
                                <span class="badge bg-danger rounded-pill"><?= $user['pendentes'] ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header"><strong>Top 5 Treinamentos Críticos</strong></div>
                <div class="card-body">
                    <ul class="list-group">
                        <?php foreach ($topTrainings as $t): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?= htmlspecialchars($t['training_name']) ?>
                                <span class="badge bg-warning rounded-pill">Pendentes: <?= $t['pendentes'] ?> | Vencidos: <?= $t['vencidos'] ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Gráfico de status
    const statusData = {
        labels: ['Em dia/Concluído', 'Próx. do Vencimento', 'Vencido', 'Pendente'],
        datasets: [{
            data: [
                <?= ($statusCounts['em_dia'] ?? 0) + ($statusCounts['concluido'] ?? 0) ?>,
                <?= $statusCounts['proximo_vencimento'] ?? 0 ?>,
                <?= $statusCounts['vencido'] ?? 0 ?>,
                <?= $statusCounts['pendente'] ?? 0 ?>
            ],
            backgroundColor: ['#0d6efd', '#ffc107', '#dc3545', '#6c757d'],
        }]
    };
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: statusData,
        options: {responsive: true, plugins: {legend: {position: 'bottom'}}}
    });
    // Gráfico de realizações por mês
    const monthlyLabels = <?= json_encode(array_keys($monthly)) ?>;
    const monthlyData = <?= json_encode(array_values($monthly)) ?>;
    new Chart(document.getElementById('monthlyChart'), {
        type: 'bar',
        data: {
            labels: monthlyLabels,
            datasets: [{
                label: 'Realizações',
                data: monthlyData,
                backgroundColor: '#0d6efd',
            }]
        },
        options: {responsive: true, plugins: {legend: {display: false}}}
    });
</script> 