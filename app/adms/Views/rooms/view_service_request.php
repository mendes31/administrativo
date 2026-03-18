<?php
use App\adms\Helpers\CSRFHelper;

$r = $this->data['request'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Solicitação #<?php echo (int)($r['id'] ?? 0); ?> (Salas)</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
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
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-eye me-2"></i>Detalhes</span>
            <div class="d-flex gap-2">
                <?php if (in_array('RoomsUpdateServiceRequest', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>rooms-update-service-request/<?php echo (int)($r['id'] ?? 0); ?>" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit me-1"></i>Editar
                    </a>
                <?php } ?>
                <a href="<?php echo $_ENV['URL_ADM']; ?>rooms-list-service-requests" class="btn btn-sm btn-secondary">Voltar</a>
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

            <div class="d-flex gap-2 flex-wrap">
                <?php
                $canClaim = empty($r['claimed_by_user_id']) && !empty($r['responsible_group_id']);
                ?>
                <?php if ($canClaim): ?>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="claim">
                        <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_claim_room_service_request'); ?>">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-hand-paper me-1"></i>Assumir solicitação
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

