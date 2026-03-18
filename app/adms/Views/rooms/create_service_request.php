<?php
use App\adms\Helpers\CSRFHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Criar Solicitação (Salas)</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Reserva de Salas</li>
            <li class="breadcrumb-item">Solicitações</li>
            <li class="breadcrumb-item active">Criar</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="card border-light shadow">
        <div class="card-header">
            <i class="fas fa-plus me-2"></i>Nova Solicitação
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_create_room_service_request'); ?>">

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
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label for="start_time" class="form-label">Hora de início *</label>
                        <input type="time" name="start_time" id="start_time" class="form-control" required>
                    </div>

                    <div class="col-md-3">
                        <label for="end_time" class="form-label">Hora de término</label>
                        <input type="time" name="end_time" id="end_time" class="form-control">
                        <div class="form-text">Opcional. Use quando precisar de janela de atendimento.</div>
                    </div>

                    <div class="col-md-3">
                        <label for="location" class="form-label">Local *</label>
                        <input type="text" name="location" id="location" class="form-control" required
                               placeholder="Ex.: Sala X, Refeitório, Copa...">
                        <div class="form-text">Quando vinculado a uma reserva, você pode repetir ou ajustar o local.</div>
                    </div>

                    <div class="col-12">
                        <label for="request_description" class="form-label">Informações adicionais</label>
                        <textarea name="request_description" id="request_description" class="form-control" rows="3"></textarea>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Salvar</button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>rooms-list-service-requests" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

