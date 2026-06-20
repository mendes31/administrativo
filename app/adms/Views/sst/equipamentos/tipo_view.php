<?php
use App\adms\Helpers\CSRFHelper;
$item = $this->data['item'] ?? [];
$checklist = $this->data['checklist'] ?? [];
$perms = $this->data['buttonPermission'] ?? [];
$csrfChecklist = CSRFHelper::generateCSRFToken('sst_equipamento_checklist');
$csrfDelete = CSRFHelper::generateCSRFToken('form_delete_sst_equipamento_tipos');
$id = (int)($item['id'] ?? 0);
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3"><i class="fas fa-layer-group me-2"></i><?= htmlspecialchars($item['nome'] ?? 'Tipo') ?></h2>
        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-equipamento-tipos">Tipos</a></li>
            <li class="breadcrumb-item active">Detalhe</li>
        </ol>
    </div>
    <div class="card mb-3 shadow-sm">
        <div class="card-header hstack gap-2">
            <span>Dados do tipo</span>
            <span class="ms-auto">
                <?php if (in_array('SstUpdateEquipamentoTipo', $perms, true)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-update-equipamento-tipo/<?= $id ?>" class="btn btn-warning btn-sm"><i class="fa-regular fa-pen-to-square"></i> Editar</a><?php endif; ?>
            </span>
        </div>
        <div class="card-body row">
            <div class="col-md-3"><strong>Código:</strong> <code><?= htmlspecialchars($item['codigo'] ?? '') ?></code></div>
            <div class="col-md-3"><strong>Status:</strong> <?= htmlspecialchars($item['status'] ?? '') ?></div>
            <?php if (!empty($item['descricao'])): ?><div class="col-12 mt-2"><?= nl2br(htmlspecialchars($item['descricao'])) ?></div><?php endif; ?>
        </div>
    </div>
    <div class="card shadow-sm">
        <div class="card-header">Modelo de checklist</div>
        <div class="card-body">
            <?php if (in_array('SstManageEquipamentoChecklistItem', $perms, true)): ?>
            <form method="POST" action="<?= $_ENV['URL_ADM']; ?>sst-manage-equipamento-checklist-item" class="row g-2 mb-3 border-bottom pb-3">
                <input type="hidden" name="csrf_token" value="<?= $csrfChecklist ?>">
                <input type="hidden" name="adms_sst_equipamento_tipo_id" value="<?= $id ?>">
                <input type="hidden" name="action" value="add">
                <div class="col-md-6"><input type="text" name="descricao" class="form-control form-control-sm" placeholder="Descrição do item *" required></div>
                <div class="col-md-2"><input type="number" name="ordem" class="form-control form-control-sm" placeholder="Ordem" min="0"></div>
                <div class="col-md-2 form-check pt-2"><input type="checkbox" name="obrigatorio" value="1" class="form-check-input" id="obrigatorio" checked><label class="form-check-label" for="obrigatorio">Obrigatório</label></div>
                <div class="col-md-2"><button class="btn btn-success btn-sm w-100">Adicionar</button></div>
            </form>
            <?php endif; ?>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead><tr><th>#</th><th>Descrição</th><th>Obrig.</th><th>Ativo</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($checklist as $c): ?>
                        <tr>
                            <td><?= (int)($c['ordem'] ?? 0) ?></td>
                            <td><?= htmlspecialchars($c['descricao'] ?? '') ?></td>
                            <td><?= !empty($c['obrigatorio']) ? 'Sim' : 'Não' ?></td>
                            <td><?= !empty($c['ativo']) ? 'Sim' : 'Não' ?></td>
                            <td>
                                <?php if (in_array('SstManageEquipamentoChecklistItem', $perms, true)): ?>
                                <form method="POST" action="<?= $_ENV['URL_ADM']; ?>sst-manage-equipamento-checklist-item" class="d-inline" onsubmit="return confirm('Remover item?');">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfChecklist ?>">
                                    <input type="hidden" name="adms_sst_equipamento_tipo_id" value="<?= $id ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="item_id" value="<?= (int)$c['id'] ?>">
                                    <button class="btn btn-danger btn-sm"><i class="fa-regular fa-trash-can"></i></button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
