<?php
use App\adms\Helpers\CSRFHelper;
$csrfToken = CSRFHelper::generateCSRFToken('form_create_booking');
$rooms = $this->data['rooms'] ?? [];
$users = $this->data['users'] ?? [];
$requestTypes = $this->data['requestTypes'] ?? [];
?>
<?php include __DIR__ . '/partials/module_head.php'; ?>
<div class="container-fluid rooms-module-page px-2 px-sm-3 px-md-4">
    <div class="mb-2 mb-md-1 d-flex flex-column flex-md-row gap-2 align-items-start align-items-md-center">
        <h2 class="rooms-page-title mt-2 mt-md-3 mb-0">Criar Reserva de Sala</h2>
        <ol class="breadcrumb mb-0 mt-1 mt-md-3 ms-md-auto small">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-bookings" class="text-decoration-none">Reservas</a>
            </li>
            <li class="breadcrumb-item">Criar</li>
        </ol>
    </div>
    
    <div class="card mb-4 border-light shadow">
        <div class="card-header rooms-card-header">
            <span><i class="fas fa-calendar-plus me-2"></i>Nova Reserva</span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form action="" method="POST" class="row g-3" id="bookingForm">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                
                <!-- Informações Básicas -->
                <div class="col-12">
                    <h5 class="border-bottom pb-2 mb-3">Informações da Reunião</h5>
                </div>
                
                <div class="col-md-6">
                    <label for="room_id" class="form-label">Sala <span class="text-danger">*</span></label>
                    <select name="room_id" id="room_id" class="form-select" required>
                        <option value="">Selecione uma sala...</option>
                        <?php 
                        $selectedRoomId = $this->data['selected_room_id'] ?? null;
                        foreach ($rooms as $room): 
                        ?>
                            <option value="<?= $room['id'] ?>" 
                                    data-capacity="<?= $room['capacity'] ?>"
                                    data-location="<?= htmlspecialchars($room['location'] ?? '') ?>"
                                    <?= ($selectedRoomId && $selectedRoomId == $room['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($room['name']) ?> 
                                (<?= $room['capacity'] ?> pessoas)
                                <?php if (!empty($room['location'])): ?>
                                    - <?= htmlspecialchars($room['location']) ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="title" class="form-label">Título da Reunião <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="title" class="form-control" required 
                           placeholder="Ex: Reunião de Planejamento" 
                           value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                </div>
                
                <div class="col-md-12">
                    <label for="description" class="form-label">Descrição/Finalidade</label>
                    <textarea name="description" id="description" class="form-control" rows="3" 
                              placeholder="Descreva o objetivo da reunião..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>
                
                <!-- Data e Horário -->
                <div class="col-12">
                    <h5 class="border-bottom pb-2 mb-3 mt-4">Data e Horário</h5>
                </div>
                
                <div class="col-md-6">
                    <label for="start_datetime" class="form-label">Data e Hora de Início <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="start_datetime" id="start_datetime" 
                           class="form-control" required 
                           value="<?= htmlspecialchars($_POST['start_datetime'] ?? '') ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="end_datetime" class="form-label">Data e Hora de Fim <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="end_datetime" id="end_datetime" 
                           class="form-control" required 
                           value="<?= htmlspecialchars($_POST['end_datetime'] ?? '') ?>">
                </div>

                <div class="col-12">
                    <h5 class="border-bottom pb-2 mb-3 mt-2">Recorrência (opcional)</h5>
                </div>
                <div class="col-12">
                    <div class="form-check mb-2">
                        <input type="checkbox" name="recurrence_enabled" value="1" id="recurrence_enabled" class="form-check-input"
                            <?= !empty($_POST['recurrence_enabled']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="recurrence_enabled">Repetir <strong>semanalmente</strong> até à data final abaixo</label>
                    </div>
                    <label for="recurrence_until" class="form-label">Data final da série (último dia em que pode haver sessão)</label>
                    <input type="date" name="recurrence_until" id="recurrence_until" class="form-control" style="max-width: 280px"
                           value="<?= htmlspecialchars($_POST['recurrence_until'] ?? '') ?>">
                    <small class="text-muted d-block mt-1">A mesma duração repete-se todas as semanas, no mesmo dia da semana. Deixe desmarcado para uma única reserva.</small>
                </div>
                
                <!-- Participantes -->
                <div class="col-12">
                    <h5 class="border-bottom pb-2 mb-3 mt-4">Participantes</h5>
                </div>
                
                <div class="col-md-12">
                    <label for="participants" class="form-label">Adicionar Participantes</label>
                    <select name="participants[]" id="participants" class="form-select" multiple size="5">
                        <?php foreach ($users as $user): ?>
                            <?php if (($user['id'] ?? 0) != ($_SESSION['user_id'] ?? 0)): ?>
                                <option value="<?= $user['id'] ?>">
                                    <?= htmlspecialchars($user['name'] ?? '') ?>
                                    <?php if (!empty($user['email'])): ?>
                                        (<?= htmlspecialchars($user['email']) ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted">Mantenha Ctrl (ou Cmd no Mac) pressionado para selecionar múltiplos participantes</small>
                </div>
                
                <!-- Solicitações Adicionais -->
                <div class="col-12">
                    <h5 class="border-bottom pb-2 mb-3 mt-4">Solicitações Adicionais</h5>
                </div>
                
                <div class="col-12">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addRequestBtn">
                        <i class="fas fa-plus me-1"></i>Adicionar Solicitação
                    </button>
                </div>
                
                <div class="col-12" id="additionalRequestsContainer">
                    <!-- Solicitações serão adicionadas dinamicamente aqui -->
                </div>
                
                <div class="col-12">
                    <div class="d-flex flex-wrap gap-2 rooms-form-actions">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Criar Reserva
                        </button>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>list-bookings" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let requestCounter = 0;
    const requestTypes = <?= json_encode($requestTypes) ?>;
    const users = <?= json_encode($users) ?>;
    
    // Adicionar solicitação adicional
    document.getElementById('addRequestBtn').addEventListener('click', function() {
        const container = document.getElementById('additionalRequestsContainer');
        const requestId = 'request_' + (++requestCounter);
        
        const requestHtml = `
            <div class="card mb-3 request-item" data-request-id="${requestId}">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-12 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Solicitação Adicional</h6>
                            <button type="button" class="btn btn-sm btn-danger remove-request" data-request-id="${requestId}">
                                <i class="fas fa-times"></i> Remover
                            </button>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tipo <span class="text-danger">*</span></label>
                            <select name="additional_requests[${requestCounter}][type]" class="form-select request-type" required>
                                <option value="">Selecione...</option>
                                ${requestTypes.map(type => `
                                    <option value="${type.code}" 
                                            data-requires-quantity="${type.requires_quantity ? 1 : 0}"
                                            data-requires-responsible="${type.requires_responsible ? 1 : 0}"
                                            data-default-responsible="${type.default_responsible_user_id || ''}">
                                        ${type.name}
                                    </option>
                                `).join('')}
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Responsável <span class="text-danger">*</span></label>
                            <select name="additional_requests[${requestCounter}][responsible_user_id]" class="form-select request-responsible" required>
                                <option value="">Selecione...</option>
                                ${users.map(user => `
                                    <option value="${user.id}">${user.name}</option>
                                `).join('')}
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Quantidade</label>
                            <input type="number" name="additional_requests[${requestCounter}][quantity]" 
                                   class="form-control request-quantity" min="1" 
                                   placeholder="Quantidade">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Descrição</label>
                            <textarea name="additional_requests[${requestCounter}][description]" 
                                      class="form-control" rows="2" 
                                      placeholder="Descreva a solicitação..."></textarea>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', requestHtml);
        
        // Configurar eventos para o novo item
        setupRequestItem(requestId);
    });
    
    // Remover solicitação
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-request')) {
            const requestId = e.target.closest('.remove-request').dataset.requestId;
            document.querySelector(`[data-request-id="${requestId}"]`).remove();
        }
    });
    
    // Configurar eventos de um item de solicitação
    function setupRequestItem(requestId) {
        const item = document.querySelector(`[data-request-id="${requestId}"]`);
        if (!item) return;
        
        const typeSelect = item.querySelector('.request-type');
        const quantityInput = item.querySelector('.request-quantity');
        const responsibleSelect = item.querySelector('.request-responsible');
        
        // Quando o tipo muda, ajustar campos
        typeSelect.addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            const requiresQuantity = option.dataset.requiresQuantity === '1';
            const requiresResponsible = option.dataset.requiresResponsible === '1';
            const defaultResponsible = option.dataset.defaultResponsible;
            
            // Ajustar quantidade
            if (requiresQuantity) {
                quantityInput.required = true;
                quantityInput.closest('.col-md-4').style.display = 'block';
            } else {
                quantityInput.required = false;
                quantityInput.closest('.col-md-4').style.display = 'none';
            }
            
            // Ajustar responsável
            if (requiresResponsible) {
                responsibleSelect.required = true;
                responsibleSelect.closest('.col-md-4').style.display = 'block';
            } else {
                responsibleSelect.required = false;
                responsibleSelect.closest('.col-md-4').style.display = 'none';
            }
            
            // Definir responsável padrão se houver
            if (defaultResponsible) {
                responsibleSelect.value = defaultResponsible;
            }
        });
    }
    
    // Validação de data/hora
    const startDatetime = document.getElementById('start_datetime');
    const endDatetime = document.getElementById('end_datetime');
    
    function validateDatetime() {
        if (startDatetime.value && endDatetime.value) {
            const start = new Date(startDatetime.value);
            const end = new Date(endDatetime.value);
            
            if (end <= start) {
                endDatetime.setCustomValidity('A data/hora de fim deve ser posterior à data/hora de início');
            } else {
                endDatetime.setCustomValidity('');
            }
        }
    }
    
    startDatetime.addEventListener('change', validateDatetime);
    endDatetime.addEventListener('change', validateDatetime);
});
</script>

<style>
.request-item {
    border: 1px solid #dee2e6;
    border-radius: 8px;
}
</style>

