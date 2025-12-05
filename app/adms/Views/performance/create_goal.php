<?php
use App\adms\Helpers\CSRFHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Criar Meta de Desempenho</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-performance-goals" class="text-decoration-none">Metas</a>
            </li>
            <li class="breadcrumb-item">Criar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <span><i class="fas fa-bullseye me-2"></i>Nova Meta de Desempenho (OKR)</span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            
            <form action="" method="POST" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_create_performance_goal'); ?>">
                
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
                    <label for="goal_type" class="form-label">Tipo de Meta <span class="text-danger">*</span></label>
                    <select name="goal_type" id="goal_type" class="form-select" required>
                        <option value="individual" selected>Individual</option>
                        <option value="team">Equipe</option>
                        <option value="company">Empresa</option>
                    </select>
                </div>
                
                <div class="col-12">
                    <label for="goal_title" class="form-label">Título da Meta <span class="text-danger">*</span></label>
                    <input type="text" name="goal_title" id="goal_title" class="form-control" 
                           placeholder="Ex: Aumentar vendas em 20%" required>
                </div>
                
                <div class="col-12">
                    <label for="goal_description" class="form-label">Descrição</label>
                    <textarea name="goal_description" id="goal_description" class="form-control" rows="3" 
                              placeholder="Descreva a meta em detalhes..."></textarea>
                </div>
                
                <div class="col-md-4">
                    <label for="target_value" class="form-label">Valor Alvo</label>
                    <input type="number" name="target_value" id="target_value" class="form-control" 
                           step="0.01" placeholder="Ex: 100">
                </div>
                
                <div class="col-md-4">
                    <label for="current_value" class="form-label">Valor Atual</label>
                    <input type="number" name="current_value" id="current_value" class="form-control" 
                           step="0.01" value="0" placeholder="0">
                </div>
                
                <div class="col-md-4">
                    <label for="unit" class="form-label">Unidade de Medida</label>
                    <input type="text" name="unit" id="unit" class="form-control" 
                           placeholder="Ex: %, unidades, R$, etc.">
                </div>
                
                <div class="col-md-4">
                    <label for="deadline" class="form-label">Prazo</label>
                    <input type="date" name="deadline" id="deadline" class="form-control">
                </div>
                
                <div class="col-md-4">
                    <label for="weight" class="form-label">Peso (Importância)</label>
                    <input type="number" name="weight" id="weight" class="form-control" 
                           step="0.1" min="0.1" max="10" value="1.0" placeholder="1.0">
                    <small class="form-text text-muted">Peso da meta na avaliação (1.0 = padrão)</small>
                </div>
                
                <div class="col-md-4">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="pending" selected>Pendente</option>
                        <option value="in_progress">Em Andamento</option>
                        <option value="achieved">Alcançada</option>
                        <option value="failed">Não Alcançada</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label for="progress_percentage" class="form-label">Progresso (%)</label>
                    <input type="number" name="progress_percentage" id="progress_percentage" class="form-control" 
                           min="0" max="100" value="0" placeholder="0">
                    <small class="form-text text-muted">Será calculado automaticamente se valores alvo/atual forem informados</small>
                </div>
                
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Dica:</strong> Se você informar o Valor Alvo e Valor Atual, o progresso será calculado automaticamente.
                    </div>
                </div>
                
                <div class="col-12">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Salvar
                        </button>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>list-performance-goals" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const targetValue = document.getElementById('target_value');
    const currentValue = document.getElementById('current_value');
    const progressPercentage = document.getElementById('progress_percentage');
    
    function calculateProgress() {
        if (targetValue.value && currentValue.value) {
            const target = parseFloat(targetValue.value);
            const current = parseFloat(currentValue.value);
            if (target > 0) {
                const progress = Math.min(100, Math.max(0, Math.round((current / target) * 100)));
                progressPercentage.value = progress;
            }
        }
    }
    
    targetValue.addEventListener('input', calculateProgress);
    currentValue.addEventListener('input', calculateProgress);
});
</script>

