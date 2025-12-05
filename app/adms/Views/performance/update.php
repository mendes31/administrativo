<?php
use App\adms\Helpers\FormatHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Editar Avaliação de Desempenho</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-performance-reviews" class="text-decoration-none">Avaliações</a>
            </li>
            <li class="breadcrumb-item">Editar</li>
        </ol>
    </div>
    
    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <span><i class="fas fa-edit me-2"></i>Editar Avaliação #<?= $this->data['review']['id'] ?></span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form action="" method="POST" class="row g-3">
                <div class="col-md-3">
                    <label for="review_type" class="form-label">Tipo de Avaliação</label>
                    <select name="review_type" id="review_type" class="form-select">
                        <option value="90" <?= ($this->data['review']['review_type'] ?? '') == '90' ? 'selected' : '' ?>>90°</option>
                        <option value="180" <?= ($this->data['review']['review_type'] ?? '') == '180' ? 'selected' : '' ?>>180°</option>
                        <option value="360" <?= ($this->data['review']['review_type'] ?? '') == '360' ? 'selected' : '' ?>>360°</option>
                        <option value="annual" <?= ($this->data['review']['review_type'] ?? '') == 'annual' ? 'selected' : '' ?>>Anual</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="review_period_start" class="form-label">Período Início</label>
                    <input type="date" name="review_period_start" id="review_period_start" class="form-control" 
                           value="<?= $this->data['review']['review_period_start'] ?? '' ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="review_period_end" class="form-label">Período Fim</label>
                    <input type="date" name="review_period_end" id="review_period_end" class="form-control" 
                           value="<?= $this->data['review']['review_period_end'] ?? '' ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="review_date" class="form-label">Data da Avaliação</label>
                    <input type="date" name="review_date" id="review_date" class="form-control" 
                           value="<?= $this->data['review']['review_date'] ?? '' ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="draft" <?= ($this->data['review']['status'] ?? '') == 'draft' ? 'selected' : '' ?>>Rascunho</option>
                        <option value="in_progress" <?= ($this->data['review']['status'] ?? '') == 'in_progress' ? 'selected' : '' ?>>Em Andamento</option>
                        <option value="completed" <?= ($this->data['review']['status'] ?? '') == 'completed' ? 'selected' : '' ?>>Concluída</option>
                        <option value="cancelled" <?= ($this->data['review']['status'] ?? '') == 'cancelled' ? 'selected' : '' ?>>Cancelada</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="overall_score" class="form-label">Nota Geral (0-10)</label>
                    <input type="number" name="overall_score" id="overall_score" class="form-control" 
                           step="0.1" min="0" max="10" value="<?= $this->data['review']['overall_score'] ?? '' ?>">
                </div>
                
                <div class="col-md-12">
                    <label for="strengths" class="form-label">Pontos Fortes</label>
                    <textarea name="strengths" id="strengths" class="form-control" rows="3"><?= htmlspecialchars($this->data['review']['strengths'] ?? '') ?></textarea>
                </div>
                
                <div class="col-md-12">
                    <label for="improvements" class="form-label">Pontos de Melhoria</label>
                    <textarea name="improvements" id="improvements" class="form-control" rows="3"><?= htmlspecialchars($this->data['review']['improvements'] ?? '') ?></textarea>
                </div>
                
                <div class="col-md-12">
                    <label for="comments" class="form-label">Comentários Gerais</label>
                    <textarea name="comments" id="comments" class="form-control" rows="4"><?= htmlspecialchars($this->data['review']['comments'] ?? '') ?></textarea>
                </div>
                
                <div class="col-md-12">
                    <label for="employee_comments" class="form-label">Comentários do Colaborador</label>
                    <textarea name="employee_comments" id="employee_comments" class="form-control" rows="3"><?= htmlspecialchars($this->data['review']['employee_comments'] ?? '') ?></textarea>
                </div>
                
                <div class="col-12">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Salvar Alterações
                        </button>
                        <?php if (!empty($this->data['review']['id'])): ?>
                            <a href="<?php echo $_ENV['URL_ADM']; ?>view-performance-review/<?= $this->data['review']['id'] ?>" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </a>
                        <?php else: ?>
                            <a href="<?php echo $_ENV['URL_ADM']; ?>list-performance-reviews" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

