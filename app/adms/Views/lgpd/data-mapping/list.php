<?php

use App\adms\Helpers\CSRFHelper;

// Gera o token CSRF para proteger o formulário de deleção
$csrf_token = CSRFHelper::generateCSRFToken('form_delete_data_mapping');

?>

<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Data Mapping LGPD</h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">LGPD</li>
            <li class="breadcrumb-item">Data Mapping</li>
        </ol>

    </div>

    <div class="card mb-4 border-light shadow">

        <div class="card-header hstack gap-2">
            <span>Listar</span>

            <span class="ms-auto">
                <?php
                if (in_array('LgpdDataMappingCreate', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}lgpd-data-mapping-create' class='btn btn-success btn-sm'><i class='fa-regular fa-square-plus'></i> Cadastrar</a> ";
                }
                ?>
            </span>
        </div>

        <div class="card-body">

            <?php // Inclui o arquivo que exibe mensagens de sucesso e erro
            include './app/adms/Views/partials/alerts.php';

            // Verifica se há data mappings no array
            if ($this->data['dataMappings'] ?? false) {
            ?>

                <form method="get" class="row g-2 mb-3 align-items-end" onsubmit="this.page.value=1;">
                    <div class="col-md-3">
                        <label for="source_system" class="form-label mb-1">Sistema Origem</label>
                        <input type="text" name="source_system" id="source_system" class="form-control" value="<?= htmlspecialchars($_GET['source_system'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="destination_system" class="form-label mb-1">Sistema Destino</label>
                        <input type="text" name="destination_system" id="destination_system" class="form-control" value="<?= htmlspecialchars($_GET['destination_system'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="ropa_id" class="form-label mb-1">ROPA</label>
                        <select name="ropa_id" id="ropa_id" class="form-select">
                            <option value="">Todos</option>
                            <?php if (isset($this->data['ropas'])): ?>
                                <?php foreach ($this->data['ropas'] as $ropa): ?>
                                    <option value="<?= $ropa['id'] ?>" <?= (isset($_GET['ropa_id']) && $_GET['ropa_id'] == $ropa['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($ropa['atividade']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
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
                    <div class="col-md-3 filtros-btns-row w-100 mt-2">
                        <button type="submit" class="btn btn-primary btn-sm btn-filtros-mobile"><i class="fa fa-search"></i> Filtrar</button>
                        <a href="?limpar_filtros=1" class="btn btn-secondary btn-sm btn-filtros-mobile"><i class="fa fa-times"></i> Limpar Filtros</a>
                    </div>
                    <input type="hidden" name="page" value="1">
                </form>

                <div class="table-responsive d-none d-md-block">
                    <table class="table table-striped table-hover" id="tabela">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Sistema Origem</th>
                                <th scope="col">Campo Origem</th>
                                <th scope="col">Sistema Destino</th>
                                <th scope="col">Campo Destino</th>
                                <th scope="col" class="d-none d-md-table-cell">ROPA</th>
                                <th scope="col" class="d-none d-md-table-cell">Inventário</th>
                                <th scope="col" class="text-center">Ações</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php
                            // Percorre o array de data mappings
                            foreach ($this->data['dataMappings'] as $mapping) {

                                // Extrai variáveis do array de mapping
                                extract($mapping); ?>
                                <tr>
                                    <td><?php echo $id; ?></td>
                                    <td><?php echo htmlspecialchars($source_system); ?></td>
                                    <td><?php echo htmlspecialchars($source_field); ?></td>
                                    <td><?php echo htmlspecialchars($destination_system); ?></td>
                                    <td><?php echo htmlspecialchars($destination_field); ?></td>
                                    <td class="d-none d-md-table-cell">
                                        <?php echo $ropa_atividade ? htmlspecialchars($ropa_atividade) : '<span class="text-muted">N/A</span>'; ?>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <?php echo $inventory_area ? htmlspecialchars($inventory_area) : '<span class="text-muted">N/A</span>'; ?>
                                    </td>

                                    <td class="text-center">
                                        <?php
                                        if (in_array('LgpdDataMappingView', $this->data['buttonPermission'])) {
                                            echo "<a href='{$_ENV['URL_ADM']}lgpd-data-mapping-view/$id' class='btn btn-primary btn-sm me-1 mb-1'><i class='fa-regular fa-eye'></i> Visualizar</a>";
                                        }

                                        if (in_array('LgpdDataMappingEdit', $this->data['buttonPermission'])) {
                                            echo "<a href='{$_ENV['URL_ADM']}lgpd-data-mapping-edit/$id' class='btn btn-warning btn-sm me-1 mb-1'><i class='fa-solid fa-pen-to-square'></i> Editar</a>";
                                        }

                                        if (in_array('LgpdDataMappingDelete', $this->data['buttonPermission'])) {
                                        ?>
                                            <form id="formDelete<?php echo $id; ?>" action="<?php echo $_ENV['URL_ADM']; ?>lgpd-data-mapping-delete" method="POST" class="d-inline">

                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                                                <input type="hidden" name="id" id="id" value="<?php echo $id ?? ''; ?>">

                                                <button type="submit" class="btn btn-danger btn-sm me-1 mb-1" onclick="confirmDeletion(event, <?php echo $id; ?>)"><i class="fa-regular fa-trash-can"></i> Apagar</button>

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
                    <?php if (!empty($this->data['dataMappings'])): ?>
                        <?php foreach ($this->data['dataMappings'] as $i => $mapping) { ?>
                            <div class="card mb-2 shadow-sm" style="border-radius: 10px;">
                                <div class="card-body p-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title mb-1"><b><?= htmlspecialchars($mapping['source_system']) ?> → <?= htmlspecialchars($mapping['destination_system']) ?></b></h6>
                                            <div class="mb-1"><b>Campo:</b> <?= htmlspecialchars($mapping['source_field']) ?> → <?= htmlspecialchars($mapping['destination_field']) ?></div>
                                        </div>
                                        <button class="btn btn-outline-primary btn-sm ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#cardMappingDetails<?= $i ?>" aria-expanded="false" aria-controls="cardMappingDetails<?= $i ?>">Ver mais</button>
                                    </div>
                                    <div class="collapse mt-2" id="cardMappingDetails<?= $i ?>">
                                        <div><b>ID:</b> <?= $mapping['id'] ?></div>
                                        <div><b>ROPA:</b> <?= htmlspecialchars($mapping['ropa_atividade'] ?? 'N/A') ?></div>
                                        <div><b>Inventário:</b> <?= htmlspecialchars($mapping['inventory_area'] ?? 'N/A') ?></div>
                                        <div class="mt-2">
                                            <?php if (in_array('LgpdDataMappingView', $this->data['buttonPermission'])): ?>
                                                <a href="<?= $_ENV['URL_ADM'] ?>lgpd-data-mapping-view/<?= $mapping['id'] ?>" class="btn btn-primary btn-sm me-1 mb-1"><i class="fa-regular fa-eye"></i> Visualizar</a>
                                            <?php endif; ?>
                                            <?php if (in_array('LgpdDataMappingEdit', $this->data['buttonPermission'])): ?>
                                                <a href="<?= $_ENV['URL_ADM'] ?>lgpd-data-mapping-edit/<?= $mapping['id'] ?>" class="btn btn-warning btn-sm me-1 mb-1"><i class="fa-solid fa-pen-to-square"></i> Editar</a>
                                            <?php endif; ?>
                                            <?php if (in_array('LgpdDataMappingDelete', $this->data['buttonPermission'])): ?>
                                                <form id="formDeleteMobile<?= $mapping['id'] ?>" action="<?= $_ENV['URL_ADM'] ?>lgpd-data-mapping-delete" method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                    <input type="hidden" name="id" id="id" value="<?= $mapping['id'] ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm me-1 mb-1" onclick="confirmDeletionMobile(event, <?= $mapping['id'] ?>)"><i class="fa-regular fa-trash-can"></i> Apagar</button>
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
                            Exibindo <?= count($this->data['dataMappings']); ?> registro(s) nesta página.
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
                            Exibindo <?= count($this->data['dataMappings']); ?> registro(s) nesta página.
                        <?php endif; ?>
                    </div>
                    <div class="w-100 d-flex justify-content-center">
                        <?php include './app/adms/Views/partials/pagination.php'; ?>
                    </div>
                </div>

            <?php
            } else { // Exibe mensagem se nenhum data mapping for encontrado
                echo "<div class='alert alert-danger' role='alert'>Nenhum mapeamento encontrado!</div>";
            } ?>

        </div>

    </div>
</div>

<script>
function confirmDeletion(event, id) {
    event.preventDefault();
    
    if (confirm('Tem certeza que deseja excluir este mapeamento de dados? Esta ação não pode ser desfeita.')) {
        document.getElementById('formDelete' + id).submit();
    }
}

// Para formulários mobile
function confirmDeletionMobile(event, id) {
    event.preventDefault();
    
    if (confirm('Tem certeza que deseja excluir este mapeamento de dados? Esta ação não pode ser desfeita.')) {
        document.getElementById('formDeleteMobile' + id).submit();
    }
}
</script>