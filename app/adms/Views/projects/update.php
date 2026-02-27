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

            <form action="" method="POST" class="row g-3">

                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_update_project'); ?>">

                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-dados-gerais" data-bs-toggle="tab" data-bs-target="#pane-dados-gerais" type="button" role="tab">
                            Dados gerais
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-etapas" data-bs-toggle="tab" data-bs-target="#pane-etapas" type="button" role="tab">
                            Etapas
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="pane-dados-gerais" role="tabpanel" aria-labelledby="tab-dados-gerais">
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

                    <div class="tab-pane fade" id="pane-etapas" role="tabpanel" aria-labelledby="tab-etapas">
                        <!-- A aba visual de etapas será preenchida na próxima etapa -->
                        <p class="text-muted">Configuração visual das etapas será adicionada aqui.</p>
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
</script>

