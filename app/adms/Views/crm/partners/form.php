<?php
$isEdit = !empty($this->data['partner']);
$partner = $this->data['partner'] ?? [];
?>

<div class="container-fluid px-4">
    
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">
            <i class="fas fa-user-plus me-2"></i><?php echo $isEdit ? 'Editar' : 'Novo'; ?> Parceiro
        </h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>crm-list-partners">Parceiros</a></li>
            <li class="breadcrumb-item active"><?php echo $isEdit ? 'Editar' : 'Novo'; ?></li>
        </ol>
    </div>

    <form method="POST" action="<?php echo $_ENV['URL_ADM']; ?><?php echo $isEdit ? 'crm-update-partner/' . $partner['id'] : 'crm-create-partner'; ?>">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?php echo $partner['id']; ?>">
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4 shadow-sm">
                    <div class="card-header"><h5 class="mb-0">Informações Básicas</h5></div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label">Código *</label>
                                <input type="text" name="code" class="form-control" required
                                       value="<?php echo $partner['code'] ?? $this->data['next_code']; ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nome/Razão Social *</label>
                                <input type="text" name="name" class="form-control" required
                                       value="<?php echo htmlspecialchars($partner['name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tipo *</label>
                                <select name="type_person" id="type_person" class="form-select" required>
                                    <option value="PJ" <?php echo ($partner['type_person'] ?? 'PJ') == 'PJ' ? 'selected' : ''; ?>>Pessoa Jurídica</option>
                                    <option value="PF" <?php echo ($partner['type_person'] ?? '') == 'PF' ? 'selected' : ''; ?>>Pessoa Física</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Nome Fantasia</label>
                                <input type="text" name="trading_name" class="form-control"
                                       value="<?php echo htmlspecialchars($partner['trading_name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">CPF/CNPJ</label>
                                <input type="text" name="document" id="document" class="form-control"
                                       placeholder="000.000.000-00"
                                       value="<?php echo htmlspecialchars($partner['document'] ?? ''); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Segmento *</label>
                                <select name="segment" class="form-select" required>
                                    <?php foreach ($this->data['segments'] as $seg): ?>
                                        <option value="<?php echo $seg; ?>" <?php echo ($partner['segment'] ?? '') == $seg ? 'selected' : ''; ?>>
                                            <?php echo $seg; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control"
                                       value="<?php echo htmlspecialchars($partner['email'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Telefone</label>
                                <input type="text" name="phone" id="phone" class="form-control"
                                       placeholder="+55 (00) 0000-0000"
                                       value="<?php echo htmlspecialchars($partner['phone'] ?? ''); ?>">
                                <div class="form-text">Formato com DDI para WhatsApp</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Celular</label>
                                <input type="text" name="mobile" id="mobile" class="form-control"
                                       placeholder="+55 (00) 00000-0000"
                                       value="<?php echo htmlspecialchars($partner['mobile'] ?? ''); ?>">
                                <div class="form-text">Formato com DDI para WhatsApp</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-4 shadow-sm">
                    <div class="card-header"><h5 class="mb-0">Classificação</h5></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Tipo de Parceiro</label>
                            <select name="partner_type" class="form-select">
                                <?php foreach ($this->data['partner_types'] as $type): ?>
                                    <option value="<?php echo $type; ?>" <?php echo ($partner['partner_type'] ?? 'Lead') == $type ? 'selected' : ''; ?>>
                                        <?php echo $type; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Responsável</label>
                            <select name="responsible_user_id" class="form-select">
                                <option value="">Sem responsável</option>
                                <?php foreach ($this->data['users'] as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" <?php echo ($partner['responsible_user_id'] ?? '') == $user['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Prioridade</label>
                            <select name="priority" class="form-select">
                                <?php foreach ($this->data['priorities'] as $priority): ?>
                                    <option value="<?php echo $priority; ?>" <?php echo ($partner['priority'] ?? 'Média') == $priority ? 'selected' : ''; ?>>
                                        <?php echo $priority; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Receita Estimada (R$)</label>
                            <input type="number" name="estimated_revenue" class="form-control" step="0.01"
                                   value="<?php echo $partner['estimated_revenue'] ?? 0; ?>">
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
                        <i class="fas fa-save me-2"></i>Salvar Parceiro
                    </button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>crm-list-partners" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// ========================================
// MÁSCARAS DE ENTRADA
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    const typePerson = document.getElementById('type_person');
    const documentInput = document.getElementById('document');
    const phoneInput = document.getElementById('phone');
    const mobileInput = document.getElementById('mobile');

    // Função para aplicar máscara CPF: 000.000.000-00
    function maskCPF(value) {
        value = value.replace(/\D/g, ''); // Remove não-dígitos
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        return value.substring(0, 14); // Limita 000.000.000-00
    }

    // Função para aplicar máscara CNPJ: 00.000.000/0000-00
    function maskCNPJ(value) {
        value = value.replace(/\D/g, '');
        value = value.replace(/(\d{2})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1/$2');
        value = value.replace(/(\d{4})(\d{1,2})$/, '$1-$2');
        return value.substring(0, 18); // Limita 00.000.000/0000-00
    }

    // Função para aplicar máscara Telefone COM DDI: +55 (00) 0000-0000 OU +55 (00) 00000-0000
    function maskPhone(value) {
        value = value.replace(/\D/g, '');
        
        // Se começar sem DDI, adicionar 55
        if (!value.startsWith('55') && value.length >= 10) {
            value = '55' + value;
        }
        
        // Remover DDI duplicado se alguém digitou 5555...
        if (value.startsWith('5555') && value.length > 12) {
            value = value.substring(2);
        }
        
        // Aplicar máscara: +55 (DD) NNNNN-NNNN ou +55 (DD) NNNN-NNNN
        if (value.length >= 12) {
            // DDI + DDD + 9 dígitos (celular)
            value = value.replace(/^(\d{2})(\d{2})(\d{5})(\d{1,4}).*/, '+$1 ($2) $3-$4');
        } else if (value.length >= 11) {
            // DDI + DDD + 8 dígitos (fixo)
            value = value.replace(/^(\d{2})(\d{2})(\d{4})(\d{1,4}).*/, '+$1 ($2) $3-$4');
        }
        
        return value.substring(0, 20); // Limita +55 (00) 00000-0000
    }

    // Função para aplicar máscara Celular COM DDI: +55 (00) 00000-0000
    function maskMobile(value) {
        value = value.replace(/\D/g, '');
        
        // Se começar sem DDI, adicionar 55
        if (!value.startsWith('55') && value.length >= 11) {
            value = '55' + value;
        }
        
        // Remover DDI duplicado
        if (value.startsWith('5555') && value.length > 13) {
            value = value.substring(2);
        }
        
        // Aplicar máscara: +55 (DD) NNNNN-NNNN
        if (value.length >= 12) {
            value = value.replace(/^(\d{2})(\d{2})(\d{5})(\d{1,4}).*/, '+$1 ($2) $3-$4');
        }
        
        return value.substring(0, 20); // Limita +55 (00) 00000-0000
    }

    // Aplicar máscara de CPF ou CNPJ baseado no tipo
    function applyDocumentMask() {
        if (!documentInput) return;
        
        const type = typePerson.value;
        const currentValue = documentInput.value;
        
        if (type === 'PF') {
            documentInput.value = maskCPF(currentValue);
            documentInput.placeholder = '000.000.000-00';
            documentInput.maxLength = 14;
        } else {
            documentInput.value = maskCNPJ(currentValue);
            documentInput.placeholder = '00.000.000/0000-00';
            documentInput.maxLength = 18;
        }
    }

    // Event Listeners
    if (typePerson && documentInput) {
        // Ao carregar a página, aplicar máscara baseada no tipo atual
        applyDocumentMask();
        
        // Ao mudar o tipo de pessoa, reaplicar máscara
        typePerson.addEventListener('change', function() {
            documentInput.value = ''; // Limpar campo ao trocar
            applyDocumentMask();
        });

        // Ao digitar, aplicar máscara
        documentInput.addEventListener('input', function(e) {
            applyDocumentMask();
        });
    }

    // Máscara de Telefone
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            e.target.value = maskPhone(e.target.value);
        });
    }

    // Máscara de Celular
    if (mobileInput) {
        mobileInput.addEventListener('input', function(e) {
            e.target.value = maskMobile(e.target.value);
        });
    }
});
</script>

