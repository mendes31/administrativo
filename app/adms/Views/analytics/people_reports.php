<?php
$fs = $this->data['filter_period_start'] ?? '';
$fe = $this->data['filter_period_end'] ?? '';
$selDep = $this->data['filter_departamento_ids'] ?? [];
$selPos = $this->data['filter_cargo_ids'] ?? [];
$fSexo = $this->data['filter_sexo'] ?? null;
$fEstadoCivil = $this->data['filter_estado_civil'] ?? null;
$fPaisIso = $this->data['filter_pais_iso'] ?? null;
$fFilhos = $this->data['filter_filhos'] ?? null;
$exportQ = $this->data['export_query'] ?? '';
$exportSuffix = $exportQ !== '' ? ('?' . $exportQ) : '';
$adm = $_ENV['URL_ADM'] ?? '';
?>
<link rel="stylesheet" href="<?= htmlspecialchars($adm) ?>public/adms/vendor/select2/css/select2.min.css" />
<style>
.people-reports-filters .select2-container { width: 100% !important; max-width: 100%; }
.people-reports-filters .select2-container--default .select2-selection--multiple {
    min-height: 42px;
    border: 1px solid var(--bs-border-color, #ced4da);
    border-radius: var(--bs-border-radius, 0.375rem);
    padding: 4px 6px;
}
.people-reports-filters .select2-container--default.select2-container--focus .select2-selection--multiple {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
.people-reports-filters .period-presets .btn { font-size: 0.8rem; }
</style>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Relatórios de RH</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?= htmlspecialchars($adm) ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= htmlspecialchars($adm) ?>people-analytics" class="text-decoration-none">People Analytics</a>
            </li>
            <li class="breadcrumb-item active">Relatórios</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow people-reports-filters">
        <div class="card-header hstack gap-2 flex-wrap">
            <span><i class="fas fa-filter me-2"></i>Filtros (iguais ao People Analytics)</span>
            <span class="ms-auto d-flex gap-2">
                <a href="<?= htmlspecialchars($adm) ?>people-analytics" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-chart-line me-1"></i>Dashboard
                </a>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form method="get" action="<?= htmlspecialchars($adm) ?>people-reports" class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold">Período</label>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <input type="date" class="form-control" style="max-width:11rem" name="pa_de" id="pa_de" value="<?= htmlspecialchars($fs) ?>">
                        <span class="text-muted small">até</span>
                        <input type="date" class="form-control" style="max-width:11rem" name="pa_ate" id="pa_ate" value="<?= htmlspecialchars($fe) ?>">
                    </div>
                    <div class="period-presets d-flex flex-wrap gap-1 mt-2">
                        <span class="text-muted small me-1">Atalhos:</span>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-pa-preset="12m">Últimos 12 meses</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-pa-preset="ytd">Ano atual</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-pa-preset="month">Mês atual</button>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="pa_dep">Departamentos</label>
                    <select class="form-select" name="pa_dep[]" id="pa_dep" multiple>
                        <?php foreach (($this->data['departments_options'] ?? []) as $dep): ?>
                            <?php $id = (int)($dep['id'] ?? 0); ?>
                            <option value="<?= $id ?>" <?= in_array($id, $selDep, true) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dep['name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="pa_pos">Cargos</label>
                    <select class="form-select" name="pa_pos[]" id="pa_pos" multiple>
                        <?php foreach (($this->data['positions_options'] ?? []) as $pos): ?>
                            <?php $id = (int)($pos['id'] ?? 0); ?>
                            <option value="<?= $id ?>" <?= in_array($id, $selPos, true) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pos['name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold" for="pa_sexo">Sexo</label>
                    <select class="form-select" name="pa_sexo" id="pa_sexo">
                        <option value="" <?= ($fSexo === null || $fSexo === '') ? 'selected' : '' ?>>Todos</option>
                        <option value="M" <?= $fSexo === 'M' ? 'selected' : '' ?>>Masculino</option>
                        <option value="F" <?= $fSexo === 'F' ? 'selected' : '' ?>>Feminino</option>
                        <option value="O" <?= $fSexo === 'O' ? 'selected' : '' ?>>Outros</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold" for="pa_estado_civil">Estado civil</label>
                    <select class="form-select" name="pa_estado_civil" id="pa_estado_civil">
                        <option value="" <?= ($fEstadoCivil === null || $fEstadoCivil === '') ? 'selected' : '' ?>>Todos</option>
                        <?php foreach (\App\adms\Helpers\UserFormHelper::estadoCivilOptions() as $slug => $lbl): ?>
                            <option value="<?= htmlspecialchars($slug) ?>" <?= $fEstadoCivil === $slug ? 'selected' : '' ?>><?= htmlspecialchars($lbl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold" for="pa_pais">País</label>
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
                    <label class="form-label fw-semibold" for="pa_filhos">Filhos</label>
                    <select class="form-select" name="pa_filhos" id="pa_filhos">
                        <option value="" <?= ($fFilhos === null || $fFilhos === '') ? 'selected' : '' ?>>Todos</option>
                        <option value="S" <?= $fFilhos === 'S' ? 'selected' : '' ?>>Sim</option>
                        <option value="N" <?= $fFilhos === 'N' ? 'selected' : '' ?>>Não</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i>Aplicar filtros</button>
                    <a href="<?= htmlspecialchars($adm) ?>people-reports" class="btn btn-outline-secondary">Limpar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <span><i class="fas fa-file-alt me-2"></i>Relatórios de Gestão de Pessoas</span>
        </div>
        <div class="card-body">
            <div class="alert alert-info mb-3">
                <i class="fas fa-info-circle me-2"></i>
                Os ficheiros CSV usam o mesmo critério de filtros acima (período e universo de colaboradores). Delimitador: ponto e vírgula (;), UTF-8 com BOM (compatível com Excel).
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card border-primary h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">
                                <i class="fas fa-users text-primary me-2"></i>Headcount
                            </h5>
                            <p class="card-text flex-grow-1">Resumo do período, distribuição por departamento e cargo (ativos), e listagem do universo filtrado.</p>
                            <a href="<?= htmlspecialchars($adm . 'people-reports/export-headcount-csv' . $exportSuffix) ?>" class="btn btn-primary btn-sm align-self-start">
                                <i class="fas fa-download me-1"></i>Gerar CSV
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-warning h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">
                                <i class="fas fa-exchange-alt text-warning me-2"></i>Turnover
                            </h5>
                            <p class="card-text flex-grow-1">Resumo de admissões, desligamentos e taxa de turnover; lista de desligamentos no período.</p>
                            <a href="<?= htmlspecialchars($adm . 'people-reports/export-turnover-csv' . $exportSuffix) ?>" class="btn btn-warning btn-sm align-self-start">
                                <i class="fas fa-download me-1"></i>Gerar CSV
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-success h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">
                                <i class="fas fa-chart-line text-success me-2"></i>Desempenho
                            </h5>
                            <p class="card-text flex-grow-1">Avaliações de desempenho cuja <strong>data da avaliação</strong> está no período (respeita as mesmas regras de visibilidade da listagem de desempenho).</p>
                            <a href="<?= htmlspecialchars($adm . 'people-reports/export-performance-csv' . $exportSuffix) ?>" class="btn btn-success btn-sm align-self-start">
                                <i class="fas fa-download me-1"></i>Gerar CSV
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-info h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">
                                <i class="fas fa-graduation-cap text-info me-2"></i>Treinamentos
                            </h5>
                            <p class="card-text flex-grow-1">Aplicações <strong>realizadas</strong> no período (data de realização) e <strong>pendentes/agendadas</strong> sem realização com data agendada ou registo no período.</p>
                            <a href="<?= htmlspecialchars($adm . 'people-reports/export-training-csv' . $exportSuffix) ?>" class="btn btn-info btn-sm align-self-start text-white">
                                <i class="fas fa-download me-1"></i>Gerar CSV
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= htmlspecialchars($adm) ?>public/adms/vendor/select2/js/select2.min.js"></script>
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
            var $root = $('.people-reports-filters');
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
            $('#pa_dep').select2($.extend({}, select2Base, { placeholder: 'Departamentos…' }));
            $('#pa_pos').select2($.extend({}, select2Base, { placeholder: 'Cargos…' }));
            $('[data-pa-preset]').on('click', function () {
                var p = $(this).data('pa-preset');
                var end = new Date();
                var start = new Date();
                if (p === '12m') { start.setFullYear(end.getFullYear() - 1); }
                else if (p === 'ytd') { start = new Date(end.getFullYear(), 0, 1); }
                else if (p === 'month') { start = new Date(end.getFullYear(), end.getMonth(), 1); }
                $('#pa_de').val(paYmd(start));
                $('#pa_ate').val(paYmd(end));
            });
        });
    }
})();
</script>
