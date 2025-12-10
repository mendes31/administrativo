<?php
use App\adms\Helpers\FormatHelper;
use App\adms\Helpers\CSRFHelper;

$review = $this->data['review'] ?? [];
$reviewCompetencies = $this->data['review_competencies'] ?? [];
$availableCompetencies = $this->data['available_competencies'] ?? [];
$reviewGoals = $this->data['review_goals'] ?? [];
$availableGoals = $this->data['available_goals'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Registrar Resultados - Avaliação #<?= $review['id'] ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-performance-reviews" class="text-decoration-none">Avaliações</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>view-performance-review/<?= $review['id'] ?>" class="text-decoration-none">Visualizar</a>
            </li>
            <li class="breadcrumb-item">Registrar Resultados</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <span><i class="fas fa-clipboard-list me-2"></i>Registrar Resultados da Avaliação</span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            
            <!-- Informações da Avaliação -->
            <div class="alert alert-info mb-4">
                <div class="row">
                    <div class="col-md-6">
                        <strong>Colaborador:</strong> <?= htmlspecialchars($review['employee_name'] ?? '') ?><br>
                        <strong>Avaliador:</strong> <?= htmlspecialchars($review['reviewer_name'] ?? '') ?><br>
                        <strong>Tipo:</strong> <?= htmlspecialchars($review['review_type']) ?>°
                    </div>
                    <div class="col-md-6">
                        <strong>Período:</strong> <?= FormatHelper::formatDate($review['review_period_start'] ?? '') ?> a <?= FormatHelper::formatDate($review['review_period_end'] ?? '') ?><br>
                        <strong>Status Atual:</strong> 
                        <?php
                        $statusLabel = match($review['status']) {
                            'draft' => 'Rascunho',
                            'in_progress' => 'Em Andamento',
                            'completed' => 'Concluída',
                            default => $review['status']
                        };
                        ?>
                        <span class="badge bg-<?= $review['status'] === 'completed' ? 'success' : 'warning' ?>"><?= $statusLabel ?></span>
                    </div>
                </div>
            </div>

            <form action="" method="POST" id="resultsForm">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_record_review_results'); ?>">
                
                <!-- Seção: Nota Geral e Potencial -->
                <div class="card mb-4 border-primary">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-star me-2"></i>Notas Gerais</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="overall_score" class="form-label">Nota de Desempenho (0-10) <span class="text-danger">*</span></label>
                                <input type="number" name="overall_score" id="overall_score" class="form-control" 
                                       step="0.1" min="0" max="10" 
                                       value="<?= htmlspecialchars($review['overall_score'] ?? '') ?>" required>
                                <small class="form-text text-muted">Nota geral do colaborador no período avaliado</small>
                            </div>
                            <div class="col-md-4">
                                <label for="potential_score" class="form-label">Nota de Potencial (0-10)</label>
                                <input type="number" name="potential_score" id="potential_score" class="form-control" 
                                       step="0.1" min="0" max="10" 
                                       value="<?= htmlspecialchars($review['potential_score'] ?? '') ?>">
                                <small class="form-text text-muted">Usado para Matriz 9BOX</small>
                            </div>
                            <div class="col-md-4">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="draft" <?= ($review['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Rascunho</option>
                                    <option value="in_progress" <?= ($review['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>Em Andamento</option>
                                    <option value="completed" <?= ($review['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Concluída</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seção: Competências Avaliadas -->
                <div class="card mb-4 border-info">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-star me-2"></i>Competências Avaliadas</h5>
                        <button type="button" class="btn btn-sm btn-light" onclick="addNewCompetency()">
                            <i class="fas fa-plus me-1"></i>Adicionar Competência
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($reviewCompetencies)): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-info-circle me-2"></i>
                                Nenhuma competência adicionada ainda. Clique em "Adicionar Competência" para começar.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Competência</th>
                                            <th>Tipo</th>
                                            <th>Nível Atual</th>
                                            <th>Nível Alvo</th>
                                            <th>Nível Avaliado <span class="text-danger">*</span></th>
                                            <th>Comentários</th>
                                        </tr>
                                    </thead>
                                    <tbody id="competenciesTableBody">
                                        <?php foreach ($reviewCompetencies as $comp): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($comp['competency_name'] ?? '') ?></strong>
                                                    <input type="hidden" name="competencies[<?= $comp['id'] ?>][id]" value="<?= $comp['id'] ?>">
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary"><?= htmlspecialchars($comp['competency_type'] ?? '') ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?= $comp['current_level'] ?? 1 ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-warning"><?= $comp['target_level'] ?? 3 ?></span>
                                                </td>
                                                <td>
                                                    <select name="competencies[<?= $comp['id'] ?>][assessed_level]" class="form-select" required>
                                                        <option value="">Selecione...</option>
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <option value="<?= $i ?>" 
                                                                    <?= ($comp['assessed_level'] ?? null) == $i ? 'selected' : '' ?>
                                                                    data-level-desc="<?= htmlspecialchars($comp['level_' . $i . '_description'] ?? 'Nível ' . $i) ?>">
                                                                Nível <?= $i ?>
                                                            </option>
                                                        <?php endfor; ?>
                                                    </select>
                                                </td>
                                                <td>
                                                    <textarea name="competencies[<?= $comp['id'] ?>][comments]" class="form-control form-control-sm" rows="2" 
                                                              placeholder="Comentários..."><?= htmlspecialchars($comp['comments'] ?? '') ?></textarea>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                        <!-- Formulário para adicionar nova competência -->
                        <div id="newCompetencyForm" style="display: none;" class="mt-3 p-3 bg-light rounded">
                            <h6>Adicionar Nova Competência</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Competência <span class="text-danger">*</span></label>
                                    <select name="new_competencies[0][competency_id]" class="form-select" id="new_competency_select">
                                        <option value="">Selecione...</option>
                                        <?php foreach ($availableCompetencies as $comp): ?>
                                            <option value="<?= $comp['id'] ?>" 
                                                    data-level-1="<?= htmlspecialchars($comp['level_1_description'] ?? '') ?>"
                                                    data-level-2="<?= htmlspecialchars($comp['level_2_description'] ?? '') ?>"
                                                    data-level-3="<?= htmlspecialchars($comp['level_3_description'] ?? '') ?>"
                                                    data-level-4="<?= htmlspecialchars($comp['level_4_description'] ?? '') ?>"
                                                    data-level-5="<?= htmlspecialchars($comp['level_5_description'] ?? '') ?>">
                                                <?= htmlspecialchars($comp['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Nível Atual</label>
                                    <input type="number" name="new_competencies[0][current_level]" class="form-control" min="1" max="5" value="1">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Nível Alvo</label>
                                    <input type="number" name="new_competencies[0][target_level]" class="form-control" min="1" max="5" value="3">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Nível Avaliado</label>
                                    <select name="new_competencies[0][assessed_level]" class="form-select" id="new_assessed_level">
                                        <option value="">Selecione...</option>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <option value="<?= $i ?>">Nível <?= $i ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <div>
                                        <button type="button" class="btn btn-success btn-sm" onclick="saveNewCompetency()">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="cancelNewCompetency()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Comentários</label>
                                    <textarea name="new_competencies[0][comments]" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seção: Metas -->
                <div class="card mb-4 border-success">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-bullseye me-2"></i>Metas</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($reviewGoals)): ?>
                            <div class="table-responsive mb-3">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Meta</th>
                                            <th>Tipo</th>
                                            <th>Progresso</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reviewGoals as $goal): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($goal['goal_title'] ?? '') ?></strong></td>
                                                <td><span class="badge bg-secondary"><?= htmlspecialchars($goal['goal_type'] ?? '') ?></span></td>
                                                <td>
                                                    <div class="progress">
                                                        <div class="progress-bar" role="progressbar" 
                                                             style="width: <?= $goal['progress_percentage'] ?? 0 ?>%">
                                                            <?= $goal['progress_percentage'] ?? 0 ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php
                                                    $goalStatusClass = match($goal['status']) {
                                                        'pending' => 'secondary',
                                                        'in_progress' => 'warning',
                                                        'achieved' => 'success',
                                                        'failed' => 'danger',
                                                        default => 'secondary'
                                                    };
                                                    ?>
                                                    <span class="badge bg-<?= $goalStatusClass ?>"><?= htmlspecialchars($goal['status'] ?? '') ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($availableGoals)): ?>
                            <div class="mb-3">
                                <label class="form-label">Vincular Metas Existentes</label>
                                <select name="link_goals[]" class="form-select" multiple size="5">
                                    <?php foreach ($availableGoals as $goal): ?>
                                        <option value="<?= $goal['id'] ?>">
                                            <?= htmlspecialchars($goal['goal_title']) ?> 
                                            (<?= $goal['progress_percentage'] ?? 0 ?>% - <?= htmlspecialchars($goal['status'] ?? '') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">Segure Ctrl (ou Cmd no Mac) para selecionar múltiplas metas</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Seção: Comentários e Observações -->
                <div class="card mb-4 border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-comments me-2"></i>Comentários e Observações</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="strengths" class="form-label">Pontos Fortes</label>
                                <textarea name="strengths" id="strengths" class="form-control" rows="3" 
                                          placeholder="Liste os principais pontos fortes do colaborador..."><?= htmlspecialchars($review['strengths'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-12">
                                <label for="improvements" class="form-label">Pontos de Melhoria</label>
                                <textarea name="improvements" id="improvements" class="form-control" rows="3" 
                                          placeholder="Liste os pontos que precisam ser melhorados..."><?= htmlspecialchars($review['improvements'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-12">
                                <label for="comments" class="form-label">Comentários Gerais</label>
                                <textarea name="comments" id="comments" class="form-control" rows="4" 
                                          placeholder="Comentários gerais sobre a avaliação..."><?= htmlspecialchars($review['comments'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-12">
                                <label for="employee_comments" class="form-label">Comentários do Colaborador</label>
                                <textarea name="employee_comments" id="employee_comments" class="form-control" rows="3" 
                                          placeholder="Comentários do próprio colaborador sobre a avaliação..."><?= htmlspecialchars($review['employee_comments'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-save me-2"></i>Salvar Resultados
                    </button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>view-performance-review/<?= $review['id'] ?>" class="btn btn-secondary btn-lg">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let competencyCounter = 0;

function addNewCompetency() {
    document.getElementById('newCompetencyForm').style.display = 'block';
}

function cancelNewCompetency() {
    document.getElementById('newCompetencyForm').style.display = 'none';
    document.getElementById('new_competency_select').value = '';
    document.getElementById('new_assessed_level').value = '';
}

function saveNewCompetency() {
    const select = document.getElementById('new_competency_select');
    const assessedLevel = document.getElementById('new_assessed_level');
    
    if (!select.value) {
        alert('Selecione uma competência!');
        return;
    }
    
    if (!assessedLevel.value) {
        alert('Selecione o nível avaliado!');
        return;
    }
    
    // O formulário será submetido normalmente, o PHP processará os dados
    document.getElementById('resultsForm').submit();
}

// Atualizar descrição do nível quando selecionado
document.addEventListener('DOMContentLoaded', function() {
    const selects = document.querySelectorAll('select[name*="[assessed_level]"]');
    selects.forEach(select => {
        select.addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            const desc = option.getAttribute('data-level-desc');
            if (desc) {
                // Pode adicionar um tooltip ou exibição da descrição
                console.log('Nível selecionado:', desc);
            }
        });
    });
});
</script>

