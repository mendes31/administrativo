<?php

use App\adms\Helpers\CSRFHelper;

// Token CSRF para exclusão futura (caso adicione botão de deletar)
$csrf_token = CSRFHelper::generateCSRFToken('form_delete_mov_between_accounts');

?>

<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Movimentações Entre Contas</h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Movimentações</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">

        <div class="card-header hstack gap-2">
            <span>Listar</span>

            <span class="ms-auto">
                <?php
                if (in_array('MovBetweenAccounts', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}mov-between-accounts' class='btn btn-success btn-sm'><i class='fa-regular fa-square-plus'></i> Nova Movimentação</a> ";
                }
                ?>
            </span>
        </div>

        <div class="card-body">

            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <!-- Filtros nativos -->
            <form method="GET" class="row g-2 mb-3 align-items-end">
                <div class="col-md-2 col-6">
                    <label for="from_bank_name" class="form-label">Conta Origem</label>
                    <input type="text" class="form-control" id="from_bank_name" name="from_bank_name" value="<?= htmlspecialchars($this->data['filters']['from_bank_name'] ?? '') ?>">
                </div>
                <div class="col-md-2 col-6">
                    <label for="to_bank_name" class="form-label">Conta Destino</label>
                    <input type="text" class="form-control" id="to_bank_name" name="to_bank_name" value="<?= htmlspecialchars($this->data['filters']['to_bank_name'] ?? '') ?>">
                </div>
                <div class="col-md-2 col-6">
                    <label for="description" class="form-label">Descrição</label>
                    <input type="text" class="form-control" id="description" name="description" value="<?= htmlspecialchars($this->data['filters']['description'] ?? '') ?>">
                </div>
                <div class="col-md-2 col-6">
                    <label for="user_name" class="form-label">Usuário</label>
                    <input type="text" class="form-control" id="user_name" name="user_name" value="<?= htmlspecialchars($this->data['filters']['user_name'] ?? '') ?>">
                </div>
                <div class="col-md-2 col-6">
                    <label for="created_at" class="form-label">Data</label>
                    <input type="text" class="form-control" id="created_at" name="created_at" placeholder="dd/mm/aaaa" value="<?= htmlspecialchars($this->data['filters']['created_at'] ?? '') ?>">
                </div>
                <div class="col-md-1 col-6">
                    <label for="per_page" class="form-label">Registros</label>
                    <select class="form-select" id="per_page" name="per_page">
                        <?php foreach ([10, 20, 50, 100] as $opt) { ?>
                            <option value="<?= $opt ?>" <?= ($this->data['filters']['per_page'] ?? 10) == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-1 col-12 d-grid">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                </div>
                <div class="col-md-1 col-12 d-grid">
                    <a href="<?= $_ENV['URL_ADM']; ?>list-mov-between-accounts" class="btn btn-outline-secondary">Limpar Filtros</a>
                </div>
            </form>

            <?php if (!empty($this->data['movBetweenAccounts'])) { ?>

                <!-- Tabela desktop -->
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Conta Origem</th>
                                <th>Conta Destino</th>
                                <th>Valor</th>
                                <th>Descrição</th>
                                <th>Usuário</th>
                                <th>Data</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->data['movBetweenAccounts'] as $mov) { extract($mov); ?>
                            <tr>
                                <td><?= $id ?></td>
                                <td><?= $from_bank_name ?></td>
                                <td><?= $to_bank_name ?></td>
                                <td>R$ <?= number_format($amount, 2, ',', '.') ?></td>
                                <td><?= $description ?></td>
                                <td><?= $user_name ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($created_at)) ?></td>
                                <td class="text-center">
                                    <?php if (in_array('ViewMovBetweenAccounts', $this->data['buttonPermission'])) {
                                        echo "<a href='{$_ENV['URL_ADM']}view-transfer/$id' class='btn btn-primary btn-sm me-1 mb-1'><i class='fa-regular fa-eye'></i> Visualizar</a>";
                                    }
                                    if (in_array('UpdateMovBetweenAccounts', $this->data['buttonPermission'])) {
                                        echo "<a href='{$_ENV['URL_ADM']}update-mov-between-accounts/$id' class='btn btn-warning btn-sm me-1 mb-1'><i class='fa-solid fa-pen-to-square'></i> Editar</a>";
                                    }
                                    if (in_array('DeleteMovBetweenAccounts', $this->data['buttonPermission'])) { ?>
                                        <form id="formDelete<?= $id ?>" action="<?= $_ENV['URL_ADM']; ?>delete-mov-between-accounts" method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf_token; ?>">
                                            <input type="hidden" name="id" value="<?= $id ?? '' ?>">
                                            <input type="hidden" name="description" value="<?= $description ?? '' ?>">
                                            <button type="submit" class="btn btn-danger btn-sm me-1 mb-1" onclick="confirmDeletion(event, <?= $id ?>)"><i class="fa-regular fa-trash-can"></i> Apagar</button>
                                        </form>
                                    <?php } ?>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

                <!-- Cards mobile -->
                <div class="d-md-none">
                    <?php foreach ($this->data['movBetweenAccounts'] as $mov) { extract($mov); ?>
                    <div class="card mb-2 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong>ID #<?= $id ?></strong>
                                <span class="text-muted small"><?= date('d/m/Y H:i', strtotime($created_at)) ?></span>
                            </div>
                            <div><b>Origem:</b> <?= $from_bank_name ?></div>
                            <div><b>Destino:</b> <?= $to_bank_name ?></div>
                            <div><b>Valor:</b> R$ <?= number_format($amount, 2, ',', '.') ?></div>
                            <div><b>Descrição:</b> <?= $description ?></div>
                            <div><b>Usuário:</b> <?= $user_name ?></div>
                            <div class="mt-2">
                                <?php if (in_array('ViewMovBetweenAccounts', $this->data['buttonPermission'])) {
                                    echo "<a href='{$_ENV['URL_ADM']}view-transfer/$id' class='btn btn-primary btn-sm me-1 mb-1'><i class='fa-regular fa-eye'></i> Visualizar</a>";
                                }
                                if (in_array('UpdateMovBetweenAccounts', $this->data['buttonPermission'])) {
                                    echo "<a href='{$_ENV['URL_ADM']}update-mov-between-accounts/$id' class='btn btn-warning btn-sm me-1 mb-1'><i class='fa-solid fa-pen-to-square'></i> Editar</a>";
                                }
                                if (in_array('DeleteMovBetweenAccounts', $this->data['buttonPermission'])) { ?>
                                    <form id="formDelete<?= $id ?>" action="<?= $_ENV['URL_ADM']; ?>delete-mov-between-accounts" method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf_token; ?>">
                                        <input type="hidden" name="id" value="<?= $id ?? '' ?>">
                                        <input type="hidden" name="description" value="<?= $description ?? '' ?>">
                                        <button type="submit" class="btn btn-danger btn-sm me-1 mb-1" onclick="confirmDeletion(event, <?= $id ?>)"><i class="fa-regular fa-trash-can"></i> Apagar</button>
                                    </form>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>

                <?php include_once './app/adms/Views/partials/pagination.php'; ?>

            <?php } else {
                echo "<div class='alert alert-danger' role='alert'>Nenhuma movimentação encontrada!</div>";
            } ?>

        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        // The original DataTables script is removed as per the edit hint.
        // The form submission and pagination handling are now handled natively.
    });
</script>
