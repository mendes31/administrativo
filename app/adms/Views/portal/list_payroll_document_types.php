<?php
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Tipos de documento (RH)</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Gestão de Pessoas</li>
            <li class="breadcrumb-item">Tipos de documento (RH)</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-tags me-2"></i>Tipos cadastrados</span>
            <div>
                <?php if (in_array('CreatePayrollDocumentType', $this->data['buttonPermission'] ?? [], true)) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>create-payroll-document-type" class="btn btn-sm btn-success">
                        <i class="fas fa-plus me-1"></i>Novo tipo
                    </a>
                <?php } ?>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($this->data['types'])): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Nenhum tipo encontrado. Execute a migração ou crie um novo tipo.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Código</th>
                                <th>Nome</th>
                                <th>Ícone</th>
                                <th>Prefixo título</th>
                                <th>Assinatura / auth</th>
                                <th>Lembretes cron</th>
                                <th>Ordem</th>
                                <th>Estado</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->data['types'] as $type): ?>
                                <?php
                                $icon = trim((string)($type['icon'] ?? ''));
                                $iconClass = $icon !== '' ? $icon : 'fa-tag';
                                ?>
                                <tr>
                                    <td><code><?= htmlspecialchars((string)$type['code']) ?></code></td>
                                    <td>
                                        <i class="fas <?= htmlspecialchars($iconClass) ?> me-2 text-secondary"></i>
                                        <strong><?= htmlspecialchars((string)$type['name']) ?></strong>
                                    </td>
                                    <td class="small text-muted"><?= $icon !== '' ? htmlspecialchars($icon) : '—' ?></td>
                                    <td class="small"><?= htmlspecialchars((string)($type['default_title_prefix'] ?? '—')) ?></td>
                                    <td class="small">
                                        <?php if (!empty($type['requires_signature'])): ?>
                                            <span class="badge bg-info">Assinatura</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">—</span>
                                        <?php endif; ?>
                                        <span class="badge bg-light text-dark border ms-1"><?= htmlspecialchars((string)($type['signature_auth'] ?? 'none')) ?></span>
                                        <?php if (!empty($type['require_auth_download'])): ?>
                                            <span class="badge bg-warning text-dark">Download auth</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small">
                                        <?php if (!empty($type['signature_reminders_enabled'])): ?>
                                            <span class="badge bg-primary">Sim</span>
                                            <span class="text-muted"><?= (int)($type['signature_reminder_day_1'] ?? 1) ?> / <?= (int)($type['signature_reminder_day_2'] ?? 3) ?> / <?= (int)($type['signature_reminder_day_3'] ?? 7) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Não</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= (int)($type['sort_order'] ?? 0) ?></td>
                                    <td>
                                        <?php if (!empty($type['is_active'])): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <?php if (in_array('UpdatePayrollDocumentType', $this->data['buttonPermission'] ?? [], true)) { ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>update-payroll-document-type/<?= (int)$type['id'] ?>"
                                                   class="btn btn-warning" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php } ?>
                                            <?php if (in_array('DeletePayrollDocumentType', $this->data['buttonPermission'] ?? [], true)) { ?>
                                                <button type="button" class="btn btn-danger"
                                                        onclick="confirmDeletePayrollDocType(<?= (int)$type['id'] ?>, '<?= htmlspecialchars((string)$type['name'], ENT_QUOTES) ?>')"
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

<div class="modal fade" id="deletePayrollDocTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="deletePayrollDocTypeForm" method="POST">
                <input type="hidden" name="csrf_token" id="delete_payroll_doc_type_csrf" value="">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Excluir o tipo <strong id="delete_payroll_doc_type_name"></strong>?</p>
                    <p class="text-danger small mb-0"><i class="fas fa-exclamation-triangle me-1"></i>Só é permitido se não existirem documentos nem lotes de importação usando este código.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Confirmar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDeletePayrollDocType(id, name) {
    document.getElementById('delete_payroll_doc_type_name').textContent = name;
    document.getElementById('deletePayrollDocTypeForm').action = '<?php echo $_ENV['URL_ADM']; ?>delete-payroll-document-type/' + id;
    document.getElementById('delete_payroll_doc_type_csrf').value = '<?php echo \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_delete_payroll_document_type'); ?>';
    new bootstrap.Modal(document.getElementById('deletePayrollDocTypeModal')).show();
}
</script>
