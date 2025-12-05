<?php
use App\adms\Helpers\FormatHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Editar Competência</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-competencies" class="text-decoration-none">Competências</a>
            </li>
            <li class="breadcrumb-item">Editar</li>
        </ol>
    </div>
    
    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <span><i class="fas fa-edit me-2"></i>Editar Competência #<?= $this->data['competency']['id'] ?></span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form action="" method="POST" class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label">Nome <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control" required 
                           value="<?= htmlspecialchars($this->data['competency']['name'] ?? '') ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="competency_type" class="form-label">Tipo <span class="text-danger">*</span></label>
                    <select name="competency_type" id="competency_type" class="form-select" required>
                        <option value="">Selecione...</option>
                        <option value="technical" <?= ($this->data['competency']['competency_type'] ?? '') == 'technical' ? 'selected' : '' ?>>Técnica</option>
                        <option value="behavioral" <?= ($this->data['competency']['competency_type'] ?? '') == 'behavioral' ? 'selected' : '' ?>>Comportamental</option>
                        <option value="leadership" <?= ($this->data['competency']['competency_type'] ?? '') == 'leadership' ? 'selected' : '' ?>>Liderança</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="category" class="form-label">Categoria</label>
                    <input type="text" name="category" id="category" class="form-control" 
                           value="<?= htmlspecialchars($this->data['competency']['category'] ?? '') ?>">
                </div>
                
                <div class="col-md-12">
                    <label for="description" class="form-label">Descrição</label>
                    <textarea name="description" id="description" class="form-control" rows="4"><?= htmlspecialchars($this->data['competency']['description'] ?? '') ?></textarea>
                </div>
                
                <div class="col-md-12">
                    <hr>
                    <h5 class="mb-3"><i class="fas fa-layer-group me-2"></i>Níveis de Proficiência</h5>
                    <p class="text-muted small mb-3">
                        Defina a descrição de cada nível de proficiência (1 a 5). 
                        Quanto maior o nível, maior a proficiência esperada.
                    </p>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="level_1_description" class="form-label">
                                <span class="badge bg-danger me-2">Nível 1</span> Iniciante
                            </label>
                            <textarea name="level_1_description" id="level_1_description" 
                                      class="form-control" rows="2" 
                                      placeholder="Ex: Conhecimento básico, precisa de supervisão constante"><?= htmlspecialchars($this->data['competency']['level_1_description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="level_2_description" class="form-label">
                                <span class="badge bg-warning me-2">Nível 2</span> Básico
                            </label>
                            <textarea name="level_2_description" id="level_2_description" 
                                      class="form-control" rows="2" 
                                      placeholder="Ex: Conhecimento básico, pode trabalhar com supervisão ocasional"><?= htmlspecialchars($this->data['competency']['level_2_description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="level_3_description" class="form-label">
                                <span class="badge bg-info me-2">Nível 3</span> Intermediário
                            </label>
                            <textarea name="level_3_description" id="level_3_description" 
                                      class="form-control" rows="2" 
                                      placeholder="Ex: Conhecimento sólido, trabalha de forma independente"><?= htmlspecialchars($this->data['competency']['level_3_description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="level_4_description" class="form-label">
                                <span class="badge bg-primary me-2">Nível 4</span> Avançado
                            </label>
                            <textarea name="level_4_description" id="level_4_description" 
                                      class="form-control" rows="2" 
                                      placeholder="Ex: Conhecimento avançado, pode orientar outros"><?= htmlspecialchars($this->data['competency']['level_4_description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="col-md-12">
                            <label for="level_5_description" class="form-label">
                                <span class="badge bg-success me-2">Nível 5</span> Especialista
                            </label>
                            <textarea name="level_5_description" id="level_5_description" 
                                      class="form-control" rows="2" 
                                      placeholder="Ex: Conhecimento especializado, referência na área"><?= htmlspecialchars($this->data['competency']['level_5_description'] ?? '') ?></textarea>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-3 mb-0">
                        <i class="fas fa-lightbulb me-2"></i>
                        <strong>Dica:</strong> Os níveis são opcionais, mas recomendamos preencher pelo menos os níveis 1, 3 e 5 
                        para facilitar a avaliação na matriz de competências.
                    </div>
                </div>
                
                <div class="col-12">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Salvar Alterações
                        </button>
                        <?php if (!empty($this->data['competency']['id'])): ?>
                            <a href="<?php echo $_ENV['URL_ADM']; ?>view-competency/<?= $this->data['competency']['id'] ?>" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </a>
                        <?php else: ?>
                            <a href="<?php echo $_ENV['URL_ADM']; ?>list-competencies" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

