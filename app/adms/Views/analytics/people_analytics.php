<?php
$fs = $this->data['filter_period_start'] ?? '';
$fe = $this->data['filter_period_end'] ?? '';
$periodLabel = ($fs !== '' && $fe !== '')
    ? (date('d/m/Y', strtotime($fs)) . ' — ' . date('d/m/Y', strtotime($fe)))
    : '';
$selDep = $this->data['filter_departamento_ids'] ?? [];
$selPos = $this->data['filter_cargo_ids'] ?? [];
$turnoverFormula = $this->data['turnover_formula'] ?? '';
?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
.people-analytics-filters .select2-container { width: 100% !important; max-width: 100%; }
.people-analytics-filters .select2-container--default .select2-selection--multiple {
    min-height: 42px;
    border: 1px solid var(--bs-border-color, #ced4da);
    border-radius: var(--bs-border-radius, 0.375rem);
    padding: 4px 6px;
}
.people-analytics-filters .select2-container--default.select2-container--focus .select2-selection--multiple {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
.people-analytics-filters .select2-container--default .select2-selection--multiple .select2-selection__choice {
    background-color: #e7f1ff;
    border: 1px solid #b6d4fe;
    border-radius: 0.25rem;
    padding: 2px 6px;
}
.people-analytics-filters .period-presets .btn { font-size: 0.8rem; }
@media (max-width: 575.98px) {
    .people-analytics-filters .select2-container { font-size: 16px; }
}
</style>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3">People Analytics</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Gestão de Pessoas</li>
            <li class="breadcrumb-item">People Analytics</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="card border-light shadow mb-4 people-analytics-filters">
        <div class="card-header d-flex flex-column flex-lg-row align-items-lg-center gap-2 py-3">
            <div class="d-flex align-items-center gap-2">
                <span class="fw-semibold"><i class="fas fa-filter text-primary me-1"></i>Filtros globais</span>
            </div>
            <small class="text-muted lh-sm ms-lg-auto">Período, departamento e cargo aplicam-se a todos os indicadores e gráficos abaixo. Em departamentos e cargos, use a busca e selecione quantos precisar; vazio significa <strong>todos</strong>.</small>
        </div>
        <div class="card-body pt-0">
            <form method="get" action="<?php echo htmlspecialchars($_ENV['URL_ADM'] . 'people-analytics'); ?>" id="people-analytics-filter-form" class="row g-4">
                <div class="col-12">
                    <label class="form-label fw-semibold mb-2" for="pa_de">Período de análise</label>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <div class="flex-grow-1" style="min-width: 9.5rem;">
                            <span class="visually-hidden">Data inicial</span>
                            <input type="date" class="form-control" name="pa_de" id="pa_de" value="<?= htmlspecialchars($fs) ?>" aria-label="Data inicial do período">
                        </div>
                        <span class="text-muted small px-1 flex-shrink-0">até</span>
                        <div class="flex-grow-1" style="min-width: 9.5rem;">
                            <span class="visually-hidden">Data final</span>
                            <input type="date" class="form-control" name="pa_ate" id="pa_ate" value="<?= htmlspecialchars($fe) ?>" aria-label="Data final do período">
                        </div>
                    </div>
                    <div class="period-presets d-flex flex-wrap align-items-center gap-1 mt-2">
                        <span class="text-muted small me-1">Atalhos:</span>
                        <button type="button" class="btn btn-sm btn-outline-secondary border-0 bg-light" data-pa-preset="12m">Últimos 12 meses</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary border-0 bg-light" data-pa-preset="ytd">Ano atual</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary border-0 bg-light" data-pa-preset="month">Mês atual</button>
                    </div>
                </div>

                <div class="col-12 col-xl-6">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                        <label class="form-label fw-semibold mb-0" for="pa_dep">Departamentos</label>
                        <button type="button" class="btn btn-link btn-sm text-decoration-none p-0" id="pa_dep_clear" aria-label="Limpar departamentos selecionados">Limpar seleção</button>
                    </div>
                    <select class="form-select" name="pa_dep[]" id="pa_dep" multiple aria-describedby="pa_dep_help">
                        <?php foreach (($this->data['departments_options'] ?? []) as $dep): ?>
                            <?php $id = (int)($dep['id'] ?? 0); ?>
                            <option value="<?= $id ?>" <?= in_array($id, $selDep, true) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dep['name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="pa_dep_help" class="form-text">Digite para filtrar a lista. Nenhuma seleção = todos os departamentos.</div>
                </div>

                <div class="col-12 col-xl-6">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                        <label class="form-label fw-semibold mb-0" for="pa_pos">Cargos</label>
                        <button type="button" class="btn btn-link btn-sm text-decoration-none p-0" id="pa_pos_clear" aria-label="Limpar cargos selecionados">Limpar seleção</button>
                    </div>
                    <select class="form-select" name="pa_pos[]" id="pa_pos" multiple aria-describedby="pa_pos_help">
                        <?php foreach (($this->data['positions_options'] ?? []) as $pos): ?>
                            <?php $id = (int)($pos['id'] ?? 0); ?>
                            <option value="<?= $id ?>" <?= in_array($id, $selPos, true) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pos['name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="pa_pos_help" class="form-text">Digite para filtrar a lista. Nenhuma seleção = todos os cargos.</div>
                </div>

                <div class="col-12">
                    <div class="d-flex flex-column flex-sm-row flex-wrap align-items-stretch align-items-sm-center justify-content-between gap-3 pt-2 border-top">
                        <?php if ($periodLabel !== ''): ?>
                            <p class="text-muted small mb-0 order-2 order-sm-1">
                                <i class="fas fa-calendar-alt me-1"></i>Período analisado: <strong><?= htmlspecialchars($periodLabel) ?></strong>
                            </p>
                        <?php else: ?>
                            <span class="order-2 order-sm-1"></span>
                        <?php endif; ?>
                        <div class="d-flex flex-column flex-sm-row gap-2 order-1 order-sm-2 ms-sm-auto">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check me-1"></i>Aplicar filtros
                            </button>
                            <a href="<?php echo htmlspecialchars($_ENV['URL_ADM'] . 'people-analytics'); ?>" class="btn btn-outline-secondary text-center">Limpar tudo</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Cards de KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-2">
            <div class="card border-primary shadow h-100">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-2x text-primary mb-2"></i>
                    <h3 class="mb-0"><?= (int)($this->data['total_employees'] ?? 0) ?></h3>
                    <p class="text-muted mb-0 small">Colaboradores (universo filtrado)</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="card border-success shadow h-100">
                <div class="card-body text-center">
                    <i class="fas fa-user-check fa-2x text-success mb-2"></i>
                    <h3 class="mb-0"><?= (int)($this->data['active_employees'] ?? 0) ?></h3>
                    <p class="text-muted mb-0 small">Ativos hoje</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="card border-secondary shadow h-100">
                <div class="card-body text-center">
                    <i class="fas fa-user-plus fa-2x text-secondary mb-2"></i>
                    <h3 class="mb-0"><?= (int)($this->data['admissions_in_period'] ?? 0) ?></h3>
                    <p class="text-muted mb-0 small">Admissões no período</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="card border-warning shadow h-100">
                <div class="card-body text-center">
                    <i class="fas fa-user-minus fa-2x text-warning mb-2"></i>
                    <h3 class="mb-0"><?= (int)($this->data['terminations_in_period'] ?? 0) ?></h3>
                    <p class="text-muted mb-0 small">Desligamentos no período</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="card border-dark shadow h-100">
                <div class="card-body text-center">
                    <i class="fas fa-balance-scale fa-2x text-dark mb-2"></i>
                    <h3 class="mb-0"><?= (int)($this->data['net_movement'] ?? 0) ?></h3>
                    <p class="text-muted mb-0 small">Saldo líquido (adm. − desl.)</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="card border-info shadow h-100">
                <div class="card-body text-center">
                    <i class="fas fa-chart-line fa-2x text-info mb-2"></i>
                    <h3 class="mb-0"><?= htmlspecialchars((string)($this->data['turnover_rate'] ?? '0.00')) ?>%</h3>
                    <p class="text-muted mb-0 small">
                        Rotatividade
                        <span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" data-bs-placement="top"
                              title="<?= htmlspecialchars($turnoverFormula) ?>">
                            <i class="fas fa-info-circle text-muted" aria-hidden="true"></i>
                        </span>
                    </p>
                    <small class="text-muted"><?= (int)($this->data['terminated_last_year'] ?? 0) ?> desligamentos</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card border-light shadow">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Headcount mensal (período filtrado)</h6>
                </div>
                <div class="card-body">
                    <canvas id="headcountChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-light shadow">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Distribuição por departamento (ativos)</h6>
                </div>
                <div class="card-body">
                    <canvas id="departmentChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-light shadow">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Turnover por departamento (período filtrado)</h6>
                </div>
                <div class="card-body">
                    <canvas id="turnoverChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-light shadow">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-briefcase me-2"></i>Distribuição por cargo (ativos, top 10)</h6>
                </div>
                <div class="card-body">
                    <canvas id="positionChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Informações -->
    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <span><i class="fas fa-chart-bar me-2"></i>Análises e indicadores</span>
        </div>
        <div class="card-body">

            <div class="row g-3">
                <div class="col-md-6">
                    <h5>Métricas calculadas</h5>
                    <ul>
                        <li><strong>Período:</strong> <?= htmlspecialchars($periodLabel !== '' ? $periodLabel : '—') ?></li>
                        <li><strong>Rotatividade:</strong> <?= htmlspecialchars((string)($this->data['turnover_rate'] ?? '0.00')) ?>%
                            <span class="text-muted">(<?= htmlspecialchars($turnoverFormula) ?>)</span>
                        </li>
                        <li><strong>Admissões no período:</strong> <?= (int)($this->data['admissions_in_period'] ?? 0) ?></li>
                        <li><strong>Desligamentos no período:</strong> <?= (int)($this->data['terminations_in_period'] ?? 0) ?></li>
                        <li><strong>Saldo líquido:</strong> <?= (int)($this->data['net_movement'] ?? 0) ?></li>
                        <li><strong>Efetivo no início / fim do período (ativos, regra de snapshot):</strong>
                            <?= (int)($this->data['active_at_period_start'] ?? 0) ?> / <?= (int)($this->data['active_at_period_end'] ?? 0) ?>
                        </li>
                        <li><strong>Tempo médio de permanência (histórico, períodos encerrados):</strong> <?= htmlspecialchars((string)($this->data['avg_tenure'] ?? 'N/A')) ?></li>
                        <li><strong>Total de recontratações (registros no histórico):</strong> <?= (int)($this->data['total_rehires'] ?? 0) ?></li>
                        <li><strong>Headcount atual (ativos sem desligamento):</strong> <?= (int)($this->data['active_employees'] ?? 0) ?></li>
                        <li><strong>Total no universo filtrado:</strong> <?= (int)($this->data['total_employees'] ?? 0) ?></li>
                    </ul>

                    <div class="mt-3">
                        <h6>Distribuição por departamento (ativos)</h6>
                        <ul class="list-unstyled">
                            <?php foreach (($this->data['department_distribution'] ?? []) as $dept => $count): ?>
                                <li>
                                    <i class="fas fa-building text-primary me-2"></i>
                                    <?= htmlspecialchars((string)$dept) ?>: <strong><?= (int)$count ?></strong>
                                </li>
                            <?php endforeach; ?>
                            <?php if (empty($this->data['department_distribution'])): ?>
                                <li class="text-muted">Nenhum dado para os filtros atuais.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
                <div class="col-md-6">
                    <h5>Relatórios disponíveis</h5>
                    <ul>
                        <li>Relatório de Headcount</li>
                        <li>Análise de Turnover</li>
                        <li>Dashboard de Engajamento</li>
                        <li>Métricas de Recrutamento</li>
                    </ul>

                    <?php if (!empty($this->data['department_distribution'])): ?>
                        <div class="mt-3">
                            <h6>Tabela — departamento</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Departamento</th>
                                            <th class="text-center">Colaboradores</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($this->data['department_distribution'] as $dept => $count): ?>
                                            <tr>
                                                <td><?= htmlspecialchars((string)$dept) ?></td>
                                                <td class="text-center"><strong><?= (int)$count ?></strong></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>

                    <p class="small text-muted mt-3 mb-0">
                        API JSON (mesmos parâmetros <code>pa_de</code>, <code>pa_ate</code>, <code>pa_dep[]</code>, <code>pa_pos[]</code>):
                        <a href="<?= htmlspecialchars((string)($this->data['metrics_json_url'] ?? '')) ?>?<?= htmlspecialchars(http_build_query($_GET)) ?>" target="_blank" rel="noopener">people-analytics/metrics</a>
                    </p>
                </div>
            </div>

            <?php if (in_array('PeopleReports', $this->data['buttonPermission'] ?? [])) { ?>
                <div class="mt-3">
                    <a href="<?php echo $_ENV['URL_ADM']; ?>people-reports" class="btn btn-primary">
                        <i class="fas fa-file-alt me-2"></i>Acessar relatórios
                    </a>
                </div>
            <?php } ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
(function () {
    function paYmd(d) {
        var y = d.getFullYear();
        var m = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + day;
    }

    if (typeof jQuery !== 'undefined') {
        jQuery(function ($) {
            var $root = $('.people-analytics-filters');
            var select2Base = {
                width: '100%',
                allowClear: true,
                closeOnSelect: false,
                dropdownParent: $root.length ? $root : $(document.body),
                language: {
                    noResults: function () { return 'Nenhum resultado'; },
                    searching: function () { return 'Buscando…'; },
                    removeAllItems: function () { return 'Limpar tudo'; }
                }
            };

            $('#pa_dep').select2($.extend({}, select2Base, {
                placeholder: 'Buscar e selecionar departamentos…'
            }));
            $('#pa_pos').select2($.extend({}, select2Base, {
                placeholder: 'Buscar e selecionar cargos…'
            }));

            $('#pa_dep_clear').on('click', function () {
                $('#pa_dep').val(null).trigger('change');
            });
            $('#pa_pos_clear').on('click', function () {
                $('#pa_pos').val(null).trigger('change');
            });

            $('[data-pa-preset]').on('click', function () {
                var p = $(this).data('pa-preset');
                var end = new Date();
                var start = new Date();
                if (p === '12m') {
                    start.setFullYear(end.getFullYear() - 1);
                } else if (p === 'ytd') {
                    start = new Date(end.getFullYear(), 0, 1);
                } else if (p === 'month') {
                    start = new Date(end.getFullYear(), end.getMonth(), 1);
                }
                $('#pa_de').val(paYmd(start));
                $('#pa_ate').val(paYmd(end));
            });
        });
    }
})();
</script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
            new bootstrap.Tooltip(el);
        });
    }

    const chartColors = {
        primary: '#0d6efd',
        success: '#198754',
        info: '#0dcaf0',
        warning: '#ffc107',
        danger: '#dc3545',
        purple: '#6f42c1',
        orange: '#fd7e14',
        pink: '#d63384',
        teal: '#20c997'
    };

    const headcountData = <?= json_encode($this->data['monthly_headcount'] ?? []) ?>;
    const headcountCtx = document.getElementById('headcountChart');
    if (headcountCtx) {
        new Chart(headcountCtx, {
            type: 'line',
            data: {
                labels: Object.keys(headcountData),
                datasets: [{
                    label: 'Colaboradores (regra mensal)',
                    data: Object.values(headcountData),
                    borderColor: chartColors.primary,
                    backgroundColor: chartColors.primary + '20',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Colaboradores: ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    }

    const deptData = <?= json_encode($this->data['department_distribution'] ?? []) ?>;
    const deptCtx = document.getElementById('departmentChart');
    if (deptCtx && Object.keys(deptData).length > 0) {
        const colors = Object.keys(deptData).map((_, i) => {
            const colorArray = Object.values(chartColors);
            return colorArray[i % colorArray.length];
        });
        new Chart(deptCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(deptData),
                datasets: [{
                    data: Object.values(deptData),
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, position: 'bottom' }
                }
            }
        });
    }

    const turnoverData = <?= json_encode($this->data['turnover_by_department'] ?? []) ?>;
    const turnoverCtx = document.getElementById('turnoverChart');
    if (turnoverCtx && Object.keys(turnoverData).length > 0) {
        const deptLabels = Object.keys(turnoverData);
        const turnoverRates = deptLabels.map(dept => turnoverData[dept]['turnover_rate'] || 0);
        new Chart(turnoverCtx, {
            type: 'bar',
            data: {
                labels: deptLabels,
                datasets: [{
                    label: 'Taxa de turnover (%)',
                    data: turnoverRates,
                    backgroundColor: turnoverRates.map(rate =>
                        rate > 15 ? chartColors.danger :
                        rate > 10 ? chartColors.warning :
                        chartColors.success
                    ),
                    borderColor: turnoverRates.map(rate =>
                        rate > 15 ? chartColors.danger :
                        rate > 10 ? chartColors.warning :
                        chartColors.success
                    ),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Turnover: ' + context.parsed.y.toFixed(2) + '%';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
    }

    const positionData = <?= json_encode($this->data['position_distribution'] ?? []) ?>;
    const positionCtx = document.getElementById('positionChart');
    if (positionCtx && Object.keys(positionData).length > 0) {
        const sortedPositions = Object.entries(positionData)
            .sort((a, b) => b[1] - a[1])
            .slice(0, 10);
        const posLabels = sortedPositions.map(([pos]) => pos);
        const posValues = sortedPositions.map(([, count]) => count);
        new Chart(positionCtx, {
            type: 'bar',
            data: {
                labels: posLabels,
                datasets: [{
                    label: 'Colaboradores',
                    data: posValues,
                    backgroundColor: chartColors.info,
                    borderColor: chartColors.info,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    }
});
</script>
