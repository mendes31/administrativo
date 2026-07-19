<?php
use App\adms\Helpers\FormatHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Ciclos de Desempenho</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Gestão de Pessoas</li>
            <li class="breadcrumb-item">Ciclos</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-calendar-alt me-2"></i>Ciclos de Desempenho</span>
            <div>
                <?php if (in_array('CreatePerformanceCycle', $this->data['buttonPermission'] ?? [], true)) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>create-performance-cycle" class="btn btn-sm btn-success">
                        <i class="fas fa-plus me-1"></i>Novo Ciclo
                    </a>
                <?php } ?>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="<?php echo $_ENV['URL_ADM']; ?>list-performance-cycles" class="row g-3 mb-4">
                <div class="col-md-2">
                    <label for="year" class="form-label small">Ano</label>
                    <input type="number" name="year" id="year" class="form-control form-control-sm"
                           value="<?= htmlspecialchars((string) ($this->data['filters']['year'] ?? '')) ?>"
                           placeholder="<?= date('Y') ?>">
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label small">Status</label>
                    <select name="status" id="status" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="draft" <?= ($this->data['filters']['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Rascunho</option>
                        <option value="open" <?= ($this->data['filters']['status'] ?? '') === 'open' ? 'selected' : '' ?>>Aberto</option>
                        <option value="closed" <?= ($this->data['filters']['status'] ?? '') === 'closed' ? 'selected' : '' ?>>Fechado</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="search" class="form-label small">Buscar</label>
                    <input type="text" name="search" id="search" class="form-control form-control-sm"
                           placeholder="Nome do ciclo..."
                           value="<?= htmlspecialchars($this->data['filters']['search'] ?? '') ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-search me-1"></i>Filtrar
                    </button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-performance-cycles?limpar=1" class="btn btn-secondary btn-sm">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </form>

            <?php if (empty($this->data['cycles'])): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Nenhum ciclo encontrado.
                </div>
            <?php else: ?>
                <?php
                $statusLabels = [
                    'draft' => ['label' => 'Rascunho', 'color' => 'secondary'],
                    'open' => ['label' => 'Aberto', 'color' => 'success'],
                    'closed' => ['label' => 'Fechado', 'color' => 'dark'],
                ];
                ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Nome</th>
                                <th>Ano</th>
                                <th>Período</th>
                                <th>Status</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->data['cycles'] as $cycle):
                                $st = $statusLabels[$cycle['status']] ?? ['label' => $cycle['status'], 'color' => 'secondary'];
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($cycle['name']) ?></td>
                                    <td><?= (int) $cycle['year'] ?></td>
                                    <td>
                                        <?= htmlspecialchars(FormatHelper::formatDate($cycle['period_start'] ?? '')) ?>
                                        —
                                        <?= htmlspecialchars(FormatHelper::formatDate($cycle['period_end'] ?? '')) ?>
                                    </td>
                                    <td><span class="badge bg-<?= $st['color'] ?>"><?= $st['label'] ?></span></td>
                                    <td class="text-center">
                                        <?php if (in_array('ViewPerformanceCycle', $this->data['buttonPermission'] ?? [], true)) { ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>view-performance-cycle/<?= (int) $cycle['id'] ?>"
                                               class="btn btn-sm btn-outline-primary" title="Visualizar">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        <?php } ?>
                                        <?php if (in_array('UpdatePerformanceCycle', $this->data['buttonPermission'] ?? [], true)) { ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>update-performance-cycle/<?= (int) $cycle['id'] ?>"
                                               class="btn btn-sm btn-outline-warning" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
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
