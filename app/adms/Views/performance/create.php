<?php
use App\adms\Helpers\FormatHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Criar Avaliação de Desempenho</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-performance-reviews" class="text-decoration-none">Avaliações</a>
            </li>
            <li class="breadcrumb-item">Criar</li>
        </ol>
    </div>
    
    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <span><i class="fas fa-clipboard-check me-2"></i>Nova Avaliação de Desempenho</span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form action="" method="POST" class="row g-3">
                <div class="col-md-6">
                    <label for="employee_id" class="form-label">Colaborador <span class="text-danger">*</span></label>
                    <select name="employee_id" id="employee_id" class="form-select" required>
                        <option value="">Selecione o colaborador...</option>
                        <?php foreach (($this->data['employees'] ?? []) as $employee): ?>
                            <option value="<?= $employee['id'] ?>"><?= htmlspecialchars($employee['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="reviewer_id" class="form-label">Avaliador <span class="text-danger">*</span></label>
                    <select name="reviewer_id" id="reviewer_id" class="form-select" required>
                        <option value="">Selecione o avaliador...</option>
                        <?php foreach (($this->data['employees'] ?? []) as $employee): ?>
                            <option value="<?= $employee['id'] ?>" <?= ($employee['id'] == ($_SESSION['user_id'] ?? 0)) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($employee['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="review_type" class="form-label">Tipo de Avaliação <span class="text-danger">*</span></label>
                    <select name="review_type" id="review_type" class="form-select" required>
                        <option value="90" selected>90°</option>
                        <option value="180">180°</option>
                        <option value="360">360°</option>
                        <option value="annual">Anual</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="review_period_start" class="form-label">Período Início <span class="text-danger">*</span></label>
                    <input type="date" name="review_period_start" id="review_period_start" class="form-control" 
                           value="<?= date('Y-m-d', strtotime('-3 months')) ?>" required>
                </div>
                
                <div class="col-md-3">
                    <label for="review_period_end" class="form-label">Período Fim <span class="text-danger">*</span></label>
                    <input type="date" name="review_period_end" id="review_period_end" class="form-control" 
                           value="<?= date('Y-m-d') ?>" required>
                </div>
                
                <div class="col-md-3">
                    <label for="review_date" class="form-label">Data da Avaliação <span class="text-danger">*</span></label>
                    <input type="date" name="review_date" id="review_date" class="form-control" 
                           value="<?= date('Y-m-d') ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label for="evaluation_id" class="form-label">Avaliação Relacionada (Opcional)</label>
                    <select name="evaluation_id" id="evaluation_id" class="form-select">
                        <option value="">Nenhuma</option>
                        <?php foreach (($this->data['evaluations'] ?? []) as $evaluation): ?>
                            <option value="<?= $evaluation['id'] ?>"><?= htmlspecialchars($evaluation['titulo'] ?? $evaluation['name'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="draft" selected>Rascunho</option>
                        <option value="in_progress">Em Andamento</option>
                        <option value="completed">Concluída</option>
                    </select>
                </div>
                
                <div class="col-12">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Salvar
                        </button>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>list-performance-reviews" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

