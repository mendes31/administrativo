<?php
use App\adms\Helpers\FormatHelper;

$boxLabels = [
    1 => ['title' => 'Reposicionar', 'color' => 'danger', 'icon' => 'fa-exclamation-triangle'],
    2 => ['title' => 'Manter', 'color' => 'warning', 'icon' => 'fa-minus-circle'],
    3 => ['title' => 'Desenvolver', 'color' => 'success', 'icon' => 'fa-arrow-up'],
    4 => ['title' => 'Monitorar', 'color' => 'danger', 'icon' => 'fa-eye'],
    5 => ['title' => 'Manter', 'color' => 'warning', 'icon' => 'fa-check'],
    6 => ['title' => 'Desenvolver', 'color' => 'success', 'icon' => 'fa-arrow-up'],
    7 => ['title' => 'Desenvolver', 'color' => 'info', 'icon' => 'fa-rocket'],
    8 => ['title' => 'Promover', 'color' => 'primary', 'icon' => 'fa-star'],
    9 => ['title' => 'Estrela', 'color' => 'success', 'icon' => 'fa-crown']
];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Matriz 9BOX</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Gestão de Pessoas</li>
            <li class="breadcrumb-item">Matriz 9BOX</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <!-- Filtros -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
            <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="<?php echo $_ENV['URL_ADM']; ?>nine-box-matrix" class="row g-3">
                <div class="col-md-3">
                    <label for="performance_cycle_id" class="form-label small">Ciclo</label>
                    <select name="performance_cycle_id" id="performance_cycle_id" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($this->data['cycles'] ?? [] as $cycle): ?>
                            <option value="<?= (int) $cycle['id'] ?>" <?= ((int) ($this->data['filters']['performance_cycle_id'] ?? 0) === (int) $cycle['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cycle['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="department_id" class="form-label small">Departamento</label>
                    <select name="department_id" id="department_id" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($this->data['departments'] ?? [] as $dept): ?>
                            <option value="<?= $dept['id'] ?>" <?= ($this->data['filters']['department_id'] ?? '') == $dept['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="position_id" class="form-label small">Cargo</label>
                    <select name="position_id" id="position_id" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($this->data['positions'] ?? [] as $pos): ?>
                            <option value="<?= $pos['id'] ?>" <?= ($this->data['filters']['position_id'] ?? '') == $pos['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pos['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="period_start" class="form-label small">Período Início</label>
                    <input type="date" name="period_start" id="period_start" class="form-control form-control-sm" 
                           value="<?= $this->data['filters']['period_start'] ?? '' ?>">
                </div>
                <div class="col-md-2">
                    <label for="period_end" class="form-label small">Período Fim</label>
                    <input type="date" name="period_end" id="period_end" class="form-control form-control-sm" 
                           value="<?= $this->data['filters']['period_end'] ?? '' ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-search me-1"></i>Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Estatísticas -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0 fw-bold"><?= $this->data['total'] ?? 0 ?></h3>
                            <p class="mb-0 small opacity-75">Total Mapeado</p>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <div class="card-body text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0 fw-bold"><?= $this->data['box_stats'][9] ?? 0 ?></h3>
                            <p class="mb-0 small opacity-75">Estrelas (Box 9)</p>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fas fa-crown"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <div class="card-body text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0 fw-bold"><?= ($this->data['box_stats'][8] ?? 0) + ($this->data['box_stats'][7] ?? 0) ?></h3>
                            <p class="mb-0 small opacity-75">Alto Potencial</p>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fas fa-rocket"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                <div class="card-body text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0 fw-bold"><?= ($this->data['box_stats'][1] ?? 0) + ($this->data['box_stats'][4] ?? 0) ?></h3>
                            <p class="mb-0 small opacity-75">Atenção (Box 1, 4)</p>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Matriz 9BOX -->
    <div class="card mb-4 border-0 shadow">
        <div class="card-header bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-th-large me-2"></i>Matriz 9BOX - Potencial vs Desempenho</h6>
                <div>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>export-nine-box-matrix-excel<?= !empty($this->data['filters']) ? '?' . http_build_query($this->data['filters']) : '' ?>" 
                       class="btn btn-sm btn-success me-2" title="Exportar para Excel">
                        <i class="fas fa-file-excel me-1"></i>Excel
                    </a>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>export-nine-box-matrix-pdf<?= !empty($this->data['filters']) ? '?' . http_build_query($this->data['filters']) : '' ?>" 
                       class="btn btn-sm btn-danger me-2" title="Exportar para PDF">
                        <i class="fas fa-file-pdf me-1"></i>PDF
                    </a>
                    <?php if (in_array('ListPerformanceReviews', $this->data['buttonPermission'] ?? [])) { ?>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>list-performance-reviews" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Voltar
                        </a>
                    <?php } ?>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="alert alert-info border-0 mb-4">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Como funciona:</strong> A Matriz 9BOX avalia colaboradores baseado em <strong>Potencial</strong> (eixo Y) e <strong>Desempenho</strong> (eixo X). 
                Clique em um colaborador para ver detalhes.
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0" style="table-layout: fixed;">
                    <thead>
                        <tr>
                            <th style="width: 12%;" class="text-center bg-light">
                                <strong>Potencial</strong>
                            </th>
                            <th class="text-center" style="width: 29.3%; background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%); color: white;">
                                <strong><i class="fas fa-arrow-down me-1"></i>Baixo Desempenho</strong>
                                <br><small>(0-6)</small>
                            </th>
                            <th class="text-center" style="width: 29.3%; background: linear-gradient(135deg, #feca57 0%, #ff9ff3 100%); color: white;">
                                <strong><i class="fas fa-minus me-1"></i>Desempenho Médio</strong>
                                <br><small>(6-8)</small>
                            </th>
                            <th class="text-center" style="width: 29.3%; background: linear-gradient(135deg, #48dbfb 0%, #0abde3 100%); color: white;">
                                <strong><i class="fas fa-arrow-up me-1"></i>Alto Desempenho</strong>
                                <br><small>(8-10)</small>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $rows = [
                            ['level' => 'high', 'label' => 'Alto Potencial', 'boxes' => [7, 8, 9], 'bg' => 'bg-success bg-opacity-10'],
                            ['level' => 'medium', 'label' => 'Potencial Médio', 'boxes' => [4, 5, 6], 'bg' => 'bg-warning bg-opacity-10'],
                            ['level' => 'low', 'label' => 'Baixo Potencial', 'boxes' => [1, 2, 3], 'bg' => 'bg-danger bg-opacity-10']
                        ];
                        
                        foreach ($rows as $row):
                        ?>
                            <tr>
                                <td class="text-center fw-bold <?= $row['bg'] ?>" style="height: 200px; vertical-align: middle;">
                                    <div class="d-flex flex-column align-items-center justify-content-center h-100">
                                        <i class="fas fa-<?= $row['level'] === 'high' ? 'arrow-up' : ($row['level'] === 'medium' ? 'minus' : 'arrow-down') ?> fa-2x mb-2"></i>
                                        <strong><?= $row['label'] ?></strong>
                                    </div>
                                </td>
                                <?php foreach ($row['boxes'] as $boxNum): 
                                    $boxData = $this->data['boxes'][$boxNum] ?? [];
                                    $boxLabel = $boxLabels[$boxNum];
                                    $count = count($boxData);
                                ?>
                                    <td class="text-center p-2" style="height: 200px; vertical-align: top; position: relative;">
                                        <div class="d-flex flex-column h-100">
                                            <div class="mb-2">
                                                <span class="badge bg-<?= $boxLabel['color'] ?> text-white px-3 py-2">
                                                    <i class="fas <?= $boxLabel['icon'] ?> me-1"></i>
                                                    Box <?= $boxNum ?>: <?= $boxLabel['title'] ?>
                                                </span>
                                                <div class="mt-1">
                                                    <small class="text-muted"><?= $count ?> colaborador(es)</small>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 overflow-auto" style="max-height: 120px;">
                                                <?php if (empty($boxData)): ?>
                                                    <div class="text-muted small">Nenhum colaborador</div>
                                                <?php else: ?>
                                                    <?php foreach ($boxData as $employee): ?>
                                                        <div class="employee-badge mb-1" 
                                                             data-bs-toggle="tooltip" 
                                                             data-bs-placement="top"
                                                             title="Clique para ver detalhes - Desempenho: <?= number_format($employee['performance_score'], 1) ?>/10 - Potencial: <?= number_format($employee['potential_score'], 1) ?>/10">
                                                            <a href="<?php echo $_ENV['URL_ADM']; ?>view-user/<?= $employee['employee_id'] ?>" 
                                                               class="badge bg-light text-dark border text-decoration-none employee-link" 
                                                               style="cursor: pointer; font-size: 0.75rem; display: inline-block;"
                                                               title="Ver detalhes de <?= htmlspecialchars($employee['employee_name']) ?>">
                                                                <i class="fas fa-user me-1"></i>
                                                                <?= htmlspecialchars(mb_substr($employee['employee_name'], 0, 20)) ?>
                                                                <?= mb_strlen($employee['employee_name']) > 20 ? '...' : '' ?>
                                                            </a>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Legenda -->
            <div class="mt-4">
                <h6 class="mb-3">Legenda de Ações Recomendadas:</h6>
                <div class="row g-2">
                    <?php foreach ($boxLabels as $boxNum => $label): ?>
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-2">
                                    <span class="badge bg-<?= $label['color'] ?> me-2">
                                        <i class="fas <?= $label['icon'] ?>"></i> Box <?= $boxNum ?>
                                    </span>
                                    <strong><?= $label['title'] ?></strong>
                                    <div class="small text-muted mt-1">
                                        <?php
                                        $descriptions = [
                                            1 => 'Reposicionar em função mais adequada ou iniciar processo de desligamento',
                                            2 => 'Manter na função atual, monitorar desempenho',
                                            3 => 'Investir em desenvolvimento, pode crescer',
                                            4 => 'Monitorar de perto, definir plano de ação',
                                            5 => 'Manter na função, está adequado',
                                            6 => 'Desenvolver para crescimento',
                                            7 => 'Alto potencial, desenvolver competências',
                                            8 => 'Promover para posição de maior responsabilidade',
                                            9 => 'Estrela: promover e reter, alto valor estratégico'
                                        ];
                                        echo $descriptions[$boxNum] ?? '';
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Inicializar tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<style>
.employee-badge:hover .employee-link {
    background-color: #e9ecef !important;
    transform: scale(1.05);
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.employee-link:hover {
    background-color: #0d6efd !important;
    color: white !important;
    border-color: #0d6efd !important;
}

.table tbody td {
    border: 2px solid #dee2e6;
}

.table thead th {
    border: 2px solid #dee2e6;
    font-weight: 600;
}
</style>
