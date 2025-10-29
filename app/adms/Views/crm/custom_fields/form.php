<?php
$field = $this->data['field'] ?? [];
        $isEdit = !empty($field['id']);
// Converter field_options de JSON para string se estiver editando
if ($isEdit && !empty($field['field_options'])) {
    $optionsArray = json_decode($field['field_options'], true);
    $field['options'] = is_array($optionsArray) ? implode(', ', $optionsArray) : '';
} elseif ($isEdit) {
    $field['options'] = '';
}
?>

<div class="container-fluid px-4">
    
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
        <h1 class="mt-2">
            <i class="fas fa-<?= $isEdit ? 'edit' : 'plus' ?> text-primary me-2"></i>
            <?= $isEdit ? 'Editar' : 'Novo' ?> Campo Customizável
        </h1>
        <a href="<?= $_ENV['URL_ADM'] ?>crm-list-custom-fields?entity_type=<?= $field['entity_type'] ?? '' ?>" 
           class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>

    <!-- Formulário -->
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" id="formCustomField">
                
                <!-- Entidade e Nome do Campo -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Entidade *</label>
                        <select name="entity_type" class="form-select" required <?= $isEdit ? 'disabled' : '' ?>>
                            <option value="partner" <?= ($field['entity_type'] ?? '') === 'partner' ? 'selected' : '' ?>>
                                Parceiro
                            </option>
                            <option value="opportunity" <?= ($field['entity_type'] ?? '') === 'opportunity' ? 'selected' : '' ?>>
                                Oportunidade
                            </option>
                        </select>
                        <?php if ($isEdit): ?>
                            <input type="hidden" name="entity_type" value="<?= $field['entity_type'] ?>">
                        <?php endif; ?>
                        <div class="form-text">Onde este campo será exibido</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nome do Campo (slug) *</label>
                        <input type="text" name="field_name" class="form-control" required
                               value="<?= htmlspecialchars($field['field_name'] ?? '') ?>"
                               pattern="[a-z_]+" 
                               placeholder="Ex: data_vencimento"
                               <?= $isEdit ? 'readonly' : '' ?>>
                        <div class="form-text">Apenas letras minúsculas e underscore (_)</div>
                    </div>
                </div>

                <!-- Label e Tipo -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Label (Rótulo) *</label>
                        <input type="text" name="field_label" class="form-control" required
                               value="<?= htmlspecialchars($field['field_label'] ?? '') ?>"
                               placeholder="Ex: Data de Vencimento">
                        <div class="form-text">Texto exibido ao usuário</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tipo de Campo *</label>
                        <select name="field_type" id="field_type" class="form-select" required>
                            <option value="text" <?= ($field['field_type'] ?? '') === 'text' ? 'selected' : '' ?>>
                                Texto (Input)
                            </option>
                            <option value="textarea" <?= ($field['field_type'] ?? '') === 'textarea' ? 'selected' : '' ?>>
                                Texto Longo (Textarea)
                            </option>
                            <option value="number" <?= ($field['field_type'] ?? '') === 'number' ? 'selected' : '' ?>>
                                Número
                            </option>
                            <option value="date" <?= ($field['field_type'] ?? '') === 'date' ? 'selected' : '' ?>>
                                Data
                            </option>
                            <option value="select" <?= ($field['field_type'] ?? '') === 'select' ? 'selected' : '' ?>>
                                Lista Suspensa (Select)
                            </option>
                            <option value="checkbox" <?= ($field['field_type'] ?? '') === 'checkbox' ? 'selected' : '' ?>>
                                Checkbox (Múltipla Escolha)
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Opções (para select e checkbox) -->
                <div class="mb-3" id="optionsContainer" style="display: none;">
                    <label class="form-label">Opções (separadas por vírgula) *</label>
                    <input type="text" name="options" class="form-control" 
                           value="<?= htmlspecialchars($field['options'] ?? '') ?>"
                           placeholder="Ex: Opção 1, Opção 2, Opção 3">
                    <div class="form-text">Obrigatório para campos do tipo Select e Checkbox</div>
                </div>

                <!-- Ordem e Status -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Ordem de Exibição</label>
                        <input type="number" name="display_order" class="form-control" 
                               value="<?= $field['display_order'] ?? 0 ?>" min="0">
                        <div class="form-text">Campos serão ordenados por este valor</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?= (!isset($field['is_active']) || $field['is_active']) ? 'selected' : '' ?>>
                                Ativo
                            </option>
                            <option value="inactive" <?= (isset($field['is_active']) && !$field['is_active']) ? 'selected' : '' ?>>
                                Inativo
                            </option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_required" id="is_required"
                                   <?= !empty($field['is_required']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_required">
                                <i class="fas fa-exclamation-triangle text-danger me-1"></i>
                                Campo Obrigatório
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Botões -->
                <div class="mt-4">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i>
                        <?= $isEdit ? 'Atualizar' : 'Criar' ?> Campo
                    </button>
                    <a href="<?= $_ENV['URL_ADM'] ?>crm-list-custom-fields?entity_type=<?= $field['entity_type'] ?? '' ?>" 
                       class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i>
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fieldTypeSelect = document.getElementById('field_type');
    const optionsContainer = document.getElementById('optionsContainer');
    const optionsInput = optionsContainer.querySelector('input[name="options"]');
    
    function toggleOptions() {
        const fieldType = fieldTypeSelect.value;
        const needsOptions = ['select', 'checkbox'].includes(fieldType);
        
        optionsContainer.style.display = needsOptions ? 'block' : 'none';
        optionsInput.required = needsOptions;
    }
    
    fieldTypeSelect.addEventListener('change', toggleOptions);
    toggleOptions(); // Executar ao carregar
});
</script>

