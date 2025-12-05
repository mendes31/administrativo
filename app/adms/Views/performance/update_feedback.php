<?php
use App\adms\Helpers\CSRFHelper;
$feedback = $this->data['feedback'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Editar Feedback de Desempenho</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-performance-feedbacks" class="text-decoration-none">Feedbacks</a>
            </li>
            <li class="breadcrumb-item">Editar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <span><i class="fas fa-comments me-2"></i>Editar Feedback de Desempenho</span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            
            <form action="" method="POST" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_update_performance_feedback'); ?>">
                
                <div class="col-md-6">
                    <label for="feedback_type" class="form-label">Tipo de Feedback <span class="text-danger">*</span></label>
                    <select name="feedback_type" id="feedback_type" class="form-select" required>
                        <option value="general" <?= ($feedback['feedback_type'] ?? '') == 'general' ? 'selected' : '' ?>>Geral</option>
                        <option value="performance" <?= ($feedback['feedback_type'] ?? '') == 'performance' ? 'selected' : '' ?>>Desempenho</option>
                        <option value="recognition" <?= ($feedback['feedback_type'] ?? '') == 'recognition' ? 'selected' : '' ?>>Reconhecimento</option>
                        <option value="improvement" <?= ($feedback['feedback_type'] ?? '') == 'improvement' ? 'selected' : '' ?>>Melhoria</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Opções</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_anonymous" id="is_anonymous" value="1" 
                               <?= ($feedback['is_anonymous'] ?? 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_anonymous">
                            Feedback Anônimo
                        </label>
                    </div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="is_public" id="is_public" value="1" 
                               <?= ($feedback['is_public'] ?? 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_public">
                            Feedback Público
                        </label>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label for="related_review_id" class="form-label">Avaliação Relacionada (Opcional)</label>
                    <select name="related_review_id" id="related_review_id" class="form-select">
                        <option value="">Nenhuma</option>
                        <?php foreach (($this->data['reviews'] ?? []) as $review): ?>
                            <option value="<?= $review['id'] ?>" <?= ($review['id'] == ($feedback['related_review_id'] ?? 0)) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($review['employee_name'] ?? '') ?> - 
                                <?= htmlspecialchars($review['review_type'] ?? '') ?>° - 
                                <?= date('d/m/Y', strtotime($review['review_date'] ?? '')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="related_goal_id" class="form-label">Meta Relacionada (Opcional)</label>
                    <select name="related_goal_id" id="related_goal_id" class="form-select">
                        <option value="">Nenhuma</option>
                        <?php foreach (($this->data['goals'] ?? []) as $goal): ?>
                            <option value="<?= $goal['id'] ?>" <?= ($goal['id'] == ($feedback['related_goal_id'] ?? 0)) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($goal['goal_title'] ?? '') ?> - 
                                <?= htmlspecialchars($goal['employee_name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-12">
                    <label for="feedback_text" class="form-label">Texto do Feedback <span class="text-danger">*</span></label>
                    <textarea name="feedback_text" id="feedback_text" class="form-control" rows="6" required><?= htmlspecialchars($feedback['feedback_text'] ?? '') ?></textarea>
                </div>
                
                <div class="col-12">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Salvar
                        </button>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>view-performance-feedback/<?= $feedback['id'] ?? '' ?>" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

