<?php
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Tipos de Solicitação</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Gestão de Pessoas</li>
            <li class="breadcrumb-item">Tipos de Solicitação</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-list me-2"></i>Tipos de Solicitação</span>
            <div>
                <?php if (in_array('CreateRequestType', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>create-request-type" class="btn btn-sm btn-success">
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
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Código</th>
                                <th>Nome</th>
                                <th>Requer Aprovação Gestor</th>
                                <th>Campos</th>
                                <th>Status</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->data['requestTypes'] as $type): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($type['code']) ?></code></td>
                                    <td>
                                        <i class="fas <?= htmlspecialchars($type['icon'] ?? 'fa-circle') ?> me-2 text-<?= htmlspecialchars($type['color'] ?? 'primary') ?>"></i>
                                        <strong><?= htmlspecialchars($type['name']) ?></strong>
                                    </td>
                                    <td>
                                        <?php if ($type['requires_manager_approval']): ?>
                                            <span class="badge bg-info">Sim</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Não</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small>
                                            <?php if ($type['requires_dates']): ?>
                                                <span class="badge bg-primary">Datas</span>
                                            <?php endif; ?>
                                            <?php if ($type['requires_days']): ?>
                                                <span class="badge bg-info">Dias</span>
                                            <?php endif; ?>
                                            <?php if ($type['requires_amount']): ?>
                                                <span class="badge bg-success">Valor</span>
                                            <?php endif; ?>
                                            <?php if (!$type['requires_dates'] && !$type['requires_days'] && !$type['requires_amount']): ?>
                                                <span class="text-muted">Nenhum</span>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($type['status']): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <?php if (in_array('UpdateRequestType', $this->data['buttonPermission'] ?? [])) { ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>update-request-type/<?= $type['id'] ?>" 
                                                   class="btn btn-warning" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php } ?>
                                            <?php if (in_array('DeleteRequestType', $this->data['buttonPermission'] ?? [])) { ?>
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
                    <p>Tem certeza que deseja excluir o tipo de solicitação <strong id="delete_type_name"></strong>?</p>
                    <p class="text-danger"><small><i class="fas fa-exclamation-triangle me-1"></i>Esta ação não pode ser desfeita. Certifique-se de que não há solicitações usando este tipo.</small></p>
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
    document.getElementById('deleteForm').action = '<?php echo $_ENV['URL_ADM']; ?>delete-request-type/' + id;
    document.getElementById('delete_csrf_token').value = '<?php echo \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_delete_request_type'); ?>';
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

