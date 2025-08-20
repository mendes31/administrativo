<?php

use App\adms\Helpers\CSRFHelper;

?>
<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Criar ROPA a partir do Inventário</h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-inventory" class="text-decoration-none">Inventário</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-inventory-view/<?php echo $this->data['inventory']['id']; ?>" class="text-decoration-none">Visualizar</a>
            </li>
            <li class="breadcrumb-item">Criar ROPA</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <h5 class="mb-0"><i class="fa-solid fa-info-circle"></i> Dados do Inventário</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Departamento:</strong> <?php echo htmlspecialchars($this->data['inventory']['area']); ?></p>
                    <p><strong>Titular:</strong> <?php echo htmlspecialchars($this->data['inventory']['data_subject']); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Local:</strong> <?php echo htmlspecialchars($this->data['inventory']['storage_location']); ?></p>
                    <p><strong>Acesso:</strong> <?php echo htmlspecialchars($this->data['inventory']['access_level']); ?></p>
                </div>
            </div>
            
            <?php if (!empty($this->data['data_groups'])): ?>
                <div class="mt-3">
                    <h6><i class="fa-solid fa-database"></i> Grupos de Dados Identificados:</h6>
                    <div class="row">
                        <?php foreach ($this->data['data_groups'] as $group): ?>
                            <div class="col-md-6 mb-2">
                                <span class="badge bg-<?php echo $group['data_category'] === 'Sensível' ? 'danger' : 'primary'; ?> me-1">
                                    <?php echo htmlspecialchars($group['name']); ?>
                                </span>
                                <small class="text-muted">(<?php echo $group['data_category']; ?> - Risco <?php echo $group['risk_level']; ?>)</small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <h5 class="mb-0"><i class="fa-solid fa-plus-circle"></i> Criar ROPA por Grupo de Dados</h5>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <form method="POST" action="">
                <?php CSRFHelper::generateCSRFToken('form_create_ropa'); ?>
                
                                 <!-- Dados Gerais da ROPA -->
                 <h6 class="mb-3"><i class="fa-solid fa-cog"></i> Dados Gerais (Aplicam-se a todas as atividades)</h6>
                 <div class="row">
                     <!-- Departamento -->
                     <div class="col-md-6 mb-3">
                         <label for="departamento_id" class="form-label">Departamento <span class="text-danger">*</span></label>
                         <select class="form-select" id="departamento_id" name="departamento_id" required>
                             <option value="">Selecione...</option>
                             <?php foreach ($this->data['departamentos'] as $dept): ?>
                                 <option value="<?php echo $dept['id']; ?>" 
                                         <?php echo ($dept['id'] == $this->data['base_data']['departamento_id']) ? 'selected' : ''; ?>>
                                     <?php echo htmlspecialchars($dept['name']); ?>
                                 </option>
                             <?php endforeach; ?>
                         </select>
                     </div>

                     <!-- Finalidade do Processamento -->
                     <div class="col-md-6 mb-3">
                         <label for="processing_purpose" class="form-label">Finalidade do Processamento</label>
                         <select class="form-select" id="processing_purpose" name="processing_purpose">
                             <option value="">Selecione uma finalidade...</option>
                             <?php foreach ($this->data['finalidades'] as $finalidade): ?>
                                 <option value="<?php echo htmlspecialchars($finalidade['finalidade']); ?>" 
                                         <?php echo ($finalidade['finalidade'] === $this->data['base_data']['processing_purpose']) ? 'selected' : ''; ?>>
                                     <?php echo htmlspecialchars($finalidade['finalidade']); ?>
                                 </option>
                             <?php endforeach; ?>
                         </select>
                     </div>

                     <!-- Titular -->
                     <div class="col-md-6 mb-3">
                         <label for="data_subject" class="form-label">Titular dos Dados</label>
                         <input type="text" class="form-control" id="data_subject" name="data_subject" 
                                value="<?php echo $this->data['base_data']['data_subject']; ?>">
                     </div>

                     <!-- Compartilhamento -->
                     <div class="col-md-6 mb-3">
                         <label for="sharing" class="form-label">Compartilhamento</label>
                         <input type="text" class="form-control" id="sharing" name="sharing" 
                                value="<?php echo $this->data['base_data']['sharing']; ?>">
                     </div>

                     <!-- Retenção -->
                     <div class="col-md-6 mb-3">
                         <label for="retencao" class="form-label">Prazo de Retenção</label>
                         <input type="text" class="form-control" id="retencao" name="retencao" 
                                value="<?php echo $this->data['base_data']['retencao']; ?>">
                     </div>
                 </div>

                                 <!-- Definição de Atividades -->
                 <hr class="my-4">
                 <h6 class="mb-3"><i class="fa-solid fa-tasks"></i> Definir Atividades e Grupos de Dados</h6>
                 
                 <div class="alert alert-info">
                     <i class="fa-solid fa-info-circle"></i>
                     <strong>Padrão Granular LGPD:</strong> Defina as atividades e selecione os grupos de dados necessários para cada uma.
                     <br><strong>Como funciona:</strong>
                     <ul class="mb-0 mt-2">
                         <li>✅ <strong>Cada atividade</strong> pode ter <strong>grupos diferentes</strong></li>
                         <li>✅ <strong>Base legal</strong> é definida <strong>por grupo</strong> dentro de cada atividade</li>
                         <li>✅ <strong>Responsável e medidas</strong> são aplicados <strong>por atividade</strong></li>
                         <li>✅ <strong>ROPA separado</strong> para cada <strong>combinação de atividade + base legal</strong></li>
                     </ul>
                 </div>

                 <!-- Botão para adicionar nova atividade -->
                 <div class="mb-3">
                     <button type="button" class="btn btn-outline-primary" onclick="addNewActivity()">
                         <i class="fa-solid fa-plus"></i> Adicionar Nova Atividade
                     </button>
                 </div>

                 <!-- Container das atividades -->
                 <div id="activities-container">
                     <!-- Atividade padrão -->
                     <div class="activity-card card mb-4 border-primary" data-activity-id="1">
                         <div class="card-header bg-primary text-white">
                             <div class="d-flex justify-content-between align-items-center">
                                 <h6 class="mb-0">Atividade #1</h6>
                                 <button type="button" class="btn btn-sm btn-outline-light" onclick="removeActivity(this)" style="display: none;">
                                     <i class="fa-solid fa-trash"></i>
                                 </button>
                             </div>
                         </div>
                         <div class="card-body">
                                                           <!-- Nome da Atividade -->
                              <div class="mb-3">
                                  <label class="form-label">Nome da Atividade <span class="text-danger">*</span></label>
                                  <input type="text" class="form-control activity-name" name="activities[1][name]" 
                                         placeholder="Ex: Recebimento de currículos" required>
                              </div>

                              <!-- Medidas de Segurança para esta Atividade -->
                              <div class="mb-3">
                                  <label class="form-label">Medidas de Segurança <span class="text-danger">*</span></label>
                                  <textarea class="form-control" name="activities[1][medidas_seguranca]" rows="3" 
                                            placeholder="Ex: Acesso restrito, criptografia, controle de acesso, backup..." required>Acesso restrito, criptografia</textarea>
                                  <div class="form-text">Descreva as medidas de segurança aplicadas a esta atividade específica</div>
                              </div>

                              <!-- Responsável por esta Atividade -->
                              <div class="mb-3">
                                  <label class="form-label">Responsável pela Atividade <span class="text-danger">*</span></label>
                                  <input type="text" class="form-control" name="activities[1][responsavel]" 
                                         placeholder="Ex: João Silva - RH" required>
                                  <div class="form-text">Nome do responsável pela execução desta atividade específica</div>
                              </div>

                              <!-- Seleção de Grupos para esta Atividade -->
                             <div class="mb-3">
                                 <label class="form-label">Grupos de Dados para esta Atividade <span class="text-danger">*</span></label>
                                 <div class="row">
                                     <?php foreach ($this->data['data_groups'] as $group): ?>
                                         <div class="col-md-6 mb-2">
                                             <div class="form-check">
                                                 <input class="form-check-input activity-group-checkbox" type="checkbox" 
                                                        id="activity_1_group_<?php echo $group['id']; ?>" 
                                                        name="activities[1][groups][<?php echo $group['id']; ?>][selected]" 
                                                        value="1" 
                                                        onchange="toggleActivityGroupForm(1, <?php echo $group['id']; ?>)">
                                                 <label class="form-check-label" for="activity_1_group_<?php echo $group['id']; ?>">
                                                     <strong><?php echo htmlspecialchars($group['name']); ?></strong>
                                                     <br>
                                                     <small class="text-muted">
                                                         <span class="badge bg-<?php echo $group['data_category'] === 'Sensível' ? 'danger' : 'primary'; ?>">
                                                             <?php echo $group['data_category']; ?>
                                                         </span>
                                                         <span class="badge bg-<?php echo $group['risk_level'] === 'Alto' ? 'danger' : ($group['risk_level'] === 'Médio' ? 'warning' : 'success'); ?>">
                                                             Risco <?php echo $group['risk_level']; ?>
                                                         </span>
                                                     </small>
                                                 </label>
                                             </div>
                                             
                                             <!-- Formulário específico do grupo para esta atividade -->
                                             <div class="activity-group-form ms-4 mt-2" id="activity_1_group_form_<?php echo $group['id']; ?>" style="display: none;">
                                                 <input type="hidden" name="activities[1][groups][<?php echo $group['id']; ?>][name]" value="<?php echo htmlspecialchars($group['name']); ?>">
                                                 <input type="hidden" name="activities[1][groups][<?php echo $group['id']; ?>][risk_level]" value="<?php echo $group['risk_level']; ?>">
                                                 
                                                                                                   <!-- Base Legal para este grupo nesta atividade -->
                                                  <div class="mb-2">
                                                      <label class="form-label small">Base Legal para <?php echo htmlspecialchars($group['name']); ?> <span class="text-danger">*</span></label>
                                                      <select class="form-select form-select-sm" name="activities[1][groups][<?php echo $group['id']; ?>][base_legal]">
                                                         <option value="">Selecione...</option>
                                                         <option value="Consentimento">Consentimento</option>
                                                         <option value="Execução de contrato">Execução de contrato</option>
                                                         <option value="Obrigação legal">Obrigação legal</option>
                                                         <option value="Legítimo interesse">Legítimo interesse</option>
                                                         <option value="Proteção ao crédito">Proteção ao crédito</option>
                                                     </select>
                                                 </div>
                                                 
                                                 <!-- Observações para este grupo nesta atividade -->
                                                 <div class="mb-2">
                                                     <label class="form-label small">Observações para <?php echo htmlspecialchars($group['name']); ?></label>
                                                     <textarea class="form-control form-control-sm" name="activities[1][groups][<?php echo $group['id']; ?>][observacoes]" rows="2" 
                                                               placeholder="Observações específicas..."></textarea>
                                                 </div>
                                             </div>
                                         </div>
                                     <?php endforeach; ?>
                                 </div>
                             </div>
                         </div>
                     </div>
                 </div>

                                 <?php if (empty($this->data['data_groups'])): ?>
                     <div class="alert alert-warning">
                         <i class="fa-solid fa-exclamation-triangle"></i>
                         Nenhum grupo de dados encontrado para este inventário.
                     </div>
                 <?php endif; ?>

                                 <div class="d-flex gap-2 mt-4">
                     <button type="submit" name="SendAddRopa" class="btn btn-success" id="submitBtn" disabled>
                         <i class="fa-solid fa-save"></i> Criar Atividade(s) ROPA
                     </button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-inventory-view/<?php echo $this->data['inventory']['id']; ?>" class="btn btn-secondary">
                        <i class="fa-solid fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </form>
        </div>
    </div>

                                       <script>
          
          let activityCounter = 1;
         
         function addNewActivity() {
             activityCounter++;
             const container = document.getElementById('activities-container');
             
             // Template da nova atividade
             const newActivity = document.createElement('div');
             newActivity.className = 'activity-card card mb-4 border-primary';
             newActivity.setAttribute('data-activity-id', activityCounter);
             
             newActivity.innerHTML = `
                 <div class="card-header bg-primary text-white">
                     <div class="d-flex justify-content-between align-items-center">
                         <h6 class="mb-0">Atividade #${activityCounter}</h6>
                         <button type="button" class="btn btn-sm btn-outline-light" onclick="removeActivity(this)">
                             <i class="fa-solid fa-trash"></i>
                         </button>
                     </div>
                 </div>
                 <div class="card-body">
                     <!-- Nome da Atividade -->
                     <div class="mb-3">
                         <label class="form-label">Nome da Atividade <span class="text-danger">*</span></label>
                         <input type="text" class="form-control activity-name" name="activities[${activityCounter}][name]" 
                                placeholder="Ex: Análise de documentos" required>
                     </div>

                     <!-- Medidas de Segurança para esta Atividade -->
                     <div class="mb-3">
                         <label class="form-label">Medidas de Segurança <span class="text-danger">*</span></label>
                         <textarea class="form-control" name="activities[${activityCounter}][medidas_seguranca]" rows="3" 
                                   placeholder="Ex: Acesso restrito, criptografia, controle de acesso, backup..." required>Acesso restrito, criptografia</textarea>
                         <div class="form-text">Descreva as medidas de segurança aplicadas a esta atividade específica</div>
                     </div>

                     <!-- Responsável por esta Atividade -->
                     <div class="mb-3">
                         <label class="form-label">Responsável pela Atividade <span class="text-danger">*</span></label>
                         <input type="text" class="form-control" name="activities[${activityCounter}][responsavel]" 
                                placeholder="Ex: Maria Santos - RH" required>
                         <div class="form-text">Nome do responsável pela execução desta atividade específica</div>
                     </div>

                     <!-- Seleção de Grupos para esta Atividade -->
                     <div class="mb-3">
                         <label class="form-label">Grupos de Dados para esta Atividade <span class="text-danger">*</span></label>
                         <div class="row">
                             <?php foreach ($this->data['data_groups'] as $group): ?>
                                 <div class="col-md-6 mb-2">
                                     <div class="form-check">
                                         <input class="form-check-input activity-group-checkbox" type="checkbox" 
                                                id="activity_${activityCounter}_group_<?php echo $group['id']; ?>" 
                                                name="activities[${activityCounter}][groups][<?php echo $group['id']; ?>][selected]" 
                                                value="1" 
                                                onchange="toggleActivityGroupForm(${activityCounter}, <?php echo $group['id']; ?>)">
                                         <label class="form-check-label" for="activity_${activityCounter}_group_<?php echo $group['id']; ?>">
                                             <strong><?php echo htmlspecialchars($group['name']); ?></strong>
                                             <br>
                                             <small class="text-muted">
                                                 <span class="badge bg-<?php echo $group['data_category'] === 'Sensível' ? 'danger' : 'primary'; ?>">
                                                     <?php echo $group['data_category']; ?>
                                                 </span>
                                                 <span class="badge bg-<?php echo $group['risk_level'] === 'Alto' ? 'danger' : ($group['risk_level'] === 'Médio' ? 'warning' : 'success'); ?>">
                                                     Risco <?php echo $group['risk_level']; ?>
                                                 </span>
                                             </small>
                                         </label>
                                     </div>
                                     
                                     <!-- Formulário específico do grupo para esta atividade -->
                                     <div class="activity-group-form ms-4 mt-2" id="activity_${activityCounter}_group_form_<?php echo $group['id']; ?>" style="display: none;">
                                         <input type="hidden" name="activities[${activityCounter}][groups][<?php echo $group['id']; ?>][name]" value="<?php echo htmlspecialchars($group['name']); ?>">
                                         <input type="hidden" name="activities[${activityCounter}][groups][<?php echo $group['id']; ?>][risk_level]" value="<?php echo $group['risk_level']; ?>">
                                         
                                                                                   <!-- Base Legal para este grupo nesta atividade -->
                                          <div class="mb-2">
                                              <label class="form-label small">Base Legal para <?php echo htmlspecialchars($group['name']); ?> <span class="text-danger">*</span></label>
                                              <select class="form-select form-select-sm" name="activities[${activityCounter}][groups][<?php echo $group['id']; ?>][base_legal]">
                                                 <option value="">Selecione...</option>
                                                 <option value="Consentimento">Consentimento</option>
                                                 <option value="Execução de contrato">Execução de contrato</option>
                                                 <option value="Obrigação legal">Obrigação legal</option>
                                                 <option value="Legítimo interesse">Legítimo interesse</option>
                                                 <option value="Proteção ao crédito">Proteção ao crédito</option>
                                             </select>
                                         </div>
                                         
                                         <!-- Observações para este grupo nesta atividade -->
                                         <div class="mb-2">
                                             <label class="form-label small">Observações para <?php echo htmlspecialchars($group['name']); ?></label>
                                             <textarea class="form-control form-control-sm" name="activities[${activityCounter}][groups][<?php echo $group['id']; ?>][observacoes]" rows="2" 
                                                       placeholder="Observações específicas..."></textarea>
                                         </div>
                                     </div>
                                 </div>
                             <?php endforeach; ?>
                         </div>
                     </div>
                 </div>
             `;
             
             container.appendChild(newActivity);
             updateSubmitButton();
         }
         
         function removeActivity(button) {
             const activityCard = button.closest('.activity-card');
             activityCard.remove();
             updateSubmitButton();
         }
         
                   function toggleActivityGroupForm(activityId, groupId) {
              const checkbox = document.getElementById(`activity_${activityId}_group_${groupId}`);
              const form = document.getElementById(`activity_${activityId}_group_form_${groupId}`);
              
              if (checkbox.checked) {
                  form.style.display = 'block';
                  // Adicionar required aos campos quando mostrar
                  const requiredFields = form.querySelectorAll('select[name*="[base_legal]"]');
                  requiredFields.forEach(field => {
                      field.setAttribute('required', 'required');
                  });
              } else {
                  form.style.display = 'none';
                  // Remover required e limpar campos quando ocultar
                  const inputs = form.querySelectorAll('input, select, textarea');
                  inputs.forEach(input => {
                      if (input.type !== 'hidden') {
                          input.value = '';
                          input.removeAttribute('required');
                      }
                  });
              }
              
              updateSubmitButton();
          }
         
                   function updateSubmitButton() {
              const submitBtn = document.getElementById('submitBtn');
              const activities = document.querySelectorAll('.activity-card');
              let totalRopas = 0;
              let hasValidActivities = false;
             
             activities.forEach(activity => {
                 const activityName = activity.querySelector('.activity-name').value.trim();
                 const selectedGroups = activity.querySelectorAll('.activity-group-checkbox:checked');
                 
                 if (activityName && selectedGroups.length > 0) {
                     hasValidActivities = true;
                     
                     // Contar bases legais únicas para esta atividade
                     const baseLegals = new Set();
                     selectedGroups.forEach(checkbox => {
                         const groupId = checkbox.id.split('_').pop();
                         const baseLegal = activity.querySelector(`select[name*="[${groupId}][base_legal]"]`);
                         if (baseLegal && baseLegal.value) {
                             baseLegals.add(baseLegal.value);
                         }
                     });
                     
                     totalRopas += baseLegals.size;
                 }
             });
             
                           if (hasValidActivities) {
                  submitBtn.disabled = false;
                  submitBtn.innerHTML = `<i class="fa-solid fa-save"></i> Criar ${totalRopas} ROPA(s)`;
              } else {
                  submitBtn.disabled = true;
                  submitBtn.innerHTML = '<i class="fa-solid fa-save"></i> Criar ROPA(s)';
              }
         }
         
                   // Verificar campos obrigatórios quando submeter
          const form = document.querySelector('form');
          
          if (form) {
              form.addEventListener('submit', function(e) {
             const departamento = document.getElementById('departamento_id').value;
             const activities = document.querySelectorAll('.activity-card');
             let hasErrors = false;
             let errorMessages = [];
             
             // Verificar departamento
             if (!departamento) {
                 hasErrors = true;
                 document.getElementById('departamento_id').classList.add('is-invalid');
                 errorMessages.push('Departamento é obrigatório');
             } else {
                 document.getElementById('departamento_id').classList.remove('is-invalid');
             }
             
             // Verificar atividades
             let hasValidActivities = false;
             activities.forEach((activity, index) => {
                 const activityName = activity.querySelector('.activity-name').value.trim();
                 const selectedGroups = activity.querySelectorAll('.activity-group-checkbox:checked');
                 
                                   if (!activityName) {
                      hasErrors = true;
                      activity.querySelector('.activity-name').classList.add('is-invalid');
                      errorMessages.push(`Nome da Atividade #${index + 1} é obrigatório`);
                  } else {
                      activity.querySelector('.activity-name').classList.remove('is-invalid');
                  }
                  
                  // Verificar medidas de segurança
                  const medidasSeguranca = activity.querySelector('textarea[name*="[medidas_seguranca]"]');
                  if (!medidasSeguranca.value.trim()) {
                      hasErrors = true;
                      medidasSeguranca.classList.add('is-invalid');
                      errorMessages.push(`Medidas de Segurança da Atividade #${index + 1} é obrigatório`);
                  } else {
                      medidasSeguranca.classList.remove('is-invalid');
                  }
                  
                  // Verificar responsável
                  const responsavel = activity.querySelector('input[name*="[responsavel]"]');
                  if (!responsavel.value.trim()) {
                      hasErrors = true;
                      responsavel.classList.add('is-invalid');
                      errorMessages.push(`Responsável da Atividade #${index + 1} é obrigatório`);
                  } else {
                      responsavel.classList.remove('is-invalid');
                  }
                 
                 if (selectedGroups.length === 0) {
                     hasErrors = true;
                     errorMessages.push(`Selecione pelo menos um grupo para a Atividade #${index + 1}`);
                 } else {
                     hasValidActivities = true;
                     
                     // Verificar base legal para grupos selecionados
                     selectedGroups.forEach(checkbox => {
                         const groupId = checkbox.id.split('_').pop();
                         const baseLegal = activity.querySelector(`select[name*="[${groupId}][base_legal]"]`);
                         
                         if (!baseLegal.value) {
                             hasErrors = true;
                             baseLegal.classList.add('is-invalid');
                             errorMessages.push(`Base legal é obrigatória para grupos selecionados na Atividade #${index + 1}`);
                         } else {
                             baseLegal.classList.remove('is-invalid');
                         }
                     });
                 }
             });
             
             if (!hasValidActivities) {
                 hasErrors = true;
                 errorMessages.push('Pelo menos uma atividade deve ter grupos selecionados');
             }
             
                                         if (hasErrors) {
                  e.preventDefault();
                  alert('Por favor, corrija os seguintes erros:\n\n' + errorMessages.join('\n'));
              }
          });
                    }
          
          // Atualizar botão quando campos mudarem
          document.addEventListener('input', updateSubmitButton);
          document.addEventListener('change', updateSubmitButton);
          
          // Chamar updateSubmitButton quando a página carregar
          updateSubmitButton();
     </script>
</div> 