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
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control"
                                       value="<?php echo htmlspecialchars($partner['email'] ?? ''); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Website</label>
                                <input type="url" name="website" class="form-control"
                                       placeholder="https://..."
                                       value="<?php echo htmlspecialchars($partner['website'] ?? ''); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">
                                    <i class="fas fa-tags me-1"></i>Tags
                                    <a href="<?= $_ENV['URL_ADM'] ?>crm-list-tags" target="_blank" class="text-decoration-none" title="Gerenciar tags">
                                        <i class="fas fa-cog text-muted"></i>
                                    </a>
                                </label>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary dropdown-toggle w-100 text-start" type="button" id="tagsDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="height: 38px;">
                                        <i class="fas fa-tag me-1"></i>
                                        <span id="tags-selected-count">Selecionar tags...</span>
                                    </button>
                                    <div class="dropdown-menu p-3" aria-labelledby="tagsDropdown" style="min-width: 250px; max-height: 300px; overflow-y: auto;">
                                        <?php 
                                        $selectedTags = [];
                                        if (!empty($this->data['partner_tags'])) {
                                            $selectedTags = array_column($this->data['partner_tags'], 'id');
                                        }
                                        
                                        if (empty($this->data['tags'])): 
                                        ?>
                                            <small class="text-muted">
                                                <i class="fas fa-info-circle me-1"></i>
                                                Nenhuma tag cadastrada. 
                                                <a href="<?= $_ENV['URL_ADM'] ?>crm-create-tag" target="_blank">Criar primeira tag</a>
                                            </small>
                                        <?php else: ?>
                                            <small class="text-muted d-block mb-2">
                                                <i class="fas fa-hand-pointer me-1"></i>Selecione uma ou mais tags:
                                            </small>
                                            <?php foreach ($this->data['tags'] as $tag): ?>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input tag-checkbox" type="checkbox" 
                                                           name="tags[]" 
                                                           value="<?= $tag['id'] ?>" 
                                                           id="tag-<?= $tag['id'] ?>"
                                                           data-color="<?= htmlspecialchars($tag['color']) ?>"
                                                           data-name="<?= htmlspecialchars($tag['name']) ?>"
                                                           <?= in_array($tag['id'], $selectedTags) ? 'checked' : '' ?>>
                                                    <label class="form-check-label w-100" for="tag-<?= $tag['id'] ?>" style="cursor: pointer;">
                                                        <span class="badge" style="background-color: <?= htmlspecialchars($tag['color']) ?>; font-size: 0.8rem;">
                                                            <i class="fas fa-tag me-1"></i><?= htmlspecialchars($tag['name']) ?>
                                                        </span>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <!-- Container para mostrar badges selecionados -->
                                <div id="tags-badges" class="mt-2" style="min-height: 30px;">
                                    <!-- Badges gerados via JavaScript -->
                                </div>
                            </div>
                        </div>

                        <!-- CEP PRIMEIRO (auto-completa tudo) -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">CEP/Código Postal</label>
                                <div class="input-group">
                                    <input type="text" name="zip_code" id="zip_code" class="form-control"
                                           placeholder="Digite o CEP"
                                           value="<?php echo htmlspecialchars($partner['zip_code'] ?? ''); ?>">
                                    <button type="button" class="btn btn-success" id="btn-search-cep" title="Buscar CEP">
                                        <i class="fas fa-search"></i> Buscar
                                    </button>
                                </div>
                                <div class="form-text" id="cep-hint">
                                    <i class="fas fa-lightbulb text-warning"></i>
                                    Digite o CEP para auto-completar o endereço
                                </div>
                                
                                <!-- Alerta se tem CEP mas sem endereço -->
                                <?php if (!empty($partner['zip_code']) && empty($partner['city'])): ?>
                                <div class="alert alert-warning mt-2 mb-2" role="alert">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    <strong>Atenção!</strong> CEP cadastrado mas endereço incompleto.
                                    <br><small>👉 Clique em <strong>[Buscar]</strong> para preencher automaticamente ou marque "Não sei o CEP" para preencher manualmente.</small>
                                </div>
                                <?php endif; ?>
                                
                                <div class="form-check mt-2">
                                    <input type="checkbox" class="form-check-input" id="no-cep-checkbox">
                                    <label class="form-check-label" for="no-cep-checkbox">
                                        <i class="fas fa-hand-pointer text-primary"></i>
                                        <strong>Não sei o CEP</strong> - Preencher endereço manualmente
                                    </label>
                                </div>
                                <div id="cep-loading" class="text-primary mt-2" style="display: none;">
                                    <i class="fas fa-spinner fa-spin"></i> Buscando CEP...
                                </div>
                                <div id="cep-success" class="text-success mt-2" style="display: none;">
                                    <i class="fas fa-check-circle"></i> CEP encontrado!
                                </div>
                                <div id="cep-error" class="text-danger mt-2" style="display: none;"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">País</label>
                                <div class="country-select-wrapper">
                                    <input type="text" id="country-search" class="form-control" 
                                           placeholder="🔍 Digite para buscar..."
                                           autocomplete="off"
                                           readonly>
                                    <select name="country" id="country" class="form-select" size="8" 
                                            style="position: absolute; z-index: 1000; display: none; max-height: 300px;">
                                        <?php
                                        use App\adms\Helpers\CountryHelper;
                                        use App\adms\Helpers\BrazilStatesHelper;
                                        use App\adms\Helpers\InternationalStatesHelper;
                                        use App\adms\Helpers\BrazilDDDHelper;
                                        $countries = CountryHelper::getCountries();
                                        $selectedCountry = $partner['country'] ?? 'BR';
                                        foreach ($countries as $code => $info):
                                        ?>
                                            <option value="<?= $code ?>" 
                                                    data-ddi="<?= $info['ddi'] ?>"
                                                    data-name="<?= strtolower($info['name']) ?>"
                                                    <?= $selectedCountry === $code ? 'selected' : '' ?>>
                                                <?= $info['flag'] ?> <?= $info['name'] ?> (+<?= $info['ddi'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-text" id="country-hint">
                                    <i class="fas fa-info-circle"></i>
                                    Auto-preenchido pelo CEP (ou selecione manualmente)
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Estado/UF <?= !empty($partner['state']) ? '*' : '' ?></label>
                                <select name="state" id="state" class="form-select" <?= !empty($partner['state']) ? 'required' : '' ?> <?= empty($partner['state']) ? 'disabled' : '' ?>>
                                    <?php if (empty($partner['state'])): ?>
                                        <option value="">Aguardando CEP...</option>
                                    <?php endif; ?>
                                    <?php
                                    $selectedState = $partner['state'] ?? '';
                                    
                                    // Se for Brasil, mostrar estados brasileiros
                                    if ($selectedCountry === 'BR'):
                                        $states = BrazilStatesHelper::getStates();
                                        foreach ($states as $uf => $name):
                                    ?>
                                        <option value="<?= $uf ?>" <?= $selectedState === $uf ? 'selected' : '' ?>>
                                            <?= $uf ?> - <?= $name ?>
                                        </option>
                                    <?php 
                                        endforeach;
                                    else:
                                    ?>
                                        <option value="<?= htmlspecialchars($selectedState) ?>" selected>
                                            <?= htmlspecialchars($selectedState) ?>
                                        </option>
                                    <?php endif; ?>
                                </select>
                                <div class="form-text" id="state-hint">
                                    Auto-preenchido pelo CEP
                                </div>
                                <div id="state-ddds-info" class="text-success mt-1" style="display: none;">
                                    <i class="fas fa-phone-alt"></i>
                                    <small><strong>DDDs deste estado:</strong> <span id="state-ddds-list"></span></small>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Telefone</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-success text-white" id="phone-ddi-display" 
                                          style="min-width: 70px; font-weight: bold;">+55</span>
                                    <input type="text" name="phone" id="phone" class="form-control"
                                           placeholder="(00) 0000-0000"
                                           value="<?php echo htmlspecialchars($partner['phone'] ?? ''); ?>">
                                </div>
                                <div class="form-text" id="phone-hint">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <strong>NÃO digite o DDI</strong> - ele já aparece em verde!
                                </div>
                                <div id="phone-ddd-warning" class="text-warning mt-1" style="display: none;">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <small><strong>Atenção:</strong> DDD não corresponde ao estado selecionado</small>
                                </div>
                                <div id="phone-ddd-suggestion" class="text-info mt-1" style="display: none;">
                                    <i class="fas fa-lightbulb"></i>
                                    <small>DDDs válidos para este estado: <strong id="phone-valid-ddds"></strong></small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Celular</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-success text-white" id="mobile-ddi-display"
                                          style="min-width: 70px; font-weight: bold;">+55</span>
                                    <input type="text" name="mobile" id="mobile" class="form-control"
                                           placeholder="(00) 00000-0000"
                                           value="<?php echo htmlspecialchars($partner['mobile'] ?? ''); ?>">
                                </div>
                                <div class="form-text" id="mobile-hint">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <strong>NÃO digite o DDI</strong> - ele já aparece em verde!
                                </div>
                                <div id="mobile-ddd-warning" class="text-warning mt-1" style="display: none;">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <small><strong>Atenção:</strong> DDD não corresponde ao estado selecionado</small>
                                </div>
                                <div id="mobile-ddd-suggestion" class="text-info mt-1" style="display: none;">
                                    <i class="fas fa-lightbulb"></i>
                                    <small>DDDs válidos para este estado: <strong id="mobile-valid-ddds"></strong></small>
                                </div>
                            </div>
                        </div>

                        <!-- ENDEREÇO (auto-completado por CEP) -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Cidade <?= !empty($partner['city']) ? '*' : '' ?></label>
                                <?php 
                                // DEBUG PHP
                                $cityValue = $partner['city'] ?? '';
                                $cityDebug = !empty($cityValue) ? "OK: {$cityValue}" : "VAZIO";
                                error_log("DEBUG HTML - Cidade: {$cityDebug}");
                                ?>
                                <input type="text" name="city" id="city" class="form-control" <?= !empty($partner['city']) ? 'required' : '' ?>
                                       placeholder="<?= !empty($partner['city']) ? 'Digite a cidade' : 'Aguardando CEP...' ?>"
                                       list="city-suggestions"
                                       <?= empty($partner['city']) ? 'disabled' : '' ?>
                                       value="<?php echo htmlspecialchars($partner['city'] ?? ''); ?>"
                                       data-original-value="<?php echo htmlspecialchars($partner['city'] ?? ''); ?>">
                                <datalist id="city-suggestions"></datalist>
                                <small class="text-muted">Debug: <?= $cityDebug ?></small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Bairro <?= !empty($partner['neighborhood']) ? '*' : '' ?></label>
                                <input type="text" name="neighborhood" id="neighborhood" class="form-control" <?= !empty($partner['neighborhood']) ? 'required' : '' ?>
                                       placeholder="<?= !empty($partner['neighborhood']) ? 'Digite o bairro' : 'Aguardando CEP...' ?>"
                                       <?= empty($partner['neighborhood']) ? 'disabled' : '' ?>
                                       value="<?php echo htmlspecialchars($partner['neighborhood'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label class="form-label">Logradouro <?= !empty($partner['address']) ? '*' : '' ?></label>
                                <input type="text" name="address" id="address" class="form-control" <?= !empty($partner['address']) ? 'required' : '' ?>
                                       placeholder="<?= !empty($partner['address']) ? 'Digite o endereço' : 'Aguardando CEP...' ?>"
                                       <?= empty($partner['address']) ? 'disabled' : '' ?>
                                       value="<?php echo htmlspecialchars($partner['address'] ?? ''); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Número</label>
                                <input type="text" name="number" id="number" class="form-control"
                                       placeholder="Nº"
                                       value="<?php echo htmlspecialchars($partner['number'] ?? ''); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Compl.</label>
                                <input type="text" name="complement" id="complement" class="form-control"
                                       placeholder="Apto"
                                       value="<?php echo htmlspecialchars($partner['complement'] ?? ''); ?>">
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
                            <label class="form-label">
                                Responsável
                                <?php if (count($this->data['users']) == 1): ?>
                                    <i class="fas fa-info-circle text-info" title="Você só pode atribuir para si mesmo. Gerentes podem atribuir para subordinados."></i>
                                <?php endif; ?>
                            </label>
                            <select name="responsible_user_id" class="form-select" <?php echo count($this->data['users']) == 1 ? 'readonly style="background-color: #e9ecef; pointer-events: none;"' : ''; ?>>
                                <option value="">Sem responsável</option>
                                <?php foreach ($this->data['users'] as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" <?php echo ($partner['responsible_user_id'] ?? $_SESSION['user_id']) == $user['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['name']); ?>
                                        <?php if ($user['id'] == $_SESSION['user_id']): ?> (Você)<?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (count($this->data['users']) == 1): ?>
                                <div class="form-text text-muted">
                                    <i class="fas fa-user me-1"></i>Apenas você pode ser o responsável por este parceiro.
                                </div>
                            <?php else: ?>
                                <div class="form-text text-success">
                                    <i class="fas fa-users me-1"></i>Como gerente, você pode atribuir para qualquer membro da equipe.
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-building me-1"></i>Departamento
                            </label>
                            <select name="department_id" class="form-select">
                                <option value="">Nenhum</option>
                                <?php foreach ($this->data['departments'] as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" <?php echo ($partner['department_id'] ?? '') == $dept['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> Departamento interno responsável pelo atendimento ao parceiro
                            </small>
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

<style>
/* Estilo para busca de país */
.country-select-wrapper {
    position: relative;
}

.country-select-wrapper #country {
    width: 100%;
    margin-top: 5px;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    background-color: white;
    cursor: pointer;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.country-select-wrapper #country option {
    padding: 8px 12px;
    cursor: pointer;
}

.country-select-wrapper #country option:hover {
    background-color: #2E9263 !important;
    color: white !important;
}

.country-select-wrapper #country-search {
    cursor: pointer;
}

.country-select-wrapper #country-search:focus {
    border-color: #2E9263;
    box-shadow: 0 0 0 0.25rem rgba(46, 146, 99, 0.25);
}
</style>

