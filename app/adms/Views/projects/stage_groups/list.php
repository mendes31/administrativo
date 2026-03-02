<?php
use App\adms\Helpers\CSRFHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Gestão de Projetos - Grupos de Etapas</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Gestão de Projetos</li>
            <li class="breadcrumb-item active">Grupos de Etapas</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2 flex-wrap">
            <span><i class="fa-solid fa-layer-group me-2"></i>Grupos de Etapas</span>
            <span class="ms-auto d-sm-flex flex-row flex-wrap gap-1">
                <?php if (in_array('CreateStageGroup', $this->data['buttonPermission'] ?? [])) : ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>create-stage-group" class="btn btn-success btn-sm mb-1 btn-min-width-90">
                        <i class="fa-solid fa-plus"></i> Cadastrar
                    </a>
                <?php endif; ?>
            </span>
        </div>

        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <form method="GET" class="row g-2 mb-3 align-items-end">
                <div class="col-md-5 mb-2">
                    <label for="name" class="form-label mb-1">Nome do grupo</label>
                    <input type="text" name="name" id="name" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($_GET['name'] ?? '') ?>" placeholder="Buscar por nome do grupo">
                </div>

                <div class="col-md-2 mb-2">
                    <label for="active" class="form-label mb-1">Ativo?</label>
                    <?php $active = $_GET['active'] ?? ''; ?>
                    <select name="active" id="active" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="1" <?= $active === '1' ? 'selected' : ''; ?>>Sim</option>
                        <option value="0" <?= $active === '0' ? 'selected' : ''; ?>>Não</option>
                    </select>
                </div>

                <div class="col-md-2 mb-2">
                    <label for="per_page" class="form-label mb-1">Mostrar</label>
                    <select name="per_page" id="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                        <?php foreach ([10, 20, 50, 100] as $opt): ?>
                            <option value="<?= $opt ?>" <?= ($_GET['per_page'] ?? ($this->data['per_page'] ?? 10)) == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 mb-2 filtros-btns-row w-100 mt-2">
                    <button type="submit" class="btn btn-primary btn-sm btn-filtros-mobile">
                        <i class="fas fa-search me-1"></i>Filtrar
                    </button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-stage-groups" class="btn btn-secondary btn-sm btn-filtros-mobile ms-1">
                        <i class="fas fa-times me-1"></i>Limpar
                    </a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 8%;">ID</th>
                            <th style="width: 35%;">Nome do grupo</th>
                            <th style="width: 12%;">Qtde de etapas</th>
                            <th style="width: 10%;">Ativo</th>
                            <th style="width: 20%;">Criado em</th>
                            <th style="width: 15%;" class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($this->data['groups'])): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    Nenhum grupo de etapas cadastrado.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($this->data['groups'] as $group): ?>
                                <tr>
                                    <td><?= (int)$group['id']; ?></td>
                                    <td><strong><?= htmlspecialchars($group['name']); ?></strong></td>
                                    <td><?= (int)($group['stages_count'] ?? 0); ?></td>
                                    <td>
                                        <?php if (!empty($group['active'])): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($group['created_at'] ?? ''); ?></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <?php if (in_array('UpdateStageGroup', $this->data['buttonPermission'] ?? [])) : ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>update-stage-group/<?= (int)$group['id']; ?>"
                                                   class="btn btn-warning btn-sm" title="Editar">
                                                    <i class="fas fa-pen-to-square"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (in_array('DeleteStageGroup', $this->data['buttonPermission'] ?? [])) : 
                                                $csrf = CSRFHelper::generateCSRFToken('form_delete_stage_group'); ?>
                                                <form action="<?php echo $_ENV['URL_ADM']; ?>delete-stage-group" method="POST"
                                                      onsubmit="return confirm('Tem certeza que deseja apagar este grupo de etapas?');">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrf; ?>">
                                                    <input type="hidden" name="id" value="<?= (int)$group['id']; ?>">
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

