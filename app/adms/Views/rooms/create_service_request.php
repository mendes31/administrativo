<?php
use App\adms\Helpers\CSRFHelper;

$linked = $this->data['linkedBooking'] ?? null;
$prefill = $this->data['prefill'] ?? null;
$defaultDate = $prefill['service_date'] ?? date('Y-m-d');
$defaultStart = $prefill['start_time'] ?? '';
$defaultEnd = $prefill['end_time'] ?? '';
$defaultLocation = $prefill['location'] ?? '';
?>
<?php include __DIR__ . '/partials/module_head.php'; ?>
<div class="container-fluid rooms-module-page px-2 px-sm-3 px-md-4">
    <div class="mb-2 mb-md-1 d-flex flex-column flex-md-row gap-2 align-items-start align-items-md-center">
        <h2 class="rooms-page-title mt-2 mt-md-3 mb-0"><?= $linked ? 'Nova solicitação vinculada à reserva' : 'Criar Solicitação (Salas)'; ?></h2>
        <ol class="breadcrumb mb-0 mt-1 mt-md-3 ms-md-auto small">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Reserva de Salas</li>
            <li class="breadcrumb-item">Solicitações</li>
            <li class="breadcrumb-item active">Criar</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <?php if ($linked): ?>
        <div class="alert alert-info border-0 shadow-sm">
            <div class="d-flex flex-column flex-md-row gap-2 justify-content-between align-items-start">
                <div>
                    <i class="fas fa-link me-2"></i>
                    <strong>Vinculada à reserva:</strong>
                    <?= htmlspecialchars((string)($linked['title'] ?? '—')); ?>
                    <span class="text-muted">— <?= htmlspecialchars((string)($linked['room_name'] ?? '')); ?></span>
                    <br>
                    <small class="text-muted">
                        <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string)($linked['start_datetime'] ?? 'now')))); ?>
                        &ndash;
                        <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string)($linked['end_datetime'] ?? 'now')))); ?>
                    </small>
                </div>
                <a href="<?php echo $_ENV['URL_ADM']; ?>view-booking/<?php echo (int)($linked['id'] ?? 0); ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Voltar à reserva
                </a>
            </div>
        </div>
    <?php endif; ?>

    <div class="card border-light shadow">
        <div class="card-header rooms-card-header">
            <i class="fas fa-plus me-2"></i>Nova Solicitação
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_create_room_service_request'); ?>">
                <?php if ($linked): ?>
                    <input type="hidden" name="booking_id" value="<?php echo (int)($linked['id'] ?? 0); ?>">
                <?php endif; ?>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="request_type_id" class="form-label">Tipo de Solicitação *</label>
                        <select name="request_type_id" id="request_type_id" class="form-select" required>
                            <option value="">Selecione</option>
                            <?php foreach (($this->data['requestTypes'] ?? []) as $t): ?>
                                <option value="<?php echo (int)$t['id']; ?>">
                                    <?php echo htmlspecialchars($t['name']); ?> (<?php echo htmlspecialchars($t['code']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="quantity" class="form-label">Quantidade</label>
                        <input type="number" min="1" name="quantity" id="quantity" class="form-control">
                        <div class="form-text">Alguns tipos podem exigir quantidade.</div>
                    </div>
                    <div class="col-md-3">
                        <label for="service_date" class="form-label">Data *</label>
                        <input type="date" name="service_date" id="service_date" class="form-control"
                               value="<?php echo htmlspecialchars($defaultDate); ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label for="start_time" class="form-label">Hora de início *</label>
                        <input type="time" name="start_time" id="start_time" class="form-control" required
                               value="<?php echo htmlspecialchars($defaultStart); ?>">
                    </div>

                    <div class="col-md-3">
                        <label for="end_time" class="form-label">Hora de término</label>
                        <input type="time" name="end_time" id="end_time" class="form-control"
                               value="<?php echo htmlspecialchars($defaultEnd); ?>">
                        <div class="form-text">Opcional. Use quando precisar de janela de atendimento.</div>
                    </div>

                    <div class="col-md-3">
                        <label for="location" class="form-label">Local *</label>
                        <input type="text" name="location" id="location" class="form-control" required
                               value="<?php echo htmlspecialchars($defaultLocation); ?>"
                               placeholder="Ex.: Sala X, Refeitório, Copa...">
                        <div class="form-text">Sugerido a partir da reserva; pode ajustar.</div>
                    </div>

                    <div class="col-12">
                        <label for="request_description" class="form-label">Informações adicionais</label>
                        <textarea name="request_description" id="request_description" class="form-control" rows="3"></textarea>
                    </div>
                </div>

                <div class="mt-4 rooms-form-actions">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Salvar</button>
                    <?php if ($linked): ?>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>view-booking/<?php echo (int)($linked['id'] ?? 0); ?>" class="btn btn-secondary">Cancelar</a>
                    <?php else: ?>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>rooms-list-service-requests" class="btn btn-secondary">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>
