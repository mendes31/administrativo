<?php
$stats = $this->data['stats'] ?? [
    'total_candidatos' => 0,
    'por_status'       => [],
    'por_origem'       => [],
    'lgpd_resumo'      => [],
];

// Preparar dados para gráficos
$statusLabels = [];
$statusValues = [];
$statusColors = [
    'recebido'      => '#17a2b8', // info
    'em_entrevista' => '#ffc107', // warning
    'reprovado'     => '#dc3545', // danger
    'banco_talentos' => '#007bff', // primary
    'contratado'    => '#28a745', // success
    'anonimizado'   => '#6c757d', // secondary
];

foreach ($stats['por_status'] ?? [] as $status => $total) {
    $statusLabels[] = ucfirst(str_replace('_', ' ', $status));
    $statusValues[] = (int)$total;
}

$origemLabels = [];
$origemValues = [];
$origemColors = [
    'manual'              => '#007bff',
    'email'               => '#28a745',
    'whatsapp'            => '#25d366',
    'form_trabalhe_conosco' => '#17a2b8',
    'linkedin'            => '#0077b5',
    'indeed'              => '#2164f3',
];

foreach ($stats['por_origem'] ?? [] as $origem => $total) {
    $origemLabels[] = ucfirst(str_replace('_', ' ', $origem));
    $origemValues[] = (int)$total;
}
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Dashboard de Recrutamento / Currículos</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">RH</li>
            <li class="breadcrumb-item">Recrutamento</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <!-- Cards de Resumo -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-primary shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <h4 class="text-primary mb-1">
                        <i class="fas fa-users"></i>
                        <?= number_format($stats['total_candidatos'] ?? 0) ?>
                    </h4>
                    <div class="small text-muted">Total de Candidatos</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-info shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <h4 class="text-info mb-1">
                        <i class="fas fa-user-check"></i>
                        <?= number_format($stats['por_status']['recebido'] ?? 0) ?>
                    </h4>
                    <div class="small text-muted">Recebidos</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-warning shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <h4 class="text-warning mb-1">
                        <i class="fas fa-user-clock"></i>
                        <?= number_format($stats['por_status']['em_entrevista'] ?? 0) ?>
                    </h4>
                    <div class="small text-muted">Em Entrevista</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-success shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <h4 class="text-success mb-1">
                        <i class="fas fa-user-tie"></i>
                        <?= number_format($stats['por_status']['contratado'] ?? 0) ?>
                    </h4>
                    <div class="small text-muted">Contratados</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie text-primary me-2"></i>
                        Distribuição por Status do Processo
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($statusValues) && array_sum($statusValues) > 0): ?>
                        <canvas id="chartStatus" height="200"></canvas>
                    <?php else: ?>
                        <p class="text-muted text-center py-4 mb-0">Nenhum dado disponível para exibir.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie text-info me-2"></i>
                        Distribuição por Origem
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($origemValues) && array_sum($origemValues) > 0): ?>
                        <canvas id="chartOrigem" height="200"></canvas>
                    <?php else: ?>
                        <p class="text-muted text-center py-4 mb-0">Nenhum dado disponível para exibir.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabelas Detalhadas -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-list text-primary me-2"></i>
                        Detalhamento por Status
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($stats['por_status'])): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th class="text-end">Quantidade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats['por_status'] as $status => $total): ?>
                                        <tr>
                                            <td>
                                                <?php
                                                $badgeClass = 'badge bg-secondary';
                                                if ($status === 'recebido') $badgeClass = 'badge bg-info';
                                                elseif ($status === 'em_entrevista') $badgeClass = 'badge bg-warning text-dark';
                                                elseif ($status === 'reprovado') $badgeClass = 'badge bg-danger';
                                                elseif ($status === 'banco_talentos') $badgeClass = 'badge bg-primary';
                                                elseif ($status === 'contratado') $badgeClass = 'badge bg-success';
                                                elseif ($status === 'anonimizado') $badgeClass = 'badge bg-dark';
                                                ?>
                                                <span class="<?= $badgeClass ?>">
                                                    <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $status))) ?>
                                                </span>
                                            </td>
                                            <td class="text-end fw-semibold"><?= number_format((int)$total) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-3 mb-0">Nenhum candidato cadastrado ainda.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-list text-info me-2"></i>
                        Detalhamento por Origem
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($stats['por_origem'])): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Origem</th>
                                        <th class="text-end">Quantidade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats['por_origem'] as $origem => $total): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $origem))) ?></td>
                                            <td class="text-end fw-semibold"><?= number_format((int)$total) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-3 mb-0">Nenhum registro de origem encontrado.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Resumo LGPD -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-shield-alt text-success me-2"></i>
                        Resumo LGPD
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($stats['lgpd_resumo'])): ?>
                        <div class="row">
                            <?php foreach ($stats['lgpd_resumo'] as $lgpdStatus => $total): ?>
                                <div class="col-md-3 col-sm-6 mb-2">
                                    <div class="d-flex justify-content-between align-items-center p-2 border rounded">
                                        <span class="fw-semibold">
                                            <?php
                                            $lgpdBadgeClass = 'badge bg-secondary';
                                            if ($lgpdStatus === 'Ativo') $lgpdBadgeClass = 'badge bg-success';
                                            elseif ($lgpdStatus === 'Vencido') $lgpdBadgeClass = 'badge bg-danger';
                                            elseif ($lgpdStatus === 'Anonimizado') $lgpdBadgeClass = 'badge bg-dark';
                                            ?>
                                            <span class="<?= $lgpdBadgeClass ?>">
                                                <?= htmlspecialchars($lgpdStatus) ?>
                                            </span>
                                        </span>
                                        <span class="h5 mb-0"><?= number_format((int)$total) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-3 mb-0">Nenhuma informação de LGPD registrada ainda.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Verificar se Chart.js carregou
    if (typeof Chart === 'undefined') {
        console.error('Chart.js não foi carregado. Tentando carregar novamente...');
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';
        script.onload = function() {
            initCharts();
        };
        document.head.appendChild(script);
        return;
    }
    initCharts();
});

function initCharts() {
    // Dados do PHP
    const statusLabels = <?= json_encode($statusLabels) ?>;
    const statusValues = <?= json_encode($statusValues) ?>;
    const statusColors = <?= json_encode(array_values($statusColors)) ?>;
    
    const origemLabels = <?= json_encode($origemLabels) ?>;
    const origemValues = <?= json_encode($origemValues) ?>;
    const origemColors = <?= json_encode(array_values($origemColors)) ?>;

    // Gráfico de Status
    const ctxStatus = document.getElementById('chartStatus');
    if (ctxStatus && statusValues.length > 0 && statusValues.reduce((a, b) => a + b, 0) > 0) {
        new Chart(ctxStatus, {
            type: 'pie',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusValues,
                    backgroundColor: statusColors.slice(0, statusValues.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return label + ': ' + value + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // Gráfico de Origem
    const ctxOrigem = document.getElementById('chartOrigem');
    if (ctxOrigem && origemValues.length > 0 && origemValues.reduce((a, b) => a + b, 0) > 0) {
        new Chart(ctxOrigem, {
            type: 'pie',
            data: {
                labels: origemLabels,
                datasets: [{
                    data: origemValues,
                    backgroundColor: origemColors.slice(0, origemValues.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return label + ': ' + value + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }
}
</script>
