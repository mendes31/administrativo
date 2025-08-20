<?php

use App\adms\Helpers\CSRFHelper;

// Gera o token CSRF para proteger o formulário de deleção
$csrf_token = CSRFHelper::generateCSRFToken('form_delete_ropa');

?>

<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">ROPA</h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-ropa" class="text-decoration-none">LGPD</a>
            </li>
            <li class="breadcrumb-item">ROPA</li>
        </ol>

    </div>

    <div class="card mb-4 border-light shadow">

        <div class="card-header hstack gap-2">
            <span>Listar</span>

            <span class="ms-auto">
                <?php
                if (in_array('CreateLgpdRopa', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}lgpd-ropa-create' class='btn btn-success btn-sm'><i class='fa-regular fa-square-plus'></i> Cadastrar</a> ";
                }
                ?>
            </span>
        </div>

        <div class="card-body">

            <?php // Inclui o arquivo que exibe mensagens de sucesso e erro
            include './app/adms/Views/partials/alerts.php';

            // Verifica se há registros no array
            if ($this->data['registros'] ?? false) {
            ?>

                <form method="get" class="row g-2 mb-3 align-items-end" onsubmit="this.page.value=1;">
                    <div class="col-md-3">
                        <label for="atividade" class="form-label mb-1">Atividade</label>
                        <input type="text" name="atividade" id="atividade" class="form-control" value="<?= htmlspecialchars($_GET['atividade'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="departamento_id" class="form-label mb-1">Departamento</label>
                        <select name="departamento_id" id="departamento_id" class="form-select">
                            <option value="">Todos</option>
                            <?php foreach (($this->data['departamentos'] ?? []) as $dep): ?>
                                <option value="<?= $dep['id'] ?>" <?= (($_GET['departamento_id'] ?? '') == $dep['id']) ? 'selected' : '' ?>><?= htmlspecialchars($dep['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="status" class="form-label mb-1">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">Todos</option>
                            <option value="Ativo" <?= (($_GET['status'] ?? '') === 'Ativo') ? 'selected' : '' ?>>Ativo</option>
                            <option value="Inativo" <?= (($_GET['status'] ?? '') === 'Inativo') ? 'selected' : '' ?>>Inativo</option>
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

                <div class="table-responsive d-none d-md-block">
                    <table class="table table-striped table-hover" id="tabela">
                        <thead>
                            <tr>
                                <th scope="col">Código</th>
                                <th scope="col">Atividade</th>
                                <th scope="col">Departamento</th>
                                <th scope="col" class="d-none d-lg-table-cell">Base Legal</th>
                                <th scope="col" class="d-none d-lg-table-cell">Retenção</th>
                                <th scope="col" class="d-none d-lg-table-cell">Risco</th>
                                <th scope="col" class="d-none d-md-table-cell">Status</th>
                                <th scope="col" class="d-none d-lg-table-cell">Última Atualização</th>
                                <th scope="col" class="text-center">Ações</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php
                            // Percorre o array de registros
                            foreach ($this->data['registros'] as $registro) {

                                // Extrai variáveis do array de registro
                                extract($registro); ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($codigo); ?></td>
                                    <td><?php echo htmlspecialchars($atividade); ?></td>
                                    <td><?php echo htmlspecialchars($departamento_nome); ?></td>
                                    <td class="d-none d-lg-table-cell"><?php echo htmlspecialchars($base_legal); ?></td>
                                    <td class="d-none d-lg-table-cell"><?php echo htmlspecialchars($retencao); ?></td>
                                    <td class="d-none d-lg-table-cell"><?php echo htmlspecialchars($riscos); ?></td>
                                    <td class="d-none d-md-table-cell">
                                        <?php echo $status === 'Ativo' ? "<span class='badge text-bg-success'>Ativo</span>" : "<span class='badge text-bg-danger'>Inativo</span>"; ?>
                                    </td>
                                    <td class="d-none d-lg-table-cell">
                                        <?php echo $ultima_atualizacao ? (new DateTime($ultima_atualizacao))->format('d/m/Y') : '-'; ?>
                                    </td>

                                    <td class="text-center">
                                        <?php
                                        if (in_array('ViewLgpdRopa', $this->data['buttonPermission'])) {
                                            echo "<a href='{$_ENV['URL_ADM']}lgpd-ropa-view/$id' class='btn btn-primary btn-sm me-1 mb-1'><i class='fa-regular fa-eye'></i> Visualizar</a>";
                                        }

                                        if (in_array('EditLgpdRopa', $this->data['buttonPermission'])) {
                                            echo "<a href='{$_ENV['URL_ADM']}lgpd-ropa-edit/$id' class='btn btn-warning btn-sm me-1 mb-1'><i class='fa-solid fa-pen-to-square'></i> Editar</a>";
                                        }

                                        if (in_array('DeleteLgpdRopa', $this->data['buttonPermission'])) {
                                        ?>
                                            <form id="formDelete<?php echo $id; ?>" action="<?php echo $_ENV['URL_ADM']; ?>lgpd-ropa-delete" method="POST" class="d-inline">

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
                    <?php if (!empty($this->data['registros'])): ?>
                        <?php foreach ($this->data['registros'] as $i => $registro) { ?>
                            <div class="card mb-2 shadow-sm" style="border-radius: 10px;">
                                <div class="card-body p-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title mb-1"><b><?= htmlspecialchars($registro['atividade']) ?></b></h6>
                                            <div class="mb-1"><b>Status:</b> <?= $registro['status'] === 'Ativo' ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-danger">Inativo</span>' ?></div>
                                        </div>
                                        <button class="btn btn-outline-primary btn-sm ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#cardRopaDetails<?= $i ?>" aria-expanded="false" aria-controls="cardRopaDetails<?= $i ?>">Ver mais</button>
                                    </div>
                                    <div class="collapse mt-2" id="cardRopaDetails<?= $i ?>">
                                        <div><b>Código:</b> <?= htmlspecialchars($registro['codigo']) ?></div>
                                        <div><b>Departamento:</b> <?= htmlspecialchars($registro['departamento_nome']) ?></div>
                                        <div><b>Base Legal:</b> <?= htmlspecialchars($registro['base_legal']) ?></div>
                                        <div><b>Retenção:</b> <?= htmlspecialchars($registro['retencao']) ?></div>
                                        <div><b>Risco:</b> <?= htmlspecialchars($registro['riscos']) ?></div>
                                        <div><b>Última Atualização:</b> <?= $registro['ultima_atualizacao'] ? (new DateTime($registro['ultima_atualizacao']))->format('d/m/Y') : '-' ?></div>
                                        <div class="mt-2">
                                            <?php if (in_array('ViewLgpdRopa', $this->data['buttonPermission'])): ?>
                                                <a href="<?= $_ENV['URL_ADM'] ?>lgpd-ropa-view/<?= $registro['id'] ?>" class="btn btn-primary btn-sm me-1 mb-1"><i class="fa-regular fa-eye"></i> Visualizar</a>
                                            <?php endif; ?>
                                            <?php if (in_array('EditLgpdRopa', $this->data['buttonPermission'])): ?>
                                                <a href="<?= $_ENV['URL_ADM'] ?>lgpd-ropa-edit/<?= $registro['id'] ?>" class="btn btn-warning btn-sm me-1 mb-1"><i class="fa-solid fa-pen-to-square"></i> Editar</a>
                                            <?php endif; ?>
                                            <?php if (in_array('DeleteLgpdRopa', $this->data['buttonPermission'])): ?>
                                                <form id="formDeleteMobile<?= $registro['id'] ?>" action="<?= $_ENV['URL_ADM'] ?>lgpd-ropa-delete" method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                    <input type="hidden" name="id" id="id" value="<?= $registro['id'] ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm me-1 mb-1" onclick="confirmDeletion(event, <?= $registro['id'] ?>)"><i class="fa-regular fa-trash-can"></i> Apagar</button>
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
                        <?php if (!empty($this->data['paginator'])): ?>
                            Mostrando registros da página atual
                        <?php else: ?>
                            Exibindo <?= count($this->data['registros']); ?> registro(s) nesta página.
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if (!empty($this->data['paginator'])): ?>
                            <?= $this->data['paginator'] ?>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Paginação Mobile -->
                <div class="d-flex d-md-none flex-column align-items-center w-100 mt-2">
                    <div class="text-secondary small w-100 text-center mb-1">
                        <?php if (!empty($this->data['paginator'])): ?>
                            Mostrando registros da página atual
                        <?php else: ?>
                            Exibindo <?= count($this->data['registros']); ?> registro(s) nesta página.
                        <?php endif; ?>
                    </div>
                    <div class="w-100 d-flex justify-content-center">
                        <?php if (!empty($this->data['paginator'])): ?>
                            <?= $this->data['paginator'] ?>
                        <?php endif; ?>
                    </div>
                </div>

            <?php
            } else { // Exibe mensagem se nenhum registro for encontrado
                echo "<div class='alert alert-danger' role='alert'>Nenhum registro encontrado!</div>";
            } ?>

        </div>

    </div>
</div> 