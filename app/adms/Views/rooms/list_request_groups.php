<?php
use App\adms\Helpers\CSRFHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Equipes/Grupos Responsáveis (Reserva de Salas)</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Reserva de Salas</li>
            <li class="breadcrumb-item active">Equipes</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-users me-2"></i>Equipes/Grupos</span>
            <div>
                <?php if (in_array('RoomsCreateRequestGroup', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>rooms-create-request-group" class="btn btn-sm btn-success">
                        <i class="fas fa-plus me-1"></i>Novo Grupo
                    </a>
                <?php } ?>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($this->data['groups'])): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Nenhum grupo encontrado.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                        <tr>
                            <th>Nome</th>
                            <th>Membros</th>
                            <th>Status</th>
                            <th class="text-center">Ações</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($this->data['groups'] as $group): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($group['name']) ?></strong></td>
                                <td>
                                    <span class="badge bg-info text-dark">
                                        <?= (int)($group['members_count'] ?? 0) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($group['is_active'])): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <?php if (in_array('RoomsUpdateRequestGroup', $this->data['buttonPermission'] ?? [])) { ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>rooms-update-request-group/<?= (int)$group['id'] ?>"
                                               class="btn btn-warning" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        <?php } ?>
                                        <?php if (in_array('RoomsDeleteRequestGroup', $this->data['buttonPermission'] ?? [])) { ?>
                                            <button type="button" class="btn btn-danger"
                                                    onclick="confirmDelete(<?= (int)$group['id'] ?>, '<?= htmlspecialchars($group['name']) ?>')"
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
                    <p>Tem certeza que deseja excluir o grupo <strong id="delete_group_name"></strong>?</p>
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
        document.getElementById('delete_group_name').textContent = name;
        document.getElementById('deleteForm').action = "<?php echo $_ENV['URL_ADM']; ?>rooms-delete-request-group/" + id;
        document.getElementById('delete_csrf_token').value = "<?php echo CSRFHelper::generateCSRFToken('form_delete_room_request_group'); ?>";
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }
</script>

