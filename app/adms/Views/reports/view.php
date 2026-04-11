<?php
$report = $this->data['report'] ?? null;
$result = $this->data['result'] ?? null;
$connectionLabels = [
    'local' => 'Banco Local',
    'sap_b1' => 'SAP B1 HANA',
    'sap_api' => 'SAP API'
];
$connectionLabel = $connectionLabels[$result['connection_type'] ?? ''] ?? ($result['connection_type'] ?? 'Desconhecido');
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-chart-line"></i> <?= htmlspecialchars($report['name']) ?>
                    </h1>
                    <?php if (!empty($report['description'])): ?>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($report['description'])) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <a href="<?= $_ENV['URL_ADM'] ?>list-dynamic-reports" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                    <?php if (!empty($report['id'])): ?>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-warning dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-sync"></i> Atualizar consulta
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="<?= $_ENV['URL_ADM'] ?>view-dynamic-report/<?= $report['id'] ?>?refresh=1">
                                        <i class="fas fa-sync-alt"></i> Atualização Completa
                                        <small class="d-block text-muted">Busca todos os dados novamente</small>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?= $_ENV['URL_ADM'] ?>view-dynamic-report/<?= $report['id'] ?>?refresh=1&incremental=1">
                                        <i class="fas fa-plus-circle"></i> Busca Incremental
                                        <small class="d-block text-muted">Busca apenas novos registros (mais rápido)</small>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <a href="<?= $_ENV['URL_ADM'] ?>create-dashboard/<?= $report['id'] ?>" class="btn btn-success">
                        <i class="fas fa-chart-bar"></i> Criar Dashboard
                    </a>
                    <?php if ($report['created_by'] == ($_SESSION['user_id'] ?? 0)): ?>
                        <a href="<?= $_ENV['URL_ADM'] ?>dynamic-report-builder?id=<?= $report['id'] ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Informações do Relatório -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-light">
                <div class="card-body">
                    <small class="text-muted">Fonte de Dados</small>
                    <p class="mb-0 fw-bold"><?= htmlspecialchars($report['data_source'] ?? 'SQL Personalizado') ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-light">
                <div class="card-body">
                    <small class="text-muted">Tipo</small>
                    <p class="mb-0 fw-bold"><?= ucfirst(str_replace('_', ' ', $report['visualization_type'])) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-light">
                <div class="card-body">
                    <small class="text-muted">Criado por</small>
                    <p class="mb-0 fw-bold"><?= htmlspecialchars($report['creator_name'] ?? 'Desconhecido') ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-light">
                <div class="card-body">
                    <small class="text-muted">Última atualização</small>
                    <p class="mb-0 fw-bold"><?= date('d/m/Y H:i', strtotime($report['updated_at'] ?? $report['created_at'])) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Resultado do Relatório -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar"></i> Resultado
                    </h5>
                    <?php if ($result['success']): ?>
                        <div class="d-flex flex-wrap gap-1 justify-content-end">
                            <?php
                            $reportIdForExport = !empty($report['id']) ? (int)$report['id'] : 0;
                            $perms = $this->data['buttonPermission'] ?? [];
                            $canExportPdf = $reportIdForExport > 0
                                && in_array('ExportDynamicReportPdf', $perms, true);
                            $canExportExcel = !empty($result['data'])
                                && in_array('ExportDynamicReportExcel', $perms, true)
                                && $reportIdForExport > 0;
                            if ($canExportPdf):
                                $exportPdfUrl = $_ENV['URL_ADM'] . 'export-dynamic-report-pdf/' . $reportIdForExport;
                            ?>
                                <a href="<?= htmlspecialchars($exportPdfUrl) ?>" class="btn btn-sm btn-danger">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </a>
                            <?php endif; ?>
                            <?php if ($canExportExcel):
                                $exportExcelUrl = $_ENV['URL_ADM'] . 'export-dynamic-report-excel/' . $reportIdForExport;
                            ?>
                                <a href="<?= htmlspecialchars($exportExcelUrl) ?>" class="btn btn-sm btn-success">
                                    <i class="fas fa-file-excel"></i> Excel
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (!$result['success']): ?>
                        <div class="alert alert-danger">
                            <strong><i class="fas fa-exclamation-triangle"></i> Erro ao executar relatório:</strong><br>
                            <div style="white-space: pre-wrap; margin-top: 10px;"><?= htmlspecialchars($result['error']) ?></div>
                        </div>
                    <?php elseif (empty($result['data'])): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle"></i> Nenhum dado encontrado
                        </div>
                    <?php else: ?>
                        <!-- Renderizar dados -->
                        <?php if ($report['visualization_type'] === 'table'): ?>
                            <?php
                            $headers = !empty($result['data']) ? array_keys($result['data'][0]) : [];
                            $totalRows = $result['total_rows'] ?? count($result['data'] ?? []);
                            $currentPage = $this->data['pagination']['current_page'] ?? 1;
                            $perPage = isset($this->data['pagination']['per_page']) ? (int)$this->data['pagination']['per_page'] : 25;
                            ?>
                            
                            <!-- Informações de paginação -->
                            <?php if (isset($this->data['pagination'])): ?>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="text-secondary">
                                        Mostrando <?= $this->data['pagination']['first_item'] ?> até <?= $this->data['pagination']['last_item'] ?> 
                                        de <?= number_format($this->data['pagination']['total'], 0, ',', '.') ?> registro(s)
                                    </div>
                                    <div>
                                        <label class="me-2">Registros por página:</label>
                                        <select id="perPageSelect" class="form-select form-select-sm d-inline-block" style="width: auto;" onchange="changePerPage(this.value)">
                                            <option value="10" <?= $perPage == 10 ? 'selected' : '' ?>>10</option>
                                            <option value="25" <?= $perPage == 25 ? 'selected' : '' ?>>25</option>
                                            <option value="50" <?= $perPage == 50 ? 'selected' : '' ?>>50</option>
                                            <option value="100" <?= $perPage == 100 ? 'selected' : '' ?>>100</option>
                                            <option value="500" <?= $perPage == 500 ? 'selected' : '' ?>>500</option>
                                        </select>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <?php foreach ($headers as $header): ?>
                                                <th><?= htmlspecialchars($header) ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($result['data'] as $row): ?>
                                            <tr>
                                                <?php foreach ($headers as $header): ?>
                                                    <td><?php
                                                        $value = $row[$header] ?? '';
                                                        if (is_array($value) || is_object($value)) {
                                                            // Converter array/objeto para JSON formatado
                                                            $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
                                                            // Limitar tamanho para exibição
                                                            if (strlen($json) > 200) {
                                                                $json = substr($json, 0, 200) . '...';
                                                            }
                                                            echo '<pre style="margin:0;font-size:0.85em;white-space:pre-wrap;word-break:break-all;">' . htmlspecialchars($json) . '</pre>';
                                                        } elseif ($value === null) {
                                                            echo '<span class="text-muted">NULL</span>';
                                                        } else {
                                                            echo htmlspecialchars((string)$value);
                                                        }
                                                    ?></td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Paginação -->
                            <?php if (isset($this->data['pagination'])): ?>
                                <?php if ($this->data['pagination']['last_page'] > 1): ?>
                                    <div class="d-flex justify-content-center mt-3">
                                        <?= $this->data['pagination']['html'] ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center text-muted mt-2 small">
                                        <i class="fas fa-info-circle"></i> Exibindo todos os <?= number_format($this->data['pagination']['total'], 0, ',', '.') ?> registro(s)
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <script>
                            function changePerPage(value) {
                                const url = new URL(window.location.href);
                                url.searchParams.set('per_page', value);
                                url.searchParams.set('page', '1'); // Resetar para primeira página
                                window.location.href = url.toString();
                            }
                            </script>
                        <?php else: ?>
                            <!-- Gráfico -->
                            <canvas id="reportChart" style="max-height: 500px;"></canvas>
                            <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
                            <script>
                                const chartData = <?= json_encode($result['data']) ?>;
                                const labels = chartData.map(row => Object.values(row)[0]);
                                const values = chartData.map(row => parseFloat(Object.values(row)[1]) || 0);
                                
                                // Configuração de cores do relatório (se existir)
                                const chartConfig = <?= json_encode($report['chart_config'] ?? []) ?>;
                                
                                // Cores padrão (15 cores diferentes)
                                const defaultColors = [
                                    'rgba(54, 162, 235, 0.8)',   // Azul
                                    'rgba(255, 99, 132, 0.8)',   // Vermelho
                                    'rgba(255, 206, 86, 0.8)',   // Amarelo
                                    'rgba(75, 192, 192, 0.8)',   // Ciano
                                    'rgba(153, 102, 255, 0.8)',  // Roxo
                                    'rgba(255, 159, 64, 0.8)',   // Laranja
                                    'rgba(199, 199, 199, 0.8)',  // Cinza
                                    'rgba(83, 102, 255, 0.8)',   // Azul Escuro
                                    'rgba(255, 99, 255, 0.8)',   // Rosa
                                    'rgba(0, 204, 102, 0.8)',    // Verde
                                    'rgba(255, 140, 0, 0.8)',    // Laranja Escuro
                                    'rgba(0, 128, 255, 0.8)',    // Azul Claro
                                    'rgba(128, 0, 128, 0.8)',    // Roxo Escuro
                                    'rgba(255, 0, 127, 0.8)',    // Rosa Quente
                                    'rgba(0, 255, 127, 0.8)'     // Verde Claro
                                ];
                                
                                const defaultBorderColors = [
                                    'rgba(54, 162, 235, 1)',
                                    'rgba(255, 99, 132, 1)',
                                    'rgba(255, 206, 86, 1)',
                                    'rgba(75, 192, 192, 1)',
                                    'rgba(153, 102, 255, 1)',
                                    'rgba(255, 159, 64, 1)',
                                    'rgba(199, 199, 199, 1)',
                                    'rgba(83, 102, 255, 1)',
                                    'rgba(255, 99, 255, 1)',
                                    'rgba(0, 204, 102, 1)',
                                    'rgba(255, 140, 0, 1)',
                                    'rgba(0, 128, 255, 1)',
                                    'rgba(128, 0, 128, 1)',
                                    'rgba(255, 0, 127, 1)',
                                    'rgba(0, 255, 127, 1)'
                                ];
                                
                                // Usar cores configuradas ou padrão
                                const backgroundColor = chartConfig.backgroundColor && chartConfig.backgroundColor.length > 0
                                    ? chartConfig.backgroundColor
                                    : defaultColors;
                                const borderColor = chartConfig.borderColor && chartConfig.borderColor.length > 0
                                    ? chartConfig.borderColor
                                    : defaultBorderColors;
                                
                                // Para gráficos de pizza/rosca, usar array de cores
                                // Para gráficos de barras/linhas, usar array ou cor única conforme tipo
                                const chartType = '<?= str_replace('_chart', '', $report['visualization_type']) ?>';
                                const isMultiColor = chartType === 'pie' || chartType === 'doughnut' || chartType === 'bar';
                                
                                const datasetConfig = {
                                    label: '<?= htmlspecialchars($report['name']) ?>',
                                    data: values,
                                    backgroundColor: isMultiColor 
                                        ? labels.map((_, i) => backgroundColor[i % backgroundColor.length])
                                        : (backgroundColor[0] || 'rgba(54, 162, 235, 0.8)'),
                                    borderColor: isMultiColor
                                        ? labels.map((_, i) => borderColor[i % borderColor.length])
                                        : (borderColor[0] || 'rgba(54, 162, 235, 1)'),
                                    borderWidth: chartConfig.borderWidth || 2
                                };
                                
                                // Para gráficos de linha, adicionar fill
                                if (chartType === 'line') {
                                    datasetConfig.fill = false;
                                    datasetConfig.tension = 0.4;
                                }
                                
                                new Chart(document.getElementById('reportChart'), {
                                    type: chartType,
                                    data: {
                                        labels: labels,
                                        datasets: [datasetConfig]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: true,
                                        aspectRatio: 2,
                                        plugins: {
                                            legend: {
                                                display: chartConfig.showLegend !== false,
                                                position: chartConfig.legendPosition || 'top'
                                            },
                                            tooltip: {
                                                callbacks: {
                                                    label: function(context) {
                                                        let label = context.dataset.label || '';
                                                        if (label) {
                                                            label += ': ';
                                                        }
                                                        if (context.parsed.y !== null) {
                                                            label += new Intl.NumberFormat('pt-BR', {
                                                                style: 'currency',
                                                                currency: 'BRL'
                                                            }).format(context.parsed.y);
                                                        }
                                                        return label;
                                                    }
                                                }
                                            }
                                        },
                                        scales: chartType !== 'pie' && chartType !== 'doughnut' ? {
                                            y: {
                                                beginAtZero: true,
                                                ticks: {
                                                    callback: function(value) {
                                                        return new Intl.NumberFormat('pt-BR', {
                                                            style: 'currency',
                                                            currency: 'BRL'
                                                        }).format(value);
                                                    }
                                                }
                                            }
                                        } : undefined
                                    }
                                });
                            </script>
                        <?php endif; ?>
                        
                        <!-- Info -->
                        <div class="mt-3 text-muted small">
                            <i class="fas fa-info-circle"></i> 
                            <?= $result['rows_count'] ?> registro(s) | 
                            Executado em <?= $result['execution_time'] ?>s |
                            Conexão: <?= $connectionLabel ?>
                        </div>
                        <?php if (!empty($result['cache'])): ?>
                            <div class="alert alert-secondary mt-2">
                                <i class="fas fa-database"></i>
                                <?= !empty($result['cache']['from_cache']) ? 'Dados vindos do cache' : 'Consulta atualizada agora' ?>
                                em <?= !empty($result['cache']['stored_at']) ? date('d/m/Y H:i', $result['cache']['stored_at']) : '-' ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

