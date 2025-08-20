<?php

use App\adms\Helpers\CSRFHelper;

?>
<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Inventário LGPD</h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-inventory" class="text-decoration-none">Inventário</a>
            </li>
            <li class="breadcrumb-item">Editar</li>
        </ol>

    </div>

    <div class="card mb-4 border-light shadow">

        <div class="card-header hstack gap-2">
            <span>Editar</span>

            <span class="ms-auto d-sm-flex flex-row">
            <?php
                if (in_array('LgpdInventory', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}lgpd-inventory' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-list'></i> Listar</a> ";
                }
                ?>
            </span>

        </div>

        <div class="card-body">

            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <!-- Formulário para editar o inventário -->
            <form action="" method="POST" class="row g-3">

                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_edit_inventory'); ?>">

                <div class="col-md-6 col-sm-12">
                    <label for="area" class="form-label">Área</label>
                    <input type="text" name="area" class="form-control" id="area" placeholder="Ex: Vendas, RH, Marketing" value="<?php echo $this->data['form']['area'] ?? ($this->data['inventory']['area'] ?? ''); ?>">
                </div>

                <div class="col-md-6 col-sm-12">
                    <label for="data_type" class="form-label">Tipo de Dado</label>
                    <input type="text" name="data_type" class="form-control" id="data_type" placeholder="Ex: Nome, CPF, Salário" value="<?php echo $this->data['form']['data_type'] ?? ($this->data['inventory']['data_type'] ?? ''); ?>">
                </div>

                <div class="col-md-6 col-sm-12">
                    <label for="department_id" class="form-label">Departamento</label>
                    <select name="department_id" class="form-select" id="department_id" required>
                        <option value="" selected>Selecione</option>
                        <?php if (isset($this->data['departamentos'])): ?>
                            <?php foreach ($this->data['departamentos'] as $department): ?>
                                <option value="<?= $department['id'] ?>" <?= (isset($this->data['form']['department_id']) ? $this->data['form']['department_id'] : ($this->data['inventory']['department_id'] ?? '')) == $department['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($department['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="col-md-6 col-sm-12">
                    <label for="data_subject" class="form-label">Titular</label>
                    <select name="data_subject" class="form-select" id="data_subject" required>
                        <option value="" selected>Selecione</option>
                        <?php if (isset($this->data['categorias_titulares'])): ?>
                            <?php foreach ($this->data['categorias_titulares'] as $categoria): ?>
                                <option value="<?= $categoria['id'] ?>" <?= (isset($this->data['form']['data_subject']) ? $this->data['form']['data_subject'] : ($this->data['inventory']['data_subject_id'] ?? '')) == $categoria['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($categoria['titular']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label for="data_groups" class="form-label">Grupos de Dados</label>
                    
                    <!-- Botão para expandir/colapsar a seleção -->
                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="toggleDataGroups">
                            <i class="fa-solid fa-chevron-down" id="toggleIcon"></i>
                            Selecionar Grupos de Dados
                        </button>
                        <small class="form-text text-muted ms-2">Clique para expandir e selecionar os grupos de dados</small>
                    </div>

                    <!-- Área de seleção (inicialmente oculta) -->
                    <div class="border rounded p-3 mb-3" id="dataGroupsSelection" style="display: none; max-height: 400px; overflow-y: auto;">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-search"></i></span>
                                    <input type="text" class="form-control" id="searchDataGroups" placeholder="Buscar grupos de dados...">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary mb-2"><i class="fa-solid fa-user"></i> Dados Pessoais</h6>
                                <?php if (isset($this->data['grupos_dados']) && is_array($this->data['grupos_dados'])): ?>
                                    <?php foreach ($this->data['grupos_dados'] as $grupo): ?>
                                        <?php if (!$grupo['is_sensitive']): ?>
                                            <?php 
                                            $isSelected = false;
                                            if (isset($this->data['grupos_selecionados']) && is_array($this->data['grupos_selecionados'])) {
                                                foreach ($this->data['grupos_selecionados'] as $selecionado) {
                                                    if ($selecionado['id'] == $grupo['id']) {
                                                        $isSelected = true;
                                                        break;
                                                    }
                                                }
                                            }
                                            ?>
                                            <div class="form-check data-group-item" data-category="pessoal">
                                                <input class="form-check-input" type="checkbox" name="data_groups[]" value="<?= $grupo['id'] ?? '' ?>" id="grupo_<?= $grupo['id'] ?? '' ?>" 
                                                       <?= $isSelected ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="grupo_<?= $grupo['id'] ?? '' ?>">
                                                    <strong><?= htmlspecialchars($grupo['name'] ?? '') ?></strong>
                                                    <span class="badge bg-info ms-2">Pessoal</span>
                                                    <br>
                                                    <small class="text-muted"><?= htmlspecialchars($grupo['example_fields'] ?? '') ?></small>
                                                </label>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="text-danger mb-2"><i class="fa-solid fa-shield-alt"></i> Dados Sensíveis</h6>
                                <?php if (isset($this->data['grupos_dados']) && is_array($this->data['grupos_dados'])): ?>
                                    <?php foreach ($this->data['grupos_dados'] as $grupo): ?>
                                        <?php if ($grupo['is_sensitive']): ?>
                                            <?php 
                                            $isSelected = false;
                                            if (isset($this->data['grupos_selecionados']) && is_array($this->data['grupos_selecionados'])) {
                                                foreach ($this->data['grupos_selecionados'] as $selecionado) {
                                                    if ($selecionado['id'] == $grupo['id']) {
                                                        $isSelected = true;
                                                        break;
                                                    }
                                                }
                                            }
                                            ?>
                                            <div class="form-check data-group-item" data-category="sensivel">
                                                <input class="form-check-input" type="checkbox" name="data_groups[]" value="<?= $grupo['id'] ?? '' ?>" id="grupo_<?= $grupo['id'] ?? '' ?>" 
                                                       <?= $isSelected ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="grupo_<?= $grupo['id'] ?? '' ?>">
                                                    <strong><?= htmlspecialchars($grupo['name'] ?? '') ?></strong>
                                                    <span class="badge bg-danger ms-2">Sensível</span>
                                                    <br>
                                                    <small class="text-muted"><?= htmlspecialchars($grupo['example_fields'] ?? '') ?></small>
                                                </label>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-12">
                                <button type="button" class="btn btn-success btn-sm" id="selectAllGroups">
                                    <i class="fa-solid fa-check-double"></i> Selecionar Todos
                                </button>
                                <button type="button" class="btn btn-warning btn-sm" id="deselectAllGroups">
                                    <i class="fa-solid fa-times"></i> Desmarcar Todos
                                </button>
                                <button type="button" class="btn btn-primary btn-sm" id="confirmSelection">
                                    <i class="fa-solid fa-check"></i> Confirmar Seleção
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Área de grupos selecionados -->
                    <div class="border rounded p-3 bg-light" id="selectedGroupsArea">
                        <h6 class="text-success mb-2">
                            <i class="fa-solid fa-list-check"></i> Grupos Selecionados
                            <span class="badge bg-success ms-2" id="selectedCount">0</span>
                        </h6>
                        <div id="selectedGroupsList">
                            <p class="text-muted mb-0">Nenhum grupo selecionado. Clique em "Selecionar Grupos de Dados" para escolher.</p>
                        </div>
                    </div>
                    
                    <small class="form-text text-muted mt-2">Selecione os grupos de dados que são tratados neste inventário.</small>
                </div>

                <!-- Script para funcionalidade da seleção -->
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const toggleBtn = document.getElementById('toggleDataGroups');
                    const toggleIcon = document.getElementById('toggleIcon');
                    const selectionArea = document.getElementById('dataGroupsSelection');
                    const selectedArea = document.getElementById('selectedGroupsArea');
                    const selectedList = document.getElementById('selectedGroupsList');
                    const selectedCount = document.getElementById('selectedCount');
                    const searchInput = document.getElementById('searchDataGroups');
                    const selectAllBtn = document.getElementById('selectAllGroups');
                    const deselectAllBtn = document.getElementById('deselectAllGroups');
                    const confirmBtn = document.getElementById('confirmSelection');
                    
                    // Toggle da área de seleção
                    toggleBtn.addEventListener('click', function() {
                        if (selectionArea.style.display === 'none') {
                            selectionArea.style.display = 'block';
                            toggleIcon.className = 'fa-solid fa-chevron-up';
                            toggleBtn.innerHTML = '<i class="fa-solid fa-chevron-up" id="toggleIcon"></i> Ocultar Seleção';
                        } else {
                            selectionArea.style.display = 'none';
                            toggleIcon.className = 'fa-solid fa-chevron-down';
                            toggleBtn.innerHTML = '<i class="fa-solid fa-chevron-down" id="toggleIcon"></i> Selecionar Grupos de Dados';
                        }
                    });
                    
                    // Busca em tempo real
                    searchInput.addEventListener('input', function() {
                        const searchTerm = this.value.toLowerCase();
                        const items = document.querySelectorAll('.data-group-item');
                        
                        items.forEach(item => {
                            const label = item.querySelector('label').textContent.toLowerCase();
                            if (label.includes(searchTerm)) {
                                item.style.display = 'block';
                            } else {
                                item.style.display = 'none';
                            }
                        });
                    });
                    
                    // Selecionar todos
                    selectAllBtn.addEventListener('click', function() {
                        const checkboxes = document.querySelectorAll('input[name="data_groups[]"]');
                        checkboxes.forEach(checkbox => checkbox.checked = true);
                        updateSelectedGroups();
                    });
                    
                    // Desmarcar todos
                    deselectAllBtn.addEventListener('click', function() {
                        const checkboxes = document.querySelectorAll('input[name="data_groups[]"]');
                        checkboxes.forEach(checkbox => checkbox.checked = false);
                        updateSelectedGroups();
                    });
                    
                    // Confirmar seleção
                    confirmBtn.addEventListener('click', function() {
                        selectionArea.style.display = 'none';
                        toggleIcon.className = 'fa-solid fa-chevron-down';
                        toggleBtn.innerHTML = '<i class="fa-solid fa-chevron-down" id="toggleIcon"></i> Selecionar Grupos de Dados';
                    });
                    
                    // Atualizar grupos selecionados
                    function updateSelectedGroups() {
                        const checkboxes = document.querySelectorAll('input[name="data_groups[]"]:checked');
                        const count = checkboxes.length;
                        
                        selectedCount.textContent = count;
                        
                        if (count === 0) {
                            selectedList.innerHTML = '<p class="text-muted mb-0">Nenhum grupo selecionado. Clique em "Selecionar Grupos de Dados" para escolher.</p>';
                        } else {
                            let html = '<div class="row">';
                            checkboxes.forEach(checkbox => {
                                const label = checkbox.nextElementSibling;
                                const name = label.querySelector('strong').textContent;
                                const badge = label.querySelector('.badge').outerHTML;
                                const examples = label.querySelector('small').textContent;
                                
                                html += `
                                    <div class="col-md-6 mb-2">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center">
                                                    <strong class="me-2">${name}</strong>
                                                    ${badge}
                                                </div>
                                                <small class="text-muted d-block">${examples}</small>
                                            </div>
                                            <button type="button" class="btn btn-outline-danger btn-sm ms-2" onclick="document.getElementById('${checkbox.id}').click()">
                                                <i class="fa-solid fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                `;
                            });
                            html += '</div>';
                            selectedList.innerHTML = html;
                        }
                    }
                    
                    // Event listeners para checkboxes
                    document.querySelectorAll('input[name="data_groups[]"]').forEach(checkbox => {
                        checkbox.addEventListener('change', updateSelectedGroups);
                    });
                    
                    // Inicializar
                    updateSelectedGroups();
                });
                </script>

                <div class="col-12">
                    <label for="storage_location" class="form-label">Local de Armazenamento</label>
                    <input type="text" name="storage_location" class="form-control" id="storage_location" placeholder="Ex: ERP - Servidor local, RH - Servidor interno" value="<?php echo $this->data['form']['storage_location'] ?? ($this->data['inventory']['storage_location'] ?? ''); ?>">
                </div>

                <div class="col-12">
                    <label for="access_level" class="form-label">Quem Tem Acesso</label>
                    <input type="text" name="access_level" class="form-control" id="access_level" placeholder="Ex: Vendas, Financeiro, RH" value="<?php echo $this->data['form']['access_level'] ?? ($this->data['inventory']['access_level'] ?? ''); ?>">
                </div>

                <!-- Campo de nível de risco removido - agora configurado por grupo de dados -->

                <div class="col-12">
                    <button type="submit" name="SendEditInventory" class="btn btn-primary"><i class="fa-solid fa-save"></i> Atualizar</button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-inventory" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
                </div>

            </form>

        </div>

    </div>
</div>