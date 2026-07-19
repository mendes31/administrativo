<?php
use App\adms\Helpers\CSRFHelper;

$cycle = $this->data['cycle'] ?? [];
$isClosed = ($cycle['status'] ?? '') === 'closed';
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Editar Ciclo de Desempenho</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-performance-cycles" class="text-decoration-none">Ciclos</a>
            </li>
            <li class="breadcrumb-item">Editar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <span><i class="fas fa-edit me-2"></i>Editar Ciclo</span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <?php if ($isClosed): ?>
                <div class="alert alert-warning">
                    Ciclo fechado: o status não pode voltar para rascunho ou aberto. Demais campos podem ser ajustados se necessário.
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_update_performance_cycle'); ?>">

                <div class="col-md-8">
                    <label for="name" class="form-label">Nome <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control" required
                           value="<?= htmlspecialchars((string) ($cycle['name'] ?? '')) ?>">
                </div>
                <div class="col-md-4">
                    <label for="year" class="form-label">Ano <span class="text-danger">*</span></label>
                    <input type="number" name="year" id="year" class="form-control" required min="2000" max="2100"
                           value="<?= htmlspecialchars((string) ($cycle['year'] ?? '')) ?>">
                </div>
                <div class="col-md-4">
                    <label for="period_start" class="form-label">Início <span class="text-danger">*</span></label>
                    <input type="date" name="period_start" id="period_start" class="form-control" required
                           value="<?= htmlspecialchars((string) ($cycle['period_start'] ?? '')) ?>">
                </div>
                <div class="col-md-4">
                    <label for="period_end" class="form-label">Fim <span class="text-danger">*</span></label>
                    <input type="date" name="period_end" id="period_end" class="form-control" required
                           value="<?= htmlspecialchars((string) ($cycle['period_end'] ?? '')) ?>">
                </div>
                <div class="col-md-4">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select" <?= $isClosed ? 'disabled' : '' ?>>
                        <option value="draft" <?= ($cycle['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Rascunho</option>
                        <option value="open" <?= ($cycle['status'] ?? '') === 'open' ? 'selected' : '' ?>>Aberto</option>
                        <option value="closed" <?= ($cycle['status'] ?? '') === 'closed' ? 'selected' : '' ?>>Fechado</option>
                    </select>
                    <?php if ($isClosed): ?>
                        <input type="hidden" name="status" value="closed">
                    <?php endif; ?>
                </div>
                <div class="col-12">
                    <label for="description" class="form-label">Descrição</label>
                    <textarea name="description" id="description" class="form-control" rows="3"><?= htmlspecialchars((string) ($cycle['description'] ?? '')) ?></textarea>
                </div>
                <div class="col-12">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Salvar
                        </button>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>view-performance-cycle/<?= (int) ($cycle['id'] ?? 0) ?>" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
