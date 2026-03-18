<?php
use App\adms\Helpers\CSRFHelper;

$filters = $this->data['filters'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Solicitações (Reserva de Salas)</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Reserva de Salas</li>
            <li class="breadcrumb-item active">Solicitações</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-clipboard-list me-2"></i>Solicitações</span>
            <div>
                <?php if (in_array('RoomsCreateServiceRequest', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>rooms-create-service-request" class="btn btn-sm btn-success">
                        <i class="fas fa-plus me-1"></i>Nova Solicitação
                    </a>
                <?php } ?>
            </div>
        </div>
        <div class="card-body">

            <form method="GET" class="row g-2 align-items-end mb-3">
                <div class="col-md-3">
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
                <div class="col-md-4">
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
                <div class="col-md-5">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i>Filtrar</button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>rooms-list-service-requests" class="btn btn-outline-secondary">Limpar</a>
                </div>
            </form>

            <?php if (empty($this->data['requests'])): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>Nenhuma solicitação encontrada.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                        <tr>
                            <th>#</th>
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
                        <?php foreach ($this->data['requests'] as $r): ?>
                            <tr>
                                <td><?php echo (int)$r['id']; ?></td>
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

                <div class="mt-3">
                    <?php echo $this->data['pagination'] ?? ''; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Exclusão -->
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

