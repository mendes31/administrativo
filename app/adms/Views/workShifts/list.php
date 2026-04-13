<?php

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\WorkShiftsRepository;

$csrf_token = CSRFHelper::generateCSRFToken('form_delete_work_shift');
?>
<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Turnos de trabalho</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item">Turnos</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Listar</span>
            <span class="ms-auto">
                <?php if (in_array('CreateWorkShift', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?= $_ENV['URL_ADM']; ?>create-work-shift" class="btn btn-success btn-sm"><i class="fa-regular fa-square-plus"></i> Cadastrar</a>
                <?php } ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <?php if (!empty($this->data['work_shifts'])) { ?>
                <form method="get" class="row g-2 mb-3 align-items-end">
                    <div class="col-md-4">
                        <label for="description" class="form-label mb-1">Descrição</label>
                        <input type="text" name="description" id="description" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($_GET['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Buscar...">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <label for="per_page" class="form-label mb-1 me-2">Mostrar</label>
                        <select name="per_page" id="per_page" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                            <?php foreach ([10, 20, 50, 100] as $opt): ?>
                                <option value="<?= $opt ?>" <?= ($this->data['per_page'] ?? 10) == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2 align-items-end">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Filtrar</button>
                        <a href="list-work-shifts" class="btn btn-secondary btn-sm"><i class="fa fa-times"></i> Limpar</a>
                    </div>
                </form>

                <div class="table-responsive d-none d-md-block list-desktop">
                    <table class="table table-striped table-hover">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Descrição</th>
                            <th>Intervalos</th>
                            <th>Carga líquida</th>
                            <th class="text-center">Ações</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($this->data['work_shifts'] as $row) {
                            $id = (int) $row['id'];
                            $iv = [];
                            foreach ([1, 2, 3] as $n) {
                                $a = WorkShiftsRepository::timeInputValue($row['entry_' . $n] ?? null);
                                $b = WorkShiftsRepository::timeInputValue($row['exit_' . $n] ?? null);
                                if ($a !== '' && $b !== '') {
                                    $iv[] = $a . '–' . $b;
                                }
                            }
                            $ivs = $iv ? implode(' · ', $iv) : '—';
                            $tot = WorkShiftsRepository::formatMinutesLabel((int) ($row['total_minutes'] ?? 0));
                            ?>
                            <tr>
                                <td><?= $id; ?></td>
                                <td><?= htmlspecialchars((string) $row['description'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="small"><?= htmlspecialchars($ivs, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($tot, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-center">
                                    <?php if (in_array('ViewWorkShift', $this->data['buttonPermission'] ?? [])) { ?>
                                        <a href="<?= $_ENV['URL_ADM']; ?>view-work-shift/<?= $id ?>" class="btn btn-primary btn-sm me-1 mb-1"><i class="fa-regular fa-eye"></i></a>
                                    <?php }
                                    if (in_array('UpdateWorkShift', $this->data['buttonPermission'] ?? [])) { ?>
                                        <a href="<?= $_ENV['URL_ADM']; ?>update-work-shift/<?= $id ?>" class="btn btn-warning btn-sm me-1 mb-1"><i class="fa-solid fa-pen-to-square"></i></a>
                                    <?php }
                                    if (in_array('DeleteWorkShift', $this->data['buttonPermission'] ?? [])) { ?>
                                        <form id="formDelete<?= $id; ?>" action="<?= $_ENV['URL_ADM']; ?>delete-work-shift" method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf_token; ?>">
                                            <input type="hidden" name="id" value="<?= $id; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm me-1 mb-1" onclick="confirmDeletion(event, <?= $id; ?>)"><i class="fa-regular fa-trash-can"></i></button>
                                        </form>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-block d-md-none list-mobile">
                    <?php foreach ($this->data['work_shifts'] as $row) {
                        $id = (int) $row['id']; ?>
                        <div class="card mb-2 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <strong><?= htmlspecialchars((string) $row['description'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <span class="text-muted small">#<?= $id; ?></span>
                                </div>
                                <div class="small text-muted mb-2">Carga: <?= htmlspecialchars(WorkShiftsRepository::formatMinutesLabel((int) ($row['total_minutes'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?></div>
                                <div>
                                    <?php if (in_array('ViewWorkShift', $this->data['buttonPermission'] ?? [])) { ?>
                                        <a href="<?= $_ENV['URL_ADM']; ?>view-work-shift/<?= $id ?>" class="btn btn-primary btn-sm">Ver</a>
                                    <?php }
                                    if (in_array('UpdateWorkShift', $this->data['buttonPermission'] ?? [])) { ?>
                                        <a href="<?= $_ENV['URL_ADM']; ?>update-work-shift/<?= $id ?>" class="btn btn-warning btn-sm">Editar</a>
                                    <?php }
                                    if (in_array('DeleteWorkShift', $this->data['buttonPermission'] ?? [])) { ?>
                                        <form id="formDeleteMob<?= $id; ?>" action="<?= $_ENV['URL_ADM']; ?>delete-work-shift" method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf_token; ?>">
                                            <input type="hidden" name="id" value="<?= $id; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="confirmDeletion(event, <?= $id; ?>)">Apagar</button>
                                        </form>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                    <div class="d-flex flex-column align-items-center w-100 mt-2">
                        <div class="text-secondary small w-100 text-center mb-1">
                            <?php if (!empty($this->data['pagination']['total'])): ?>
                                Mostrando <?= $this->data['pagination']['first_item'] ?> até <?= $this->data['pagination']['last_item'] ?> de <?= $this->data['pagination']['total'] ?> registro(s)
                            <?php endif; ?>
                        </div>
                        <div class="w-100 d-flex justify-content-center">
                            <?php
                            $paginationHtml = $this->data['pagination']['html'] ?? '';
                            if ($paginationHtml) {
                                $paginationHtml = str_replace(
                                    ['>Primeiro<', '>Anterior<', '>Próximo<', '>Último<'],
                                    ['>&laquo;<', '>&lsaquo;<', '>&rsaquo;<', '>&raquo;<'],
                                    $paginationHtml
                                );
                                $paginationHtml = preg_replace('/class=\"pagination(.*?)\"/', 'class="pagination pagination-sm$1"', $paginationHtml, 1);
                                echo $paginationHtml;
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <div class="d-none d-md-flex justify-content-between align-items-center mt-2">
                    <div class="text-secondary small">
                        <?php if (!empty($this->data['pagination']['total'])): ?>
                            Mostrando <?= $this->data['pagination']['first_item'] ?> até <?= $this->data['pagination']['last_item'] ?> de <?= $this->data['pagination']['total'] ?> registro(s)
                        <?php endif; ?>
                    </div>
                    <div><?= $this->data['pagination']['html'] ?? ''; ?></div>
                </div>
            <?php } else { ?>
                <div class="alert alert-info" role="alert">Nenhum turno cadastrado.</div>
            <?php } ?>
        </div>
    </div>
</div>
