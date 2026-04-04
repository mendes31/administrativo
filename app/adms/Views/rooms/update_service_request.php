<?php
use App\adms\Helpers\CSRFHelper;

$r = $this->data['request'] ?? [];
?>
<?php include __DIR__ . '/partials/module_head.php'; ?>
<div class="container-fluid rooms-module-page px-2 px-sm-3 px-md-4">
    <div class="mb-2 mb-md-1 d-flex flex-column flex-md-row gap-2 align-items-start align-items-md-center">
        <h2 class="rooms-page-title mt-2 mt-md-3 mb-0">Editar Solicitação #<?php echo (int)($r['id'] ?? 0); ?> (Salas)</h2>
        <ol class="breadcrumb mb-0 mt-1 mt-md-3 ms-md-auto small">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Reserva de Salas</li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>rooms-list-service-requests" class="text-decoration-none">Solicitações</a>
            </li>
            <li class="breadcrumb-item active">Editar</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="card border-light shadow">
        <div class="card-header rooms-card-header">
            <i class="fas fa-edit me-2"></i>Editar Solicitação
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_update_room_service_request'); ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="request_type_id" class="form-label">Tipo de Solicitação *</label>
                        <select name="request_type_id" id="request_type_id" class="form-select" required>
                            <option value="">Selecione</option>
                            <?php foreach (($this->data['requestTypes'] ?? []) as $t): ?>
                                <option value="<?php echo (int)$t['id']; ?>"
                                    <?php echo (!empty($r['request_type_id']) && (int)$r['request_type_id'] === (int)$t['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($t['name']); ?> (<?php echo htmlspecialchars($t['code']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="quantity" class="form-label">Quantidade</label>
                        <input type="number" min="1" name="quantity" id="quantity" class="form-control"
                               value="<?php echo htmlspecialchars((string)($r['quantity'] ?? '')); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select">
                            <?php
                            $statusOptions = [
                                'pending' => 'Pendente',
                                'in_progress' => 'Em andamento',
                                'done' => 'Concluída',
                                'cancelled' => 'Cancelada',
                            ];
                            foreach ($statusOptions as $k => $label) {
                                $selected = (!empty($r['status']) && $r['status'] === $k) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($k) . '" ' . $selected . '>' . htmlspecialchars($label) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="service_date" class="form-label">Data *</label>
                        <input type="date" name="service_date" id="service_date" class="form-control"
                               value="<?php echo htmlspecialchars(substr((string)($r['service_date'] ?? ''), 0, 10)); ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label for="start_time" class="form-label">Hora de início *</label>
                        <input type="time" name="start_time" id="start_time" class="form-control"
                               value="<?php echo htmlspecialchars(substr((string)($r['start_time'] ?? ''), 0, 5)); ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label for="end_time" class="form-label">Hora de término</label>
                        <input type="time" name="end_time" id="end_time" class="form-control"
                               value="<?php echo htmlspecialchars(substr((string)($r['end_time'] ?? ''), 0, 5)); ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="responsible_group_id" class="form-label">Equipe Responsável</label>
                        <select name="responsible_group_id" id="responsible_group_id" class="form-select">
                            <option value="">Automático (do tipo) / Nenhuma</option>
                            <?php foreach (($this->data['groups'] ?? []) as $g): ?>
                                <option value="<?php echo (int)$g['id']; ?>"
                                    <?php echo (!empty($r['responsible_group_id']) && (int)$r['responsible_group_id'] === (int)$g['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($g['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="location" class="form-label">Local *</label>
                        <input type="text" name="location" id="location" class="form-control"
                               value="<?php echo htmlspecialchars($r['location'] ?? ''); ?>" required>
                    </div>

                    <div class="col-12">
                        <label for="request_description" class="form-label">Informações adicionais</label>
                        <textarea name="request_description" id="request_description" class="form-control" rows="3"><?php
                            echo htmlspecialchars($r['request_description'] ?? '');
                        ?></textarea>
                    </div>
                </div>

                <div class="mt-4 rooms-form-actions">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Salvar</button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>rooms-view-service-request/<?php echo (int)($r['id'] ?? 0); ?>" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

