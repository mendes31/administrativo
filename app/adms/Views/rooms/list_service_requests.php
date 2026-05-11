<?php
use App\adms\Helpers\CSRFHelper;

$filters = $this->data['filters'] ?? [];
$moduleRequests = $this->data['requests'] ?? [];
$bookingAddReq = $this->data['booking_additional_requests'] ?? [];
$hasModule = !empty($moduleRequests);
$hasBookingAdd = !empty($bookingAddReq);
$canViewBooking = in_array('ViewBooking', $this->data['buttonPermission'] ?? [], true);
$canUpdateBooking = in_array('UpdateBooking', $this->data['buttonPermission'] ?? [], true);
?>
<?php include __DIR__ . '/partials/module_head.php'; ?>
<div class="container-fluid rooms-module-page px-2 px-sm-3 px-md-4">
    <div class="mb-2 mb-md-1 d-flex flex-column flex-md-row gap-2 align-items-start align-items-md-center">
        <h2 class="rooms-page-title mt-2 mt-md-3 mb-0">Solicitações (Reserva de Salas)</h2>
        <ol class="breadcrumb mb-0 mt-1 mt-md-3 ms-md-auto small">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Reserva de Salas</li>
            <li class="breadcrumb-item active">Solicitações</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="card mb-4 border-light shadow">
        <div class="card-header rooms-card-header d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center justify-content-between">
            <span><i class="fas fa-clipboard-list me-2"></i>Solicitações</span>
            <div class="rooms-card-header-actions">
                <?php if (in_array('RoomsCreateServiceRequest', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>rooms-create-service-request" class="btn btn-sm btn-success w-100 w-sm-auto">
                        <i class="fas fa-plus me-1"></i>Nova Solicitação
                    </a>
                <?php } ?>
            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($this->data['service_requests_list_only_own'])): ?>
                <div class="alert alert-light border small py-2 mb-3">
                    <i class="fas fa-info-circle me-1 text-muted"></i>
                    <strong>Solicitações de serviço (módulo):</strong> em que é o solicitante ou vinculadas a uma reserva sua.
                    <strong>Pedidos adicionais na reserva:</strong> listados na secção abaixo quando é organizador da reserva ou responsável pelo pedido.
                    Membros da equipa de atendimento podem abrir solicitações de serviço pelo URL ou notificações.
                </div>
            <?php endif; ?>

            <form method="GET" class="row g-2 align-items-end mb-3">
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Todos</option>
                        <?php
                        $statusOptions = [
                            'pending' => 'Pendente',
                            'in_progress' => 'Em andamento',
                            'done' => 'Concluída',
                            'cancelled' => 'Cancelada',
                        ];
                        foreach ($statusOptions as $k => $label) {
                            $selected = (!empty($filters['status']) && $filters['status'] === $k) ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($k) . '" ' . $selected . '>' . htmlspecialchars($label) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-4">
                    <label class="form-label">Equipe</label>
                    <select name="responsible_group_id" class="form-select">
                        <option value="">Todas</option>
                        <?php foreach (($this->data['groups'] ?? []) as $g): ?>
                            <option value="<?php echo (int)$g['id']; ?>"
                                <?php echo (!empty($filters['responsible_group_id']) && (int)$filters['responsible_group_id'] === (int)$g['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($g['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label">Reserva</label>
                    <select name="has_booking" class="form-select">
                        <option value="">Todas</option>
                        <option value="1" <?php echo (isset($filters['has_booking']) && (string)$filters['has_booking'] === '1') ? 'selected' : ''; ?>>Vinculada à reserva</option>
                        <option value="0" <?php echo (isset($filters['has_booking']) && (string)$filters['has_booking'] === '0') ? 'selected' : ''; ?>>Avulsa (sem reserva)</option>
                    </select>
                </div>
                <div class="col-12 col-md-5">
                    <button type="submit" class="btn btn-primary me-1"><i class="fas fa-filter me-1"></i>Filtrar</button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>rooms-list-service-requests" class="btn btn-outline-secondary">Limpar</a>
                </div>
            </form>

            <?php if (!$hasModule && !$hasBookingAdd): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>Nenhuma solicitação encontrada (nem no módulo nem pedidos adicionais nas suas reservas).
                </div>
            <?php else: ?>
                <?php if (!$hasModule): ?>
                <div class="alert alert-light border small mb-3">
                    Nenhuma solicitação de serviço no módulo com os filtros atuais. Os filtros acima aplicam-se apenas a essa lista.
                </div>
                <?php endif; ?>

                <?php if ($hasModule): ?>
                <h6 class="text-muted text-uppercase small mb-2">Solicitações de serviço (módulo)</h6>
                <div class="d-none d-md-block table-responsive">
                    <table class="table table-hover align-middle table-sm">
                        <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Reserva</th>
                            <th>Tipo</th>
                            <th>Equipe</th>
                            <th>Data</th>
                            <th>Horário</th>
                            <th>Status</th>
                            <th>Assumida por</th>
                            <th class="text-center">Ações</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($moduleRequests as $r): ?>
                            <tr>
                                <td><?php echo (int)$r['id']; ?></td>
                                <td class="small">
                                    <?php if (!empty($r['booking_id'])): ?>
                                        <span class="badge bg-info text-dark">#<?php echo (int)$r['booking_id']; ?></span>
                                        <div class="text-muted text-truncate" style="max-width: 12rem;" title="<?php echo htmlspecialchars($r['booking_title'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($r['booking_room_name'] ?? ''); ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($r['request_type_name'] ?? ''); ?></strong><br>
                                    <small class="text-muted"><code><?php echo htmlspecialchars($r['request_type_code'] ?? ''); ?></code></small>
                                </td>
                                <td>
                                    <?php if (!empty($r['responsible_group_name'])): ?>
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($r['responsible_group_name']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">Não definido</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?php echo htmlspecialchars($r['service_date'] ?? ''); ?></small>
                                </td>
                                <td>
                                    <small>
                                        <?php echo htmlspecialchars(substr((string)($r['start_time'] ?? ''), 0, 5)); ?>
                                        <?php if (!empty($r['end_time'])): ?>
                                            &ndash; <?php echo htmlspecialchars(substr((string)$r['end_time'], 0, 5)); ?>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td>
                                    <?php
                                    $status = $r['status'] ?? 'pending';
                                    $badge = 'secondary';
                                    $label = 'Pendente';
                                    if ($status === 'pending') { $badge = 'secondary'; $label = 'Pendente'; }
                                    elseif ($status === 'in_progress') { $badge = 'warning'; $label = 'Em andamento'; }
                                    elseif ($status === 'done') { $badge = 'success'; $label = 'Concluída'; }
                                    elseif ($status === 'cancelled') { $badge = 'dark'; $label = 'Cancelada'; }
                                    ?>
                                    <span class="badge bg-<?php echo $badge; ?>"><?php echo $label; ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($r['claimed_by_name'])): ?>
                                        <?php echo htmlspecialchars($r['claimed_by_name']); ?>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <?php if (in_array('RoomsViewServiceRequest', $this->data['buttonPermission'] ?? [])) { ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>rooms-view-service-request/<?php echo (int)$r['id']; ?>" class="btn btn-primary" title="Visualizar">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        <?php } ?>
                                        <?php if (in_array('RoomsUpdateServiceRequest', $this->data['buttonPermission'] ?? [])) { ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>rooms-update-service-request/<?php echo (int)$r['id']; ?>" class="btn btn-warning" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        <?php } ?>
                                        <?php if (in_array('RoomsDeleteServiceRequest', $this->data['buttonPermission'] ?? [])) { ?>
                                            <button type="button" class="btn btn-danger"
                                                    onclick="confirmDelete(<?php echo (int)$r['id']; ?>, '<?php echo htmlspecialchars($r['request_type_name'] ?? ''); ?>')"
                                                    title="Excluir">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php } ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-md-none">
                    <?php foreach ($moduleRequests as $r): ?>
                        <?php
                        $status = $r['status'] ?? 'pending';
                        $badge = 'secondary';
                        $label = 'Pendente';
                        if ($status === 'pending') { $badge = 'secondary'; $label = 'Pendente'; }
                        elseif ($status === 'in_progress') { $badge = 'warning'; $label = 'Em andamento'; }
                        elseif ($status === 'done') { $badge = 'success'; $label = 'Concluída'; }
                        elseif ($status === 'cancelled') { $badge = 'dark'; $label = 'Cancelada'; }
                        ?>
                        <div class="card rooms-mobile-card shadow-sm mb-3">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <div>
                                        <span class="text-muted small">#<?php echo (int)$r['id']; ?></span>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($r['request_type_name'] ?? ''); ?></div>
                                        <code class="small text-muted"><?php echo htmlspecialchars($r['request_type_code'] ?? ''); ?></code>
                                    </div>
                                    <span class="badge bg-<?php echo $badge; ?>"><?php echo $label; ?></span>
                                </div>
                                <?php if (!empty($r['booking_id'])): ?>
                                    <div class="small mb-2">
                                        <span class="badge bg-info text-dark">Reserva #<?php echo (int)$r['booking_id']; ?></span>
                                        <?php echo htmlspecialchars($r['booking_room_name'] ?? ''); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="small mb-1"><span class="rooms-mobile-label">Equipe</span>
                                    <?php if (!empty($r['responsible_group_name'])): ?>
                                        <?php echo htmlspecialchars($r['responsible_group_name']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </div>
                                <div class="small mb-1"><span class="rooms-mobile-label">Quando</span>
                                    <?php echo htmlspecialchars($r['service_date'] ?? ''); ?>
                                    <?php echo htmlspecialchars(substr((string)($r['start_time'] ?? ''), 0, 5)); ?>
                                    <?php if (!empty($r['end_time'])): ?>
                                        – <?php echo htmlspecialchars(substr((string)$r['end_time'], 0, 5)); ?>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($r['claimed_by_name'])): ?>
                                    <div class="small text-muted mb-2"><i class="fas fa-user-check me-1"></i><?php echo htmlspecialchars($r['claimed_by_name']); ?></div>
                                <?php endif; ?>
                                <div class="rooms-mobile-actions mt-2">
                                    <?php if (in_array('RoomsViewServiceRequest', $this->data['buttonPermission'] ?? [])) { ?>
                                        <a href="<?php echo $_ENV['URL_ADM']; ?>rooms-view-service-request/<?php echo (int)$r['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-eye me-1"></i>Ver</a>
                                    <?php } ?>
                                    <?php if (in_array('RoomsUpdateServiceRequest', $this->data['buttonPermission'] ?? [])) { ?>
                                        <a href="<?php echo $_ENV['URL_ADM']; ?>rooms-update-service-request/<?php echo (int)$r['id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit me-1"></i>Editar</a>
                                    <?php } ?>
                                    <?php if (in_array('RoomsDeleteServiceRequest', $this->data['buttonPermission'] ?? [])) { ?>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo (int)$r['id']; ?>, '<?php echo htmlspecialchars($r['request_type_name'] ?? ''); ?>')"><i class="fas fa-trash me-1"></i>Excluir</button>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-3">
                    <?php echo $this->data['pagination'] ?? ''; ?>
                </div>
                <?php endif; ?>

                <?php if ($hasBookingAdd): ?>
                <h6 class="text-muted text-uppercase small mb-2 mt-4">Pedidos adicionais na reserva</h6>
                <p class="small text-muted mb-3">Registados ao criar ou editar a reserva. Para alterar, use <strong>Reservas → Editar reserva</strong> (regras de prazo aplicam-se).</p>
                <div class="d-none d-md-block table-responsive">
                    <table class="table table-hover align-middle table-sm">
                        <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Reserva</th>
                            <th>Sala / horário</th>
                            <th>Tipo</th>
                            <th>Qtd</th>
                            <th>Responsável</th>
                            <th>Estado</th>
                            <th class="text-center">Ações</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($bookingAddReq as $bar): ?>
                            <tr>
                                <td><?php echo (int) ($bar['id'] ?? 0); ?></td>
                                <td class="small">
                                    <span class="badge bg-secondary">#<?php echo (int) ($bar['booking_id'] ?? 0); ?></span>
                                    <div class="text-truncate" style="max-width: 14rem;" title="<?php echo htmlspecialchars((string) ($bar['booking_title'] ?? '')); ?>">
                                        <?php echo htmlspecialchars((string) ($bar['booking_title'] ?? '—')); ?>
                                    </div>
                                </td>
                                <td class="small">
                                    <?php echo htmlspecialchars((string) ($bar['room_name'] ?? '')); ?><br>
                                    <span class="text-muted">
                                        <?php
                                        $bs = strtotime((string) ($bar['booking_start_datetime'] ?? ''));
                                        $be = strtotime((string) ($bar['booking_end_datetime'] ?? ''));
                                        echo $bs ? htmlspecialchars(date('d/m/Y H:i', $bs)) : '—';
                                        if ($be) {
                                            echo ' – ' . htmlspecialchars(date('H:i', $be));
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars((string) ($bar['request_type_display_name'] ?? '')); ?></strong><br>
                                    <small class="text-muted"><code><?php echo htmlspecialchars((string) ($bar['request_type'] ?? '')); ?></code></small>
                                </td>
                                <td><?php echo isset($bar['quantity']) && $bar['quantity'] !== '' && $bar['quantity'] !== null ? (int) $bar['quantity'] : '—'; ?></td>
                                <td class="small"><?php echo htmlspecialchars((string) ($bar['responsible_name'] ?? '')); ?></td>
                                <td>
                                    <?php
                                    $bst = (string) ($bar['status'] ?? 'pending');
                                    $bb = 'secondary';
                                    $bl = $bst;
                                    if ($bst === 'pending') { $bb = 'warning'; $bl = 'Pendente'; }
                                    elseif ($bst === 'in_preparation') { $bb = 'info'; $bl = 'Em preparação'; }
                                    elseif ($bst === 'attended') { $bb = 'success'; $bl = 'Atendido'; }
                                    elseif ($bst === 'cancelled') { $bb = 'dark'; $bl = 'Cancelado'; }
                                    ?>
                                    <span class="badge bg-<?php echo $bb; ?>"><?php echo htmlspecialchars($bl); ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if ($canViewBooking): ?>
                                        <a href="<?php echo $_ENV['URL_ADM']; ?>view-booking/<?php echo (int) ($bar['booking_id'] ?? 0); ?>" class="btn btn-sm btn-outline-primary" title="Ver reserva">
                                            <i class="fas fa-calendar-check"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($canUpdateBooking): ?>
                                        <a href="<?php echo $_ENV['URL_ADM']; ?>update-booking/<?php echo (int) ($bar['booking_id'] ?? 0); ?>" class="btn btn-sm btn-outline-secondary" title="Editar reserva">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-md-none">
                    <?php foreach ($bookingAddReq as $bar): ?>
                        <?php
                        $bst = (string) ($bar['status'] ?? 'pending');
                        $bb = 'secondary';
                        $bl = $bst;
                        if ($bst === 'pending') { $bb = 'warning'; $bl = 'Pendente'; }
                        elseif ($bst === 'in_preparation') { $bb = 'info'; $bl = 'Em preparação'; }
                        elseif ($bst === 'attended') { $bb = 'success'; $bl = 'Atendido'; }
                        elseif ($bst === 'cancelled') { $bb = 'dark'; $bl = 'Cancelado'; }
                        $bs = strtotime((string) ($bar['booking_start_datetime'] ?? ''));
                        ?>
                        <div class="card rooms-mobile-card shadow-sm mb-3 border-start border-3 border-secondary">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between gap-2 mb-2">
                                    <div>
                                        <span class="badge bg-secondary">Reserva #<?php echo (int) ($bar['booking_id'] ?? 0); ?></span>
                                        <div class="fw-semibold mt-1"><?php echo htmlspecialchars((string) ($bar['request_type_display_name'] ?? '')); ?></div>
                                        <code class="small text-muted"><?php echo htmlspecialchars((string) ($bar['request_type'] ?? '')); ?></code>
                                    </div>
                                    <span class="badge bg-<?php echo $bb; ?>"><?php echo htmlspecialchars($bl); ?></span>
                                </div>
                                <div class="small mb-1"><?php echo htmlspecialchars((string) ($bar['room_name'] ?? '')); ?></div>
                                <div class="small text-muted mb-2"><?php echo $bs ? htmlspecialchars(date('d/m/Y H:i', $bs)) : ''; ?></div>
                                <div class="small mb-2"><span class="rooms-mobile-label">Responsável</span> <?php echo htmlspecialchars((string) ($bar['responsible_name'] ?? '')); ?></div>
                                <div class="rooms-mobile-actions">
                                    <?php if ($canViewBooking): ?>
                                        <a href="<?php echo $_ENV['URL_ADM']; ?>view-booking/<?php echo (int) ($bar['booking_id'] ?? 0); ?>" class="btn btn-sm btn-primary"><i class="fas fa-eye me-1"></i>Reserva</a>
                                    <?php endif; ?>
                                    <?php if ($canUpdateBooking): ?>
                                        <a href="<?php echo $_ENV['URL_ADM']; ?>update-booking/<?php echo (int) ($bar['booking_id'] ?? 0); ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit me-1"></i>Editar</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="deleteForm" method="POST">
                <input type="hidden" name="csrf_token" id="delete_csrf_token" value="">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir a solicitação <strong id="delete_request_name"></strong>?</p>
                    <p class="text-danger"><small><i class="fas fa-exclamation-triangle me-1"></i>Esta ação não pode ser desfeita.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Confirmar Exclusão</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function confirmDelete(id, name) {
        document.getElementById('delete_request_name').textContent = name;
        document.getElementById('deleteForm').action = "<?php echo $_ENV['URL_ADM']; ?>rooms-delete-service-request/" + id;
        document.getElementById('delete_csrf_token').value = "<?php echo CSRFHelper::generateCSRFToken('form_delete_room_service_request'); ?>";
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }
</script>
