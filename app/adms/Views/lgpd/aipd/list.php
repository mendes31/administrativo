<?php

use App\adms\Helpers\CSRFHelper;

// Gera o token CSRF para proteger o formulário de deleção
$csrf_token = CSRFHelper::generateCSRFToken('form_delete_aipd');

?>

<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Avaliação de Impacto à Proteção de Dados (AIPD)</h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-aipd" class="text-decoration-none">LGPD</a>
            </li>
            <li class="breadcrumb-item">AIPD</li>
        </ol>

    </div>

    <div class="card mb-4 border-light shadow">

        <div class="card-header hstack gap-2">
            <span>Listar</span>

            <span class="ms-auto">
                <?php
                if (in_array('CreateLgpdAipd', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}lgpd-aipd-create' class='btn btn-success btn-sm'><i class='fa-regular fa-square-plus'></i> Cadastrar</a> ";
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
                        <label for="titulo" class="form-label mb-1">Título</label>
                        <input type="text" name="titulo" id="titulo" class="form-control" value="<?= htmlspecialchars($_GET['titulo'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="departamento_id" class="form-label mb-1">Departamento</label>
                        <select name="departamento_id" id="departamento_id" class="form-select">
                            <option value="">Todos</option>
                            <?php foreach (($this->data['departamentos'] ?? []) as $dept): ?>
                                <option value="<?= $dept['id'] ?>" <?= (($_GET['departamento_id'] ?? '') == $dept['id']) ? 'selected' : '' ?>><?= htmlspecialchars($dept['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="status" class="form-label mb-1">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">Todos</option>
                            <option value="Em Andamento" <?= (($_GET['status'] ?? '') === 'Em Andamento') ? 'selected' : '' ?>>Em Andamento</option>
                            <option value="Concluída" <?= (($_GET['status'] ?? '') === 'Concluída') ? 'selected' : '' ?>>Concluída</option>
                            <option value="Aprovada" <?= (($_GET['status'] ?? '') === 'Aprovada') ? 'selected' : '' ?>>Aprovada</option>
                            <option value="Revisão" <?= (($_GET['status'] ?? '') === 'Revisão') ? 'selected' : '' ?>>Revisão</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="nivel_risco" class="form-label mb-1">Nível de Risco</label>
                        <select name="nivel_risco" id="nivel_risco" class="form-select">
                            <option value="">Todos</option>
                            <option value="Baixo" <?= (($_GET['nivel_risco'] ?? '') === 'Baixo') ? 'selected' : '' ?>>Baixo</option>
                            <option value="Médio" <?= (($_GET['nivel_risco'] ?? '') === 'Médio') ? 'selected' : '' ?>>Médio</option>
                            <option value="Alto" <?= (($_GET['nivel_risco'] ?? '') === 'Alto') ? 'selected' : '' ?>>Alto</option>
                            <option value="Crítico" <?= (($_GET['nivel_risco'] ?? '') === 'Crítico') ? 'selected' : '' ?>>Crítico</option>
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
                                <th scope="col">Título</th>
                                <th scope="col">Departamento</th>
                                <th scope="col" class="d-none d-lg-table-cell">ROPA</th>
                                <th scope="col" class="d-none d-md-table-cell">Status</th>
                                <th scope="col" class="d-none d-lg-table-cell">Nível de Risco</th>
                                <th scope="col" class="d-none d-xl-table-cell">Data Início</th>
                                <th scope="col" class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php foreach ($this->data['registros'] as $registro): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-primary"><?= htmlspecialchars($registro['codigo']) ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($registro['titulo']) ?></div>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($registro['responsavel_nome'] ?? 'N/A') ?>
                                        </small>
                                    </td>
                                    <td><?= htmlspecialchars($registro['departamento_nome']) ?></td>
                                    <td class="d-none d-lg-table-cell">
                                        <?php if ($registro['ropa_atividade']): ?>
                                            <span class="badge bg-info"><?= htmlspecialchars($registro['ropa_atividade']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <?php
                                        $statusClass = match($registro['status']) {
                                            'Em Andamento' => 'warning',
                                            'Concluída' => 'success',
                                            'Aprovada' => 'primary',
                                            'Revisão' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?= $statusClass ?>"><?= htmlspecialchars($registro['status']) ?></span>
                                    </td>
                                    <td class="d-none d-lg-table-cell">
                                        <?php
                                        $riscoClass = match($registro['nivel_risco']) {
                                            'Baixo' => 'success',
                                            'Médio' => 'warning',
                                            'Alto' => 'danger',
                                            'Crítico' => 'dark',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?= $riscoClass ?>"><?= htmlspecialchars($registro['nivel_risco']) ?></span>
                                    </td>
                                    <td class="d-none d-xl-table-cell">
                                        <?= date('d/m/Y', strtotime($registro['data_inicio'])) ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if (in_array('ViewLgpdAipd', $this->data['buttonPermission'])): ?>
                                            <a href="<?= $_ENV['URL_ADM'] ?>lgpd-aipd-view/<?= $registro['id'] ?>" class="btn btn-primary btn-sm me-1 mb-1"><i class="fa-regular fa-eye"></i> Visualizar</a>
                                        <?php endif; ?>
                                        <?php if (in_array('EditLgpdAipd', $this->data['buttonPermission'])): ?>
                                            <a href="<?= $_ENV['URL_ADM'] ?>lgpd-aipd-edit/<?= $registro['id'] ?>" class="btn btn-warning btn-sm me-1 mb-1"><i class="fa-solid fa-pen-to-square"></i> Editar</a>
                                        <?php endif; ?>
                                        <?php if (in_array('DeleteLgpdAipd', $this->data['buttonPermission'])): ?>
                                            <form id="formDelete<?= $registro['id'] ?>" action="<?= $_ENV['URL_ADM'] ?>lgpd-aipd-delete" method="POST" class="d-inline">

                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                                                <input type="hidden" name="id" id="id" value="<?php echo $registro['id'] ?? ''; ?>">

                                                <button type="submit" class="btn btn-danger btn-sm me-1 mb-1" onclick="confirmDeletion(event, <?php echo $registro['id']; ?>)"><i class="fa-regular fa-trash-can"></i> Apagar</button>

                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                        </tbody>
                    </table>
                </div>
                <!-- CARDS MOBILE -->
                <div class="d-block d-md-none">
                    <?php if (!empty($this->data['registros'])): ?>
                        <?php foreach ($this->data['registros'] as $i => $registro): ?>
                            <div class="card mb-2 shadow-sm" style="border-radius: 10px;">
                                <div class="card-body p-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title mb-1"><b><?= htmlspecialchars($registro['titulo']) ?></b></h6>
                                            <div class="mb-1"><b>Status:</b> 
                                                <?php
                                                $statusClass = match($registro['status']) {
                                                    'Em Andamento' => 'warning',
                                                    'Concluída' => 'success',
                                                    'Aprovada' => 'primary',
                                                    'Revisão' => 'danger',
                                                    default => 'secondary'
                                                };
                                                ?>
                                                <span class="badge bg-<?= $statusClass ?>"><?= htmlspecialchars($registro['status']) ?></span>
                                            </div>
                                        </div>
                                        <button class="btn btn-outline-primary btn-sm ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#cardAipdDetails<?= $i ?>" aria-expanded="false" aria-controls="cardAipdDetails<?= $i ?>">Ver mais</button>
                                    </div>
                                    <div class="collapse mt-2" id="cardAipdDetails<?= $i ?>">
                                        <div><b>Código:</b> <?= htmlspecialchars($registro['codigo']) ?></div>
                                        <div><b>Departamento:</b> <?= htmlspecialchars($registro['departamento_nome']) ?></div>
                                        <div><b>ROPA:</b> <?= $registro['ropa_atividade'] ? htmlspecialchars($registro['ropa_atividade']) : '-' ?></div>
                                        <div><b>Nível de Risco:</b> 
                                            <?php
                                            $riscoClass = match($registro['nivel_risco']) {
                                                'Baixo' => 'success',
                                                'Médio' => 'warning',
                                                'Alto' => 'danger',
                                                'Crítico' => 'dark',
                                                default => 'secondary'
                                            };
                                            ?>
                                            <span class="badge bg-<?= $riscoClass ?>"><?= htmlspecialchars($registro['nivel_risco']) ?></span>
                                        </div>
                                        <div><b>Data Início:</b> <?= date('d/m/Y', strtotime($registro['data_inicio'])) ?></div>
                                        <div><b>Responsável:</b> <?= htmlspecialchars($registro['responsavel_nome'] ?? 'N/A') ?></div>
                                        <div class="mt-2">
                                            <?php if (in_array('ViewLgpdAipd', $this->data['buttonPermission'])): ?>
                                                <a href="<?= $_ENV['URL_ADM'] ?>lgpd-aipd-view/<?= $registro['id'] ?>" class="btn btn-primary btn-sm me-1 mb-1"><i class="fa-regular fa-eye"></i> Visualizar</a>
                                            <?php endif; ?>
                                            <?php if (in_array('EditLgpdAipd', $this->data['buttonPermission'])): ?>
                                                <a href="<?= $_ENV['URL_ADM'] ?>lgpd-aipd-edit/<?= $registro['id'] ?>" class="btn btn-warning btn-sm me-1 mb-1"><i class="fa-solid fa-pen-to-square"></i> Editar</a>
                                            <?php endif; ?>
                                            <?php if (in_array('DeleteLgpdAipd', $this->data['buttonPermission'])): ?>
                                                <form id="formDeleteMobile<?= $registro['id'] ?>" action="<?= $_ENV['URL_ADM'] ?>lgpd-aipd-delete" method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                    <input type="hidden" name="id" id="id" value="<?= $registro['id'] ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm me-1 mb-1" onclick="confirmDeletion(event, <?= $registro['id'] ?>)"><i class="fa-regular fa-trash-can"></i> Apagar</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
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
