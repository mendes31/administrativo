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
            <li class="breadcrumb-item active">Projetos</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2 flex-wrap">
            <span><i class="fa-solid fa-diagram-project me-2"></i>Projetos</span>
            <span class="ms-auto d-sm-flex flex-row flex-wrap gap-1">
                <?php if (in_array('CreateProject', $this->data['buttonPermission'] ?? [])) : ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>create-project" class="btn btn-success btn-sm mb-1 btn-min-width-90">
                        <i class="fa-solid fa-plus"></i> Cadastrar
                    </a>
                <?php endif; ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <?php
            $filters = $this->data['filters'] ?? [];
            $currentType = $filters['type'] ?? '';
            $currentStatus = $filters['status'] ?? '';
            $currentActive = $filters['active'] ?? '';
            ?>

            <form method="GET" class="row g-2 mb-3 align-items-end">
                <div class="col-md-4 mb-2">
                    <label for="name" class="form-label mb-1">Nome do projeto</label>
                    <input type="text" name="name" id="name" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($filters['name'] ?? '') ?>" placeholder="Buscar por nome">
                </div>
                <div class="col-md-2 mb-2">
                    <label for="type" class="form-label mb-1">Tipo</label>
                    <select name="type" id="type" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="INTERNAL" <?= $currentType === 'INTERNAL' ? 'selected' : ''; ?>>Interno</option>
                        <option value="EXTERNAL" <?= $currentType === 'EXTERNAL' ? 'selected' : ''; ?>>Externo</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label for="status" class="form-label mb-1">Status</label>
                    <select name="status" id="status" class="form-select form-select-sm">
                        <?php
                        $statusOptions = [
                            '' => 'Todos',
                            'INICIADO' => 'Iniciado',
                            'ATRASADO' => 'Atrasado',
                            'SUSPENSO' => 'Suspenso',
                            'CANCELADO' => 'Cancelado',
                            'CONCLUIDO' => 'Concluído',
                            'ENCERRADO' => 'Encerrado',
                        ];
                        foreach ($statusOptions as $value => $label) {
                            $sel = ($currentStatus === $value) ? 'selected' : '';
                            echo "<option value=\"{$value}\" {$sel}>".htmlspecialchars($label)."</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label for="active" class="form-label mb-1">Ativo?</label>
                    <select name="active" id="active" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="1" <?= $currentActive === '1' ? 'selected' : ''; ?>>Sim</option>
                        <option value="0" <?= $currentActive === '0' ? 'selected' : ''; ?>>Não</option>
                    </select>
                </div>
                <div class="col-md-1 mb-2">
                    <label for="per_page" class="form-label mb-1">Mostrar</label>
                    <select name="per_page" id="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                        <?php foreach ([10, 20, 50, 100] as $opt): ?>
                            <option value="<?= $opt ?>" <?= ($this->data['per_page'] ?? 10) == $opt ? 'selected' : ''; ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-2 filtros-btns-row w-100 mt-2">
                    <button type="submit" class="btn btn-primary btn-sm btn-filtros-mobile">
                        <i class="fas fa-search me-1"></i>Filtrar
                    </button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-projects" class="btn btn-secondary btn-sm btn-filtros-mobile ms-1">
                        <i class="fas fa-times me-1"></i>Limpar
                    </a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 5%;">ID</th>
                            <th style="width: 25%;">Projeto</th>
                            <th style="width: 10%;">Tipo</th>
                            <th style="width: 15%;">Parceiro / PN</th>
                            <th style="width: 15%;">Responsável</th>
                            <th style="width: 10%;">% Concluído</th>
                            <th style="width: 10%;">Status</th>
                            <th style="width: 10%;" class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($this->data['projects'])): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    Nenhum projeto cadastrado.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($this->data['projects'] as $project): ?>
                                <tr>
                                    <td><?= (int)$project['id']; ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($project['name']); ?></strong><br>
                                        <small class="text-muted">
                                            Início: <?= htmlspecialchars($project['start_date'] ?? '-'); ?> |
                                            Prev. fim: <?= htmlspecialchars($project['expected_end_date'] ?? '-'); ?>
                                        </small>
                                    </td>
                                    <td><?= $project['type'] === 'EXTERNAL' ? 'Externo' : 'Interno'; ?></td>
                                    <td><?= htmlspecialchars($project['pn_name'] ?: $project['pn_code'] ?: '-'); ?></td>
                                    <td><?= htmlspecialchars($project['owner_name'] ?? '-'); ?></td>
                                    <td><?= number_format((float)($project['percent_complete'] ?? 0), 2, ',', '.'); ?>%</td>
                                    <td><?= htmlspecialchars($project['status']); ?></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <?php if (in_array('UpdateProject', $this->data['buttonPermission'] ?? [])) : ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>update-project/<?= (int)$project['id']; ?>"
                                                   class="btn btn-warning btn-sm" title="Editar">
                                                    <i class="fas fa-pen-to-square"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (in_array('DeleteProject', $this->data['buttonPermission'] ?? [])) : 
                                                $csrf = CSRFHelper::generateCSRFToken('form_delete_project'); ?>
                                                <form action="<?php echo $_ENV['URL_ADM']; ?>delete-project" method="POST"
                                                      onsubmit="return confirm('Tem certeza que deseja apagar este projeto?');">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrf; ?>">
                                                    <input type="hidden" name="id" value="<?= (int)$project['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Apagar">
                                                        <i class="fa-regular fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php
            $paginationHtml = $this->data['pagination']['html'] ?? '';
            if ($paginationHtml) {
                echo $paginationHtml;
            }
            ?>
        </div>
    </div>
</div>