<script>
// ========================================
// MÁSCARAS DE ENTRADA
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    const typePerson = document.getElementById('type_person');
    const documentInput = document.getElementById('document');
    const phoneInput = document.getElementById('phone');
    const mobileInput = document.getElementById('mobile');
    const countrySelect = document.getElementById('country');
    let currentDDI = '55'; // Brasil por padrão

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

    // Função para aplicar máscara Telefone COM DDI DINÂMICO
    function maskPhone(value) {
        value = value.replace(/\D/g, '');
        
        const ddiLength = currentDDI.length;
        
        // Se começar sem DDI, adicionar o DDI atual
        if (!value.startsWith(currentDDI) && value.length >= 10) {
            value = currentDDI + value;
        }
        
        // Remover DDI duplicado
        const doubleDDI = currentDDI + currentDDI;
        if (value.startsWith(doubleDDI) && value.length > (ddiLength * 2 + 8)) {
            value = value.substring(ddiLength);
        }
        
        // Aplicar máscara: +DDI (DD) NNNNN-NNNN ou +DDI (DD) NNNN-NNNN
        const totalLength = value.length;
        const expectedLengthCelular = ddiLength + 11; // DDI + DDD(2) + 9 dígitos
        const expectedLengthFixo = ddiLength + 10;    // DDI + DDD(2) + 8 dígitos
        
        if (totalLength >= expectedLengthCelular) {
            // Celular: +DDI (DD) NNNNN-NNNN
            value = value.replace(new RegExp(`^(\\d{${ddiLength}})(\\d{2})(\\d{5})(\\d{1,4}).*`), '+$1 ($2) $3-$4');
        } else if (totalLength >= expectedLengthFixo) {
            // Fixo: +DDI (DD) NNNN-NNNN
            value = value.replace(new RegExp(`^(\\d{${ddiLength}})(\\d{2})(\\d{4})(\\d{1,4}).*`), '+$1 ($2) $3-$4');
        }
        
        return value.substring(0, 25);
    }

    // Função para aplicar máscara Celular COM DDI DINÂMICO
    function maskMobile(value) {
        value = value.replace(/\D/g, '');
        
        const ddiLength = currentDDI.length;
        
        // Se começar sem DDI, adicionar o DDI atual
        if (!value.startsWith(currentDDI) && value.length >= 11) {
            value = currentDDI + value;
        }
        
        // Remover DDI duplicado
        const doubleDDI = currentDDI + currentDDI;
        if (value.startsWith(doubleDDI) && value.length > (ddiLength * 2 + 9)) {
            value = value.substring(ddiLength);
        }
        
        // Aplicar máscara: +DDI (DD) NNNNN-NNNN
        const expectedLength = ddiLength + 11; // DDI + DDD(2) + 9 dígitos
        
        if (value.length >= expectedLength) {
            value = value.replace(new RegExp(`^(\\d{${ddiLength}})(\\d{2})(\\d{5})(\\d{1,4}).*`), '+$1 ($2) $3-$4');
        }
        
        return value.substring(0, 25);
    }
    
    // Atualizar placeholders e dicas quando mudar o país
    function updatePhonePlaceholders() {
        const selectedOption = countrySelect.options[countrySelect.selectedIndex];
        currentDDI = selectedOption.getAttribute('data-ddi') || '55';
        
        phoneInput.placeholder = `+${currentDDI} (00) 0000-0000`;
        mobileInput.placeholder = `+${currentDDI} (00) 00000-0000`;
        
        document.getElementById('phone-hint').textContent = `DDI +${currentDDI} fixo - Digite apenas DDD + número`;
        document.getElementById('mobile-hint').textContent = `DDI +${currentDDI} fixo - Digite apenas DDD + número`;
        
        // Reaplicar máscara nos campos existentes
        if (phoneInput.value) {
            phoneInput.value = maskPhone(phoneInput.value);
        } else {
            // Se campo vazio, preencher com DDI
            phoneInput.value = `+${currentDDI} `;
        }
        
        if (mobileInput.value) {
            mobileInput.value = maskMobile(mobileInput.value);
        } else {
            // Se campo vazio, preencher com DDI
            mobileInput.value = `+${currentDDI} `;
        }
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

    // ========================================
    // BUSCA DE PAÍS COM FILTRO
    // ========================================
    const countrySearchInput = document.getElementById('country-search');
    
    if (countrySelect && countrySearchInput) {
        // Carregar DDI do país selecionado inicialmente
        const selectedOption = countrySelect.options[countrySelect.selectedIndex];
        currentDDI = selectedOption.getAttribute('data-ddi') || '55';
        
        // Mostrar país selecionado no campo de busca
        countrySearchInput.value = selectedOption.textContent.trim();
        
        // Atualizar displays DDI
        document.getElementById('phone-ddi-display').textContent = '+' + currentDDI;
        document.getElementById('mobile-ddi-display').textContent = '+' + currentDDI;
        
        // Ao focar no campo de busca, mostrar lista
        countrySearchInput.addEventListener('focus', function() {
            console.log('👉 Campo País focado');
            
            // Se estiver readonly, não permitir edição mas mostrar lista
            if (this.readOnly) {
                console.log('⚠️ Campo País está bloqueado (readonly)');
                return;
            }
            
            countrySelect.style.display = 'block';
            countrySelect.size = 8;
            
            // Se campo vazio, mostrar todos
            if (this.value.trim() === '') {
                filterCountries('');
            } else {
                // Se tem texto, filtrar pelo texto atual
                filterCountries(this.value.toLowerCase());
            }
        });
        
        // Ao clicar no campo, também tentar abrir (mesmo se readonly)
        countrySearchInput.addEventListener('click', function() {
            console.log('👉 Campo País clicado');
            
            // Se estiver readonly, não abrir
            if (this.readOnly) {
                console.log('⚠️ Campo bloqueado - Marque "Não sei o CEP" para habilitar');
                return;
            }
            
            // LIMPAR o campo ao clicar para permitir digitação livre
            // Mas só limpar se ainda tiver o valor padrão do país
            const currentText = this.value.trim();
            if (currentText.includes('Brasil') || currentText.includes('+55') || currentText.includes('BR')) {
                console.log('🗑️ Limpando campo para permitir busca livre');
                this.value = '';
                this.placeholder = '🔍 Digite o nome do país...';
            }
            
            countrySelect.style.display = 'block';
            countrySelect.size = 8;
            filterCountries(''); // Mostrar TODOS os países ao clicar
        });
        
        // Navegação por teclado no campo de busca
        countrySearchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                // ESC fecha a lista
                countrySelect.style.display = 'none';
                this.blur();
            } else if (e.key === 'Enter') {
                // Enter seleciona o primeiro visível
                e.preventDefault();
                const firstVisible = Array.from(countrySelect.options).find(opt => opt.style.display !== 'none');
                if (firstVisible) {
                    countrySelect.value = firstVisible.value;
                    countrySelect.dispatchEvent(new Event('change'));
                }
            } else if (e.key === 'ArrowDown') {
                // Seta baixo foca no select
                e.preventDefault();
                countrySelect.focus();
            }
        });
        
        // Ao digitar, filtrar países (SEM auto-seleção)
        countrySearchInput.addEventListener('input', function(e) {
            const searchTerm = this.value.toLowerCase();
            console.log('🔍 Digitando:', this.value, '| Termo busca:', searchTerm);
            
            // IMPORTANTE: Não permitir que o valor seja sobrescrito
            // Manter o texto digitado pelo usuário
            const currentValue = this.value;
            
            // Filtrar países pela busca
            filterCountries(searchTerm);
            
            // Mostrar dropdown
            if (countrySelect.style.display === 'none') {
                countrySelect.style.display = 'block';
                countrySelect.size = 8;
            }
            
            // Se limpar completamente, resetar filtro mas NÃO mudar país
            if (searchTerm === '') {
                console.log('⚠️ Campo vazio - mostrando todos os países');
                filterCountries('');
            }
        });
        
        // Ao clicar em um país da lista
        countrySelect.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const selectedCountryCode = selected.value;
            
            countrySearchInput.value = selected.textContent.trim();
            currentDDI = selected.getAttribute('data-ddi') || '55';
            
            console.log('🌍 País selecionado:', selectedCountryCode, '| DDI:', currentDDI);
            
            // Atualizar displays DDI
            document.getElementById('phone-ddi-display').textContent = '+' + currentDDI;
            document.getElementById('mobile-ddi-display').textContent = '+' + currentDDI;
            
            // IMPORTANTE: Atualizar campo Estado baseado no país
            if (selectedCountryCode === 'BR') {
                console.log('✅ Brasil selecionado - Carregando estados brasileiros');
                loadBrazilianStates();
            } else {
                console.log('🌎 País internacional - Convertendo Estado para input livre');
                convertStateToFreeInput(selectedCountryCode);
            }
            
            // Esconder lista
            countrySelect.style.display = 'none';
            
            // Limpar campos de telefone ao mudar país
            phoneInput.value = '';
            mobileInput.value = '';
            
            // Limpar campos de endereço
            cityInput.value = '';
            neighborhoodInput.value = '';
            addressInput.value = '';
        });
        
        // Fechar lista ao clicar fora
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.country-select-wrapper')) {
                countrySelect.style.display = 'none';
            }
        });
        
        // Permitir clique direto no dropdown (sem precisar usar o campo de busca)
        countrySelect.addEventListener('click', function() {
            console.log('👉 Clique direto na lista de países');
        });
        
        // Permitir navegação por teclado no select
        countrySelect.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.dispatchEvent(new Event('change'));
                countrySelect.style.display = 'none';
            } else if (e.key === 'Escape') {
                countrySelect.style.display = 'none';
                countrySearchInput.focus();
            }
        });
        
        // Função para filtrar países
        function filterCountries(searchTerm) {
            let visibleCount = 0;
            
            Array.from(countrySelect.options).forEach(option => {
                const countryName = option.getAttribute('data-name') || '';
                const countryText = option.textContent.toLowerCase();
                
                if (searchTerm === '' || countryName.includes(searchTerm) || countryText.includes(searchTerm)) {
                    option.style.display = '';
                    visibleCount++;
                } else {
                    option.style.display = 'none';
                }
            });
            
            // Ajustar altura do select baseado em quantos itens visíveis
            countrySelect.size = Math.min(visibleCount, 8);
        }
        
        // Função para carregar estados brasileiros no select
        function loadBrazilianStates() {
            let currentStateElement = document.getElementById('state');
            if (!currentStateElement) {
                console.error('❌ Campo state não encontrado!');
                return;
            }
            
            const currentStateContainer = currentStateElement.parentNode;
            
            // Se já é um input, converter de volta para select
            if (currentStateElement.tagName === 'INPUT') {
                console.log('🔄 Convertendo INPUT para SELECT (estados brasileiros)');
                const newSelect = document.createElement('select');
                newSelect.name = 'state';
                newSelect.id = 'state';
                newSelect.className = 'form-select';
                newSelect.required = true;
                currentStateContainer.replaceChild(newSelect, currentStateElement);
                
                // Atualizar referência
                currentStateElement = newSelect;
            }
            
            // Limpar e recarregar estados
            currentStateElement.innerHTML = '<option value="">Selecione o estado</option>';
            currentStateElement.disabled = false;
            
            const states = <?php echo json_encode(\App\adms\Helpers\BrazilStatesHelper::getStates()); ?>;
            Object.keys(states).forEach(uf => {
                const option = document.createElement('option');
                option.value = uf;
                option.textContent = `${uf} - ${states[uf]}`;
                currentStateElement.appendChild(option);
            });
            
            console.log('✅ Estados brasileiros carregados:', Object.keys(states).length, 'estados');
            
            // Retornar elemento atualizado
            return currentStateElement;
        }
        
        // Função para converter Estado para input livre ou select (países internacionais)
        function convertStateToFreeInput(countryCode) {
            if (!stateSelect) return;
            
            console.log('🌍 Carregando estados para país:', countryCode);
            
            const currentStateContainer = stateSelect.parentNode;
            
            // Estados por país (principais países da América Latina)
            const statesByCountry = <?php 
                $statesJson = [
                    'BR' => array_keys(\App\adms\Helpers\BrazilStatesHelper::getStates()),
                    'AR' => \App\adms\Helpers\InternationalStatesHelper::getStatesByCountry('AR'),
                    'CL' => \App\adms\Helpers\InternationalStatesHelper::getStatesByCountry('CL'),
                    'CO' => \App\adms\Helpers\InternationalStatesHelper::getStatesByCountry('CO'),
                    'UY' => \App\adms\Helpers\InternationalStatesHelper::getStatesByCountry('UY'),
                    'PY' => \App\adms\Helpers\InternationalStatesHelper::getStatesByCountry('PY'),
                    'PE' => \App\adms\Helpers\InternationalStatesHelper::getStatesByCountry('PE'),
                    'MX' => \App\adms\Helpers\InternationalStatesHelper::getStatesByCountry('MX'),
                    'US' => \App\adms\Helpers\InternationalStatesHelper::getStatesByCountry('US'),
                    'CA' => \App\adms\Helpers\InternationalStatesHelper::getStatesByCountry('CA'),
                    'ES' => \App\adms\Helpers\InternationalStatesHelper::getStatesByCountry('ES'),
                    'PT' => \App\adms\Helpers\InternationalStatesHelper::getStatesByCountry('PT'),
                ];
                echo json_encode($statesJson);
            ?>;
            
            // Verificar se país tem lista de estados
            const countryStates = statesByCountry[countryCode] || [];
            
            if (countryStates.length > 0) {
                console.log('✅ País tem', countryStates.length, 'estados - Criando SELECT');
                
                // Se é input, converter para select
                if (stateSelect.tagName === 'INPUT') {
                    const newSelect = document.createElement('select');
                    newSelect.name = 'state';
                    newSelect.id = 'state';
                    newSelect.className = 'form-select';
                    newSelect.required = true;
                    currentStateContainer.replaceChild(newSelect, stateSelect);
                    window.stateSelect = newSelect;
                    stateSelect = newSelect;
                }
                
                // Preencher select com estados
                stateSelect.innerHTML = '<option value="">Selecione o estado</option>';
                stateSelect.disabled = false;
                
                countryStates.forEach(state => {
                    const option = document.createElement('option');
                    option.value = state;
                    option.textContent = state;
                    stateSelect.appendChild(option);
                });
                
                console.log('✅ Select de estados criado com', countryStates.length, 'opções');
                
            } else {
                console.log('⚠️ País sem lista de estados - Criando INPUT livre');
                
                // Se é select, converter para input
                if (stateSelect.tagName === 'SELECT') {
                    const stateInput = document.createElement('input');
                    stateInput.type = 'text';
                    stateInput.name = 'state';
                    stateInput.id = 'state';
                    stateInput.className = 'form-control';
                    stateInput.placeholder = 'Digite o estado/província';
                    stateInput.required = true;
                    currentStateContainer.replaceChild(stateInput, stateSelect);
                    window.stateSelect = stateInput;
                    stateSelect = stateInput;
                } else {
                    stateSelect.value = '';
                    stateSelect.placeholder = 'Digite o estado/província';
                }
                
                console.log('✅ Campo Estado convertido para input livre');
            }
        }
    }

    // ========================================
    // BUSCA DE CEP E ENDEREÇO (SISTEMA INTELIGENTE)
    // ========================================
    const zipCodeInput = document.getElementById('zip_code');
    const btnSearchCep = document.getElementById('btn-search-cep');
    const noCepCheckbox = document.getElementById('no-cep-checkbox');
    const addressInput = document.getElementById('address');
    const neighborhoodInput = document.getElementById('neighborhood');
    const cityInput = document.getElementById('city');
    let stateSelect = document.getElementById('state'); // LET para permitir reatribuição
    const cepLoading = document.getElementById('cep-loading');
    const cepSuccess = document.getElementById('cep-success');
    const cepError = document.getElementById('cep-error');
    
    // DDDs por estado (para validação)
    const dddsByState = <?php 
        $allDDDs = [];
        $brazilStates = \App\adms\Helpers\BrazilStatesHelper::getStates();
        foreach (array_keys($brazilStates) as $uf) {
            $allDDDs[$uf] = \App\adms\Helpers\BrazilDDDHelper::getDDDsByState($uf);
        }
        echo json_encode($allDDDs);
    ?>;
    
    console.log('📞 DDDs por estado carregados:', Object.keys(dddsByState).length, 'estados');

    // Função para validar DDD baseado no estado
    function validateDDD(phoneValue, fieldType) {
        const currentCountry = countrySelect ? countrySelect.value : 'BR';
        
        // Só validar para Brasil
        if (currentCountry !== 'BR') {
            console.log('ℹ️ País não é Brasil - validação de DDD desabilitada');
            hideDDDWarning(fieldType);
            return true;
        }
        
        // Obter estado atual
        const currentStateElement = document.getElementById('state');
        const currentState = currentStateElement ? currentStateElement.value : '';
        
        if (!currentState || currentState === '') {
            console.log('ℹ️ Estado não selecionado - validação de DDD aguardando');
            hideDDDWarning(fieldType);
            return true;
        }
        
        // Extrair DDD do telefone (primeiros 2 dígitos)
        const digits = phoneValue.replace(/\D/g, '');
        if (digits.length < 2) {
            hideDDDWarning(fieldType);
            return true;
        }
        
        const ddd = digits.substring(0, 2);
        const validDDDs = dddsByState[currentState] || [];
        
        console.log('🔍 Validando DDD:', ddd, '| Estado:', currentState, '| DDDs válidos:', validDDDs.join(', '));
        
        if (validDDDs.includes(ddd)) {
            // DDD válido
            console.log('✅ DDD válido para', currentState);
            hideDDDWarning(fieldType);
            return true;
        } else {
            // DDD inválido
            console.log('⚠️ DDD inválido! DDD', ddd, 'não pertence a', currentState);
            showDDDWarning(fieldType, currentState, validDDDs);
            return false;
        }
    }
    
    // Mostrar aviso de DDD inválido
    function showDDDWarning(fieldType, state, validDDDs) {
        const warningElement = document.getElementById(`${fieldType}-ddd-warning`);
        const suggestionElement = document.getElementById(`${fieldType}-ddd-suggestion`);
        const validDDDsElement = document.getElementById(`${fieldType}-valid-ddds`);
        
        if (warningElement) {
            warningElement.style.display = 'block';
        }
        
        if (suggestionElement && validDDDsElement) {
            validDDDsElement.textContent = validDDDs.join(', ');
            suggestionElement.style.display = 'block';
        }
    }
    
    // Esconder aviso de DDD
    function hideDDDWarning(fieldType) {
        const warningElement = document.getElementById(`${fieldType}-ddd-warning`);
        const suggestionElement = document.getElementById(`${fieldType}-ddd-suggestion`);
        
        if (warningElement) {
            warningElement.style.display = 'none';
        }
        
        if (suggestionElement) {
            suggestionElement.style.display = 'none';
        }
    }
    
    // Função para mostrar DDDs válidos do estado selecionado
    function showStateDDDsInfo(state) {
        const infoElement = document.getElementById('state-ddds-info');
        const listElement = document.getElementById('state-ddds-list');
        
        if (!state || state === '') {
            if (infoElement) infoElement.style.display = 'none';
            return;
        }
        
        const validDDDs = dddsByState[state] || [];
        
        if (validDDDs.length > 0 && infoElement && listElement) {
            listElement.textContent = validDDDs.join(', ');
            infoElement.style.display = 'block';
            console.log('📞 DDDs válidos para', state + ':', validDDDs.join(', '));
        } else {
            if (infoElement) infoElement.style.display = 'none';
        }
    }

    // Máscara de Telefone COM VALIDAÇÃO
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            e.target.value = maskPhone(e.target.value);
            
            // Validar DDD após aplicar máscara
            setTimeout(() => {
                validateDDD(e.target.value, 'phone');
            }, 300);
        });
        
        // Validar ao sair do campo
        phoneInput.addEventListener('blur', function(e) {
            validateDDD(e.target.value, 'phone');
        });
    }

    // Máscara de Celular COM VALIDAÇÃO
    if (mobileInput) {
        mobileInput.addEventListener('input', function(e) {
            e.target.value = maskMobile(e.target.value);
            
            // Validar DDD após aplicar máscara
            setTimeout(() => {
                validateDDD(e.target.value, 'mobile');
            }, 300);
        });
        
        // Validar ao sair do campo
        mobileInput.addEventListener('blur', function(e) {
            validateDDD(e.target.value, 'mobile');
        });
    }
    
    // Revalidar ao mudar o Estado
    if (stateSelect) {
        // Usar event delegation para capturar mudanças mesmo após conversão SELECT/INPUT
        document.addEventListener('change', function(e) {
            if (e.target && e.target.id === 'state') {
                const selectedState = e.target.value;
                console.log('🔄 Estado alterado para:', selectedState, '- Revalidando DDDs...');
                
                // Mostrar DDDs válidos para o estado
                showStateDDDsInfo(selectedState);
                
                // Revalidar telefone
                if (phoneInput && phoneInput.value) {
                    validateDDD(phoneInput.value, 'phone');
                }
                
                // Revalidar celular
                if (mobileInput && mobileInput.value) {
                    validateDDD(mobileInput.value, 'mobile');
                }
            }
        });
    }
    
    // Função para mostrar DDDs válidos do estado selecionado
    function showStateDDDsInfo(state) {
        const infoElement = document.getElementById('state-ddds-info');
        const listElement = document.getElementById('state-ddds-list');
        
        if (!state || state === '') {
            if (infoElement) infoElement.style.display = 'none';
            return;
        }
        
        const validDDDs = dddsByState[state] || [];
        
        if (validDDDs.length > 0 && infoElement && listElement) {
            listElement.textContent = validDDDs.join(', ');
            infoElement.style.display = 'block';
            console.log('📞 DDDs válidos para', state + ':', validDDDs.join(', '));
        } else {
            if (infoElement) infoElement.style.display = 'none';
        }
    }

    // ========================================
    // BUSCA DE CEP E ENDEREÇO (SISTEMA INTELIGENTE)
    // DEBUG: Verificar se elementos foram encontrados
    console.log('=== DEBUG CEP SYSTEM ===');
    console.log('zipCodeInput:', zipCodeInput ? 'FOUND' : 'NOT FOUND');
    console.log('btnSearchCep:', btnSearchCep ? 'FOUND' : 'NOT FOUND');
    console.log('noCepCheckbox:', noCepCheckbox ? 'FOUND' : 'NOT FOUND');
    console.log('countrySearchInput:', countrySearchInput ? 'FOUND' : 'NOT FOUND');
    console.log('stateSelect:', stateSelect ? 'FOUND' : 'NOT FOUND');
    console.log('addressInput:', addressInput ? 'FOUND' : 'NOT FOUND');
    console.log('cityInput:', cityInput ? 'FOUND' : 'NOT FOUND');

    // Máscara de CEP (Brasil)
    if (zipCodeInput) {
        console.log('✅ Adicionando máscara de CEP');
        zipCodeInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.length > 8) {
                value = value.substring(0, 8);
            }
            
            if (value.length >= 5) {
                e.target.value = value.replace(/^(\d{5})(\d)/, '$1-$2');
            } else {
                e.target.value = value;
            }
        });

        // Buscar ao pressionar Enter
        zipCodeInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                console.log('👉 Enter pressionado no CEP');
                searchCep();
            }
        });
    } else {
        console.error('❌ Campo CEP não encontrado!');
    }

    // Botão de buscar CEP
    if (btnSearchCep) {
        console.log('✅ Adicionando evento ao botão Buscar');
        btnSearchCep.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('👉 Botão Buscar clicado!');
            searchCep();
        });
    } else {
        console.error('❌ Botão Buscar não encontrado!');
    }

    // Checkbox "Não sei o CEP"
    if (noCepCheckbox) {
        console.log('✅ Adicionando evento ao checkbox "Não sei o CEP"');
        noCepCheckbox.addEventListener('change', function() {
            if (this.checked) {
                console.log('☑️ Checkbox marcado - Bloqueando CEP e liberando campos manuais');
                
                // Limpar e bloquear CEP
                zipCodeInput.value = '';
                zipCodeInput.disabled = true;
                zipCodeInput.style.backgroundColor = '#e9ecef';
                zipCodeInput.placeholder = 'CEP desabilitado';
                
                // Desabilitar botão Buscar
                btnSearchCep.disabled = true;
                btnSearchCep.classList.add('btn-secondary');
                btnSearchCep.classList.remove('btn-success');
                
                // Habilitar preenchimento manual
                enableManualAddressInput();
                
            } else {
                console.log('☐ Checkbox desmarcado - Liberando CEP e bloqueando campos manuais');
                
                // Habilitar CEP
                zipCodeInput.disabled = false;
                zipCodeInput.style.backgroundColor = '';
                zipCodeInput.placeholder = 'Digite o CEP';
                
                // Habilitar botão Buscar
                btnSearchCep.disabled = false;
                btnSearchCep.classList.add('btn-success');
                btnSearchCep.classList.remove('btn-secondary');
                
                // Bloquear campos até digitar CEP
                disableManualAddressInput();
            }
        });
    } else {
        console.error('❌ Checkbox "Não sei o CEP" não encontrado!');
    }

    // Função para buscar CEP no ViaCEP
    function searchCep() {
        const cep = zipCodeInput.value.replace(/\D/g, '');
        
        if (cep.length !== 8) {
            showCepError('CEP deve ter 8 dígitos. Digite apenas números.');
            return;
        }

        // Resetar mensagens
        cepError.style.display = 'none';
        cepSuccess.style.display = 'none';
        cepLoading.style.display = 'block';
        
        console.log('Buscando CEP:', cep); // Debug
        
        // Buscar no ViaCEP
        fetch(`https://viacep.com.br/ws/${cep}/json/`)
            .then(response => {
                console.log('Response status:', response.status); // Debug
                return response.json();
            })
            .then(data => {
                console.log('📦 Data recebida do ViaCEP:', data);
                cepLoading.style.display = 'none';
                
                if (data.erro) {
                    showCepError('CEP não encontrado. Clique em "Não sei o CEP" para preencher manualmente.');
                    return;
                }
                
                console.log('✅ CEP VÁLIDO!');
                console.log('  Logradouro:', data.logradouro);
                console.log('  Bairro:', data.bairro);
                console.log('  Cidade:', data.localidade);
                console.log('  UF:', data.uf);
                
                // Auto-preencher PAÍS (Brasil)
                countrySelect.value = 'BR';
                const brOption = Array.from(countrySelect.options).find(opt => opt.value === 'BR');
                if (brOption) {
                    countrySearchInput.value = brOption.textContent.trim();
                    currentDDI = '55';
                    document.getElementById('phone-ddi-display').textContent = '+55';
                    document.getElementById('mobile-ddi-display').textContent = '+55';
                    console.log('✅ País definido: Brasil');
                }
                
                // Carregar estados brasileiros e preencher UF
                console.log('🔄 Carregando estados brasileiros...');
                const stateElement = loadBrazilianStates();
                
                // Aguardar SELECT ser criado e então preencher
                setTimeout(() => {
                    const currentState = document.getElementById('state');
                    console.log('📍 Tipo do elemento State:', currentState ? currentState.tagName : 'NULL');
                    console.log('📍 Total de options:', currentState && currentState.options ? currentState.options.length : 0);
                    
                    if (currentState && currentState.tagName === 'SELECT') {
                        // Verificar se estado existe nas options
                        const ufOption = Array.from(currentState.options).find(opt => opt.value === data.uf);
                        
                        if (ufOption) {
                            // HABILITAR o select ANTES de preencher
                            currentState.disabled = false;
                            currentState.removeAttribute('disabled');
                            currentState.removeAttribute('required'); // Adicionar required depois
                            
                            currentState.value = data.uf;
                            
                            // Adicionar required de volta
                            currentState.required = true;
                            
                            console.log('✅✅✅ Estado preenchido com SUCESSO:', data.uf, '-', ufOption.textContent);
                            console.log('  ✅ Estado HABILITADO | disabled:', currentState.disabled, '| value:', currentState.value);
                            
                            // Mostrar DDDs válidos para o estado
                            setTimeout(() => {
                                showStateDDDsInfo(data.uf);
                            }, 100);
                        } else {
                            console.error('❌ UF não encontrada:', data.uf);
                            console.log('📋 Options disponíveis:', Array.from(currentState.options).map(o => o.value).join(', '));
                        }
                    } else {
                        console.error('❌ Campo State não é SELECT ou não existe');
                        console.log('Tipo atual:', currentState ? currentState.tagName : 'NULL');
                    }
                }, 200);
                
                // HABILITAR E PREENCHER CIDADE
                cityInput.disabled = false;
                cityInput.removeAttribute('disabled');
                cityInput.required = true;
                cityInput.value = data.localidade || '';
                console.log('✅ Cidade HABILITADA e preenchida:', cityInput.value, '| disabled:', cityInput.disabled);
                
                // HABILITAR E PREENCHER BAIRRO
                neighborhoodInput.disabled = false;
                neighborhoodInput.removeAttribute('disabled');
                neighborhoodInput.required = true;
                neighborhoodInput.value = data.bairro || '';
                console.log('✅ Bairro HABILITADO e preenchido:', neighborhoodInput.value, '| disabled:', neighborhoodInput.disabled);
                
                // HABILITAR E PREENCHER LOGRADOURO
                addressInput.disabled = false;
                addressInput.removeAttribute('disabled');
                addressInput.required = true;
                addressInput.value = data.logradouro || '';
                console.log('✅ Logradouro HABILITADO e preenchido:', addressInput.value, '| disabled:', addressInput.disabled);
                
                // Destacar campos preenchidos pelo CEP (fundo azul claro)
                if (data.logradouro) {
                    addressInput.style.backgroundColor = '#e3f2fd';
                    addressInput.readOnly = false; // Garantir que NÃO fica readonly
                    console.log('  ✅ Logradouro: editável, fundo azul');
                }
                if (data.bairro) {
                    neighborhoodInput.style.backgroundColor = '#e3f2fd';
                    neighborhoodInput.readOnly = false;
                    console.log('  ✅ Bairro: editável, fundo azul');
                }
                if (data.localidade) {
                    cityInput.style.backgroundColor = '#e3f2fd';
                    cityInput.readOnly = false;
                    console.log('  ✅ Cidade: editável, fundo azul');
                }
                
                console.log('✅✅✅ Campos de endereço 100% HABILITADOS e EDITÁVEIS');
                console.log('📝 Campos podem ser salvos no banco agora!');
                
                // Focar no campo Número
                const numberInput = document.getElementById('number');
                if (numberInput) {
                    numberInput.focus();
                }
                
                // Mostrar mensagem de sucesso
                cepSuccess.style.display = 'block';
                setTimeout(() => {
                    cepSuccess.style.display = 'none';
                }, 3000);
            })
            .catch(error => {
                console.error('Erro ao buscar CEP:', error); // Debug
                cepLoading.style.display = 'none';
                showCepError('Erro ao buscar CEP. Verifique sua conexão com a internet.');
            });
    }

    // Função para mostrar erro
    function showCepError(message) {
        cepError.style.display = 'block';
        cepError.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + message;
    }

    // Função para habilitar preenchimento manual
    function enableManualAddressInput() {
        console.log('🔓 Habilitando preenchimento manual...');
        
        // Habilitar País para seleção manual
        if (countrySearchInput) {
            countrySearchInput.removeAttribute('readonly');
            countrySearchInput.readOnly = false;
            countrySearchInput.placeholder = '🔍 Clique para selecionar o país...';
            countrySearchInput.classList.add('border-warning');
            countrySearchInput.style.cursor = 'pointer';
            console.log('✅ Campo País habilitado');
        }
        
        // Carregar estados brasileiros (padrão)
        const currentCountry = countrySelect ? countrySelect.value : 'BR';
        
        if (currentCountry === 'BR') {
            console.log('🇧🇷 Carregando estados brasileiros');
            loadBrazilianStates();
        } else {
            console.log('🌍 Carregando estados para país:', currentCountry);
            convertStateToFreeInput(currentCountry);
        }
        
        // Habilitar demais campos
        if (cityInput) {
            cityInput.disabled = false;
            cityInput.placeholder = 'Digite a cidade';
            cityInput.removeAttribute('readonly');
            cityInput.readOnly = false;
            cityInput.style.backgroundColor = '';
            console.log('✅ Campo Cidade habilitado');
        }
        
        if (neighborhoodInput) {
            neighborhoodInput.disabled = false;
            neighborhoodInput.placeholder = 'Digite o bairro';
            neighborhoodInput.removeAttribute('readonly');
            neighborhoodInput.readOnly = false;
            neighborhoodInput.style.backgroundColor = '';
            console.log('✅ Campo Bairro habilitado');
        }
        
        if (addressInput) {
            addressInput.disabled = false;
            addressInput.placeholder = 'Digite o endereço completo';
            addressInput.removeAttribute('readonly');
            addressInput.readOnly = false;
            addressInput.style.backgroundColor = '';
            console.log('✅ Campo Logradouro habilitado');
        }
        
        // Focar no país e abrir dropdown
        setTimeout(() => {
            if (countrySearchInput) {
                countrySearchInput.focus();
                countrySearchInput.click();
                console.log('👉 Foco no campo País');
            }
        }, 100);
    }
    
    // Função para desabilitar preenchimento manual (volta ao modo CEP)
    function disableManualAddressInput() {
        console.log('🔒 Desabilitando preenchimento manual - Aguardando CEP...');
        
        // Bloquear País
        if (countrySearchInput) {
            countrySearchInput.readOnly = true;
            countrySearchInput.placeholder = 'Aguardando CEP...';
            countrySearchInput.classList.remove('border-warning');
            countrySearchInput.style.cursor = 'not-allowed';
        }
        
        // Bloquear Estado
        if (stateSelect) {
            stateSelect.disabled = true;
            stateSelect.innerHTML = '<option value="">Aguardando CEP...</option>';
        }
        
        // Bloquear demais campos
        if (cityInput) {
            cityInput.disabled = true;
            cityInput.value = '';
            cityInput.placeholder = 'Aguardando CEP...';
        }
        
        if (neighborhoodInput) {
            neighborhoodInput.disabled = true;
            neighborhoodInput.value = '';
            neighborhoodInput.placeholder = 'Aguardando CEP...';
        }
        
        if (addressInput) {
            addressInput.disabled = true;
            addressInput.value = '';
            addressInput.placeholder = 'Aguardando CEP...';
        }
        
        console.log('✅ Campos bloqueados - Digite o CEP para habilitar');
    }
    
    // ========================================
    // MODO EDIÇÃO: Garantir que campos salvos permaneçam habilitados
    // ========================================
    const isEditMode = document.querySelector('input[name="id"]') !== null;
    
    if (isEditMode) {
        console.log('📝 MODO EDIÇÃO DETECTADO - Preservando dados salvos');
        
        // Verificar e GARANTIR que campos com dados permaneçam habilitados
        setTimeout(function() {
            const cityInput = document.getElementById('city');
            const neighborhoodInput = document.getElementById('neighborhood');
            const addressInput = document.getElementById('address');
            const stateSelect = document.getElementById('state');
            const zipCodeInput = document.getElementById('zip_code');
            
            // Verificar se TEM endereço completo salvo
            const hasCity = cityInput && cityInput.value && cityInput.value !== '' && cityInput.value !== 'Aguardando CEP...';
            const hasNeighborhood = neighborhoodInput && neighborhoodInput.value && neighborhoodInput.value !== '' && neighborhoodInput.value !== 'Aguardando CEP...';
            const hasAddress = addressInput && addressInput.value && addressInput.value !== '' && addressInput.value !== 'Aguardando CEP...';
            const hasState = stateSelect && stateSelect.value && stateSelect.value !== '';
            const hasCEP = zipCodeInput && zipCodeInput.value && zipCodeInput.value !== '';
            
            console.log('🔍 DEBUG - Valores dos campos:');
            console.log('  - Cidade:', cityInput ? cityInput.value : 'NULL');
            console.log('  - Bairro:', neighborhoodInput ? neighborhoodInput.value : 'NULL');
            console.log('  - Logradouro:', addressInput ? addressInput.value : 'NULL');
            console.log('  - Estado:', stateSelect ? stateSelect.value : 'NULL');
            console.log('  - CEP:', hasCEP ? zipCodeInput.value : 'NULL');
            
            if (hasCity || hasNeighborhood || hasAddress || hasState) {
                console.log('✅ ENDEREÇO SALVO DETECTADO - Habilitando campos...');
                
                // FORÇAR habilitação dos campos (ignorar lógica de CEP)
                if (cityInput) {
                    cityInput.disabled = false;
                    cityInput.readOnly = false;
                    cityInput.removeAttribute('disabled');
                    cityInput.removeAttribute('readonly');
                    cityInput.style.backgroundColor = '';
                    cityInput.placeholder = 'Digite a cidade';
                    console.log('  ✅ Cidade habilitada:', cityInput.value, '| disabled:', cityInput.disabled);
                }
                
                if (neighborhoodInput) {
                    neighborhoodInput.disabled = false;
                    neighborhoodInput.readOnly = false;
                    neighborhoodInput.removeAttribute('disabled');
                    neighborhoodInput.removeAttribute('readonly');
                    neighborhoodInput.style.backgroundColor = '';
                    neighborhoodInput.placeholder = 'Digite o bairro';
                    console.log('  ✅ Bairro habilitado:', neighborhoodInput.value, '| disabled:', neighborhoodInput.disabled);
                }
                
                if (addressInput) {
                    addressInput.disabled = false;
                    addressInput.readOnly = false;
                    addressInput.removeAttribute('disabled');
                    addressInput.removeAttribute('readonly');
                    addressInput.style.backgroundColor = '';
                    addressInput.placeholder = 'Digite o endereço';
                    console.log('  ✅ Logradouro habilitado:', addressInput.value, '| disabled:', addressInput.disabled);
                }
                
                if (stateSelect) {
                    stateSelect.disabled = false;
                    stateSelect.removeAttribute('disabled');
                    console.log('  ✅ Estado habilitado:', stateSelect.value, '| disabled:', stateSelect.disabled);
                }
                
                console.log('✅✅✅ TODOS OS CAMPOS DE ENDEREÇO HABILITADOS PARA EDIÇÃO');
                console.log('ℹ️ Altere o CEP e clique em Buscar para atualizar, ou edite manualmente');
                
            } else {
                console.log('⚠️ Nenhum endereço salvo - Aguardando CEP ou preenchimento manual');
                console.log('  hasCity:', hasCity, '| hasNeighborhood:', hasNeighborhood);
                console.log('  hasAddress:', hasAddress, '| hasState:', hasState);
            }
        }, 200); // Delay maior para garantir que o PHP renderizou
    }
    
    // ========================================
    // VALIDAÇÃO ANTES DE SUBMETER FORMULÁRIO
    // ========================================
    const partnerForm = document.querySelector('form[action*="crm-"][action*="-partner"]');
    if (partnerForm) {
        partnerForm.addEventListener('submit', function(e) {
            console.log('📝 Formulário sendo submetido...');
            console.log('=== VERIFICANDO CAMPOS ANTES DE ENVIAR ===');
            
            // Remover 'required' de campos disabled para permitir submit
            const disabledInputs = partnerForm.querySelectorAll('input[disabled], select[disabled]');
            disabledInputs.forEach(input => {
                if (input.hasAttribute('required')) {
                    input.removeAttribute('required');
                    console.log('  ✅ Removido required de campo disabled:', input.name);
                }
            });
            
            // Verificar campos de endereço (para debug)
            const cityInput = partnerForm.querySelector('[name="city"]');
            const stateSelect = partnerForm.querySelector('[name="state"]');
            const neighborhoodInput = partnerForm.querySelector('[name="neighborhood"]');
            const addressInput = partnerForm.querySelector('[name="address"]');
            
            console.log('📊 STATUS DOS CAMPOS DE ENDEREÇO:');
            console.log('  Estado:', stateSelect ? (stateSelect.disabled ? '🔒 disabled' : '✅ enabled') + ' | value: ' + stateSelect.value : 'NULL');
            console.log('  Cidade:', cityInput ? (cityInput.disabled ? '🔒 disabled' : '✅ enabled') + ' | value: ' + cityInput.value : 'NULL');
            console.log('  Bairro:', neighborhoodInput ? (neighborhoodInput.disabled ? '🔒 disabled' : '✅ enabled') + ' | value: ' + neighborhoodInput.value : 'NULL');
            console.log('  Logradouro:', addressInput ? (addressInput.disabled ? '🔒 disabled' : '✅ enabled') + ' | value: ' + addressInput.value : 'NULL');
            
            // Garantir que campos readOnly NÃO sejam disabled
            const readOnlyInputs = partnerForm.querySelectorAll('[readonly]');
            readOnlyInputs.forEach(input => {
                if (input.disabled) {
                    input.disabled = false;
                    input.removeAttribute('disabled');
                    console.log('  ⚠️ Campo readonly estava disabled - Habilitado:', input.name);
                }
            });
            
            console.log('✅ Validação concluída - Formulário pode ser enviado');
            return true; // Permitir submit
        });
        
        console.log('✅ Validação de submit adicionada ao formulário');
    }
    
    // ========================================
    // TAGS: Checkboxes com badges dinâmicos
    // ========================================
    const tagCheckboxes = document.querySelectorAll('.tag-checkbox');
    const badgesContainer = document.getElementById('tags-badges');
    const tagsCountSpan = document.getElementById('tags-selected-count');
    
    if (tagCheckboxes.length > 0 && badgesContainer) {
        // Função para renderizar badges
        function renderTagsBadges() {
            const checkedBoxes = Array.from(tagCheckboxes).filter(cb => cb.checked);
            badgesContainer.innerHTML = '';
            
            // Atualizar contador no botão dropdown
            if (tagsCountSpan) {
                if (checkedBoxes.length === 0) {
                    tagsCountSpan.textContent = 'Selecionar tags...';
                } else if (checkedBoxes.length === 1) {
                    tagsCountSpan.textContent = '1 tag selecionada';
                } else {
                    tagsCountSpan.textContent = `${checkedBoxes.length} tags selecionadas`;
                }
            }
            
            // Se nenhuma tag selecionada
            if (checkedBoxes.length === 0) {
                badgesContainer.innerHTML = '<small class="text-muted"><i class="fas fa-info-circle me-1"></i>Nenhuma tag selecionada</small>';
                return;
            }
            
            // Renderizar badges
            checkedBoxes.forEach(checkbox => {
                const badge = document.createElement('span');
                badge.className = 'badge me-2 mb-2';
                badge.style.backgroundColor = checkbox.dataset.color || '#6c757d';
                badge.style.fontSize = '0.85rem';
                badge.style.padding = '0.4rem 0.7rem';
                badge.style.cursor = 'pointer';
                badge.innerHTML = `<i class="fas fa-tag me-1"></i>${checkbox.dataset.name} <i class="fas fa-times ms-1"></i>`;
                badge.title = 'Clique para remover';
                
                // Remover ao clicar no X
                badge.addEventListener('click', function(e) {
                    e.preventDefault();
                    checkbox.checked = false;
                    renderTagsBadges();
                });
                
                badgesContainer.appendChild(badge);
            });
        }
        
        // Renderizar inicial
        renderTagsBadges();
        
        // Atualizar ao mudar qualquer checkbox
        tagCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', renderTagsBadges);
        });
        
        // Evitar que o dropdown feche ao clicar nos checkboxes
        const dropdownMenu = document.querySelector('#tagsDropdown + .dropdown-menu');
        if (dropdownMenu) {
            dropdownMenu.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
        
        console.log('✅ Sistema de tags com checkboxes inicializado');
    }
});
</script>

