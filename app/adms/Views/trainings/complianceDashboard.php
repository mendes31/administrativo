<?php

use App\adms\Helpers\PositionDisplayHelper;



$filters = $this->data['filters'] ?? [];

$dashboard = $this->data['dashboard'] ?? [];

$totalCadastrados = (int)($dashboard['totalCadastrados'] ?? 0);

$totalObrigacoes = (int)($dashboard['totalObrigacoes'] ?? 0);

$totalRealizados = (int)($dashboard['totalRealizados'] ?? 0);

$totalPendentes = (int)($dashboard['totalPendentes'] ?? 0);

$percentRealizadosGlobal = (float)($dashboard['percentRealizados'] ?? 0);

$hasFilters = (bool)($dashboard['hasFilters'] ?? false);

$departmentStats = $dashboard['departmentStats'] ?? [];

$positionStats = $dashboard['positionStats'] ?? [];

$userStats = $dashboard['userStats'] ?? [];

$userPagination = $dashboard['userPagination'] ?? [];

$percentRealizados = static function (int $totalObrigacoesRow, int $realizados): float {

    if ($totalObrigacoesRow <= 0) {

        return 0.0;

    }



    return round(($realizados / $totalObrigacoesRow) * 100, 1);

};



$departmentPagination = $dashboard['departmentPagination'] ?? [];
$positionPagination = $dashboard['positionPagination'] ?? [];
$positionStatsAll = $dashboard['positionStatsAll'] ?? [];

$departmentStatsAll = $dashboard['departmentStatsAll'] ?? [];
$userStatsAll = $dashboard['userStatsAll'] ?? [];

$pages = $this->data['pages'] ?? ['depto' => 1, 'cargo' => 1, 'colab' => 1];

$paginationSettings = $this->data['paginationSettings'] ?? ['options' => [10, 20, 50, 100], 'per_page' => 10];
$perPageCurrent = (int)($_GET['per_page'] ?? ($paginationSettings['per_page'] ?? 10));

$filtersCollapseId = 'complianceDashboardFilters';
$infoCollapseId = 'complianceDashboardInfo';

$filterDeptName = '';
$filterCargoName = '';
$filterUserName = '';
foreach (($this->data['listDepartments'] ?? []) as $dep) {
    if ((int)($filters['departamento'] ?? 0) === (int)($dep['id'] ?? 0)) {
        $filterDeptName = (string)($dep['name'] ?? '');
    }
}
foreach (($this->data['listPositions'] ?? []) as $pos) {
    if ((int)($filters['cargo'] ?? 0) === (int)($pos['id'] ?? 0)) {
        $filterCargoName = PositionDisplayHelper::formatForDisplay((string)($pos['name'] ?? ''));
    }
}
foreach (($this->data['listUsers'] ?? []) as $user) {
    if ((int)($filters['colaborador'] ?? 0) === (int)($user['id'] ?? 0)) {
        $filterUserName = (string)($user['name'] ?? '');
    }
}

$summaryVisualTitle = 'Resumo visual';
$summaryVisualSubtitle = 'Todos os departamentos';
if (!empty($filters['colaborador'])) {
    $summaryVisualTitle = 'Resumo do colaborador';
    $summaryVisualSubtitle = $filterUserName !== '' ? $filterUserName : 'Colaborador filtrado';
} elseif (!empty($filters['cargo'])) {
    $summaryVisualTitle = 'Resumo do cargo';
    $summaryVisualSubtitle = $filterCargoName !== '' ? $filterCargoName : 'Cargo filtrado';
} elseif (!empty($filters['departamento'])) {
    $summaryVisualTitle = 'Resumo do departamento';
    $summaryVisualSubtitle = $filterDeptName !== '' ? $filterDeptName : 'Departamento filtrado';
}

$filterBadges = [];
if ($filterDeptName !== '') {
    $filterBadges[] = 'Depto: ' . $filterDeptName;
}
if ($filterCargoName !== '') {
    $filterBadges[] = 'Cargo: ' . $filterCargoName;
}
if ($filterUserName !== '') {
    $filterBadges[] = 'Colab.: ' . $filterUserName;
}

$summaryBarMax = max($totalObrigacoes, 1);
$summaryBarPct = static function (int $value) use ($summaryBarMax): int {
    return max(4, (int) round(($value / $summaryBarMax) * 100));
};

