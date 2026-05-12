<?php
use App\adms\Helpers\CSRFHelper;

$r = $this->data['request'] ?? [];
?>
<?php include __DIR__ . '/partials/module_head.php'; ?>
<div class="container-fluid rooms-module-page px-2 px-sm-3 px-md-4">
    <div class="mb-2 mb-md-1 d-flex flex-column flex-md-row gap-2 align-items-start align-items-md-center">
        <h2 class="rooms-page-title mt-2 mt-md-3 mb-0">Solicitação #<?php echo (int)($r['id'] ?? 0); ?> (Salas)</h2>
        <ol class="breadcrumb mb-0 mt-1 mt-md-3 ms-md-auto small">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Reserva de Salas</li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>rooms-list-service-requests" class="text-decoration-none">Solicitações</a>
            </li>
            <li class="breadcrumb-item active">Visualizar</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="card border-light shadow mb-4">
        <div class="card-header rooms-card-header d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center justify-content-between gap-2">
            <span><i class="fas fa-eye me-2"></i>Detalhes</span>
            <div class="rooms-card-header-actions d-flex flex-wrap gap-2">
                <?php if (in_array('RoomsUpdateServiceRequest', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>rooms-update-service-request/<?php echo (int)($r['id'] ?? 0); ?>" class="btn btn-sm btn-warning w-100 w-sm-auto">
                        <i class="fas fa-edit me-1"></i>Editar
                    </a>
                <?php } ?>
                <?php
                $log_resumo = $this->data['log_resumo'] ?? [];
                $log_btn_class = 'btn btn-sm btn-outline-info w-100 w-sm-auto';
                include __DIR__ . '/../partials/button_log_alteracoes.php';
                ?>
                <a href="<?php echo $_ENV['URL_ADM']; ?>rooms-list-service-requests" class="btn btn-sm btn-secondary w-100 w-sm-auto">Voltar</a>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="mb-1 text-muted small">Tipo</div>
                    <div><strong><?php echo htmlspecialchars($r['request_type_name'] ?? ''); ?></strong></div>
                    <div class="text-muted small"><code><?php echo htmlspecialchars($r['request_type_code'] ?? ''); ?></code></div>
                </div>
                <div class="col-md-3">
                    <div class="mb-1 text-muted small">Status</div>
                    <div><strong><?php echo htmlspecialchars($r['status'] ?? ''); ?></strong></div>
                </div>
                <div class="col-md-3">
                    <div class="mb-1 text-muted small">Quantidade</div>
                    <div><strong><?php echo htmlspecialchars((string)($r['quantity'] ?? '')); ?></strong></div>
                </div>

                <div class="col-md-4">
                    <div class="mb-1 text-muted small">Data</div>
                    <div><strong><?php echo htmlspecialchars($r['service_date'] ?? ''); ?></strong></div>
                </div>
                <div class="col-md-4">
                    <div class="mb-1 text-muted small">Horário</div>
                    <div>
                        <strong>
                            <?php echo htmlspecialchars(substr((string)($r['start_time'] ?? ''), 0, 5)); ?>
                            <?php if (!empty($r['end_time'])): ?>
                                &ndash; <?php echo htmlspecialchars(substr((string)$r['end_time'], 0, 5)); ?>
                            <?php endif; ?>
                        </strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-1 text-muted small">Local</div>
                    <div><strong><?php echo htmlspecialchars($r['location'] ?? ''); ?></strong></div>
                </div>

                <?php if (!empty($r['booking_id'])): ?>
                    <div class="col-12">
                        <div class="mb-1 text-muted small">Reserva vinculada</div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="badge bg-info text-dark">Reserva #<?php echo (int)$r['booking_id']; ?></span>
                            <?php if (!empty($r['booking_title'])): ?>
                                <span><strong><?php echo htmlspecialchars((string)$r['booking_title']); ?></strong></span>
                            <?php endif; ?>
                            <?php if (!empty($r['booking_room_name'])): ?>
                                <span class="text-muted">(<?php echo htmlspecialchars((string)$r['booking_room_name']); ?>)</span>
                            <?php endif; ?>
                            <a href="<?php echo $_ENV['URL_ADM']; ?>view-booking/<?php echo (int)$r['booking_id']; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-calendar-check me-1"></i>Abrir reserva
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="col-md-6">
                    <div class="mb-1 text-muted small">Equipe responsável</div>
                    <div>
                        <?php if (!empty($r['responsible_group_name'])): ?>
                            <span class="badge bg-primary"><?php echo htmlspecialchars($r['responsible_group_name']); ?></span>
                        <?php else: ?>
                            <span class="text-muted small">Não definido</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-1 text-muted small">Assumida por</div>
                    <div>
                        <?php if (!empty($r['claimed_by_name'])): ?>
                            <?php echo htmlspecialchars($r['claimed_by_name']); ?>
                            <small class="text-muted">(<?php echo htmlspecialchars($r['claimed_at'] ?? ''); ?>)</small>
                        <?php else: ?>
                            <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-12">
                    <div class="mb-1 text-muted small">Informações adicionais</div>
                    <div class="border rounded p-3 bg-light">
                        <?php echo nl2br(htmlspecialchars($r['request_description'] ?? '')); ?>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <div class="rooms-form-actions d-flex gap-2 flex-wrap">
                <?php
                $canClaim = empty($r['claimed_by_user_id']) && !empty($r['responsible_group_id']);
                ?>
                <?php if ($canClaim): ?>
                    <form method="POST" class="d-inline flex-grow-1 flex-sm-grow-0">
                        <input type="hidden" name="action" value="claim">
                        <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_claim_room_service_request'); ?>">
                        <button type="submit" class="btn btn-outline-primary w-100 w-sm-auto">
                            <i class="fas fa-hand-paper me-1"></i>Assumir solicitação
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

