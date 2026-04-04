<?php
?>
<?php include __DIR__ . '/partials/module_head.php'; ?>
<div class="container-fluid rooms-module-page px-2 px-sm-3 px-md-4">
    <div class="mb-2 mb-md-1 d-flex flex-column flex-md-row gap-2 align-items-start align-items-md-center">
        <h2 class="rooms-page-title mt-2 mt-md-3 mb-0">Tipos de Solicitação (Reserva de Salas)</h2>
        <ol class="breadcrumb mb-0 mt-1 mt-md-3 ms-md-auto small">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Reserva de Salas</li>
            <li class="breadcrumb-item">Tipos de Solicitação</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="card mb-4 border-light shadow">
        <div class="card-header rooms-card-header d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center justify-content-between">
            <span><i class="fas fa-list me-2"></i>Tipos de Solicitação</span>
            <div class="rooms-card-header-actions">
                <?php if (in_array('RoomsCreateRequestType', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>rooms-create-request-type" class="btn btn-sm btn-success w-100 w-sm-auto">
                        <i class="fas fa-plus me-1"></i>Novo Tipo
                    </a>
                <?php } ?>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($this->data['requestTypes'])): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Nenhum tipo de solicitação encontrado.
                </div>
            <?php else: ?>
                <div class="d-none d-md-block table-responsive">
                    <table class="table table-hover align-middle table-sm">
                        <thead class="table-light">
                        <tr>
                            <th>Código</th>
                            <th>Nome</th>
                            <th>Equipe Responsável</th>
                            <th>Campos</th>
                            <th>Status</th>
                            <th class="text-center">Ações</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($this->data['requestTypes'] as $type): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($type['code']) ?></code></td>
                                <td><strong><?= htmlspecialchars($type['name']) ?></strong></td>
                                <td>
                                    <?php if (!empty($type['default_group_name'])): ?>
                                        <span class="badge bg-primary"><?= htmlspecialchars($type['default_group_name']) ?></span>
                                    <?php elseif (!empty($type['default_responsible_name'])): ?>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($type['default_responsible_name']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">Não definido</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small>
                                        <?php if (!empty($type['requires_quantity'])): ?>
                                            <span class="badge bg-success">Quantidade</span>
                                        <?php else: ?>
                                            <span class="text-muted">Nenhum</span>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if (!empty($type['is_active'])): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <?php if (in_array('RoomsUpdateRequestType', $this->data['buttonPermission'] ?? [])) { ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>rooms-update-request-type/<?= $type['id'] ?>"
                                               class="btn btn-warning" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        <?php } ?>
                                        <?php if (in_array('RoomsDeleteRequestType', $this->data['buttonPermission'] ?? [])) { ?>
                                            <button type="button" class="btn btn-danger"
                                                    onclick="confirmDelete(<?= $type['id'] ?>, '<?= htmlspecialchars($type['name']) ?>')"
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
                    <?php foreach ($this->data['requestTypes'] as $type): ?>
                        <div class="card rooms-mobile-card shadow-sm mb-3">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <div>
                                        <code><?= htmlspecialchars($type['code']) ?></code>
                                        <div class="fw-semibold"><?= htmlspecialchars($type['name']) ?></div>
                                    </div>
                                    <?php if (!empty($type['is_active'])): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inativo</span>
                                    <?php endif; ?>
                                </div>
                                <div class="small mb-2">
                                    <span class="rooms-mobile-label">Equipe</span>
                                    <?php if (!empty($type['default_group_name'])): ?>
                                        <?= htmlspecialchars($type['default_group_name']) ?>
                                    <?php elseif (!empty($type['default_responsible_name'])): ?>
                                        <?= htmlspecialchars($type['default_responsible_name']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Não definido</span>
                                    <?php endif; ?>
                                </div>
                                <div class="small mb-2">
                                    <?php if (!empty($type['requires_quantity'])): ?>
                                        <span class="badge bg-success">Exige quantidade</span>
                                    <?php else: ?>
                                        <span class="text-muted">Sem campo extra</span>
                                    <?php endif; ?>
                                </div>
                                <div class="rooms-mobile-actions">
                                    <?php if (in_array('RoomsUpdateRequestType', $this->data['buttonPermission'] ?? [])) { ?>
                                        <a href="<?php echo $_ENV['URL_ADM']; ?>rooms-update-request-type/<?= $type['id'] ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit me-1"></i>Editar</a>
                                    <?php } ?>
                                    <?php if (in_array('RoomsDeleteRequestType', $this->data['buttonPermission'] ?? [])) { ?>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $type['id'] ?>, '<?= htmlspecialchars($type['name']) ?>')"><i class="fas fa-trash me-1"></i>Excluir</button>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
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
                    <p>Tem certeza que deseja excluir o tipo de solicitação <strong id="delete_type_name"></strong>?</p>
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
    document.getElementById('delete_type_name').textContent = name;
    document.getElementById('deleteForm').action = '<?php echo $_ENV['URL_ADM']; ?>rooms-delete-request-type/' + id;
    document.getElementById('delete_csrf_token').value = '<?php echo \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_delete_room_request_type'); ?>';
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
