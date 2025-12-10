<?php
use App\adms\Helpers\FormatHelper;
$booking = $this->data['booking'] ?? [];
$participants = $this->data['participants'] ?? [];
$additionalRequests = $this->data['additionalRequests'] ?? [];

$statusClass = match($booking['status'] ?? '') {
    'pending' => 'warning',
    'confirmed' => 'success',
    'in_progress' => 'info',
    'completed' => 'secondary',
    'cancelled' => 'danger',
    default => 'secondary'
};

$statusText = match($booking['status'] ?? '') {
    'pending' => 'Pendente',
    'confirmed' => 'Confirmada',
    'in_progress' => 'Em Andamento',
    'completed' => 'Concluída',
    'cancelled' => 'Cancelada',
    default => $booking['status'] ?? 'Desconhecido'
};
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Visualizar Reserva</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-bookings" class="text-decoration-none">Reservas</a>
            </li>
            <li class="breadcrumb-item">Visualizar</li>
        </ol>
    </div>
    
    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2 flex-wrap">
            <span><i class="fas fa-calendar-check me-2"></i><?= htmlspecialchars($booking['title'] ?? 'Reserva') ?></span>
            <span class="ms-auto">
                <span class="badge bg-<?= $statusClass ?> fs-6"><?= $statusText ?></span>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="border-bottom pb-2 mb-3">Informações da Reserva</h5>
                    <dl class="row">
                        <dt class="col-sm-4">Sala:</dt>
                        <dd class="col-sm-8">
                            <strong><?= htmlspecialchars($booking['room_name'] ?? '') ?></strong>
                            <?php if (!empty($booking['location'])): ?>
                                <br><small class="text-muted"><?= htmlspecialchars($booking['location']) ?></small>
                            <?php endif; ?>
                        </dd>

                        <dt class="col-sm-4">Solicitante:</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($booking['user_name'] ?? '') ?></dd>

                        <dt class="col-sm-4">Data/Hora Início:</dt>
                        <dd class="col-sm-8">
                            <strong><?= FormatHelper::formatDate($booking['start_datetime'] ?? '', 'd/m/Y H:i') ?></strong>
                        </dd>

                        <dt class="col-sm-4">Data/Hora Fim:</dt>
                        <dd class="col-sm-8">
                            <strong><?= FormatHelper::formatDate($booking['end_datetime'] ?? '', 'd/m/Y H:i') ?></strong>
                        </dd>

                        <?php if (!empty($booking['description'])): ?>
                            <dt class="col-sm-4">Descrição:</dt>
                            <dd class="col-sm-8"><?= nl2br(htmlspecialchars($booking['description'])) ?></dd>
                        <?php endif; ?>

                        <?php if ($booking['requires_approval'] ?? false): ?>
                            <dt class="col-sm-4">Aprovação:</dt>
                            <dd class="col-sm-8">
                                <?php if (!empty($booking['approved_by'])): ?>
                                    <span class="badge bg-success">Aprovada por <?= htmlspecialchars($booking['approver_name'] ?? '') ?></span>
                                    <br><small class="text-muted"><?= FormatHelper::formatDate($booking['approved_at'] ?? '', 'd/m/Y H:i') ?></small>
                                <?php else: ?>
                                    <span class="badge bg-warning">Aguardando Aprovação</span>
                                <?php endif; ?>
                            </dd>
                        <?php endif; ?>

                        <?php if ($booking['status'] === 'cancelled' && !empty($booking['cancellation_reason'])): ?>
                            <dt class="col-sm-4">Motivo Cancelamento:</dt>
                            <dd class="col-sm-8"><?= nl2br(htmlspecialchars($booking['cancellation_reason'])) ?></dd>
                        <?php endif; ?>
                    </dl>
                </div>

                <div class="col-md-6">
                    <h5 class="border-bottom pb-2 mb-3">Ações</h5>
                    <div class="d-flex flex-column gap-2">
                        <?php if (in_array('ListBookings', $this->data['buttonPermission'] ?? [])) { ?>
                            <a href="<?php echo $_ENV['URL_ADM']; ?>list-bookings" class="btn btn-info">
                                <i class="fas fa-list me-2"></i>Listar Reservas
                            </a>
                        <?php } ?>
                        <?php if (in_array('UpdateBooking', $this->data['buttonPermission'] ?? []) && $booking['status'] !== 'cancelled' && $booking['status'] !== 'completed') { ?>
                            <a href="<?php echo $_ENV['URL_ADM']; ?>update-booking/<?= $booking['id'] ?>" class="btn btn-warning">
                                <i class="fas fa-edit me-2"></i>Editar Reserva
                            </a>
                        <?php } ?>
                        <?php if (in_array('CancelBooking', $this->data['buttonPermission'] ?? []) && $booking['status'] !== 'cancelled' && $booking['status'] !== 'completed') { ?>
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cancelBookingModal">
                                <i class="fas fa-times me-2"></i>Cancelar Reserva
                            </button>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <!-- Participantes -->
            <?php if (!empty($participants)): ?>
                <div class="mb-4">
                    <h5 class="border-bottom pb-2 mb-3">Participantes</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>E-mail</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($participants as $participant): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($participant['user_name'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($participant['user_email'] ?? '') ?></td>
                                        <td>
                                            <?php
                                            $partStatusClass = match($participant['status'] ?? '') {
                                                'confirmed' => 'success',
                                                'pending' => 'warning',
                                                'declined' => 'danger',
                                                default => 'secondary'
                                            };
                                            $partStatusText = match($participant['status'] ?? '') {
                                                'confirmed' => 'Confirmado',
                                                'pending' => 'Pendente',
                                                'declined' => 'Recusado',
                                                default => 'Desconhecido'
                                            };
                                            ?>
                                            <span class="badge bg-<?= $partStatusClass ?>"><?= $partStatusText ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Solicitações Adicionais -->
            <?php if (!empty($additionalRequests)): ?>
                <div class="mb-4">
                    <h5 class="border-bottom pb-2 mb-3">Solicitações Adicionais</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Descrição</th>
                                    <th>Quantidade</th>
                                    <th>Responsável</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($additionalRequests as $request): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($request['request_type'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($request['request_description'] ?? '') ?></td>
                                        <td><?= $request['quantity'] ?? '-' ?></td>
                                        <td><?= htmlspecialchars($request['responsible_name'] ?? '') ?></td>
                                        <td>
                                            <?php
                                            $reqStatusClass = match($request['status'] ?? '') {
                                                'attended' => 'success',
                                                'pending' => 'warning',
                                                'cancelled' => 'danger',
                                                default => 'secondary'
                                            };
                                            $reqStatusText = match($request['status'] ?? '') {
                                                'attended' => 'Atendida',
                                                'pending' => 'Pendente',
                                                'cancelled' => 'Cancelada',
                                                default => 'Desconhecido'
                                            };
                                            ?>
                                            <span class="badge bg-<?= $reqStatusClass ?>"><?= $reqStatusText ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de Cancelamento -->
<?php if (in_array('CancelBooking', $this->data['buttonPermission'] ?? []) && $booking['status'] !== 'cancelled' && $booking['status'] !== 'completed'): ?>
    <?php
    $cancelToken = CSRFHelper::generateCSRFToken('form_cancel_booking');
    ?>
    <div class="modal fade" id="cancelBookingModal" tabindex="-1" aria-labelledby="cancelBookingModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="<?php echo $_ENV['URL_ADM']; ?>cancel-booking/<?= $booking['id'] ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $cancelToken ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="cancelBookingModalLabel">Cancelar Reserva</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Tem certeza que deseja cancelar esta reserva?</p>
                        <div class="mb-3">
                            <label for="cancellation_reason" class="form-label">Motivo do Cancelamento (Opcional)</label>
                            <textarea name="cancellation_reason" id="cancellation_reason" class="form-control" rows="3" 
                                      placeholder="Informe o motivo do cancelamento..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Não, manter reserva</button>
                        <button type="submit" class="btn btn-danger">Sim, cancelar reserva</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

