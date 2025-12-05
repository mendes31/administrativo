<?php
use App\adms\Helpers\FormatHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Dashboard de Desempenho</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Gestão de Pessoas</li>
            <li class="breadcrumb-item">Dashboard de Desempenho</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <!-- Cards de Estatísticas -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-primary shadow">
                <div class="card-body text-center">
                    <i class="fas fa-clipboard-check fa-3x text-primary mb-3"></i>
                    <h3 class="mb-0"><?= $this->data['total_reviews'] ?? 0 ?></h3>
                    <p class="text-muted mb-0">Total de Avaliações</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success shadow">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h3 class="mb-0"><?= $this->data['completed_reviews'] ?? 0 ?></h3>
                    <p class="text-muted mb-0">Concluídas</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning shadow">
                <div class="card-body text-center">
                    <i class="fas fa-clock fa-3x text-warning mb-3"></i>
                    <h3 class="mb-0"><?= $this->data['pending_reviews'] ?? 0 ?></h3>
                    <p class="text-muted mb-0">Pendentes</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info shadow">
                <div class="card-body text-center">
                    <i class="fas fa-star fa-3x text-info mb-3"></i>
                    <h3 class="mb-0"><?= $this->data['total_competencies'] ?? 0 ?></h3>
                    <p class="text-muted mb-0">Competências</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row g-4 mb-4">
        <!-- Gráfico de Avaliações por Tipo -->
        <div class="col-md-6">
            <div class="card border-light shadow">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Avaliações por Tipo</h6>
                </div>
                <div class="card-body">
                    <canvas id="reviewsByTypeChart" height="250"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Gráfico de Avaliações por Status -->
        <div class="col-md-6">
            <div class="card border-light shadow">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="fas fa-chart-doughnut me-2"></i>Avaliações por Status</h6>
                </div>
                <div class="card-body">
                    <canvas id="reviewsByStatusChart" height="250"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Gráfico de Avaliações Mensais -->
        <div class="col-md-12">
            <div class="card border-light shadow">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Avaliações Realizadas por Mês (12 meses)</h6>
                </div>
                <div class="card-body">
                    <canvas id="monthlyReviewsChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Card de Média de Notas -->
    <div class="row g-4 mb-4">
        <div class="col-md-12">
            <div class="card border-warning shadow">
                <div class="card-body text-center">
                    <h5 class="mb-3">Média Geral de Desempenho</h5>
                    <h1 class="display-4 text-warning mb-0">
                        <?= number_format($this->data['average_score'] ?? 0, 1) ?>/10
                    </h1>
                    <p class="text-muted mt-2">
                        Baseado em <?= $this->data['completed_reviews'] ?? 0 ?> avaliações concluídas
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Últimas Avaliações -->
    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span><i class="fas fa-list me-2"></i>Últimas Avaliações</span>
            <span class="ms-auto">
                <?php if (in_array('ListPerformanceReviews', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-performance-reviews" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye me-1"></i>Ver Todas
                    </a>
                <?php } ?>
            </span>
        </div>
        <div class="card-body">
            <?php if (!empty($this->data['recent_reviews'])): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Colaborador</th>
                                <th>Tipo</th>
                                <th>Status</th>
                                <th>Nota</th>
                                <th>Data</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->data['recent_reviews'] as $review): ?>
                                <tr>
                                    <td><?= htmlspecialchars($review['employee_name'] ?? '') ?></td>
                                    <td><span class="badge bg-info"><?= htmlspecialchars($review['review_type'] ?? '') ?>°</span></td>
                                    <td>
                                        <?php
                                        $statusClass = match($review['status']) {
                                            'draft' => 'secondary',
                                            'pending' => 'warning',
                                            'in_progress' => 'info',
                                            'completed' => 'success',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?= $statusClass ?>"><?= htmlspecialchars($review['status'] ?? '') ?></span>
                                    </td>
                                    <td>
                                        <?php if ($review['overall_score']): ?>
                                            <span class="badge bg-primary"><?= number_format((float)$review['overall_score'], 1) ?>/10</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= FormatHelper::formatDate($review['review_date'] ?? '') ?></td>
                                    <td class="text-center">
                                        <?php if (in_array('ViewPerformanceReview', $this->data['buttonPermission'] ?? [])) { ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>view-performance-review/<?= $review['id'] ?>" 
                                               class="btn btn-sm btn-info" title="Visualizar">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>Nenhuma avaliação encontrada.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chartColors = {
        primary: '#0d6efd',
        success: '#198754',
        info: '#0dcaf0',
        warning: '#ffc107',
        danger: '#dc3545',
        purple: '#6f42c1'
    };
    
    // ===== GRÁFICO DE AVALIAÇÕES POR TIPO =====
    const reviewsByTypeData = <?= json_encode($this->data['reviews_by_type'] ?? []) ?>;
    const reviewsByTypeCtx = document.getElementById('reviewsByTypeChart');
    if (reviewsByTypeCtx && Object.keys(reviewsByTypeData).length > 0) {
        const typeLabels = Object.keys(reviewsByTypeData).map(type => type + '°');
        const typeValues = Object.values(reviewsByTypeData);
        const typeColors = [chartColors.primary, chartColors.success, chartColors.info, chartColors.warning, chartColors.danger];
        
        new Chart(reviewsByTypeCtx, {
            type: 'pie',
            data: {
                labels: typeLabels,
                datasets: [{
                    data: typeValues,
                    backgroundColor: typeColors.slice(0, typeLabels.length),
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
    }
    
    // ===== GRÁFICO DE AVALIAÇÕES POR STATUS =====
    const reviewsByStatusData = <?= json_encode($this->data['reviews_by_status'] ?? []) ?>;
    const reviewsByStatusCtx = document.getElementById('reviewsByStatusChart');
    if (reviewsByStatusCtx && Object.keys(reviewsByStatusData).length > 0) {
        const statusLabels = Object.keys(reviewsByStatusData).map(status => {
            const labels = {
                'draft': 'Rascunho',
                'pending': 'Pendente',
                'in_progress': 'Em Andamento',
                'completed': 'Concluída'
            };
            return labels[status] || status;
        });
        const statusValues = Object.values(reviewsByStatusData);
        const statusColors = {
            'draft': chartColors.danger,
            'pending': chartColors.warning,
            'in_progress': chartColors.info,
            'completed': chartColors.success
        };
        const bgColors = Object.keys(reviewsByStatusData).map(status => statusColors[status] || chartColors.primary);
        
        new Chart(reviewsByStatusCtx, {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusValues,
                    backgroundColor: bgColors,
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
    }
    
    // ===== GRÁFICO DE AVALIAÇÕES MENSAIS =====
    const monthlyReviewsData = <?= json_encode($this->data['monthly_reviews'] ?? []) ?>;
    const monthlyReviewsCtx = document.getElementById('monthlyReviewsChart');
    if (monthlyReviewsCtx && Object.keys(monthlyReviewsData).length > 0) {
        new Chart(monthlyReviewsCtx, {
            type: 'bar',
            data: {
                labels: Object.keys(monthlyReviewsData),
                datasets: [{
                    label: 'Avaliações Realizadas',
                    data: Object.values(monthlyReviewsData),
                    backgroundColor: chartColors.info,
                    borderColor: chartColors.info,
                    borderWidth: 1
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
    }
});
</script>

