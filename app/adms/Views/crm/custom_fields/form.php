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
                        <?php if ($isEdit): ?>
                            <!-- Em edição: permitir alterar -->
                            <input type="number" name="display_order" class="form-control" 
                                   value="<?= $field['display_order'] ?? 0 ?>" min="0" step="10">
                            <div class="form-text">
                                <i class="fas fa-edit text-warning me-1"></i>
                                Você pode alterar a ordem aqui. Use múltiplos de 10 (10, 20, 30...)
                            </div>
                        <?php else: ?>
                            <!-- Em criação: automático e readonly -->
                            <input type="hidden" name="display_order" value="<?= $field['display_order'] ?? 0 ?>">
                            <input type="number" class="form-control" 
                                   value="<?= $field['display_order'] ?? 0 ?>" 
                                   readonly 
                                   disabled
                                   style="background-color: #e9ecef; cursor: not-allowed;">
                            <div class="form-text">
                                <i class="fas fa-magic text-success me-1"></i>
                                <strong>Ordem calculada automaticamente:</strong> <span class="badge bg-success"><?= $field['display_order'] ?? 0 ?></span>
                                <br><small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    A ordem será incrementada automaticamente em múltiplos de 10 (10, 20, 30...).
                                    <br>
                                    <i class="fas fa-edit me-1"></i>
                                    Você pode alterar a ordem após criar o campo (edite o campo).
                                </small>
                            </div>
                        <?php endif; ?>
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

    <!-- Tabela de Campos Existentes (para referência) -->
    <?php if (!$isEdit && !empty($this->data['existing_fields'])): ?>
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-light">
                <h6 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Campos Existentes (<?= $field['entity_type'] === 'partner' ? 'Parceiros' : 'Oportunidades' ?>)
                </h6>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-2">
                    <i class="fas fa-lightbulb me-1"></i>
                    Use esta tabela como referência para escolher a ordem de exibição.
                </p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 10%;">Ordem</th>
                                <th style="width: 30%;">Label</th>
                                <th style="width: 30%;">Nome (slug)</th>
                                <th style="width: 20%;">Tipo</th>
                                <th style="width: 10%;" class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->data['existing_fields'] as $existingField): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-info"><?= $existingField['display_order'] ?? 0 ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($existingField['field_label']) ?></td>
                                    <td><code><?= htmlspecialchars($existingField['field_name']) ?></code></td>
                                    <td><?= ucfirst($existingField['field_type']) ?></td>
                                    <td class="text-center">
                                        <?php if ($existingField['is_active']): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-info mt-3 mb-2">
                    <strong><i class="fas fa-info-circle me-1"></i>Informações sobre a Ordem:</strong><br>
                    <small>
                        • A ordem é calculada <strong>automaticamente</strong> em múltiplos de 10 (10, 20, 30...)<br>
                        • Você <strong>NÃO precisa</strong> escolher a ordem ao criar o campo<br>
                        • A ordem <strong>PODE ser alterada</strong> depois (edite o campo e mude o número)<br>
                        • Menor número = aparece primeiro | Maior número = aparece depois
                    </small>
                </div>
                <div class="alert alert-warning mt-2 mb-0">
                    <strong><i class="fas fa-map-marker-alt me-1"></i>Onde os campos aparecerão no formulário?</strong><br>
                    <small>
                        Os campos customizados aparecerão em uma <strong>seção separada</strong> chamada "Campos Customizáveis", 
                        localizada <strong>DEPOIS</strong> das seções "Informações Básicas" e "Classificação", 
                        mas <strong>ANTES</strong> dos botões "Salvar" e "Cancelar".<br><br>
                        <strong>Estrutura do formulário:</strong><br>
                        1️⃣ Informações Básicas (nome, email, telefone, endereço...)<br>
                        2️⃣ Classificação (tipo, responsável, departamento...)<br>
                        3️⃣ <strong>📋 Campos Customizáveis</strong> ← SEUS CAMPOS APARECERÃO AQUI<br>
                        4️⃣ Botões (Salvar/Cancelar)
                    </small>
                </div>
            </div>
        </div>
    <?php endif; ?>

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

