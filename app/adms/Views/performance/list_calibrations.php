<?php

$statusLabels = [
    'draft' => ['label' => 'Rascunho', 'color' => 'secondary'],
    'open' => ['label' => 'Aberta', 'color' => 'success'],
    'locked' => ['label' => 'Travada', 'color' => 'dark'],
];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Calibrações de Desempenho</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item">Gestão de Pessoas</li>
            <li class="breadcrumb-item">Calibração</li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-balance-scale me-2"></i>Sessões de Calibração</span>
            <?php if (in_array('CreatePerformanceCalibration', $this->data['buttonPermission'] ?? [], true)) { ?>
                <a href="<?php echo $_ENV['URL_ADM']; ?>create-performance-calibration" class="btn btn-sm btn-success">
                    <i class="fas fa-plus me-1"></i>Nova Calibração
                </a>
            <?php } ?>
        </div>
        <div class="card-body">
            <form method="GET" action="<?php echo $_ENV['URL_ADM']; ?>list-performance-calibrations" class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label small">Ciclo</label>
                    <select name="performance_cycle_id" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach (($this->data['cycles'] ?? []) as $cycle): ?>
                            <option value="<?= (int) $cycle['id'] ?>" <?= ((int) ($this->data['filters']['performance_cycle_id'] ?? 0) === (int) $cycle['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cycle['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="draft" <?= ($this->data['filters']['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Rascunho</option>
                        <option value="open" <?= ($this->data['filters']['status'] ?? '') === 'open' ? 'selected' : '' ?>>Aberta</option>
                        <option value="locked" <?= ($this->data['filters']['status'] ?? '') === 'locked' ? 'selected' : '' ?>>Travada</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Buscar</label>
                    <input type="text" name="search" class="form-control form-control-sm" value="<?= htmlspecialchars($this->data['filters']['search'] ?? '') ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i>Filtrar</button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-performance-calibrations?limpar=1" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i></a>
                </div>
            </form>
            <?php if (empty($this->data['calibrations'])): ?>
                <div class="alert alert-info">Nenhuma calibração encontrada. Crie um ciclo e depois abra a sessão.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Ciclo</th>
                                <th>Ano</th>
                                <th>Status</th>
                                <th>Criado por</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->data['calibrations'] as $cal):
                                $st = $statusLabels[$cal['status']] ?? ['label' => $cal['status'], 'color' => 'secondary'];
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($cal['cycle_name'] ?? '') ?></td>
                                    <td><?= (int) ($cal['cycle_year'] ?? 0) ?></td>
                                    <td><span class="badge bg-<?= $st['color'] ?>"><?= $st['label'] ?></span></td>
                                    <td><?= htmlspecialchars($cal['created_by_name'] ?? '-') ?></td>
                                    <td class="text-center">
                                        <?php if (in_array('ViewPerformanceCalibration', $this->data['buttonPermission'] ?? [], true)) { ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>view-performance-calibration/<?= (int) $cal['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                                        <?php } ?>
                                        <?php if (in_array('UpdatePerformanceCalibration', $this->data['buttonPermission'] ?? [], true) && ($cal['status'] ?? '') !== 'locked') { ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>update-performance-calibration/<?= (int) $cal['id'] ?>" class="btn btn-sm btn-outline-warning"><i class="fas fa-edit"></i></a>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?= $this->data['pagination'] ?? '' ?>
            <?php endif; ?>
        </div>
    </div>
</div>
