<?php
use App\adms\Helpers\CSRFHelper;
$csrfToken = CSRFHelper::generateCSRFToken('form_update_booking');
$form = $this->data['form'] ?? [];
$rooms = $this->data['rooms'] ?? [];
$users = $this->data['users'] ?? [];
$requestTypes = $this->data['requestTypes'] ?? [];
$participants = $this->data['participants'] ?? [];
$additionalRequests = $this->data['additionalRequests'] ?? [];
$canChangeBookingAdditionalRequestResponsible = !empty($this->data['can_change_booking_additional_request_responsible']);
$bookingAdditionalRequestSessionUserId = (int) ($this->data['booking_additional_request_session_user_id'] ?? 0);
$bookingAdditionalRequestSessionUserName = (string) ($this->data['booking_additional_request_session_user_name'] ?? '');
$canEditBookingAdditionalRequests = !empty($this->data['can_edit_booking_additional_requests']);

// Converter datetime para formato datetime-local
$startDatetime = !empty($form['start_datetime']) ? date('Y-m-d\TH:i', strtotime($form['start_datetime'])) : '';
$endDatetime = !empty($form['end_datetime']) ? date('Y-m-d\TH:i', strtotime($form['end_datetime'])) : '';
$participantIds = $form['participant_ids'] ?? [];
?>
<?php include __DIR__ . '/partials/module_head.php'; ?>
<div class="container-fluid rooms-module-page px-2 px-sm-3 px-md-4">
    <div class="mb-2 mb-md-1 d-flex flex-column flex-md-row gap-2 align-items-start align-items-md-center">
        <h2 class="rooms-page-title mt-2 mt-md-3 mb-0">Editar Reserva de Sala</h2>
        <ol class="breadcrumb mb-0 mt-1 mt-md-3 ms-md-auto small">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-bookings" class="text-decoration-none">Reservas</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>view-booking/<?= $form['id'] ?? '' ?>" class="text-decoration-none">Visualizar</a>
            </li>
            <li class="breadcrumb-item">Editar</li>
        </ol>
    </div>
    
    <div class="card mb-4 border-light shadow">
        <div class="card-header rooms-card-header">
            <span><i class="fas fa-calendar-edit me-2"></i>Editar Reserva</span>
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
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?= $room['id'] ?>" 
                                    data-capacity="<?= $room['capacity'] ?>"
                                    data-location="<?= htmlspecialchars($room['location'] ?? '') ?>"
                                    <?= (($form['room_id'] ?? '') == $room['id']) ? 'selected' : '' ?>>
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
                           value="<?= htmlspecialchars($form['title'] ?? '') ?>">
                </div>
                
                <div class="col-md-12">
                    <label for="description" class="form-label">Descrição/Finalidade</label>
                    <textarea name="description" id="description" class="form-control" rows="3" 
                              placeholder="Descreva o objetivo da reunião..."><?= htmlspecialchars($form['description'] ?? '') ?></textarea>
                </div>
                
                <!-- Data e Horário -->
                <div class="col-12">
                    <h5 class="border-bottom pb-2 mb-3 mt-4">Data e Horário</h5>
                </div>
                
                <div class="col-md-6">
                    <label for="start_datetime" class="form-label">Data e Hora de Início <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="start_datetime" id="start_datetime" 
                           class="form-control" required 
                           value="<?= htmlspecialchars($startDatetime) ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="end_datetime" class="form-label">Data e Hora de Fim <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="end_datetime" id="end_datetime" 
                           class="form-control" required 
                           value="<?= htmlspecialchars($endDatetime) ?>">
                </div>

                <?php if (!empty($this->data['edit_recurrence_series']) && (int)($this->data['edit_recurrence_future_count'] ?? 0) > 1): ?>
                <div class="col-12">
                    <div class="alert alert-info mb-0">
                        <strong><i class="fas fa-layer-group me-1"></i> Recorrência</strong>
                        <p class="small mb-2 mt-1">Esta reserva faz parte de uma série (<?= (int)($this->data['edit_recurrence_future_count'] ?? 0) ?> ocorrência(s) em aberto a partir desta data). Indique se as alterações valem só para <strong>esta</strong> sessão ou para <strong>todas as seguintes</strong> na série.</p>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="edit_recurrence_scope" id="scope_this" value="this_occurrence" checked>
                            <label class="form-check-label" for="scope_this">Apenas esta ocorrência</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="edit_recurrence_scope" id="scope_future" value="future_open">
                            <label class="form-check-label" for="scope_future">Esta e todas as ocorrências futuras em aberto (mesmo deslocamento de horário, sala e título)</label>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <input type="hidden" name="edit_recurrence_scope" value="this_occurrence">
                <?php endif; ?>
                
                <!-- Participantes -->
                <div class="col-12">
                    <h5 class="border-bottom pb-2 mb-3 mt-4">Participantes</h5>
                </div>
                
                <div class="col-md-12">
                    <label for="participants" class="form-label">Adicionar Participantes</label>
                    <select name="participants[]" id="participants" class="form-select" multiple size="5">
                        <?php foreach ($users as $user): ?>
                            <?php if (($user['id'] ?? 0) != ($_SESSION['user_id'] ?? 0)): ?>
                                <option value="<?= $user['id'] ?>" 
                                        <?= in_array($user['id'], $participantIds) ? 'selected' : '' ?>>
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
                    <?php if (!$canEditBookingAdditionalRequests): ?>
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-lock me-2"></i>
                            Impossível editar as solicitações adicionais: falta <strong>menos de 1 hora</strong> para o início do evento.
                            Contacte a equipa responsável.
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($canEditBookingAdditionalRequests): ?>
                <div class="col-12">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addRequestBtn">
                        <i class="fas fa-plus me-1"></i>Adicionar Solicitação
                    </button>
                </div>
                <?php endif; ?>
                
                <div class="col-12" id="additionalRequestsContainer">
                    <?php 
                    $requestCounter = 0;
                    foreach ($additionalRequests as $request): 
                        $requestCounter++;
                        $reqTypeCode = (string) ($request['request_type'] ?? '');
                        $reqTypeLabel = $reqTypeCode;
                        foreach ($requestTypes as $type) {
                            if (($type['code'] ?? '') === $reqTypeCode) {
                                $reqTypeLabel = (string) ($type['name'] ?? $reqTypeCode);
                                break;
                            }
                        }
                        $qtyRaw = $request['quantity'] ?? null;
                        $qtyStr = ($qtyRaw === null || $qtyRaw === '') ? '' : (string) (int) $qtyRaw;
                    ?>
                        <div class="card mb-3 request-item<?= $canEditBookingAdditionalRequests ? '' : ' request-item-locked' ?>" data-request-id="request_<?= $requestCounter ?>">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-12 d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">Solicitação Adicional</h6>
                                        <?php if ($canEditBookingAdditionalRequests): ?>
                                        <button type="button" class="btn btn-sm btn-danger remove-request" data-request-id="request_<?= $requestCounter ?>">
                                            <i class="fas fa-times"></i> Remover
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($canEditBookingAdditionalRequests): ?>
                                    <div class="col-md-4">
                                        <label class="form-label">Tipo <span class="text-danger">*</span></label>
                                        <select name="additional_requests[<?= $requestCounter ?>][type]" class="form-select request-type" required>
                                            <option value="">Selecione...</option>
                                            <?php foreach ($requestTypes as $type): ?>
                                                <option value="<?= $type['code'] ?>" 
                                                        data-requires-quantity="<?= $type['requires_quantity'] ? 1 : 0 ?>"
                                                        data-requires-responsible="<?= $type['requires_responsible'] ? 1 : 0 ?>"
                                                        data-default-responsible="<?= $type['default_responsible_user_id'] ?? '' ?>"
                                                        <?= (($request['request_type'] ?? '') == $type['code']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($type['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 request-responsible-col">
                                        <label class="form-label">Responsável <span class="text-danger">*</span></label>
                                        <?php if ($canChangeBookingAdditionalRequestResponsible): ?>
                                        <select name="additional_requests[<?= $requestCounter ?>][responsible_user_id]" class="form-select request-responsible" required>
                                            <option value="">Selecione...</option>
                                            <?php foreach ($users as $user): ?>
                                                <option value="<?= $user['id'] ?>" 
                                                        <?= (($request['responsible_user_id'] ?? '') == $user['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($user['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php else: ?>
                                        <input type="hidden" name="additional_requests[<?= $requestCounter ?>][responsible_user_id]" value="<?= (int) ($_SESSION['user_id'] ?? 0) ?>">
                                        <div class="form-control-plaintext border rounded px-3 py-2 bg-light"><?= htmlspecialchars($bookingAdditionalRequestSessionUserName !== '' ? $bookingAdditionalRequestSessionUserName : ('ID ' . (string) (int) ($_SESSION['user_id'] ?? 0))) ?></div>
                                        <small class="text-muted">Utilizador em sessão (não editável)</small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Quantidade</label>
                                        <input type="number" name="additional_requests[<?= $requestCounter ?>][quantity]" 
                                               class="form-control request-quantity" min="1" 
                                               placeholder="Quantidade"
                                               value="<?= htmlspecialchars($request['quantity'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Descrição</label>
                                        <textarea name="additional_requests[<?= $requestCounter ?>][description]" 
                                                  class="form-control" rows="2" 
                                                  placeholder="Descreva a solicitação..."><?= htmlspecialchars($request['request_description'] ?? '') ?></textarea>
                                    </div>
                                    <?php else: ?>
                                    <input type="hidden" name="additional_requests[<?= $requestCounter ?>][type]" value="<?= htmlspecialchars($reqTypeCode) ?>">
                                    <input type="hidden" name="additional_requests[<?= $requestCounter ?>][responsible_user_id]" value="<?= (int) ($request['responsible_user_id'] ?? 0) ?>">
                                    <input type="hidden" name="additional_requests[<?= $requestCounter ?>][quantity]" value="<?= htmlspecialchars($qtyStr) ?>">
                                    <div class="col-md-4">
                                        <label class="form-label">Tipo</label>
                                        <div class="form-control-plaintext border rounded px-3 py-2 bg-light"><?= htmlspecialchars($reqTypeLabel) ?></div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Responsável</label>
                                        <div class="form-control-plaintext border rounded px-3 py-2 bg-light"><?= htmlspecialchars((string) ($request['responsible_name'] ?? '')) ?></div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Quantidade</label>
                                        <div class="form-control-plaintext border rounded px-3 py-2 bg-light"><?= $qtyStr !== '' ? htmlspecialchars($qtyStr) : '—' ?></div>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Descrição</label>
                                        <textarea name="additional_requests[<?= $requestCounter ?>][description]" 
                                                  class="form-control bg-light" rows="2" readonly><?= htmlspecialchars($request['request_description'] ?? '') ?></textarea>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="col-12">
                    <div class="d-flex flex-wrap gap-2 rooms-form-actions">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Salvar Alterações
                        </button>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>view-booking/<?= $form['id'] ?? '' ?>" class="btn btn-secondary">
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
    let requestCounter = <?= $requestCounter ?>;
    const CAN_EDIT_ADDITIONAL_REQUESTS = <?= json_encode($canEditBookingAdditionalRequests) ?>;
    const requestTypes = <?= json_encode($requestTypes) ?>;
    const users = <?= json_encode($users) ?>;
    const RR = {
        canChange: <?= json_encode($canChangeBookingAdditionalRequestResponsible) ?>,
        sessionUserId: <?= (int) $bookingAdditionalRequestSessionUserId ?>,
        sessionUserName: <?= json_encode($bookingAdditionalRequestSessionUserName) ?>
    };

    function escapeHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function responsibleFieldHtml(counter) {
        if (RR.canChange) {
            return `
                            <select name="additional_requests[${counter}][responsible_user_id]" class="form-select request-responsible" required>
                                <option value="">Selecione...</option>
                                ${users.map(user => `
                                    <option value="${user.id}">${escapeHtml(user.name)}</option>
                                `).join('')}
                            </select>`;
        }
        return `
                            <input type="hidden" name="additional_requests[${counter}][responsible_user_id]" value="${RR.sessionUserId}">
                            <div class="form-control-plaintext border rounded px-3 py-2 bg-light">${escapeHtml(RR.sessionUserName)}</div>
                            <small class="text-muted">Utilizador em sessão (não editável)</small>`;
    }
    
    // Adicionar solicitação adicional
    const addRequestBtn = document.getElementById('addRequestBtn');
    if (CAN_EDIT_ADDITIONAL_REQUESTS && addRequestBtn) addRequestBtn.addEventListener('click', function() {
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
                        <div class="col-md-4 request-responsible-col">
                            <label class="form-label">Responsável <span class="text-danger">*</span></label>
                            ${responsibleFieldHtml(requestCounter)}
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
        setupRequestItem(requestId);
    });
    
    // Remover solicitação
    document.addEventListener('click', function(e) {
        if (!CAN_EDIT_ADDITIONAL_REQUESTS) return;
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
        const responsibleCol = item.querySelector('.request-responsible-col');
        const responsibleSelect = item.querySelector('.request-responsible');

        function applyTypeConstraints() {
            if (!typeSelect) return;
            const option = typeSelect.options[typeSelect.selectedIndex];
            const requiresQuantity = option.dataset.requiresQuantity === '1';
            const requiresResponsible = option.dataset.requiresResponsible === '1';
            const defaultResponsible = option.dataset.defaultResponsible || '';

            if (quantityInput) {
                const qtyCol = quantityInput.closest('.col-md-4');
                if (requiresQuantity) {
                    quantityInput.required = true;
                    if (qtyCol) qtyCol.style.display = 'block';
                } else {
                    quantityInput.required = false;
                    if (qtyCol) qtyCol.style.display = 'none';
                }
            }

            if (responsibleCol) {
                responsibleCol.style.display = requiresResponsible ? 'block' : 'none';
            }
            if (responsibleSelect) {
                responsibleSelect.required = requiresResponsible;
                if (defaultResponsible && RR.canChange) {
                    responsibleSelect.value = defaultResponsible;
                }
            }
        }

        if (typeSelect) {
            typeSelect.addEventListener('change', applyTypeConstraints);
            applyTypeConstraints();
        }
    }
    
    // Configurar eventos para solicitações existentes (só em modo editável)
    if (CAN_EDIT_ADDITIONAL_REQUESTS) {
        document.querySelectorAll('.request-item').forEach(item => {
            const requestId = item.dataset.requestId;
            if (requestId) {
                setupRequestItem(requestId);
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
    
    if (startDatetime && endDatetime) {
        startDatetime.addEventListener('change', validateDatetime);
        endDatetime.addEventListener('change', validateDatetime);
    }
});
</script>

<style>
.request-item {
    border: 1px solid #dee2e6;
    border-radius: 8px;
}
</style>

