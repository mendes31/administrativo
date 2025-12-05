<?php
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\FormatHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Matriz de Competências</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Gestão de Pessoas</li>
            <li class="breadcrumb-item">Matriz de Competências</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <!-- Estatísticas -->
    <?php if (!empty($this->data['stats'])): ?>
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="card-body text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-0 fw-bold"><?= $this->data['stats']['total_positions'] ?? 0 ?></h3>
                                <p class="mb-0 small opacity-75">Cargos</p>
                            </div>
                            <div class="fs-1 opacity-50">
                                <i class="fas fa-briefcase"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="card-body text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-0 fw-bold"><?= $this->data['stats']['total_competencies'] ?? 0 ?></h3>
                                <p class="mb-0 small opacity-75">Competências</p>
                            </div>
                            <div class="fs-1 opacity-50">
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <div class="card-body text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-0 fw-bold"><?= $this->data['stats']['total_assignments'] ?? 0 ?></h3>
                                <p class="mb-0 small opacity-75">Associações</p>
                            </div>
                            <div class="fs-1 opacity-50">
                                <i class="fas fa-link"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                    <div class="card-body text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-0 fw-bold"><?= number_format($this->data['stats']['avg_required_level'] ?? 0, 1) ?></h3>
                                <p class="mb-0 small opacity-75">Nível Médio</p>
                            </div>
                            <div class="fs-1 opacity-50">
                                <i class="fas fa-chart-line"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span><i class="fas fa-briefcase me-2"></i>Competências por Cargo</span>
            <span class="ms-auto">
                <?php if (in_array('ListCompetencies', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-competencies" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Voltar
                    </a>
                <?php } ?>
                <?php if (in_array('CreateCompetency', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>create-competency" class="btn btn-sm btn-success ms-2">
                        <i class="fas fa-plus me-1"></i>Nova Competência
                    </a>
                <?php } ?>
            </span>
        </div>
        <div class="card-body">
            <?php if (empty($this->data['positions_with_competencies'])): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Nenhum cargo encontrado.
                </div>
            <?php else: ?>
                <div class="accordion" id="positionsAccordion">
                    <?php foreach ($this->data['positions_with_competencies'] as $index => $position): 
                        $positionId = $position['id'];
                        $competencies = $position['competencies'] ?? [];
                        $competenciesCount = count($competencies);
                        $accordionId = 'position_' . $positionId;
                    ?>
                        <div class="accordion-item mb-2">
                            <h2 class="accordion-header" id="heading<?= $positionId ?>">
                                <button class="accordion-button <?= $index > 0 ? 'collapsed' : '' ?>" 
                                        type="button" 
                                        data-bs-toggle="collapse" 
                                        data-bs-target="#<?= $accordionId ?>" 
                                        aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>"
                                        aria-controls="<?= $accordionId ?>">
                                    <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <i class="fas fa-briefcase fs-5"></i>
                                            <div>
                                                <strong class="fs-6"><?= htmlspecialchars($position['name']) ?></strong>
                                                <div class="small opacity-75 mt-1">
                                                    <i class="fas fa-star me-1"></i>
                                                    <span class="badge bg-light text-dark ms-1"><?= $competenciesCount ?> competência(s)</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </button>
                            </h2>
                            <div id="<?= $accordionId ?>" 
                                 class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" 
                                 aria-labelledby="heading<?= $positionId ?>"
                                 data-bs-parent="#positionsAccordion">
                                <div class="accordion-body">
                                    <!-- Formulário para Adicionar Nova Competência -->
                                    <div class="card border-0 shadow-sm mb-4" style="border-left: 4px solid #0d6efd !important;">
                                        <div class="card-header bg-white border-0 py-3">
                                            <h6 class="mb-0 text-primary">
                                                <i class="fas fa-plus-circle me-2"></i>Adicionar Nova Competência
                                            </h6>
                                        </div>
                                        <div class="card-body bg-light">
                                            <form method="POST" class="row g-2">
                                                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_add_competency_to_position_' . $positionId); ?>">
                                                <input type="hidden" name="action" value="add">
                                                <input type="hidden" name="position_id" value="<?= $positionId ?>">
                                                
                                                <div class="col-md-5">
                                                    <label for="competency_id_<?= $positionId ?>" class="form-label small mb-1">Competência</label>
                                                    <select name="competency_id" 
                                                            id="competency_id_<?= $positionId ?>" 
                                                            class="form-select form-select-sm" 
                                                            required
                                                            onchange="updateAddFormDescription(<?= $positionId ?>)">
                                                        <option value="">Selecione...</option>
                                                        <?php 
                                                        $typeLabels = [
                                                            'technical' => 'Técnica',
                                                            'behavioral' => 'Comportamental',
                                                            'leadership' => 'Liderança'
                                                        ];
                                                        foreach ($position['available_competencies'] ?? [] as $comp): 
                                                            $typeLabel = $typeLabels[$comp['competency_type']] ?? $comp['competency_type'];
                                                        ?>
                                                            <option value="<?= $comp['id'] ?>" 
                                                                    data-level-1="<?= htmlspecialchars($comp['level_1_description'] ?? '') ?>"
                                                                    data-level-2="<?= htmlspecialchars($comp['level_2_description'] ?? '') ?>"
                                                                    data-level-3="<?= htmlspecialchars($comp['level_3_description'] ?? '') ?>"
                                                                    data-level-4="<?= htmlspecialchars($comp['level_4_description'] ?? '') ?>"
                                                                    data-level-5="<?= htmlspecialchars($comp['level_5_description'] ?? '') ?>">
                                                                <?= htmlspecialchars($comp['name']) ?> 
                                                                (<?= htmlspecialchars($typeLabel) ?>)
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="required_level_<?= $positionId ?>" class="form-label small mb-1">Nível Requerido</label>
                                                    <select name="required_level" 
                                                            id="required_level_<?= $positionId ?>" 
                                                            class="form-select form-select-sm" 
                                                            required
                                                            onchange="updateAddFormDescription(<?= $positionId ?>)">
                                                        <option value="">Selecione...</option>
                                                        <option value="1">1 - Iniciante</option>
                                                        <option value="2">2 - Básico</option>
                                                        <option value="3">3 - Intermediário</option>
                                                        <option value="4">4 - Avançado</option>
                                                        <option value="5">5 - Especialista</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-check form-switch mt-3">
                                                        <input class="form-check-input" type="checkbox" name="is_mandatory" id="is_mandatory_<?= $positionId ?>" value="1">
                                                        <label class="form-check-label small" for="is_mandatory_<?= $positionId ?>">Obrigatória</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <button type="submit" class="btn btn-primary w-100 shadow-sm">
                                                        <i class="fas fa-plus me-2"></i>Adicionar
                                                    </button>
                                                </div>
                                                <div class="col-12 mt-3">
                                                    <div id="level_description_<?= $positionId ?>" class="alert mb-0 border-0 shadow-sm" style="display: none; border-left: 4px solid #0dcaf0 !important; background: #f8f9fa;">
                                                        <div class="d-flex align-items-start">
                                                            <div class="me-3">
                                                                <i class="fas fa-info-circle fa-lg" id="level_icon_<?= $positionId ?>" style="color: #0dcaf0;"></i>
                                                            </div>
                                                            <div class="flex-grow-1">
                                                                <div class="d-flex align-items-center mb-2">
                                                                    <strong class="me-2 text-dark">Descrição do Nível:</strong>
                                                                    <span id="level_badge_<?= $positionId ?>" class="badge"></span>
                                                                </div>
                                                                <p id="level_description_text_<?= $positionId ?>" class="mb-0 text-dark" style="line-height: 1.6;"></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- Lista de Competências Vinculadas -->
                                    <?php if (empty($competencies)): ?>
                                        <div class="alert alert-light border-0 shadow-sm mb-0 text-center py-4">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                            <h6 class="text-muted mb-2">Nenhuma competência vinculada</h6>
                                            <p class="text-muted small mb-0">Use o formulário acima para adicionar competências a este cargo.</p>
                                        </div>
                                    <?php else: ?>
                                        <form method="POST" id="updateForm_<?= $positionId ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_update_competencies_' . $positionId); ?>">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="position_id" value="<?= $positionId ?>">
                                            
                                            <div class="table-responsive">
                                                <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th style="width: 4%;" class="text-center">#</th>
                                                            <th style="width: 22%;">Competência</th>
                                                            <th style="width: 10%;">Tipo</th>
                                                            <th style="width: 16%;">Nível Requerido</th>
                                                            <th style="width: 28%;">Descrição do Nível</th>
                                                            <th style="width: 12%;" class="text-center">Obrigatória</th>
                                                            <th style="width: 8%;" class="text-center">Ações</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($competencies as $idx => $comp): 
                                                            $colors = ['', 'danger', 'warning', 'info', 'primary', 'success'];
                                                            $color = $colors[$comp['required_level']] ?? 'secondary';
                                                            $typeLabels = [
                                                                'technical' => 'Técnica',
                                                                'behavioral' => 'Comportamental',
                                                                'leadership' => 'Liderança'
                                                            ];
                                                            $typeLabel = $typeLabels[$comp['competency_type']] ?? $comp['competency_type'];
                                                            
                                                            // Buscar descrição do nível atual
                                                            $levelField = 'level_' . $comp['required_level'] . '_description';
                                                            $levelDescription = !empty($comp[$levelField]) ? $comp[$levelField] : '';
                                                            
                                                            // Descrições padrão se não houver customizada
                                                            $defaultDescriptions = [
                                                                1 => 'Conhecimento básico, precisa de supervisão constante',
                                                                2 => 'Conhecimento básico, pode trabalhar com supervisão ocasional',
                                                                3 => 'Conhecimento sólido, trabalha de forma independente',
                                                                4 => 'Conhecimento avançado, pode orientar outros',
                                                                5 => 'Conhecimento especializado, referência na área'
                                                            ];
                                                            
                                                            $displayDescription = !empty($levelDescription) ? $levelDescription : ($defaultDescriptions[$comp['required_level']] ?? '');
                                                        ?>
                                                            <tr class="border-bottom">
                                                                <td class="text-center fw-bold text-muted"><?= $idx + 1 ?></td>
                                                                <td>
                                                                    <div>
                                                                        <strong class="text-dark"><?= htmlspecialchars($comp['name']) ?></strong>
                                                                        <?php if (!empty($comp['category'])): ?>
                                                                            <br><small class="text-muted"><i class="fas fa-tag me-1"></i><?= htmlspecialchars($comp['category']) ?></small>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <?php
                                                                    $typeBadgeColors = [
                                                                        'technical' => 'bg-primary',
                                                                        'behavioral' => 'bg-success',
                                                                        'leadership' => 'bg-warning'
                                                                    ];
                                                                    $badgeColor = $typeBadgeColors[$comp['competency_type']] ?? 'bg-info';
                                                                    ?>
                                                                    <span class="badge <?= $badgeColor ?> text-white"><?= htmlspecialchars($typeLabel) ?></span>
                                                                </td>
                                                                <td>
                                                                    <div class="d-flex align-items-center gap-2">
                                                                        <select name="competencies[<?= $comp['competency_id'] ?>][required_level]" 
                                                                                class="form-select form-select-sm border-<?= $color ?>" 
                                                                                style="width: 130px; font-weight: 500;"
                                                                                onchange="updateLevelBadgeAndDescription(this, <?= $comp['competency_id'] ?>)"
                                                                                data-competency-id="<?= $comp['competency_id'] ?>">
                                                                            <option value="1" <?= $comp['required_level'] == 1 ? 'selected' : '' ?>>1 - Iniciante</option>
                                                                            <option value="2" <?= $comp['required_level'] == 2 ? 'selected' : '' ?>>2 - Básico</option>
                                                                            <option value="3" <?= $comp['required_level'] == 3 ? 'selected' : '' ?>>3 - Intermediário</option>
                                                                            <option value="4" <?= $comp['required_level'] == 4 ? 'selected' : '' ?>>4 - Avançado</option>
                                                                            <option value="5" <?= $comp['required_level'] == 5 ? 'selected' : '' ?>>5 - Especialista</option>
                                                                        </select>
                                                                        <span class="badge bg-<?= $color ?> text-white px-2 py-1" id="badge_<?= $comp['competency_id'] ?>" style="font-size: 0.75rem;">
                                                                            <i class="fas fa-star me-1"></i>Nível <?= $comp['required_level'] ?>
                                                                        </span>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div id="description_<?= $comp['competency_id'] ?>" class="small text-muted" style="line-height: 1.5; font-style: italic;">
                                                                        <i class="fas fa-info-circle me-1 text-<?= $color ?>"></i>
                                                                        <?= htmlspecialchars($displayDescription) ?>
                                                                    </div>
                                                                </td>
                                                                <td class="text-center">
                                                                    <div class="form-check form-switch d-inline-block">
                                                                        <input class="form-check-input" 
                                                                               type="checkbox" 
                                                                               name="competencies[<?= $comp['competency_id'] ?>][is_mandatory]" 
                                                                               value="1"
                                                                               id="mandatory_<?= $comp['competency_id'] ?>"
                                                                               <?= $comp['is_mandatory'] ? 'checked' : '' ?>>
                                                                        <label class="form-check-label d-none" for="mandatory_<?= $comp['competency_id'] ?>">
                                                                            <?= $comp['is_mandatory'] ? 'Sim' : 'Não' ?>
                                                                        </label>
                                                                    </div>
                                                                    <?php if ($comp['is_mandatory']): ?>
                                                                        <span class="badge bg-danger text-white ms-2" style="font-size: 0.7rem;">
                                                                            <i class="fas fa-exclamation-circle"></i> Obrigatória
                                                                        </span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td class="text-center">
                                                                    <button type="button" 
                                                                            class="btn btn-sm btn-outline-danger" 
                                                                            onclick="removeCompetency(<?= $positionId ?>, <?= $comp['competency_id'] ?>, '<?= htmlspecialchars(addslashes($comp['name'])) ?>')"
                                                                            title="Remover competência">
                                                                        <i class="fas fa-trash-alt"></i>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                            <script>
                                                            // Armazenar descrições da competência no JavaScript
                                                            if (typeof competencyDescriptions === 'undefined') {
                                                                window.competencyDescriptions = {};
                                                            }
                                                            window.competencyDescriptions[<?= $comp['competency_id'] ?>] = {
                                                                level_1: <?= json_encode($comp['level_1_description'] ?? '') ?>,
                                                                level_2: <?= json_encode($comp['level_2_description'] ?? '') ?>,
                                                                level_3: <?= json_encode($comp['level_3_description'] ?? '') ?>,
                                                                level_4: <?= json_encode($comp['level_4_description'] ?? '') ?>,
                                                                level_5: <?= json_encode($comp['level_5_description'] ?? '') ?>
                                                            };
                                                            </script>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <div class="mt-4 d-flex justify-content-end">
                                                <button type="submit" class="btn btn-primary px-4">
                                                    <i class="fas fa-save me-2"></i>Salvar Alterações
                                                </button>
                                            </div>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Descrições padrão para cada nível
const defaultLevelDescriptions = {
    1: 'Conhecimento básico, precisa de supervisão constante',
    2: 'Conhecimento básico, pode trabalhar com supervisão ocasional',
    3: 'Conhecimento sólido, trabalha de forma independente',
    4: 'Conhecimento avançado, pode orientar outros',
    5: 'Conhecimento especializado, referência na área'
};

function updateAddFormDescription(positionId) {
    const competencySelect = document.getElementById('competency_id_' + positionId);
    const levelSelect = document.getElementById('required_level_' + positionId);
    const descriptionDiv = document.getElementById('level_description_' + positionId);
    const descriptionText = document.getElementById('level_description_text_' + positionId);
    const levelBadge = document.getElementById('level_badge_' + positionId);
    const levelIcon = document.getElementById('level_icon_' + positionId);
    
    if (!competencySelect || !levelSelect || !descriptionDiv || !descriptionText) {
        return;
    }
    
    const selectedCompetencyId = competencySelect.value;
    const selectedLevel = levelSelect.value;
    
    // Se não tiver competência ou nível selecionado, ocultar descrição
    if (!selectedCompetencyId || !selectedLevel) {
        descriptionDiv.style.display = 'none';
        return;
    }
    
    // Buscar a opção selecionada da competência
    const selectedOption = competencySelect.options[competencySelect.selectedIndex];
    const level = parseInt(selectedLevel);
    
    // Buscar descrição customizada do atributo data-level-X
    const customDescription = selectedOption.getAttribute('data-level-' + level);
    
    // Usar descrição customizada se existir e não estiver vazia, senão usar a padrão
    const description = (customDescription && customDescription.trim()) 
        ? customDescription 
        : (defaultLevelDescriptions[level] || '');
    
    // Nomes dos níveis
    const levelNames = {
        1: 'Iniciante',
        2: 'Básico',
        3: 'Intermediário',
        4: 'Avançado',
        5: 'Especialista'
    };
    
    // Cores por nível
    const levelColors = {
        1: { badge: 'bg-danger', icon: '#dc3545', border: '#dc3545' },
        2: { badge: 'bg-warning', icon: '#ffc107', border: '#ffc107' },
        3: { badge: 'bg-info', icon: '#0dcaf0', border: '#0dcaf0' },
        4: { badge: 'bg-primary', icon: '#0d6efd', border: '#0d6efd' },
        5: { badge: 'bg-success', icon: '#198754', border: '#198754' }
    };
    
    const colors = levelColors[level] || { badge: 'bg-secondary', icon: '#6c757d', border: '#6c757d' };
    
    // Atualizar badge do nível
    if (levelBadge) {
        levelBadge.className = 'badge ' + colors.badge + ' text-white';
        levelBadge.textContent = 'Nível ' + level + ' - ' + levelNames[level];
    }
    
    // Atualizar ícone
    if (levelIcon) {
        levelIcon.style.color = colors.icon;
    }
    
    // Atualizar borda do card
    descriptionDiv.style.borderLeft = '4px solid ' + colors.border;
    
    // Exibir descrição
    descriptionText.textContent = description;
    descriptionDiv.style.display = 'block';
}

function updateLevelBadgeAndDescription(select, competencyId) {
    const level = parseInt(select.value);
    const badge = document.getElementById('badge_' + competencyId);
    const descriptionDiv = document.getElementById('description_' + competencyId);
    const colors = ['', 'danger', 'warning', 'info', 'primary', 'success'];
    const color = colors[level] || 'secondary';
    
    // Atualizar badge
    badge.className = 'badge bg-' + color + ' text-white px-2 py-1';
    badge.innerHTML = '<i class="fas fa-star me-1"></i>Nível ' + level;
    
    // Atualizar cor da borda do select
    select.className = 'form-select form-select-sm border-' + color;
    
    // Atualizar descrição
    if (descriptionDiv && window.competencyDescriptions && window.competencyDescriptions[competencyId]) {
        const compDescriptions = window.competencyDescriptions[competencyId];
        const levelField = 'level_' + level;
        const customDescription = compDescriptions[levelField];
        
        // Usar descrição customizada se existir, senão usar a padrão
        const description = customDescription && customDescription.trim() 
            ? customDescription 
            : (defaultLevelDescriptions[level] || '');
        
        descriptionDiv.innerHTML = '<i class="fas fa-info-circle me-1 text-' + color + '"></i>' + description;
    }
}

function removeCompetency(positionId, competencyId, competencyName) {
    if (!confirm(`Tem certeza que deseja remover a competência "${competencyName}" deste cargo?`)) {
        return;
    }

    // Buscar token CSRF do formulário de atualização deste cargo específico
    const updateForm = document.getElementById('updateForm_' + positionId);
    let csrfToken = '';
    
    if (updateForm) {
        const csrfInput = updateForm.querySelector('input[name="csrf_token"]');
        if (csrfInput) {
            csrfToken = csrfInput.value;
        }
    }
    
    // Se não encontrou no formulário de atualização, buscar no formulário de adicionar
    if (!csrfToken) {
        const addForm = document.querySelector('form[action=""][method="POST"]');
        if (addForm) {
            const csrfInput = addForm.querySelector('input[name="csrf_token"]');
            if (csrfInput) {
                csrfToken = csrfInput.value;
            }
        }
    }
    
    if (!csrfToken) {
        alert('Erro: Token de segurança não encontrado. Recarregue a página e tente novamente.');
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '';
    
    form.innerHTML = `
        <input type="hidden" name="csrf_token" value="${csrfToken}">
        <input type="hidden" name="action" value="remove">
        <input type="hidden" name="position_id" value="${positionId}">
        <input type="hidden" name="competency_id" value="${competencyId}">
    `;
    
    document.body.appendChild(form);
    form.submit();
}
</script>

<style>
.accordion-button {
    background-color: #f8f9fa;
    border: none;
    font-weight: 500;
}

.accordion-button:not(.collapsed) {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: none;
}

.accordion-button:not(.collapsed) .badge {
    background-color: rgba(255, 255, 255, 0.3) !important;
    color: white !important;
}

.accordion-button:not(.collapsed) i {
    color: white;
}

.accordion-button:focus {
    box-shadow: none;
    border-color: transparent;
}

.accordion-item {
    border: 1px solid #e0e0e0;
    border-radius: 0.5rem;
    margin-bottom: 0.75rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    overflow: hidden;
}

.accordion-item:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transition: box-shadow 0.3s ease;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
    transition: background-color 0.2s ease;
}

.table thead th {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
}

.card {
    border-radius: 0.5rem;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
}
</style>
