<?php
$fs = $this->data['filter_period_start'] ?? '';
$fe = $this->data['filter_period_end'] ?? '';
$periodLabel = ($fs !== '' && $fe !== '')
    ? (date('d/m/Y', strtotime($fs)) . ' — ' . date('d/m/Y', strtotime($fe)))
    : '';
$selDep = $this->data['filter_departamento_ids'] ?? [];
$selPos = $this->data['filter_cargo_ids'] ?? [];
$fSexo = $this->data['filter_sexo'] ?? null;
$fEstadoCivil = $this->data['filter_estado_civil'] ?? null;
$fPaisIso = $this->data['filter_pais_iso'] ?? null;
$fFilhos = $this->data['filter_filhos'] ?? null;
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
/* Altura fixa do bloco do gráfico: evita canvas gigante e ajuda o Chart.js */
.people-analytics-page .people-analytics-chart-body {
    position: relative;
    min-height: 220px;
    height: min(300px, 42vh);
    max-height: 340px;
}
@media (min-width: 1200px) {
    .people-analytics-page .people-analytics-chart-body {
        height: min(280px, 36vh);
    }
}
.people-analytics-page .people-analytics-chart-body > canvas {
    max-width: 100%;
}
</style>
<div class="container-fluid px-4 people-analytics-page">
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
            <small class="text-muted lh-sm ms-lg-auto">Período, departamento, cargo, sexo, estado civil, país e filhos aplicam-se a todo o painel. Nos gráficos de barras e rosca de departamentos, <strong>clique num segmento</strong> para aplicar o filtro correspondente (nova carga da página). Faixa etária ainda não tem filtro por clique.</small>
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

                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold mb-0" for="pa_sexo">Sexo</label>
                    <select class="form-select" name="pa_sexo" id="pa_sexo" aria-describedby="pa_demo_help">
                        <option value="" <?= ($fSexo === null || $fSexo === '') ? 'selected' : '' ?>>Todos</option>
                        <option value="M" <?= $fSexo === 'M' ? 'selected' : '' ?>>Masculino</option>
                        <option value="F" <?= $fSexo === 'F' ? 'selected' : '' ?>>Feminino</option>
                        <option value="O" <?= $fSexo === 'O' ? 'selected' : '' ?>>Outros</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold mb-0" for="pa_estado_civil">Estado civil</label>
                    <select class="form-select" name="pa_estado_civil" id="pa_estado_civil">
                        <option value="" <?= ($fEstadoCivil === null || $fEstadoCivil === '') ? 'selected' : '' ?>>Todos</option>
                        <?php foreach (\App\adms\Helpers\UserFormHelper::estadoCivilOptions() as $slug => $lbl): ?>
                            <option value="<?= htmlspecialchars($slug) ?>" <?= $fEstadoCivil === $slug ? 'selected' : '' ?>><?= htmlspecialchars($lbl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold mb-0" for="pa_pais">País (residência)</label>
                    <select class="form-select" name="pa_pais" id="pa_pais">
                        <option value="" <?= ($fPaisIso === null || $fPaisIso === '') ? 'selected' : '' ?>>Todos</option>
                        <?php foreach (($this->data['countries_options_pa'] ?? []) as $code => $info): ?>
                            <option value="<?= htmlspecialchars($code) ?>" <?= strtoupper((string) $fPaisIso) === $code ? 'selected' : '' ?>>
                                <?= htmlspecialchars(($info['flag'] ?? '') . ' ' . ($info['name'] ?? $code)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold mb-0" for="pa_filhos">Filhos (cadastro)</label>
                    <select class="form-select" name="pa_filhos" id="pa_filhos">
                        <option value="" <?= ($fFilhos === null || $fFilhos === '') ? 'selected' : '' ?>>Todos</option>
                        <option value="S" <?= $fFilhos === 'S' ? 'selected' : '' ?>>Sim</option>
                        <option value="N" <?= $fFilhos === 'N' ? 'selected' : '' ?>>Não</option>
                    </select>
                </div>
                <div class="col-12">
                    <p id="pa_demo_help" class="form-text mb-0">Demografia e Power BI: use os mesmos parâmetros GET na URL <code>people-analytics/metrics</code> (<code>pa_sexo</code>, <code>pa_estado_civil</code>, <code>pa_pais</code>, <code>pa_filhos</code> além de <code>pa_de</code>, <code>pa_ate</code>, <code>pa_dep[]</code>, <code>pa_pos[]</code>). No gateway Power BI, aponte para a mesma base após executar as migrações.</p>
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
                <div class="card-body people-analytics-chart-body">
                    <canvas id="headcountChart" aria-label="Gráfico headcount mensal"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-light shadow">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Distribuição por departamento (ativos) <small class="fw-normal opacity-75">(clique para filtrar)</small></h6>
                </div>
                <div class="card-body people-analytics-chart-body">
                    <canvas id="departmentChart" aria-label="Gráfico por departamento"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-light shadow">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Turnover por departamento (período filtrado) <small class="fw-normal opacity-75">(clique para filtrar)</small></h6>
                </div>
                <div class="card-body people-analytics-chart-body">
                    <canvas id="turnoverChart" aria-label="Gráfico turnover por departamento"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-light shadow">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-briefcase me-2"></i>Distribuição por cargo (ativos, top 10) <small class="fw-normal opacity-75">(clique para filtrar)</small></h6>
                </div>
                <div class="card-body people-analytics-chart-body">
                    <canvas id="positionChart" aria-label="Gráfico por cargo"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Demografia e classificação de desligamentos (agregados) -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <p class="text-muted small mb-0">
                <i class="fas fa-shield-alt me-1"></i><?= htmlspecialchars((string)($this->data['demographics_note'] ?? 'Indicadores demográficos agregados.')) ?>
                Data de referência (ativos): <strong><?= htmlspecialchars((string)($this->data['demographics_ref_date'] ?? '')) ?></strong>.
            </p>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card border-light shadow h-100">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Desligamentos no período — impacto (RH)</h6>
                </div>
                <div class="card-body people-analytics-chart-body">
                    <canvas id="impactChart" aria-label="Gráfico impacto desligamentos"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card border-light shadow h-100">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-venus-mars me-2"></i>Ativos (ref. final) — sexo <small class="fw-normal opacity-75">(clique para filtrar)</small></h6>
                </div>
                <div class="card-body people-analytics-chart-body">
                    <canvas id="activeSexChart" aria-label="Gráfico ativos por sexo"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12">
            <div class="card border-light shadow h-100">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="fas fa-user-minus me-2"></i>Desligamentos no período — sexo <small class="fw-normal">(clique para filtrar)</small></h6>
                </div>
                <div class="card-body people-analytics-chart-body">
                    <canvas id="termSexChart" aria-label="Gráfico desligamentos por sexo"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-light shadow h-100">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-birthday-cake me-2"></i>Ativos (ref. final) — faixa etária</h6>
                </div>
                <div class="card-body people-analytics-chart-body">
                    <canvas id="activeAgeChart" aria-label="Gráfico ativos por idade"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-light shadow h-100">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="fas fa-birthday-cake me-2"></i>Desligamentos no período — faixa etária</h6>
                </div>
                <div class="card-body people-analytics-chart-body">
                    <canvas id="termAgeChart" aria-label="Gráfico desligamentos por idade"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-light shadow h-100">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-child me-2"></i>Ativos (ref. final) — filhos (cadastro) <small class="fw-normal opacity-75">(clique para filtrar)</small></h6>
                </div>
                <div class="card-body people-analytics-chart-body">
                    <canvas id="activeFilhosChart" aria-label="Gráfico ativos filhos"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-light shadow h-100">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="fas fa-child me-2"></i>Desligamentos no período — filhos (cadastro) <small class="fw-normal">(clique para filtrar)</small></h6>
                </div>
                <div class="card-body people-analytics-chart-body">
                    <canvas id="termFilhosChart" aria-label="Gráfico desligamentos filhos"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-light shadow h-100">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-ring me-2"></i>Ativos (ref. final) — estado civil <small class="fw-normal opacity-75">(clique para filtrar)</small></h6>
                </div>
                <div class="card-body people-analytics-chart-body">
                    <canvas id="activeEstadoCivilChart" aria-label="Gráfico ativos estado civil"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-light shadow h-100">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="fas fa-ring me-2"></i>Desligamentos no período — estado civil <small class="fw-normal">(clique para filtrar)</small></h6>
                </div>
                <div class="card-body people-analytics-chart-body">
                    <canvas id="termEstadoCivilChart" aria-label="Gráfico desligamentos estado civil"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-light shadow h-100">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-globe me-2"></i>Ativos (ref. final) — país <small class="fw-normal opacity-75">(clique para filtrar)</small></h6>
                </div>
                <div class="card-body people-analytics-chart-body">
                    <canvas id="activePaisChart" aria-label="Gráfico ativos país"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-light shadow h-100">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="fas fa-globe me-2"></i>Desligamentos no período — país <small class="fw-normal">(clique para filtrar)</small></h6>
                </div>
                <div class="card-body people-analytics-chart-body">
                    <canvas id="termPaisChart" aria-label="Gráfico desligamentos país"></canvas>
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
                        <h6>Desligamentos no período — por impacto (RH)</h6>
                        <ul class="list-unstyled small">
                            <?php foreach (($this->data['terminations_in_period_by_impact'] ?? []) as $lbl => $cnt): ?>
                                <li><?= htmlspecialchars((string)$lbl) ?>: <strong><?= (int)$cnt ?></strong></li>
                            <?php endforeach; ?>
                            <?php if (empty($this->data['terminations_in_period_by_impact'])): ?>
                                <li class="text-muted">Nenhum desligamento no período ou dados ainda não classificados.</li>
                            <?php endif; ?>
                        </ul>
                    </div>

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
                        API JSON (GET): <code>pa_de</code>, <code>pa_ate</code>, <code>pa_dep[]</code>, <code>pa_pos[]</code>, <code>pa_sexo</code>, <code>pa_estado_civil</code>, <code>pa_pais</code> (ISO2), <code>pa_filhos</code>.
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

    /** Paleta ampla e contrastada (evita repetir só azul/amarelo) */
    const paPalette = [
        '#4e79a7', '#f28e2b', '#59a14f', '#e15759', '#b07aa1',
        '#9c755f', '#edc948', '#bab0ab', '#76b7b2', '#ff9da7',
        '#b6992d', '#499894', '#79706e', '#d37295', '#fabfd2',
        '#b07f3e'
    ];

    function paColors(count) {
        const n = Math.max(0, count | 0);
        const out = [];
        for (let i = 0; i < n; i++) {
            out.push(paPalette[i % paPalette.length]);
        }
        return out;
    }

    /** Poucas fatias: pizza; muitas categorias: barras com uma cor por série */
    const PA_PIE_MAX_SLICES = 7;

    if (typeof Chart !== 'undefined') {
        Chart.defaults.animation = false;
    }

    /**
     * Eixo para contagens inteiras: stepSize 1 só quando o máximo é baixo.
     * Com max alto, stepSize: 1 gera centenas de ticks no eixo Y e trava a página.
     */
    function paCountAxisTicks(numericValues) {
        const vals = (numericValues || []).map(function (v) { return Number(v) || 0; });
        let max = 0;
        for (let i = 0; i < vals.length; i++) {
            if (vals[i] > max) {
                max = vals[i];
            }
        }
        if (max <= 12) {
            return {
                beginAtZero: true,
                ticks: { stepSize: 1, precision: 0 }
            };
        }
        return {
            beginAtZero: true,
            ticks: { maxTicksLimit: 8, precision: 0 }
        };
    }

    function paDrill(overrides) {
        const u = new URL(window.location.href);
        const sp = u.searchParams;
        Object.keys(overrides).forEach(function (key) {
            var val = overrides[key];
            if (key === 'pa_dep') {
                sp.delete('pa_dep[]');
                sp.delete('pa_dep');
                if (val !== null && val !== undefined && String(val) !== '') {
                    sp.append('pa_dep[]', String(val));
                }
                return;
            }
            if (key === 'pa_pos') {
                sp.delete('pa_pos[]');
                sp.delete('pa_pos');
                if (val !== null && val !== undefined && String(val) !== '') {
                    sp.append('pa_pos[]', String(val));
                }
                return;
            }
            if (val === null || val === undefined || String(val) === '') {
                sp.delete(key);
            } else {
                sp.set(key, String(val));
            }
        });
        window.location.href = u.toString();
    }

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
                    borderColor: paPalette[0],
                    backgroundColor: paPalette[0] + '33',
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
                    y: paCountAxisTicks(Object.values(headcountData))
                }
            }
        });
    }

    const deptSeg = <?= json_encode($this->data['active_department_segments'] ?? []) ?>;
    const deptData = <?= json_encode($this->data['department_distribution'] ?? []) ?>;
    const deptCtx = document.getElementById('departmentChart');
    if (deptCtx) {
        if (deptSeg.length > 0) {
            const sliceColors = paColors(deptSeg.length);
            new Chart(deptCtx, {
                type: 'doughnut',
                data: {
                    labels: deptSeg.map(function (s) { return s.name; }),
                    datasets: [{
                        data: deptSeg.map(function (s) { return s.count; }),
                        backgroundColor: sliceColors,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    onClick: function (_evt, els) {
                        if (!els.length) return;
                        var s = deptSeg[els[0].index];
                        if (s && s.department_id) paDrill({ pa_dep: s.department_id });
                    },
                    plugins: { legend: { display: true, position: 'bottom' } }
                }
            });
        } else if (Object.keys(deptData).length > 0) {
            const dk = Object.keys(deptData);
            const colors = paColors(dk.length);
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
                    plugins: { legend: { display: true, position: 'bottom' } }
                }
            });
        }
    }

    const turnoverData = <?= json_encode($this->data['turnover_by_department'] ?? []) ?>;
    const turnoverCtx = document.getElementById('turnoverChart');
    if (turnoverCtx && Object.keys(turnoverData).length > 0) {
        const deptLabels = Object.keys(turnoverData);
        const turnoverRates = deptLabels.map(function (dept) { return turnoverData[dept].turnover_rate || 0; });
        const turnoverPie = deptLabels.length > 0 && deptLabels.length <= PA_PIE_MAX_SLICES;
        const turnoverColors = paColors(deptLabels.length);

        function turnoverTooltipLabel(context) {
            var i = context.dataIndex;
            var name = deptLabels[i];
            var rate = turnoverRates[i];
            var row = turnoverData[name] || {};
            var t = (row.terminated != null) ? row.terminated : '—';
            var a = (row.active != null) ? row.active : '—';
            return [
                'Turnover: ' + Number(rate).toFixed(2) + '%',
                'Desligamentos (período): ' + t,
                'Ativos (hoje): ' + a
            ];
        }

        if (turnoverPie) {
            new Chart(turnoverCtx, {
                type: 'pie',
                data: {
                    labels: deptLabels,
                    datasets: [{
                        data: turnoverRates,
                        backgroundColor: turnoverColors,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    onClick: function (_evt, els) {
                        if (!els.length) return;
                        var name = deptLabels[els[0].index];
                        var row = turnoverData[name];
                        if (row && row.department_id) paDrill({ pa_dep: row.department_id });
                    },
                    plugins: {
                        legend: { display: true, position: 'bottom' },
                        tooltip: { callbacks: { label: turnoverTooltipLabel } }
                    }
                }
            });
        } else {
            new Chart(turnoverCtx, {
                type: 'bar',
                data: {
                    labels: deptLabels,
                    datasets: [{
                        label: 'Taxa de turnover (%)',
                        data: turnoverRates,
                        backgroundColor: turnoverColors,
                        borderColor: turnoverColors.map(function (c) { return c; }),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    onClick: function (_evt, els) {
                        if (!els.length) return;
                        var name = deptLabels[els[0].index];
                        var row = turnoverData[name];
                        if (row && row.department_id) paDrill({ pa_dep: row.department_id });
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: turnoverTooltipLabel } }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                maxTicksLimit: 8,
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    const posSeg = <?= json_encode($this->data['active_position_top_segments'] ?? []) ?>;
    const positionData = <?= json_encode($this->data['position_distribution'] ?? []) ?>;
    const positionCtx = document.getElementById('positionChart');
    if (positionCtx) {
        if (posSeg.length > 0) {
            const posPie = posSeg.length <= PA_PIE_MAX_SLICES;
            const posLabels = posSeg.map(function (s) { return s.name; });
            const posCounts = posSeg.map(function (s) { return s.count; });
            const posCols = paColors(posSeg.length);
            if (posPie) {
                new Chart(positionCtx, {
                    type: 'pie',
                    data: {
                        labels: posLabels,
                        datasets: [{
                            data: posCounts,
                            backgroundColor: posCols,
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        onClick: function (_evt, els) {
                            if (!els.length) return;
                            var s = posSeg[els[0].index];
                            if (s && s.position_id) paDrill({ pa_pos: s.position_id });
                        },
                        plugins: { legend: { display: true, position: 'bottom' } }
                    }
                });
            } else {
                new Chart(positionCtx, {
                    type: 'bar',
                    data: {
                        labels: posLabels,
                        datasets: [{
                            label: 'Colaboradores',
                            data: posCounts,
                            backgroundColor: posCols,
                            borderColor: posCols,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        onClick: function (_evt, els) {
                            if (!els.length) return;
                            var s = posSeg[els[0].index];
                            if (s && s.position_id) paDrill({ pa_pos: s.position_id });
                        },
                        plugins: { legend: { display: false } },
                        scales: { x: paCountAxisTicks(posCounts) }
                    }
                });
            }
        } else if (Object.keys(positionData).length > 0) {
            const sortedPositions = Object.entries(positionData).sort((a, b) => b[1] - a[1]).slice(0, 10);
            const posLabels = sortedPositions.map(function (r) { return r[0]; });
            const posValues = sortedPositions.map(function (r) { return r[1]; });
            const posCols = paColors(posLabels.length);
            new Chart(positionCtx, {
                type: 'bar',
                data: {
                    labels: posLabels,
                    datasets: [{
                        label: 'Colaboradores',
                        data: posValues,
                        backgroundColor: posCols,
                        borderColor: posCols,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: { legend: { display: false } },
                    scales: { x: paCountAxisTicks(posValues) }
                }
            });
        }
    }

    function paBarChart(canvasId, datasetLabel, dataObj) {
        const ctx = document.getElementById(canvasId);
        if (!ctx || !dataObj || Object.keys(dataObj).length === 0) {
            return;
        }
        const labels = Object.keys(dataObj);
        const values = Object.values(dataObj);
        const n = labels.length;
        const colors = paColors(n);
        const usePie = n <= PA_PIE_MAX_SLICES;

        if (usePie) {
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        label: datasetLabel,
                        data: values,
                        backgroundColor: colors,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: true, position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: function (c) {
                                    var v = c.raw != null ? c.raw : c.parsed;
                                    return (c.label || '') + ': ' + v;
                                }
                            }
                        }
                    }
                }
            });
        } else {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: datasetLabel,
                        data: values,
                        backgroundColor: colors,
                        borderColor: colors,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: paCountAxisTicks(values) }
                }
            });
        }
    }

    function paBarChartDrill(canvasId, datasetLabel, dataObj, paramName, labelToCode) {
        const ctx = document.getElementById(canvasId);
        if (!ctx || !dataObj || Object.keys(dataObj).length === 0) return;
        const labels = Object.keys(dataObj);
        const values = Object.values(dataObj);
        const n = labels.length;
        const colors = paColors(n);
        const usePie = n <= PA_PIE_MAX_SLICES;

        function doDrill(els) {
            if (!els.length) return;
            var lbl = labels[els[0].index];
            if (!Object.prototype.hasOwnProperty.call(labelToCode, lbl)) return;
            var code = labelToCode[lbl];
            var o = {};
            o[paramName] = code === null || code === '' ? '' : code;
            paDrill(o);
        }

        if (usePie) {
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        label: datasetLabel,
                        data: values,
                        backgroundColor: colors,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    onClick: function (_evt, els) { doDrill(els); },
                    plugins: {
                        legend: { display: true, position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: function (c) {
                                    var v = c.raw != null ? c.raw : c.parsed;
                                    return (c.label || '') + ': ' + v;
                                }
                            }
                        }
                    }
                }
            });
        } else {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: datasetLabel,
                        data: values,
                        backgroundColor: colors,
                        borderColor: colors,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    onClick: function (_evt, els) { doDrill(els); },
                    plugins: { legend: { display: false } },
                    scales: { y: paCountAxisTicks(values) }
                }
            });
        }
    }

    const paSexMap = { 'Masculino': 'M', 'Feminino': 'F', 'Outros': 'O', 'Sexo não informado': '' };
    const paFilhosMap = { 'Com filhos (cadastro)': 'S', 'Sem filhos (cadastro)': 'N', 'Filhos não informado': '' };

    const estadoCivilLabels = <?= json_encode($this->data['estado_civil_labels'] ?? []) ?>;
    const countryNamesPa = <?= json_encode(array_map(static fn ($i) => $i['name'] ?? '', $this->data['countries_options_pa'] ?? [])) ?>;

    function paBarChartDrillSlug(canvasId, datasetLabel, slugData, mode) {
        const ctx = document.getElementById(canvasId);
        if (!ctx || !slugData || Object.keys(slugData).length === 0) return;
        const slugs = Object.keys(slugData);
        const labels = slugs.map(function (s) {
            if (s === '_empty') return 'Não informado';
            if (mode === 'estado') return estadoCivilLabels[s] || s;
            if (mode === 'pais') return countryNamesPa[s] || s;
            return s;
        });
        const values = slugs.map(function (s) { return slugData[s]; });
        const n = slugs.length;
        const colors = paColors(n);
        const usePie = n <= PA_PIE_MAX_SLICES;

        function doDrillSlug(els) {
            if (!els.length) return;
            var slug = slugs[els[0].index];
            if (mode === 'estado') {
                paDrill({ pa_estado_civil: slug === '_empty' ? '' : slug });
            } else {
                paDrill({ pa_pais: slug === '_empty' ? '' : slug });
            }
        }

        if (usePie) {
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        label: datasetLabel,
                        data: values,
                        backgroundColor: colors,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    onClick: function (_evt, els) { doDrillSlug(els); },
                    plugins: {
                        legend: { display: true, position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: function (c) {
                                    var v = c.raw != null ? c.raw : c.parsed;
                                    return (c.label || '') + ': ' + v;
                                }
                            }
                        }
                    }
                }
            });
        } else {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: datasetLabel,
                        data: values,
                        backgroundColor: colors,
                        borderColor: colors,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    onClick: function (_evt, els) { doDrillSlug(els); },
                    plugins: { legend: { display: false } },
                    scales: { y: paCountAxisTicks(values) }
                }
            });
        }
    }

    const impactData = <?= json_encode($this->data['terminations_in_period_by_impact'] ?? []) ?>;
    const impactCtx = document.getElementById('impactChart');
    if (impactCtx && Object.keys(impactData).length > 0) {
        const impactSum = Object.values(impactData).reduce(function (a, b) { return a + b; }, 0);
        if (impactSum > 0) {
            const ik = Object.keys(impactData);
            const impactCols = paColors(ik.length);
            new Chart(impactCtx, {
                type: 'doughnut',
                data: {
                    labels: ik,
                    datasets: [{
                        data: Object.values(impactData),
                        backgroundColor: impactCols,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: true, position: 'bottom' } }
                }
            });
        }
    }

    paBarChartDrill('activeSexChart', 'Ativos', <?= json_encode($this->data['active_headcount_by_sex'] ?? []) ?>, 'pa_sexo', paSexMap);
    paBarChartDrill('termSexChart', 'Desligamentos', <?= json_encode($this->data['terminations_in_period_by_sex'] ?? []) ?>, 'pa_sexo', paSexMap);
    paBarChart('activeAgeChart', 'Ativos', <?= json_encode($this->data['active_headcount_by_age_band'] ?? []) ?>);
    paBarChart('termAgeChart', 'Desligamentos', <?= json_encode($this->data['terminations_in_period_by_age_band'] ?? []) ?>);
    paBarChartDrill('activeFilhosChart', 'Ativos', <?= json_encode($this->data['active_headcount_by_filhos'] ?? []) ?>, 'pa_filhos', paFilhosMap);
    paBarChartDrill('termFilhosChart', 'Desligamentos', <?= json_encode($this->data['terminations_in_period_by_filhos'] ?? []) ?>, 'pa_filhos', paFilhosMap);
    paBarChartDrillSlug('activeEstadoCivilChart', 'Ativos', <?= json_encode($this->data['active_headcount_by_estado_civil'] ?? []) ?>, 'estado');
    paBarChartDrillSlug('termEstadoCivilChart', 'Desligamentos', <?= json_encode($this->data['terminations_in_period_by_estado_civil'] ?? []) ?>, 'estado');
    paBarChartDrillSlug('activePaisChart', 'Ativos', <?= json_encode($this->data['active_headcount_by_pais'] ?? []) ?>, 'pais');
    paBarChartDrillSlug('termPaisChart', 'Desligamentos', <?= json_encode($this->data['terminations_in_period_by_pais'] ?? []) ?>, 'pais');
});
</script>
