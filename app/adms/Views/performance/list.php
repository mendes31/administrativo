<?php
use App\adms\Helpers\FormatHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Avaliações de Desempenho</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Gestão de Pessoas</li>
            <li class="breadcrumb-item">Avaliações de Desempenho</li>
        </ol>
    </div>
    
    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2 flex-wrap">
            <span><i class="fas fa-clipboard-check me-2"></i>Listar Avaliações de Desempenho</span>
            <span class="ms-auto d-sm-flex flex-row flex-wrap gap-1">
                <?php if (in_array('CreatePerformanceReview', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>create-performance-review" class="btn btn-success btn-sm mb-1"><i class="fa-solid fa-plus"></i> Nova Avaliação</a>
                <?php } ?>
                <?php if (in_array('PerformanceDashboard', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>performance-dashboard" class="btn btn-primary btn-sm mb-1"><i class="fas fa-chart-line"></i> Dashboard</a>
                <?php } ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form method="GET" class="row g-2 mb-3 align-items-end">
                <div class="col-md-3">
                    <label for="employee_id" class="form-label mb-1">Colaborador</label>
                    <select name="employee_id" id="employee_id" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach (($this->data['employees'] ?? []) as $employee): ?>
                            <option value="<?= $employee['id'] ?>" <?= (($this->data['filters']['employee_id'] ?? '') == $employee['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($employee['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="review_type" class="form-label mb-1">Tipo</label>
                    <select name="review_type" id="review_type" class="form-select">
                        <option value="">Todos</option>
                        <option value="90" <?= (($this->data['filters']['review_type'] ?? '') === '90') ? 'selected' : '' ?>>90°</option>
                        <option value="180" <?= (($this->data['filters']['review_type'] ?? '') === '180') ? 'selected' : '' ?>>180°</option>
                        <option value="360" <?= (($this->data['filters']['review_type'] ?? '') === '360') ? 'selected' : '' ?>>360°</option>
                        <option value="annual" <?= (($this->data['filters']['review_type'] ?? '') === 'annual') ? 'selected' : '' ?>>Anual</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label mb-1">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Todos</option>
                        <option value="draft" <?= (($this->data['filters']['status'] ?? '') === 'draft') ? 'selected' : '' ?>>Rascunho</option>
                        <option value="in_progress" <?= (($this->data['filters']['status'] ?? '') === 'in_progress') ? 'selected' : '' ?>>Em Andamento</option>
                        <option value="completed" <?= (($this->data['filters']['status'] ?? '') === 'completed') ? 'selected' : '' ?>>Concluída</option>
                        <option value="cancelled" <?= (($this->data['filters']['status'] ?? '') === 'cancelled') ? 'selected' : '' ?>>Cancelada</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="search" class="form-label mb-1">Buscar</label>
                    <input type="text" name="search" id="search" class="form-control" placeholder="Nome ou comentário..." value="<?= htmlspecialchars($this->data['filters']['search'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100 mb-2"><i class="fas fa-search"></i> Filtrar</button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-performance-reviews?limpar=1" class="btn btn-secondary w-100"><i class="fas fa-times"></i> Limpar</a>
                </div>
            </form>

            <?php if (empty($this->data['reviews'])): ?>
                <div class="alert alert-info" role="alert">
                    <i class="fas fa-info-circle me-2"></i>Nenhuma avaliação encontrada.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Colaborador</th>
                                <th>Avaliador</th>
                                <th>Tipo</th>
                                <th>Período</th>
                                <th>Data</th>
                                <th>Nota</th>
                                <th>Status</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->data['reviews'] as $review): ?>
                                <tr>
                                    <td><?= $review['id'] ?></td>
                                    <td><?= htmlspecialchars($review['employee_name'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($review['reviewer_name'] ?? '') ?></td>
                                    <td>
                                        <span class="badge bg-info"><?= htmlspecialchars($review['review_type']) ?>°</span>
                                    </td>
                                    <td>
                                        <?= FormatHelper::formatDate($review['review_period_start'] ?? '') ?> a 
                                        <?= FormatHelper::formatDate($review['review_period_end'] ?? '') ?>
                                    </td>
                                    <td><?= FormatHelper::formatDate($review['review_date'] ?? '') ?></td>
                                    <td>
                                        <?php if ($review['overall_score']): ?>
                                            <span class="badge bg-primary"><?= number_format((float)$review['overall_score'], 1) ?>/10</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = match($review['status']) {
                                            'draft' => 'secondary',
                                            'in_progress' => 'warning',
                                            'completed' => 'success',
                                            'cancelled' => 'danger',
                                            default => 'secondary'
                                        };
                                        $statusLabel = match($review['status']) {
                                            'draft' => 'Rascunho',
                                            'in_progress' => 'Em Andamento',
                                            'completed' => 'Concluída',
                                            'cancelled' => 'Cancelada',
                                            default => $review['status']
                                        };
                                        ?>
                                        <span class="badge bg-<?= $statusClass ?>"><?= $statusLabel ?></span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <?php if (in_array('ViewPerformanceReview', $this->data['buttonPermission'] ?? [])) { ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>view-performance-review/<?= $review['id'] ?>" 
                                                   class="btn btn-sm btn-info" title="Visualizar">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            <?php } ?>
                                            <?php if (in_array('UpdatePerformanceReview', $this->data['buttonPermission'] ?? [])) { ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>update-performance-review/<?= $review['id'] ?>" 
                                                   class="btn btn-sm btn-warning" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php } ?>
                                            <?php if (in_array('DeletePerformanceReview', $this->data['buttonPermission'] ?? [])) { ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>delete-performance-review/<?= $review['id'] ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   title="Apagar"
                                                   onclick="return confirm('Tem certeza que deseja apagar esta avaliação?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php } ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (isset($this->data['pagination'])): ?>
                    <div class="mt-3">
                        <?= $this->data['pagination'] ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

