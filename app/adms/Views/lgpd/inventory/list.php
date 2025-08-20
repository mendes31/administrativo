<?php

use App\adms\Helpers\CSRFHelper;

// Gera o token CSRF para proteger o formulário de deleção
$csrf_token = CSRFHelper::generateCSRFToken('form_delete_inventory');

?>

<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Inventário LGPD</h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">LGPD</li>
            <li class="breadcrumb-item">Inventário</li>
        </ol>

    </div>

    <div class="card mb-4 border-light shadow">

        <div class="card-header hstack gap-2">
            <span>LISTA DE INVENTÁRIOS:</span>

            <span class="ms-auto">
                <?php
                if (in_array('LgpdInventoryCreate', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}lgpd-inventory-create' class='btn btn-success btn-sm'><i class='fa-regular fa-square-plus'></i> Cadastrar</a> ";
                }
                ?>
            </span>
        </div>

        <div class="card-body">

            <?php // Inclui o arquivo que exibe mensagens de sucesso e erro
            include './app/adms/Views/partials/alerts.php';

            // Verifica se há inventários no array
            if ($this->data['inventories'] ?? false) {
            ?>

                <form method="get" class="row g-2 mb-3 align-items-end" onsubmit="this.page.value=1;">
                    <div class="col-md-3">
                        <label for="area" class="form-label mb-1">Área</label>
                        <input type="text" name="area" id="area" class="form-control" value="<?= htmlspecialchars($_GET['area'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="data_type" class="form-label mb-1">Tipo de Dado</label>
                        <input type="text" name="data_type" id="data_type" class="form-control" value="<?= htmlspecialchars($_GET['data_type'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="data_category" class="form-label mb-1">Categoria</label>
                        <select name="data_category" id="data_category" class="form-select">
                            <option value="">Todas</option>
                            <option value="Pessoal" <?= (($_GET['data_category'] ?? '') === 'Pessoal') ? 'selected' : '' ?>>Pessoal</option>
                            <option value="Sensível" <?= (($_GET['data_category'] ?? '') === 'Sensível') ? 'selected' : '' ?>>Sensível</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="risk_level" class="form-label mb-1">Nível de Risco</label>
                        <select name="risk_level" id="risk_level" class="form-select">
                            <option value="">Todos</option>
                            <option value="Alto" <?= (($_GET['risk_level'] ?? '') === 'Alto') ? 'selected' : '' ?>>Alto</option>
                            <option value="Médio" <?= (($_GET['risk_level'] ?? '') === 'Médio') ? 'selected' : '' ?>>Médio</option>
                            <option value="Baixo" <?= (($_GET['risk_level'] ?? '') === 'Baixo') ? 'selected' : '' ?>>Baixo</option>
                        </select>
                    </div>
                    <div class="col-auto mb-2">
                        <label for="per_page" class="form-label mb-1">Mostrar</label>
                        <div class="d-flex align-items-center">
                            <select name="per_page" id="per_page" class="form-select form-select-sm" style="min-width: 80px;" onchange="this.form.submit()">
                                <?php foreach ([10, 20, 50, 100] as $opt): ?>
                                    <option value="<?= $opt ?>" <?= ($_GET['per_page'] ?? 10) == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="form-label mb-1 ms-1">registros</span>
                        </div>
                    </div>
                    <div class="col-md-2 filtros-btns-row w-100 mt-2">
                        <button type="submit" class="btn btn-primary btn-sm btn-filtros-mobile"><i class="fa fa-search"></i> Filtrar</button>
                        <a href="?limpar_filtros=1" class="btn btn-secondary btn-sm btn-filtros-mobile"><i class="fa fa-times"></i> Limpar Filtros</a>
                    </div>
                    <input type="hidden" name="page" value="1">
                </form>

                <!-- TABELA PRINCIPAL - INVENTÁRIO LGPD -->
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-striped table-hover" id="tabela">
                        <thead>
                            <tr>
                                <th scope="col">Departamento</th>
                                <th scope="col">Titular</th>
                                <th scope="col">Grupos</th>
                                <th scope="col" class="text-center">Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php
                            // Percorre o array de inventários
                            foreach ($this->data['inventories'] as $inventory) {
                                // Extrai variáveis do array de inventory
                                extract($inventory); ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($area); ?></td>
                                    <td><?php echo htmlspecialchars($data_subject); ?></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $total_groups ?? 0; ?></span>
                                        <small class="text-muted ms-1">grupos</small>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        if (in_array('LgpdInventoryView', $this->data['buttonPermission'])) {
                                            echo "<a href='{$_ENV['URL_ADM']}lgpd-inventory-view/$id' class='btn btn-primary btn-sm me-1 mb-1'><i class='fa-regular fa-eye'></i> Ver</a>";
                                        }

                                        if (in_array('LgpdInventoryEdit', $this->data['buttonPermission'])) {
                                            echo "<a href='{$_ENV['URL_ADM']}lgpd-inventory-edit/$id' class='btn btn-warning btn-sm me-1 mb-1'><i class='fa-solid fa-pen-to-square'></i> Editar</a>";
                                        }

                                        if (in_array('LgpdInventoryDelete', $this->data['buttonPermission'])) {
                                        ?>
                                            <form id="formDelete<?php echo $id; ?>" action="<?php echo $_ENV['URL_ADM']; ?>lgpd-inventory-delete" method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                <input type="hidden" name="id" id="id" value="<?php echo $id ?? ''; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm me-1 mb-1" onclick="confirmDeletion(event, <?php echo $id; ?>)"><i class="fa-regular fa-trash-can"></i> Del</button>
                                            </form>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

                <!-- CARDS MOBILE -->
                <div class="d-block d-md-none">
                    <?php if (!empty($this->data['inventories'])): ?>
                        <?php foreach ($this->data['inventories'] as $i => $inventory) { ?>
                            <div class="card mb-2 shadow-sm" style="border-radius: 10px;">
                                <div class="card-body p-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title mb-1"><b><?= htmlspecialchars($inventory['area']) ?> | <?= htmlspecialchars($inventory['data_subject']) ?></b></h6>
                                            <div class="mb-1">
                                                <span class="badge bg-info"><?= $inventory['total_groups'] ?? 0 ?> grupos</span>
                                            </div>
                                        </div>
                                        <div class="btn-group btn-group-sm">
                                            <?php if (in_array('LgpdInventoryView', $this->data['buttonPermission'])): ?>
                                                <a href="<?= $_ENV['URL_ADM'] ?>lgpd-inventory-view/<?= $inventory['id'] ?>" class="btn btn-primary btn-sm"><i class="fa-regular fa-eye"></i> Ver</a>
                                            <?php endif; ?>
                                            <?php if (in_array('LgpdInventoryEdit', $this->data['buttonPermission'])): ?>
                                                <a href="<?= $_ENV['URL_ADM'] ?>lgpd-inventory-edit/<?= $inventory['id'] ?>" class="btn btn-warning btn-sm"><i class="fa-solid fa-pen-to-square"></i> Editar</a>
                                            <?php endif; ?>
                                            <?php if (in_array('LgpdInventoryDelete', $this->data['buttonPermission'])): ?>
                                                <form id="formDeleteMobile<?= $inventory['id'] ?>" action="<?= $_ENV['URL_ADM'] ?>lgpd-inventory-delete" method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                    <input type="hidden" name="id" id="id" value="<?= $inventory['id'] ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="confirmDeletionMobile(event, <?= $inventory['id'] ?>)"><i class="fa-regular fa-trash-can"></i> Del</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    <?php else: ?>
                        <div class='alert alert-danger' role='alert'>Nenhum registro encontrado.</div>
                    <?php endif; ?>
                </div>

                <!-- Paginação Desktop -->
                <div class="w-100 mt-2 d-none d-md-flex justify-content-between align-items-center">
                    <div class="text-secondary small">
                        <?php if (!empty($this->data['pagination']['total'])): ?>
                            Mostrando <?= $this->data['pagination']['first_item'] ?> até <?= $this->data['pagination']['last_item'] ?> de <?= $this->data['pagination']['total'] ?> registro(s)
                        <?php else: ?>
                            Exibindo <?= count($this->data['inventories']); ?> registro(s) nesta página.
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php include './app/adms/Views/partials/pagination.php'; ?>
                    </div>
                </div>

                <!-- Paginação Mobile -->
                <div class="d-flex d-md-none flex-column align-items-center w-100 mt-2">
                    <div class="text-secondary small w-100 text-center mb-1">
                        <?php if (!empty($this->data['pagination']['total'])): ?>
                            Mostrando <?= $this->data['pagination']['first_item'] ?> até <?= $this->data['pagination']['last_item'] ?> de <?= $this->data['pagination']['total'] ?> registro(s)
                        <?php else: ?>
                            Exibindo <?= count($this->data['inventories']); ?> registro(s) nesta página.
                        <?php endif; ?>
                    </div>
                    <div class="w-100 d-flex justify-content-center">
                        <?php include './app/adms/Views/partials/pagination.php'; ?>
                    </div>
                </div>

            <?php
            } else { // Exibe mensagem se nenhum inventário for encontrado
                echo "<div class='alert alert-danger' role='alert'>Nenhum inventário encontrado!</div>";
            } ?>

        </div>

    </div>
</div>

<script>
function confirmDeletion(event, id) {
    event.preventDefault();
    
    if (confirm('Tem certeza que deseja excluir este inventário? Esta ação não pode ser desfeita.')) {
        document.getElementById('formDelete' + id).submit();
    }
}

// Para formulários mobile
function confirmDeletionMobile(event, id) {
    event.preventDefault();
    
    if (confirm('Tem certeza que deseja excluir este inventário? Esta ação não pode ser desfeita.')) {
        document.getElementById('formDeleteMobile' + id).submit();
    }
}
</script>
