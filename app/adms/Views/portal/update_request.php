<?php
use App\adms\Helpers\FormatHelper;
$request = $this->data['request'] ?? [];
$requestTypes = $this->data['requestTypes'] ?? [];
$requestType = $request['request_type'] ?? '';
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Editar Solicitação #<?= $request['id'] ?? '' ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-employee-requests" class="text-decoration-none">Solicitações</a>
            </li>
            <li class="breadcrumb-item">Editar</li>
        </ol>
    </div>
    
    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <span><i class="fas fa-edit me-2"></i>Editar Solicitação</span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            
            <form action="" method="POST" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_update_employee_request'); ?>">
                
                <div class="col-md-6">
                    <label for="request_type" class="form-label">Tipo de Solicitação <span class="text-danger">*</span></label>
                    <select name="request_type" id="request_type" class="form-select" required onchange="toggleFields()">
                        <option value="">Selecione...</option>
                        <?php foreach ($requestTypes as $type): ?>
                            <option value="<?= htmlspecialchars($type['code']) ?>" 
                                    data-requires-dates="<?= $type['requires_dates'] ? '1' : '0' ?>"
                                    data-requires-days="<?= $type['requires_days'] ? '1' : '0' ?>"
                                    data-requires-amount="<?= $type['requires_amount'] ? '1' : '0' ?>"
                                    <?= $requestType === $type['code'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="title" class="form-label">Título <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="title" class="form-control" required 
                           value="<?= htmlspecialchars($request['title'] ?? '') ?>"
                           placeholder="Ex: Solicitação de Férias - Janeiro 2025">
                </div>
                
                <div class="col-md-12">
                    <label for="description" class="form-label">Descrição</label>
                    <textarea name="description" id="description" class="form-control" rows="4" 
                              placeholder="Descreva sua solicitação..."><?= htmlspecialchars($request['description'] ?? '') ?></textarea>
                </div>
                
                <div class="col-md-4" id="date_fields" style="display: none;">
                    <label for="start_date" class="form-label">Data de Início</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" 
                           value="<?= !empty($request['start_date']) ? date('Y-m-d', strtotime($request['start_date'])) : '' ?>"
                           onchange="calculateDays()">
                </div>
                
                <div class="col-md-4" id="end_date_fields" style="display: none;">
                    <label for="end_date" class="form-label">Data de Término</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" 
                           value="<?= !empty($request['end_date']) ? date('Y-m-d', strtotime($request['end_date'])) : '' ?>"
                           onchange="calculateDays()">
                </div>
                
                <div class="col-md-4" id="days_fields" style="display: none;">
                    <label for="days_requested" class="form-label">Dias Solicitados</label>
                    <input type="number" name="days_requested" id="days_requested" class="form-control" min="1" 
                           value="<?= htmlspecialchars($request['days_requested'] ?? '') ?>" readonly>
                    <small class="form-text text-muted">Calculado automaticamente</small>
                </div>
                
                <div class="col-md-6" id="amount_fields" style="display: none;">
                    <label for="amount" class="form-label">Valor</label>
                    <input type="number" name="amount" id="amount" class="form-control" step="0.01" min="0" 
                           value="<?= htmlspecialchars($request['amount'] ?? '') ?>"
                           placeholder="0.00">
                </div>
                
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Atenção:</strong> Esta solicitação pode ser editada pois ainda não possui aprovações. 
                        Após qualquer aprovação, será necessário cancelar e criar uma nova solicitação.
                    </div>
                </div>
                
                <div class="col-12">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Salvar Alterações
                        </button>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>view-employee-request/<?= $request['id'] ?? '' ?>" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleFields() {
    const typeSelect = document.getElementById('request_type');
    const selectedOption = typeSelect.options[typeSelect.selectedIndex];
    const type = typeSelect.value;
    
    const dateFields = document.getElementById('date_fields');
    const endDateFields = document.getElementById('end_date_fields');
    const daysFields = document.getElementById('days_fields');
    const amountFields = document.getElementById('amount_fields');
    
    if (!type) {
        dateFields.style.display = 'none';
        endDateFields.style.display = 'none';
        daysFields.style.display = 'none';
        amountFields.style.display = 'none';
        return;
    }
    
    // Usar atributos data-* do option selecionado
    const requiresDates = selectedOption.getAttribute('data-requires-dates') === '1';
    const requiresDays = selectedOption.getAttribute('data-requires-days') === '1';
    const requiresAmount = selectedOption.getAttribute('data-requires-amount') === '1';
    
    // Mostrar/ocultar campos baseado na configuração do tipo
    dateFields.style.display = requiresDates ? 'block' : 'none';
    endDateFields.style.display = requiresDates ? 'block' : 'none';
    daysFields.style.display = requiresDays ? 'block' : 'none';
    amountFields.style.display = requiresAmount ? 'block' : 'none';
    
    // Se não requer datas, limpar campos de data e dias
    if (!requiresDates) {
        document.getElementById('start_date').value = '';
        document.getElementById('end_date').value = '';
        document.getElementById('days_requested').value = '';
    }
    
    // Se não requer valor, limpar campo de valor
    if (!requiresAmount) {
        document.getElementById('amount').value = '';
    }
    
    // Recalcular dias se necessário
    if (requiresDates) {
        calculateDays();
    }
}

// Calcular dias solicitados automaticamente
function calculateDays() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const daysField = document.getElementById('days_requested');
    
    if (startDate && endDate) {
        const start = new Date(startDate);
        const end = new Date(endDate);
        
        // Validar se a data de término é posterior ou igual à data de início
        if (end < start) {
            daysField.value = '';
            alert('A data de término deve ser posterior ou igual à data de início.');
            return;
        }
        
        // Calcular diferença em dias (incluindo o dia inicial e final)
        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
        
        daysField.value = diffDays;
    } else {
        daysField.value = '';
    }
}

// Executar ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('request_type').value) {
        toggleFields();
    }
    
    // Se já houver datas preenchidas, calcular dias
    calculateDays();
});
</script>

