<?php

use App\adms\Helpers\CSRFHelper;

?>
<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Gestão de Projetos - Grupos de Etapas</h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Gestão de Projetos</li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-stage-groups" class="text-decoration-none">Grupos de Etapas</a>
            </li>
            <li class="breadcrumb-item">Editar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">

        <div class="card-header hstack gap-2">
            <span>Editar Grupo de Etapas</span>

            <span class="ms-auto d-sm-flex flex-row">
                <?php
                if (in_array('ListStageGroups', $this->data['buttonPermission'] ?? [])) {
                    echo "<a href='{$_ENV['URL_ADM']}list-stage-groups' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-list'></i> Listar</a> ";
                }
                ?>
            </span>

        </div>

        <div class="card-body">

            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <form action="" method="POST" class="row g-3">

                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_update_stage_group'); ?>">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars((string)($this->data['form']['id'] ?? '')); ?>">

                <div class="col-12 col-md-6">
                    <label for="name" class="form-label">Nome do grupo</label>
                    <input type="text" name="name" id="name" class="form-control"
                           value="<?php echo htmlspecialchars($this->data['form']['name'] ?? ''); ?>">
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label d-block">Opções</label>
                    <div class="form-check form-switch mt-1">
                        <?php $active = !empty($this->data['form']['active']); ?>
                        <input class="form-check-input" type="checkbox" id="active" name="active"
                               <?php echo $active ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="active">Ativo</label>
                    </div>
                </div>

                <div class="col-12">
                    <label for="description" class="form-label">Descrição</label>
                    <textarea name="description" id="description" rows="3" class="form-control"><?php
                        echo htmlspecialchars($this->data['form']['description'] ?? '');
                    ?></textarea>
                </div>

                <div class="col-12 mt-3">
                    <h5>Etapas do grupo</h5>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle" id="group-stages-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 15%;">Seq.</th>
                                    <th style="width: 55%;">Etapa</th>
                                    <th style="width: 15%;">Etapa de custo?</th>
                                    <th style="width: 15%;" class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $items = $this->data['items'] ?? [];
                                $listStages = $this->data['listStages'] ?? [];
                                foreach ($items as $idx => $item):
                                    $stageId = (string)($item['stage_id'] ?? '');
                                ?>
                                    <tr>
                                        <td>
                                            <input type="number" name="group_item_sequence[<?= $idx ?>]" class="form-control form-control-sm"
                                                   min="1" step="1"
                                                   value="<?= htmlspecialchars((string)($item['sequence'] ?? ($idx + 1))) ?>">
                                        </td>
                                        <td>
                                            <select name="group_item_stage_id[<?= $idx ?>]" class="form-select form-select-sm">
                                                <option value="">Selecione a etapa</option>
                                                <?php foreach ($listStages as $opt): ?>
                                                    <?php $sel = ((string)$opt['id'] === $stageId) ? 'selected' : ''; ?>
                                                    <option value="<?= (int)$opt['id']; ?>" <?= $sel; ?>>
                                                        <?= htmlspecialchars($opt['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <?php if (!empty($item['is_cost_stage'])): ?>
                                                <span class="badge bg-info">Sim</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Não</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeGroupStageRow(this)">Remover</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addGroupStageRow()">Adicionar etapa</button>
                    </div>
                </div>

                <div class="col-12 mt-3">
                    <button type="submit" class="btn btn-warning btn-sm">Salvar</button>
                </div>

            </form>

        </div>
    </div>

</div>

<script>
function removeGroupStageRow(btn) {
    var row = btn.closest('tr');
    if (row) row.remove();
    reindexGroupStageRows();
}

function reindexGroupStageRows() {
    var tbody = document.querySelector('#group-stages-table tbody');
    if (!tbody) return;
    var rows = tbody.querySelectorAll('tr');
    rows.forEach(function (row, idx) {
        var seqInput = row.querySelector('input[name^="group_item_sequence"]');
        if (seqInput) {
            seqInput.name = 'group_item_sequence[' + idx + ']';
        }
        var stageSelect = row.querySelector('select[name^="group_item_stage_id"]');
        if (stageSelect) {
            stageSelect.name = 'group_item_stage_id[' + idx + ']';
        }
    });
}

function addGroupStageRow() {
    var tbody = document.querySelector('#group-stages-table tbody');
    if (!tbody) return;
    var idx = tbody.querySelectorAll('tr').length;
    var listStages = <?php echo json_encode($this->data['listStages'] ?? []); ?>;
    var stagesOpts = '<option value=\"\">Selecione a etapa</option>';
    (listStages || []).forEach(function (s) {
        var name = (s.name || '').replace(/\"/g, '&quot;');
        stagesOpts += '<option value=\"' + s.id + '\">' + name + '</option>';
    });
    var tr = document.createElement('tr');
    tr.innerHTML =
        '<td><input type=\"number\" name=\"group_item_sequence[' + idx + ']\" class=\"form-control form-control-sm\" min=\"1\" step=\"1\" value=\"' + (idx + 1) + '\"></td>' +
        '<td><select name=\"group_item_stage_id[' + idx + ']\" class=\"form-select form-select-sm\">' + stagesOpts + '</select></td>' +
        '<td><span class=\"badge bg-secondary\">-</span></td>' +
        '<td class=\"text-end\"><button type=\"button\" class=\"btn btn-sm btn-outline-danger\" onclick=\"removeGroupStageRow(this)\">Remover</button></td>';
    tbody.appendChild(tr);
    reindexGroupStageRows();
}
</script>

