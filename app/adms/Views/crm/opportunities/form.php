<?php
$isEdit = !empty($this->data['opportunity']);
$opp = $this->data['opportunity'] ?? [];
?>

<div class="container-fluid px-4">
    
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">
            <i class="fas fa-handshake me-2"></i><?php echo $isEdit ? 'Editar' : 'Nova'; ?> Oportunidade
        </h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>crm-kanban-pipeline">Pipeline</a></li>
            <li class="breadcrumb-item active"><?php echo $isEdit ? 'Editar' : 'Nova'; ?></li>
        </ol>
    </div>

    <form method="POST" action="<?php echo $_ENV['URL_ADM']; ?><?php echo $isEdit ? 'crm-update-opportunity/' . $opp['id'] : 'crm-create-opportunity'; ?>">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?php echo $opp['id']; ?>">
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4 shadow-sm">
                    <div class="card-header"><h5 class="mb-0">Informações da Oportunidade</h5></div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label">Código *</label>
                                <input type="text" name="code" class="form-control" required
                                       value="<?php echo $opp['code'] ?? $this->data['next_code']; ?>" readonly>
                            </div>
                            <div class="col-md-9">
                                <label class="form-label">Título *</label>
                                <input type="text" name="title" class="form-control" required
                                       placeholder="Ex: Venda de sistema de gestão"
                                       value="<?php echo htmlspecialchars($opp['title'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Parceiro *</label>
                            <select name="partner_id" class="form-select" required>
                                <option value="">Selecione o parceiro</option>
                                <?php 
                                $selectedPartnerId = $opp['partner_id'] ?? ($this->data['preselected_partner_id'] ?? '');
                                foreach ($this->data['partners'] as $partner): 
                                ?>
                                    <option value="<?php echo $partner['id']; ?>" 
                                            <?php echo $selectedPartnerId == $partner['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($partner['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descrição</label>
                            <textarea name="description" class="form-control" rows="4"
                                      placeholder="Detalhes da oportunidade..."><?php echo htmlspecialchars($opp['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Valor Estimado (R$) *</label>
                                <input type="number" name="value" class="form-control" required step="0.01" min="0"
                                       value="<?php echo $opp['value'] ?? 0; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Probabilidade (%) *</label>
                                <input type="number" name="probability" class="form-control" required min="0" max="100"
                                       value="<?php echo $opp['probability'] ?? 50; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Previsão de Fechamento</label>
                                <input type="date" name="expected_close_date" class="form-control"
                                       value="<?php echo $opp['expected_close_date'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-4 shadow-sm">
                    <div class="card-header"><h5 class="mb-0">Controle</h5></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Etapa do Pipeline *</label>
                            <select name="stage_id" class="form-select" required>
                                <?php foreach ($this->data['stages'] as $stage): ?>
                                    <option value="<?php echo $stage['id']; ?>"
                                            <?php echo ($opp['stage_id'] ?? 1) == $stage['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($stage['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Responsável *
                                <?php if (count($this->data['users']) == 1): ?>
                                    <i class="fas fa-info-circle text-info" title="Você só pode atribuir para si mesmo. Gerentes podem atribuir para subordinados."></i>
                                <?php endif; ?>
                            </label>
                            <select name="responsible_user_id" class="form-select" required <?php echo count($this->data['users']) == 1 ? 'readonly style="background-color: #e9ecef; pointer-events: none;"' : ''; ?>>
                                <?php foreach ($this->data['users'] as $user): ?>
                                    <option value="<?php echo $user['id']; ?>"
                                            <?php echo ($opp['responsible_user_id'] ?? $_SESSION['user_id']) == $user['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['name']); ?>
                                        <?php if ($user['id'] == $_SESSION['user_id']): ?> (Você)<?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (count($this->data['users']) == 1): ?>
                                <div class="form-text text-muted">
                                    <i class="fas fa-user me-1"></i>Apenas você pode ser o responsável por esta oportunidade.
                                </div>
                            <?php else: ?>
                                <div class="form-text text-success">
                                    <i class="fas fa-users me-1"></i>Como gerente, você pode atribuir para qualquer membro da equipe.
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Origem</label>
                            <input type="text" name="source" class="form-control"
                                   placeholder="Ex: Indicação, Site, Telefone"
                                   value="<?php echo htmlspecialchars($opp['source'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Próxima Ação</label>
                            <input type="text" name="next_action" class="form-control"
                                   placeholder="Ex: Agendar reunião"
                                   value="<?php echo htmlspecialchars($opp['next_action'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Data da Próxima Ação</label>
                            <input type="date" name="next_action_date" class="form-control"
                                   value="<?php echo $opp['next_action_date'] ?? ''; ?>">
                        </div>
                    </div>
                </div>

                <!-- CAMPOS CUSTOMIZÁVEIS -->
                <?php
                $customFields = $this->data['custom_fields'] ?? [];
                $customFieldValues = $this->data['custom_field_values'] ?? [];
                
                if (!empty($customFields)):
                ?>
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-sliders-h me-2"></i>Campos Customizados
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($customFields as $field): 
                                $fieldName = 'custom_field_' . $field['id'];
                                $fieldValue = $customFieldValues[$field['field_name']] ?? '';
                                $isRequired = $field['is_required'] ? 'required' : '';
                                $requiredMark = $field['is_required'] ? ' *' : '';
                            ?>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <?= htmlspecialchars($field['field_label']) ?><?= $requiredMark ?>
                                    </label>
                                    
                                    <?php if ($field['field_type'] === 'text'): ?>
                                        <input type="text" 
                                               name="<?= $fieldName ?>" 
                                               class="form-control" 
                                               value="<?= htmlspecialchars($fieldValue) ?>"
                                               <?= $isRequired ?>>
                                    
                                    <?php elseif ($field['field_type'] === 'number'): ?>
                                        <input type="number" 
                                               name="<?= $fieldName ?>" 
                                               class="form-control" 
                                               value="<?= htmlspecialchars($fieldValue) ?>"
                                               <?= $isRequired ?>>
                                    
                                    <?php elseif ($field['field_type'] === 'date'): ?>
                                        <input type="date" 
                                               name="<?= $fieldName ?>" 
                                               class="form-control" 
                                               value="<?= htmlspecialchars($fieldValue) ?>"
                                               <?= $isRequired ?>>
                                    
                                    <?php elseif ($field['field_type'] === 'textarea'): ?>
                                        <textarea name="<?= $fieldName ?>" 
                                                  class="form-control" 
                                                  rows="3" 
                                                  <?= $isRequired ?>><?= htmlspecialchars($fieldValue) ?></textarea>
                                    
                                    <?php elseif ($field['field_type'] === 'select'): ?>
                                        <select name="<?= $fieldName ?>" class="form-select" <?= $isRequired ?>>
                                            <option value="">Selecione...</option>
                                            <?php 
                                            $options = json_decode($field['field_options'], true);
                                            if (is_array($options)):
                                                foreach ($options as $option):
                                            ?>
                                                <option value="<?= htmlspecialchars($option) ?>" 
                                                        <?= $fieldValue === $option ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($option) ?>
                                                </option>
                                            <?php 
                                                endforeach;
                                            endif;
                                            ?>
                                        </select>
                                    
                                    <?php elseif ($field['field_type'] === 'checkbox'): ?>
                                        <?php 
                                        $options = json_decode($field['field_options'], true);
                                        $selectedValues = is_array($fieldValue) ? $fieldValue : (empty($fieldValue) ? [] : explode(',', $fieldValue));
                                        if (is_array($options)):
                                            foreach ($options as $option):
                                        ?>
                                            <div class="form-check">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       name="<?= $fieldName ?>[]" 
                                                       value="<?= htmlspecialchars($option) ?>"
                                                       <?= in_array($option, $selectedValues) ? 'checked' : '' ?>>
                                                <label class="form-check-label">
                                                    <?= htmlspecialchars($option) ?>
                                                </label>
                                            </div>
                                        <?php 
                                            endforeach;
                                        endif;
                                        ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-save me-2"></i>Salvar Oportunidade
                    </button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>crm-kanban-pipeline" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

