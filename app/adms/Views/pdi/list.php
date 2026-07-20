<?php
use App\adms\Helpers\FormatHelper;

$statusLabels = [
    'draft' => ['label' => 'Rascunho', 'color' => 'secondary'],
    'active' => ['label' => 'Ativo', 'color' => 'success'],
    'completed' => ['label' => 'Concluído', 'color' => 'primary'],
    'cancelled' => ['label' => 'Cancelado', 'color' => 'dark'],
];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Planos de Desenvolvimento (PDI)</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item">Gestão de Pessoas</li>
            <li class="breadcrumb-item">PDI</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-user-graduate me-2"></i>PDIs</span>
            <div>
                <?php if (in_array('CreatePdiPlan', $this->data['buttonPermission'] ?? [], true)) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>create-pdi-plan" class="btn btn-sm btn-success">
                        <i class="fas fa-plus me-1"></i>Novo PDI
                    </a>
                <?php } ?>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="<?php echo $_ENV['URL_ADM']; ?>list-pdi-plans" class="row g-3 mb-4">
                <div class="col-md-3">
                    <label for="user_id" class="form-label small">Colaborador</label>
                    <select name="user_id" id="user_id" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($this->data['employees'] ?? [] as $emp): ?>
                            <option value="<?= (int) $emp['id'] ?>" <?= ((int) ($this->data['filters']['user_id'] ?? 0) === (int) $emp['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($emp['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label small">Status</label>
                    <select name="status" id="status" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($statusLabels as $code => $meta): ?>
                            <option value="<?= $code ?>" <?= ($this->data['filters']['status'] ?? '') === $code ? 'selected' : '' ?>>
                                <?= $meta['label'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="performance_cycle_id" class="form-label small">Ciclo</label>
                    <select name="performance_cycle_id" id="performance_cycle_id" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($this->data['cycles'] ?? [] as $cycle): ?>
                            <option value="<?= (int) $cycle['id'] ?>"
                                <?= ((int) ($this->data['filters']['performance_cycle_id'] ?? 0) === (int) $cycle['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cycle['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="search" class="form-label small">Buscar</label>
                    <input type="text" name="search" id="search" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($this->data['filters']['search'] ?? '') ?>" placeholder="Título...">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-pdi-plans?limpar=1" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i></a>
                </div>
            </form>

            <?php if (empty($this->data['plans'])): ?>
                <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Nenhum PDI encontrado.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Título</th>
                                <th>Colaborador</th>
                                <th>Ciclo</th>
                                <th>Período</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->data['plans'] as $plan):
                                $st = $statusLabels[$plan['status'] ?? ''] ?? ['label' => $plan['status'] ?? '-', 'color' => 'secondary'];
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($plan['title'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($plan['user_name'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($plan['cycle_name'] ?? '—') ?></td>
                                    <td>
                                        <?= htmlspecialchars(FormatHelper::formatDate($plan['period_start'] ?? '')) ?>
                                        —
                                        <?= htmlspecialchars(FormatHelper::formatDate($plan['period_end'] ?? '')) ?>
                                    </td>
                                    <td><span class="badge bg-<?= $st['color'] ?>"><?= $st['label'] ?></span></td>
                                    <td class="text-end text-nowrap">
                                        <?php if (in_array('ViewPdiPlan', $this->data['buttonPermission'] ?? [], true)) { ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>view-pdi-plan/<?= (int) $plan['id'] ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                                        <?php } ?>
                                        <?php if (in_array('UpdatePdiPlan', $this->data['buttonPermission'] ?? [], true)) { ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>update-pdi-plan/<?= (int) $plan['id'] ?>" class="btn btn-sm btn-outline-warning">Editar</a>
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
