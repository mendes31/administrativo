<?php

use App\adms\Helpers\CSRFHelper;

$csrf_token = CSRFHelper::generateCSRFToken('form_delete_sac_client');

?>

<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3 mobile-hide-page-title"><i class="fas fa-user-tie me-2"></i>Clientes SAC</h2>

        <ol class="breadcrumb mb-3 ms-auto mobile-hide-breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sac-dashboard" class="text-decoration-none">SAC</a></li>
            <li class="breadcrumb-item">Clientes</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Listar</span>

            <span class="ms-auto d-flex flex-wrap gap-1">
                <?php
                if (in_array('SacCreateClient', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}sac-create-client' class='btn btn-success btn-sm'><i class='fa-regular fa-square-plus'></i> Cadastrar</a> ";
                }
                ?>
            </span>
        </div>

        <div class="card-body">

            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <!-- Filtros -->
            <div class="d-md-none mb-2">
                <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#sacClientsFilters" aria-expanded="false">
                    <i class="fa fa-filter me-1"></i> Abrir filtros
                </button>
            </div>
            <div class="collapse d-md-block" id="sacClientsFilters">
                <form method="get" class="row g-2 mb-3 align-items-end">
                    <div class="col-6 col-sm-4 col-md-3">
                        <label for="search" class="form-label" style="font-size:.7rem;">Pesquisar</label>
                        <input type="text" name="search" id="search" class="form-control form-control-sm" placeholder="Nome, documento, e-mail..." value="<?= htmlspecialchars($this->data['filtros']['search'] ?? '') ?>">
                    </div>
                    <div class="col-6 col-sm-4 col-md-2">
                        <label for="status" class="form-label" style="font-size:.7rem;">Status</label>
                        <select name="status" id="status" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option value="Ativo" <?= ($this->data['filtros']['status'] ?? '') === 'Ativo' ? 'selected' : '' ?>>Ativo</option>
                            <option value="Inativo" <?= ($this->data['filtros']['status'] ?? '') === 'Inativo' ? 'selected' : '' ?>>Inativo</option>
                            <option value="Bloqueado" <?= ($this->data['filtros']['status'] ?? '') === 'Bloqueado' ? 'selected' : '' ?>>Bloqueado</option>
                        </select>
                    </div>
                    <div class="col-6 col-sm-4 col-md-2">
                        <label for="type_person" class="form-label" style="font-size:.7rem;">Tipo</label>
                        <select name="type_person" id="type_person" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option value="PF" <?= ($this->data['filtros']['type_person'] ?? '') === 'PF' ? 'selected' : '' ?>>PF</option>
                            <option value="PJ" <?= ($this->data['filtros']['type_person'] ?? '') === 'PJ' ? 'selected' : '' ?>>PJ</option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-auto d-flex gap-2 align-items-end">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Filtrar</button>
                        <a href="<?= $_ENV['URL_ADM']; ?>sac-list-clients" class="btn btn-secondary btn-sm"><i class="fa fa-times"></i> Limpar</a>
                    </div>
                </form>
            </div>

            <?php if ($this->data['clients'] ?? false) { ?>

                <!-- Desktop: tabela -->
                <div class="d-none d-md-block">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Razão Social</th>
                                    <th>Nome Fantasia</th>
                                    <th>CPF/CNPJ</th>
                                    <th>Telefone</th>
                                    <th>E-mail</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($this->data['clients'] as $client) {
                                    extract($client);
                                    $statusClass = match ($status ?? '') {
                                        'Ativo' => 'bg-success',
                                        'Inativo' => 'bg-secondary',
                                        'Bloqueado' => 'bg-danger',
                                        default => 'bg-secondary',
                                    };
                                ?>
                                    <tr>
                                        <td class="text-center"><?= $id ?></td>
                                        <td><?= htmlspecialchars($razao_social ?? '') ?></td>
                                        <td><?= htmlspecialchars($nome_fantasia ?? '') ?></td>
                                        <td><?= htmlspecialchars($document ?? '') ?></td>
                                        <td><?= htmlspecialchars($phone ?? $mobile ?? '') ?></td>
                                        <td><?= htmlspecialchars($email ?? '') ?></td>
                                        <td class="text-center">
                                            <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($status ?? '') ?></span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <?php
                                                if (in_array('SacViewClient', $this->data['buttonPermission'])) {
                                                    echo "<a href='{$_ENV['URL_ADM']}sac-view-client/{$id}' class='btn btn-info btn-sm' title='Visualizar'><i class='fa-regular fa-eye'></i></a>";
                                                }
                                                if (in_array('SacUpdateClient', $this->data['buttonPermission'])) {
                                                    echo "<a href='{$_ENV['URL_ADM']}sac-update-client/{$id}' class='btn btn-warning btn-sm' title='Editar'><i class='fa-regular fa-pen-to-square'></i></a>";
                                                }
                                                if (in_array('SacDeleteClient', $this->data['buttonPermission'])) {
                                                ?>
                                                    <form id="formDelete<?= $id ?>" action="<?= $_ENV['URL_ADM']; ?>sac-delete-client" method="POST" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                        <input type="hidden" name="id" value="<?= $id ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm" title="Apagar" onclick="confirmDeletion(event, <?= $id ?>)"><i class="fa-regular fa-trash-can"></i></button>
                                                    </form>
                                                <?php } ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Mobile: cards -->
                <div class="d-block d-md-none">
                    <?php foreach ($this->data['clients'] as $client) {
                        extract($client);
                        $statusClass = match ($status ?? '') {
                            'Ativo' => 'bg-success',
                            'Inativo' => 'bg-secondary',
                            'Bloqueado' => 'bg-danger',
                            default => 'bg-secondary',
                        };
                        $canView = in_array('SacViewClient', $this->data['buttonPermission']);
                        $canUpdate = in_array('SacUpdateClient', $this->data['buttonPermission']);
                        $canDelete = in_array('SacDeleteClient', $this->data['buttonPermission']);
                        $hasActions = $canUpdate || $canDelete;
                        $viewUrl = $_ENV['URL_ADM'] . 'sac-view-client/' . $id;
                    ?>
                        <div class="card mb-2 shadow-sm"<?php if ($canView): ?> onclick="window.location.href='<?= $viewUrl ?>';" style="cursor:pointer;"<?php endif; ?>>
                            <div class="card-body py-2 px-3">
                                <div class="fw-bold small"><?= htmlspecialchars($razao_social ?? '') ?></div>
                                <?php if (!empty($nome_fantasia)): ?>
                                    <div class="small text-muted"><?= htmlspecialchars($nome_fantasia) ?></div>
                                <?php endif; ?>
                                <div class="d-flex flex-wrap gap-1 mt-1 mb-1">
                                    <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($status ?? '') ?></span>
                                    <span class="badge bg-light text-dark border"><?= ($type_person ?? 'PJ') === 'PF' ? 'Pessoa Física' : 'Pessoa Jurídica' ?></span>
                                </div>
                                <?php if (!empty($document)): ?>
                                    <div class="small text-muted"><i class="fas fa-id-card me-1"></i><?= htmlspecialchars($document) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($email)): ?>
                                    <div class="small text-muted"><i class="fas fa-envelope me-1"></i><?= htmlspecialchars($email) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($phone) || !empty($mobile)): ?>
                                    <div class="small text-muted"><i class="fas fa-phone me-1"></i><?= htmlspecialchars($phone ?? $mobile ?? '') ?></div>
                                <?php endif; ?>
                                <?php if ($hasActions): ?>
                                    <div class="d-flex gap-1 mt-2 pt-2 border-top" onclick="event.stopPropagation();">
                                        <?php if ($canView): ?>
                                            <a href="<?= $viewUrl ?>" class="btn btn-outline-info btn-sm flex-fill"><i class="fas fa-eye me-1"></i>Ver</a>
                                        <?php endif; ?>
                                        <?php if ($canUpdate): ?>
                                            <a href="<?= $_ENV['URL_ADM'] ?>sac-update-client/<?= $id ?>" class="btn btn-outline-warning btn-sm flex-fill"><i class="fas fa-edit me-1"></i>Editar</a>
                                        <?php endif; ?>
                                        <?php if ($canDelete): ?>
                                            <form action="<?= $_ENV['URL_ADM'] ?>sac-delete-client" method="POST" class="flex-fill" onclick="event.stopPropagation();">
                                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                <input type="hidden" name="id" value="<?= $id ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm w-100" onclick="return confirm('Excluir cliente?');"><i class="fas fa-trash me-1"></i>Excluir</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php } ?>
                </div>

                <!-- Paginação -->
                <div class="d-flex justify-content-end mt-2 d-none d-md-flex">
                    <?= $this->data['pagination']['html'] ?? '' ?>
                </div>
                <div class="d-flex justify-content-center mt-2 d-md-none">
                    <?php
                    $paginationHtml = $this->data['pagination']['html'] ?? '';
                    if ($paginationHtml) {
                        $paginationHtml = preg_replace('/class="pagination(.*?)"/', 'class="pagination pagination-sm$1"', $paginationHtml, 1);
                        echo $paginationHtml;
                    }
                    ?>
                </div>

            <?php } else { ?>
                <div class="alert alert-warning" role="alert">Nenhum cliente encontrado.</div>
            <?php } ?>

        </div>
    </div>
</div>
