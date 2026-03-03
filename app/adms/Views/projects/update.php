<?php

use App\adms\Helpers\CSRFHelper;

?>
<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Gestão de Projetos - Projetos</h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Gestão de Projetos</li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-projects" class="text-decoration-none">Projetos</a>
            </li>
            <li class="breadcrumb-item">Editar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Editar Projeto</span>

            <span class="ms-auto d-sm-flex flex-row">
                <?php
                if (in_array('ListProjects', $this->data['buttonPermission'] ?? [])) {
                    echo "<a href='{$_ENV['URL_ADM']}list-projects' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-list'></i> Listar</a> ";
                }
                ?>
            </span>

        </div>

        <div class="card-body">

            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <?php $activeTab = $this->data['active_tab'] ?? ($_GET['tab'] ?? 'dados-gerais'); ?>
            <form action="" method="POST" class="row g-3">

                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_update_project'); ?>">
                <input type="hidden" name="active_tab" id="active_tab" value="<?php echo htmlspecialchars($activeTab); ?>">

                <div class="col-12">
                    <p class="fw-bold mb-2">
                        Projeto:
                        #<?php echo htmlspecialchars((string)($this->data['form']['id'] ?? '')); ?>
                        - <?php echo htmlspecialchars($this->data['form']['name'] ?? ''); ?>
                    </p>
                </div>

                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo $activeTab === 'dados-gerais' ? 'active' : ''; ?>" id="tab-dados-gerais" data-bs-toggle="tab" data-bs-target="#pane-dados-gerais" type="button" role="tab">
                            Dados gerais
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo $activeTab === 'etapas' ? 'active' : ''; ?>" id="tab-etapas" data-bs-toggle="tab" data-bs-target="#pane-etapas" type="button" role="tab">
                            Etapas
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade <?php echo $activeTab === 'dados-gerais' ? 'show active' : ''; ?>" id="pane-dados-gerais" role="tabpanel" aria-labelledby="tab-dados-gerais">
                        <div class="row g-3">
                            <div class="col-12 col-md-3">
                                <label for="type" class="form-label">Tipo</label>
                                <?php $type = $this->data['form']['type'] ?? 'INTERNAL'; ?>
                                <select name="type" id="type" class="form-select">
                                    <option value="INTERNAL" <?php echo $type === 'INTERNAL' ? 'selected' : ''; ?>>Interno</option>
                                    <option value="EXTERNAL" <?php echo $type === 'EXTERNAL' ? 'selected' : ''; ?>>Externo</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-5">
                                <label for="name" class="form-label">Nome do Projeto</label>
                                <input type="text" name="name" id="name" class="form-control"
                                       value="<?php echo htmlspecialchars($this->data['form']['name'] ?? ''); ?>">
                            </div>

                            <div class="col-12 col-md-4">
                                <label for="status" class="form-label">Status do Projeto</label>
                                <?php $status = $this->data['form']['status'] ?? 'INICIADO'; ?>
                                <select name="status" id="status" class="form-select">
                                    <option value="INICIADO" <?php echo $status === 'INICIADO' ? 'selected' : ''; ?>>Iniciado</option>
                                    <option value="ATRASADO" <?php echo $status === 'ATRASADO' ? 'selected' : ''; ?>>Atrasado</option>
                                    <option value="SUSPENSO" <?php echo $status === 'SUSPENSO' ? 'selected' : ''; ?>>Suspenso / Pausado</option>
                                    <option value="CANCELADO" <?php echo $status === 'CANCELADO' ? 'selected' : ''; ?>>Cancelado</option>
                                    <option value="CONCLUIDO" <?php echo $status === 'CONCLUIDO' ? 'selected' : ''; ?>>Concluído</option>
                                    <option value="ENCERRADO" <?php echo $status === 'ENCERRADO' ? 'selected' : ''; ?>>Encerrado</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-3">
                                <label for="start_date" class="form-label">Data Início</label>
                                <input type="date" name="start_date" id="start_date" class="form-control"
                                       value="<?php echo htmlspecialchars($this->data['form']['start_date'] ?? ''); ?>">
                            </div>

                            <div class="col-12 col-md-3">
                                <label for="expected_end_date" class="form-label">Data Prevista Término</label>
                                <input type="date" name="expected_end_date" id="expected_end_date" class="form-control"
                                       value="<?php echo htmlspecialchars($this->data['form']['expected_end_date'] ?? ''); ?>">
                            </div>

                            <div class="col-12 col-md-3">
                                <label for="end_date" class="form-label">Data Término</label>
                                <input type="date" name="end_date" id="end_date" class="form-control"
                                       value="<?php echo htmlspecialchars($this->data['form']['end_date'] ?? ''); ?>">
                            </div>

                            <div class="col-12 col-md-3">
                                <label class="form-label">Ativo</label>
                                <div class="form-check form-switch mt-2">
                                    <?php $active = !empty($this->data['form']['active']); ?>
                                    <input class="form-check-input" type="checkbox" id="active" name="active" <?php echo $active ? 'checked' : ''; ?> />
                                    <label class="form-check-label" for="active">Sim</label>
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <label for="pn_code" class="form-label">Código PN</label>
                                <div class="input-group">
                                    <input type="text" name="pn_code" id="pn_code" class="form-control"
                                           value="<?php echo htmlspecialchars($this->data['form']['pn_code'] ?? ''); ?>">
                                    <button class="btn btn-outline-secondary" type="button"
                                            title="Pesquisar Parceiro por código"
                                            onclick="openPartnerLookupFrom('code')">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <label for="pn_name" class="form-label">Nome PN</label>
                                <div class="input-group">
                                    <input type="text" name="pn_name" id="pn_name" class="form-control"
                                           value="<?php echo htmlspecialchars($this->data['form']['pn_name'] ?? ''); ?>">
                                    <button class="btn btn-outline-secondary" type="button"
                                            title="Pesquisar Parceiro por nome"
                                            onclick="openPartnerLookupFrom('name')">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <label for="contact_user_id" class="form-label">Pessoa de contato</label>
                                <select name="contact_user_id" id="contact_user_id" class="form-select">
                                    <option value="">Selecione</option>
                                    <?php foreach (($this->data['listUsers'] ?? []) as $user): ?>
                                        <?php
                                        $sel = ((string)($this->data['form']['contact_user_id'] ?? '') === (string)$user['id']) ? 'selected' : '';
                                        $label = $user['name'] . ' - ' . $user['email'];
                                        ?>
                                        <option value="<?= $user['id']; ?>" <?= $sel; ?>><?= htmlspecialchars($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-md-4">
                                <label for="owner_user_id" class="form-label">Responsável</label>
                                <select name="owner_user_id" id="owner_user_id" class="form-select">
                                    <option value="">Selecione</option>
                                    <?php foreach (($this->data['listUsers'] ?? []) as $user): ?>
                                        <?php
                                        $sel = ((string)($this->data['form']['owner_user_id'] ?? '') === (string)$user['id']) ? 'selected' : '';
                                        $label = $user['name'] . ' - ' . $user['email'];
                                        ?>
                                        <option value="<?= $user['id']; ?>" <?= $sel; ?>><?= htmlspecialchars($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12">
                                <label for="description" class="form-label">Descrição / Escopo</label>
                                <textarea name="description" id="description" rows="3" class="form-control"><?php
                                    echo htmlspecialchars($this->data['form']['description'] ?? '');
                                ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade <?php echo $activeTab === 'etapas' ? 'show active' : ''; ?>" id="pane-etapas" role="tabpanel" aria-labelledby="tab-etapas">

                        <div class="row mb-3">
                            <div class="col-12 col-md-6">
                                <label for="stage_group_id" class="form-label">Carregar etapas a partir do grupo</label>
                                <div class="input-group input-group-sm">
                                    <select name="stage_group_id" id="stage_group_id" class="form-select">
                                        <option value="">Selecione um grupo</option>
                                        <?php
                                        $listStageGroups = $this->data['listStageGroups'] ?? [];
                                        $selectedGroup = $this->data['form']['stage_group_id'] ?? '';
                                        foreach ($listStageGroups as $g):
                                            $sel = ((string)$selectedGroup === (string)$g['id']) ? 'selected' : '';
                                            ?>
                                            <option value="<?= (int)$g['id']; ?>" <?= $sel; ?>>
                                                <?= htmlspecialchars($g['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" name="apply_stage_group" value="1" class="btn btn-outline-primary">
                                        Aplicar grupo
                                    </button>
                                </div>
                                <small class="text-muted">
                                    As etapas serão carregadas na tabela abaixo. Revise e clique em Salvar para aplicar ao projeto.
                                </small>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle table-bordered" id="stages-table">
                                <thead class="table-light">
                                    <tr>
                                        <th style="min-width: 120px; white-space: nowrap;">Data Início</th>
                                        <th style="min-width: 140px; white-space: nowrap;">Previsão Término</th>
                                        <th style="min-width: 120px; white-space: nowrap;">Data Término</th>
                                        <th style="min-width: 220px;">Etapa</th>
                                        <th style="min-width: 220px;">Nome</th>
                                        <th style="min-width: 220px;">Atividade</th>
                                        <th style="min-width: 260px;">Descrição</th>
                                        <th style="min-width: 220px;">Titular</th>
                                        <th style="min-width: 150px;">Dependência</th>
                                        <th style="min-width: 170px;">Status</th>
                                        <th style="width: 90px;">Concluído</th>
                                        <th style="width: 110px;" class="text-end">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $listStages = $this->data['listStages'] ?? [];
                                    $listUsers = $this->data['listUsers'] ?? [];
                                    $stages = $this->data['stages'] ?? [];
                                    $statusOptions = [
                                        'NAO_INICIADO'         => 'Não Iniciado',
                                        'EM_ANDAMENTO'         => 'Em Andamento',
                                        'EM_VALIDACAO'         => 'Em Validação',
                                        'AGUARDANDO_APROVACAO' => 'Aguardando Aprovação',
                                        'BLOQUEADO'            => 'Bloqueado',
                                        'CONCLUIDO'            => 'Concluído',
                                        'CANCELADO'            => 'Cancelado',
                                    ];
                                    $stageCount = count($stages);
                                    foreach ($stages as $idx => $s):
                                        $depIdx = $s['depends_on_index'] ?? '';
                                        $currentStatus = strtoupper((string)($s['status'] ?? 'NAO_INICIADO'));
                                    ?>
                                        <tr>
                                            <td>
                                                <input type="date" name="stage_start_date[<?= $idx ?>]" class="form-control form-control-sm"
                                                       value="<?= htmlspecialchars((string)($s['start_date'] ?? '')) ?>">
                                            </td>
                                            <td>
                                                <input type="date" name="stage_expected_end_date[<?= $idx ?>]" class="form-control form-control-sm"
                                                       value="<?= htmlspecialchars((string)($s['expected_end_date'] ?? '')) ?>">
                                            </td>
                                            <td>
                                                <input type="date" name="stage_end_date[<?= $idx ?>]" class="form-control form-control-sm"
                                                       value="<?= htmlspecialchars((string)($s['end_date'] ?? '')) ?>">
                                            </td>
                                            <td>
                                                <select name="stage_id[<?= $idx ?>]" class="form-select form-select-sm stage-catalog-select">
                                                    <option value="">Selecione</option>
                                                    <?php foreach ($listStages as $opt): ?>
                                                        <?php $sel = ((string)($s['stage_id'] ?? '') === (string)$opt['id']) ? 'selected' : ''; ?>
                                                        <option value="<?= (int)$opt['id'] ?>" data-name="<?= htmlspecialchars($opt['name']) ?>" <?= $sel ?>><?= htmlspecialchars($opt['name']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="stage_name[<?= $idx ?>]" class="form-control form-control-sm stage-name-input"
                                                       value="<?= htmlspecialchars((string)($s['name'] ?? '')) ?>" placeholder="Nome da etapa">
                                            </td>
                                            <td>
                                                <input type="text" name="stage_activity[<?= $idx ?>]" class="form-control form-control-sm"
                                                       value="<?= htmlspecialchars((string)($s['activity'] ?? '')) ?>">
                                            </td>
                                            <td>
                                                <input type="text" name="stage_description[<?= $idx ?>]" class="form-control form-control-sm"
                                                       value="<?= htmlspecialchars((string)($s['description'] ?? '')) ?>">
                                            </td>
                                            <td>
                                                <select name="stage_responsible_user_id[<?= $idx ?>]" class="form-select form-select-sm">
                                                    <option value="">Selecione</option>
                                                    <?php foreach ($listUsers as $u): ?>
                                                        <?php $sel = ((string)($s['responsible_user_id'] ?? '') === (string)$u['id']) ? 'selected' : ''; ?>
                                                        <option value="<?= (int)$u['id'] ?>" <?= $sel ?>><?= htmlspecialchars($u['name'] . ' - ' . $u['email']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td>
                                                <select name="stage_depends_on_index[<?= $idx ?>]" class="form-select form-select-sm stage-depends-select">
                                                    <option value="">Nenhuma</option>
                                                    <?php for ($k = 0; $k < $stageCount; $k++): ?>
                                                        <?php if ($k === $idx) continue; ?>
                                                        <?php $sel = ($depIdx === (string)$k) ? 'selected' : ''; ?>
                                                        <option value="<?= $k ?>" <?= $sel ?>>Etapa <?= $k + 1 ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </td>
                                            <td>
                                                <select name="stage_status[<?= $idx ?>]" class="form-select form-select-sm">
                                                    <?php foreach ($statusOptions as $code => $label): ?>
                                                        <option value="<?= $code; ?>" <?= $currentStatus === $code ? 'selected' : ''; ?>>
                                                            <?= htmlspecialchars($label); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check form-check-sm d-flex justify-content-center">
                                                    <?php $checked = !empty($s['completed']); ?>
                                                    <input type="checkbox" name="stage_completed[<?= $idx ?>]" value="1" class="form-check-input stage-completed-cb" <?= $checked ? 'checked' : '' ?>>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeStageRow(this)">Remover</button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addStageRow()">Adicionar etapa</button>
                        </div>
                    </div>
                </div>

                <div class="col-12 mt-3">
                    <button type="submit" class="btn btn-primary btn-sm">Salvar</button>
                </div>

            </form>

        </div>
    </div>

</div>

<script>
function openPartnerLookupFrom(field) {
    var q = '';
    if (field === 'code') {
        q = document.getElementById('pn_code')?.value || '';
    } else if (field === 'name') {
        q = document.getElementById('pn_name')?.value || '';
    }
    var url = '<?php echo $_ENV['URL_ADM']; ?>project-partner-lookup';
    if (q) {
        url += '?q=' + encodeURIComponent(q);
    }
    window.open(url, 'projectPartnerLookup', 'width=1100,height=650,scrollbars=yes');
}

function setProjectPartner(code, name) {
    if (document.getElementById('pn_code')) {
        document.getElementById('pn_code').value = code;
    }
    if (document.getElementById('pn_name')) {
        document.getElementById('pn_name').value = name;
    }
}

// Etapas: preencher nome quando selecionar etapa do catálogo e validar dependências no front-end
document.addEventListener('DOMContentLoaded', function() {
    // Preencher nome quando selecionar etapa do catálogo
    document.getElementById('stages-table')?.addEventListener('change', function(e) {
        if (e.target.classList.contains('stage-catalog-select')) {
            var opt = e.target.options[e.target.selectedIndex];
            var nameInput = e.target.closest('tr').querySelector('.stage-name-input');
            if (nameInput && opt && opt.value) {
                var name = opt.getAttribute('data-name') || opt.textContent || '';
                if (name) nameInput.value = name;
            }
        }
    });

    // Controlar a aba ativa no hidden active_tab quando o usuário clica nas abas
    var tabInput = document.getElementById('active_tab');
    var tabDados = document.getElementById('tab-dados-gerais');
    var tabEtapas = document.getElementById('tab-etapas');

    if (tabDados && tabInput) {
        tabDados.addEventListener('click', function () {
            tabInput.value = 'dados-gerais';
        });
    }
    if (tabEtapas && tabInput) {
        tabEtapas.addEventListener('click', function () {
            tabInput.value = 'etapas';
        });
    }

    // Antes de enviar o formulário, validar dependências de etapas no front-end
    var form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function (e) {
            // Mantém active_tab coerente com a aba selecionada
            if (tabInput) {
                var activeLink = document.querySelector('.nav-tabs .nav-link.active');
                if (activeLink && activeLink.id === 'tab-etapas') {
                    tabInput.value = 'etapas';
                } else {
                    tabInput.value = 'dados-gerais';
                }
            }

            if (!validateStageDependenciesClient()) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    }
});

function validateStageDependenciesClient() {
    var table = document.getElementById('stages-table');
    if (!table) return true;

    var rows = table.querySelectorAll('tbody tr');
    if (!rows.length) return true;

    var statusByIndex = [];
    var completedByIndex = [];

    var allowedStatuses = [
        'NAO_INICIADO',
        'EM_ANDAMENTO',
        'EM_VALIDACAO',
        'AGUARDANDO_APROVACAO',
        'BLOQUEADO',
        'CONCLUIDO',
        'CANCELADO'
    ];
    var statusAtivoOuFinal = [
        'EM_ANDAMENTO',
        'EM_VALIDACAO',
        'AGUARDANDO_APROVACAO',
        'BLOQUEADO',
        'CONCLUIDO'
    ];

    // 1ª passada: coletar status e concluído por índice de linha
    rows.forEach(function(row, idx) {
        var nameInput = row.querySelector('input[name^="stage_name"]');
        var stageSelect = row.querySelector('select[name^="stage_id"]');
        var nameVal = nameInput ? nameInput.value.trim() : '';
        var stageVal = stageSelect ? (stageSelect.value || '').trim() : '';

        // linha vazia (sem nome e sem etapa) é ignorada
        if (!nameVal && !stageVal) {
            return;
        }

        var statusSelect = row.querySelector('select[name^="stage_status"]');
        var status = statusSelect ? (statusSelect.value || 'NAO_INICIADO').toUpperCase() : 'NAO_INICIADO';
        if (allowedStatuses.indexOf(status) === -1) {
            status = 'NAO_INICIADO';
        }
        statusByIndex[idx] = status;

        var cb = row.querySelector('.stage-completed-cb');
        completedByIndex[idx] = cb && cb.checked ? 1 : 0;
    });

    if (!Object.keys(statusByIndex).length) {
        return true;
    }

    // 2ª passada: validar dependências
    var hasError = false;
    rows.forEach(function(row, idx) {
        if (typeof statusByIndex[idx] === 'undefined') {
            return;
        }

        var depSelect = row.querySelector('select[name^="stage_depends_on_index"]');
        if (!depSelect) return;
        var depVal = depSelect.value;
        if (depVal === '' || depVal === null) {
            return; // sem dependência, etapa livre
        }
        var depIdx = parseInt(depVal, 10);
        if (isNaN(depIdx) || typeof statusByIndex[depIdx] === 'undefined') {
            return; // dependência inválida/fora da lista, não bloqueia
        }

        var depStatus = statusByIndex[depIdx] || 'NAO_INICIADO';
        var myStatus  = statusByIndex[idx] || 'NAO_INICIADO';
        var estaConcluida = !!completedByIndex[idx] || myStatus === 'CONCLUIDO';

        if ((estaConcluida || statusAtivoOuFinal.indexOf(myStatus) !== -1) && depStatus !== 'CONCLUIDO') {
            hasError = true;
        }
    });

    if (hasError) {
        alert('Existem etapas marcadas como concluídas ou em andamento com dependências não concluídas. Ajuste os status, o campo Concluído ou as dependências antes de salvar.');
        return false;
    }

    return true;
}

function removeStageRow(btn) {
    var row = btn.closest('tr');
    if (row) row.remove();
    reindexStageRows();
}

function reindexStageRows() {
    var tbody = document.querySelector('#stages-table tbody');
    if (!tbody) return;
    var rows = tbody.querySelectorAll('tr');
    var n = rows.length;
    rows.forEach(function(row, idx) {
        row.querySelectorAll('input[name="stage_start_date[]"]').forEach(function(el) { el.name = 'stage_start_date[' + idx + ']'; });
        row.querySelectorAll('input[name="stage_expected_end_date[]"]').forEach(function(el) { el.name = 'stage_expected_end_date[' + idx + ']'; });
        row.querySelectorAll('input[name="stage_end_date[]"]').forEach(function(el) { el.name = 'stage_end_date[' + idx + ']'; });
        row.querySelectorAll('select[name="stage_id[]"]').forEach(function(el) { el.name = 'stage_id[' + idx + ']'; });
        row.querySelectorAll('input[name="stage_name[]"]').forEach(function(el) { el.name = 'stage_name[' + idx + ']'; });
        row.querySelectorAll('input[name="stage_activity[]"]').forEach(function(el) { el.name = 'stage_activity[' + idx + ']'; });
        row.querySelectorAll('input[name="stage_description[]"]').forEach(function(el) { el.name = 'stage_description[' + idx + ']'; });
        row.querySelectorAll('select[name="stage_responsible_user_id[]"]').forEach(function(el) { el.name = 'stage_responsible_user_id[' + idx + ']'; });
        row.querySelectorAll('select[name="stage_depends_on_index[]"]').forEach(function(el) { el.name = 'stage_depends_on_index[' + idx + ']'; });
        row.querySelectorAll('select[name="stage_status[]"]').forEach(function(el) { el.name = 'stage_status[' + idx + ']'; });
        var cb = row.querySelector('.stage-completed-cb');
        if (cb) { cb.name = 'stage_completed[' + idx + ']'; }
        // Atualizar options do select Dependência
        var depSelect = row.querySelector('.stage-depends-select');
        if (depSelect) {
            var currentVal = depSelect.value;
            depSelect.innerHTML = '<option value="">Nenhuma</option>';
            for (var k = 0; k < n; k++) {
                if (k === idx) continue;
                var opt = document.createElement('option');
                opt.value = k;
                opt.textContent = 'Etapa ' + (k + 1);
                if (String(k) === currentVal) opt.selected = true;
                depSelect.appendChild(opt);
            }
        }
    });
}

function addStageRow() {
    var tbody = document.querySelector('#stages-table tbody');
    if (!tbody) return;
    var idx = tbody.querySelectorAll('tr').length;
    var listStages = <?php echo json_encode($this->data['listStages'] ?? []); ?>;
    var listUsers = <?php echo json_encode($this->data['listUsers'] ?? []); ?>;
    var stagesOpts = '<option value="">Selecione</option>';
    (listStages || []).forEach(function(s) {
        var name = (s.name || '').replace(/"/g, '&quot;');
        stagesOpts += '<option value="' + s.id + '" data-name="' + name + '">' + (s.name || '') + '</option>';
    });
    var usersOpts = '<option value="">Selecione</option>';
    (listUsers || []).forEach(function(u) {
        var label = (u.name + ' - ' + u.email).replace(/"/g, '&quot;');
        usersOpts += '<option value="' + u.id + '">' + label + '</option>';
    });
    var depOpts = '<option value="">Nenhuma</option>';
    for (var k = 0; k <= idx; k++) {
        if (k === idx) continue;
        depOpts += '<option value="' + k + '">Etapa ' + (k + 1) + '</option>';
    }
    var statusOptions = <?php echo json_encode($statusOptions ?? [
        'NAO_INICIADO'         => 'Não Iniciado',
        'EM_ANDAMENTO'         => 'Em Andamento',
        'EM_VALIDACAO'         => 'Em Validação',
        'AGUARDANDO_APROVACAO' => 'Aguardando Aprovação',
        'BLOQUEADO'            => 'Bloqueado',
        'CONCLUIDO'            => 'Concluído',
        'CANCELADO'            => 'Cancelado',
    ]); ?>;
    var statusOpts = '';
    Object.keys(statusOptions || {}).forEach(function (code) {
        var label = statusOptions[code];
        statusOpts += '<option value=\"' + code + '\">' + label + '</option>';
    });

    var tr = document.createElement('tr');
    tr.innerHTML =
        '<td><input type="date" name="stage_start_date[' + idx + ']" class="form-control form-control-sm"></td>' +
        '<td><input type="date" name="stage_expected_end_date[' + idx + ']" class="form-control form-control-sm"></td>' +
        '<td><input type="date" name="stage_end_date[' + idx + ']" class="form-control form-control-sm"></td>' +
        '<td><select name="stage_id[' + idx + ']" class="form-select form-select-sm stage-catalog-select">' + stagesOpts + '</select></td>' +
        '<td><input type="text" name="stage_name[' + idx + ']" class="form-control form-control-sm stage-name-input" placeholder="Nome da etapa"></td>' +
        '<td><input type="text" name="stage_activity[' + idx + ']" class="form-control form-control-sm"></td>' +
        '<td><input type="text" name="stage_description[' + idx + ']" class="form-control form-control-sm"></td>' +
        '<td><select name="stage_responsible_user_id[' + idx + ']" class="form-select form-select-sm">' + usersOpts + '</select></td>' +
        '<td><select name="stage_depends_on_index[' + idx + ']" class="form-select form-select-sm stage-depends-select">' + depOpts + '</select></td>' +
        '<td><select name="stage_status[' + idx + ']" class="form-select form-select-sm">' + statusOpts + '</select></td>' +
        '<td class="text-center"><div class="form-check form-check-sm d-flex justify-content-center"><input type="checkbox" name="stage_completed[' + idx + ']" value="1" class="form-check-input stage-completed-cb"></div></td>' +
        '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeStageRow(this)">Remover</button></td>';
    tbody.appendChild(tr);
    reindexStageRows();
}
</script>