$buildComplianceQuery = static function (array $overrides = []) use ($filters, $perPageCurrent, $pages): array {
    $params = [
        'departamento' => $filters['departamento'] ?? null,
        'cargo' => $filters['cargo'] ?? null,
        'colaborador' => $filters['colaborador'] ?? null,
        'per_page' => $perPageCurrent,
        'page_depto' => (int)($pages['depto'] ?? 1),
        'page_cargo' => (int)($pages['cargo'] ?? 1),
        'page_colab' => (int)($pages['colab'] ?? 1),
    ];
    $params = array_merge($params, $overrides);

    return array_filter($params, static fn($value) => $value !== null && $value !== '');
};

$buildSectionExportSuffix = static function (string $section) use ($buildComplianceQuery): string {
    $query = http_build_query($buildComplianceQuery([]));

    return ($query !== '' ? $query . '&' : '') . 'section=' . rawurlencode($section) . '&';
};
?>

<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">

        <h2 class="mt-3">Dashboard de Necessidades de Treinamento</h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">

            <li class="breadcrumb-item">

                <a href="<?= $_ENV['URL_ADM'] ?>dashboard" class="text-decoration-none">Dashboard</a>

            </li>

            <li class="breadcrumb-item">Treinamentos</li>

            <li class="breadcrumb-item">Necessidades</li>

        </ol>

    </div>

    <div class="card mb-3 border-info shadow-sm">
        <div class="card-header py-2 px-3">
            <button
                class="btn btn-link text-decoration-none p-0 w-100 text-start collapsed d-flex align-items-center justify-content-between"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#<?= $infoCollapseId ?>"
                aria-expanded="false"
                aria-controls="<?= $infoCollapseId ?>"
            >
                <span class="text-info">
                    <i class="fas fa-info-circle me-2"></i>Como funcionam os indicadores
                </span>
                <i class="fas fa-chevron-down small text-muted"></i>
            </button>
        </div>
        <div class="collapse" id="<?= $infoCollapseId ?>">
            <div class="card-body py-2 small text-muted">
                Indicadores baseados na <strong>matriz materializada</strong> (mesma lógica da LNT): colaboradores ativos,
                treinamentos ativos e consolidação de um vínculo por colaborador × treinamento.
                <?php if ($hasFilters): ?>
                    <strong>Filtros ativos</strong> — todos os totais e tabelas abaixo refletem apenas o recorte selecionado.
                <?php else: ?>
                    Treinamentos cadastrados contam cada <strong>código</strong> uma única vez (versões não duplicam).
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2 justify-content-between flex-wrap">
            <span class="d-none d-md-inline"><i class="fas fa-filter me-2"></i>Filtros</span>
            <div class="d-md-none w-100">
                <button
                    class="btn btn-outline-primary btn-sm w-100"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#<?= $filtersCollapseId ?>"
                    aria-expanded="false"
                    aria-controls="<?= $filtersCollapseId ?>"
                >
                    <i class="fas fa-filter me-1"></i>Filtros
                </button>
            </div>
            <div class="ms-md-auto d-flex flex-wrap gap-1">
                <?php $completoExport = $buildSectionExportSuffix('completo'); ?>
                <a href="<?= $_ENV['URL_ADM'] ?>training-compliance-dashboard?<?= $completoExport ?>export=excel" class="btn btn-success btn-sm">
                    <i class="fas fa-file-excel me-1"></i>Excel completo
                </a>
                <a href="<?= $_ENV['URL_ADM'] ?>training-compliance-dashboard?<?= $completoExport ?>export=pdf" class="btn btn-danger btn-sm">
                    <i class="fas fa-file-pdf me-1"></i>PDF completo
                </a>
            </div>
        </div>

        <div class="collapse d-md-block" id="<?= $filtersCollapseId ?>">
        <div class="card-body">

            <form method="GET" class="row g-2 align-items-end">

                <div class="col-md-3">

                    <label for="departamento" class="form-label">Departamento</label>

                    <select name="departamento" id="departamento" class="form-select">

                        <option value="">Todos</option>

                        <?php foreach (($this->data['listDepartments'] ?? []) as $dep): ?>

                            <option value="<?= (int)$dep['id'] ?>" <?= (int)($filters['departamento'] ?? 0) === (int)$dep['id'] ? 'selected' : '' ?>>

                                <?= htmlspecialchars($dep['name']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="col-md-3">

                    <label for="cargo" class="form-label">Cargo</label>

                    <select name="cargo" id="cargo" class="form-select">

                        <option value="">Todos</option>

                        <?php foreach (($this->data['listPositions'] ?? []) as $pos): ?>

                            <option value="<?= (int)$pos['id'] ?>" <?= (int)($filters['cargo'] ?? 0) === (int)$pos['id'] ? 'selected' : '' ?>>

                                <?= htmlspecialchars(PositionDisplayHelper::formatForDisplay((string)$pos['name'])) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="col-md-3">

                    <label for="colaborador" class="form-label">Colaborador</label>

                    <select name="colaborador" id="colaborador" class="form-select">

                        <option value="">Todos</option>

                        <?php foreach (($this->data['listUsers'] ?? []) as $user): ?>

                            <option value="<?= (int)$user['id'] ?>" <?= (int)($filters['colaborador'] ?? 0) === (int)$user['id'] ? 'selected' : '' ?>>

                                <?= htmlspecialchars($user['name']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="col-md-1">

                    <label for="per_page" class="form-label">Mostrar</label>

                    <select name="per_page" id="per_page" class="form-select">

                        <?php foreach (($paginationSettings['options'] ?? [10, 20, 50, 100]) as $opt): ?>

                            <option value="<?= (int)$opt ?>" <?= (int)$perPageCurrent === (int)$opt ? 'selected' : '' ?>><?= (int)$opt ?></option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="col-md-1">

                    <button type="submit" class="btn btn-primary w-100">

                        <i class="fas fa-search me-1"></i>Filtrar

                    </button>

                </div>

                <div class="col-md-1">

                    <a href="<?= $_ENV['URL_ADM'] ?>training-compliance-dashboard?limpar=1" class="btn btn-secondary w-100">Limpar</a>

                </div>

            </form>

        </div>
        </div>

    </div>



    <div class="row mb-4">

        <div class="col-md-6 col-lg-3">

            <div class="card border-primary shadow-sm h-100">

                <div class="card-body text-center py-4">

                    <h3 class="text-primary mb-1">

                        <i class="fas fa-graduation-cap"></i>

                        <?= number_format($totalCadastrados) ?>

                    </h3>

                    <p class="card-text mb-0 small text-muted">

                        <?= $hasFilters ? 'Treinamentos no recorte' : 'Total de Treinamentos Cadastrados' ?>

                    </p>

                </div>

            </div>

        </div>

        <div class="col-md-6 col-lg-3">

            <div class="card border-secondary shadow-sm h-100">

                <div class="card-body text-center py-4">

                    <h3 class="text-secondary mb-1">

                        <i class="fas fa-link"></i>

                        <?= number_format($totalObrigacoes) ?>

                    </h3>

                    <p class="card-text mb-0 small text-muted">Total de obrigações</p>

                </div>

            </div>

        </div>

        <div class="col-md-6 col-lg-3">

            <div class="card border-success shadow-sm h-100">

                <div class="card-body text-center py-4">

                    <h3 class="text-success mb-1">

                        <i class="fas fa-check-circle"></i>

                        <?= number_format($totalRealizados) ?>

                    </h3>

                    <p class="card-text mb-0 small text-muted">Realizados (<?= $percentRealizadosGlobal ?>%)</p>

                </div>

            </div>

        </div>

        <div class="col-md-6 col-lg-3">

            <div class="card border-danger shadow-sm h-100">

                <div class="card-body text-center py-4">

                    <h3 class="text-danger mb-1">

                        <i class="fas fa-clock"></i>

                        <?= number_format($totalPendentes) ?>

                    </h3>

                    <p class="card-text mb-0 small text-muted">Pendentes</p>

                </div>

            </div>

        </div>

    </div>

    <?php include './app/adms/Views/trainings/partials/complianceDashboardCharts.php'; ?>

    <?php include './app/adms/Views/trainings/partials/complianceDashboardSections.php'; ?>

    <div class="d-flex flex-wrap gap-2 mb-4">

        <a href="<?= $_ENV['URL_ADM'] ?>training-kpi-dashboard" class="btn btn-outline-primary btn-sm">

            <i class="fas fa-chart-line me-1"></i>Dashboard de KPIs

        </a>

        <a href="<?= $_ENV['URL_ADM'] ?>matrix-by-user" class="btn btn-outline-secondary btn-sm">

            <i class="fas fa-table me-1"></i>Matriz por Colaborador (LNT)

        </a>

        <a href="<?= $_ENV['URL_ADM'] ?>list-training-status" class="btn btn-outline-info btn-sm">

            <i class="fas fa-list me-1"></i>Status de Treinamentos

        </a>

    </div>

</div>


