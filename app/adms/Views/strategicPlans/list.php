<?php
$plans = $this->data['plans'] ?? [];
// Cabeçalho já incluso pelo controller
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Planos Estratégicos</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Planos Estratégicos</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2 flex-wrap">
            <span><i class="fas fa-project-diagram me-2"></i>Listar Planos Estratégicos</span>
            <span class="ms-auto d-sm-flex flex-row flex-wrap gap-1">
                <a href="/adms/create-strategic-plan" class="btn btn-success btn-sm mb-1"><i class="fas fa-plus"></i> Cadastrar</a>
            </span>
        </div>
        <div class="card-body">
            <form method="get" class="row g-2 mb-3 align-items-end">
                <div class="col-md-3">
                    <label for="titulo" class="form-label mb-1">Título</label>
                    <input type="text" name="titulo" id="titulo" class="form-control" placeholder="Buscar por título" value="<?= htmlspecialchars($criteria['titulo'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label for="departamento" class="form-label mb-1">Departamento</label>
                    <input type="text" name="departamento" id="departamento" class="form-control" placeholder="Buscar por departamento" value="<?= htmlspecialchars($criteria['departamento'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label for="responsavel" class="form-label mb-1">Responsável</label>
                    <input type="text" name="responsavel" id="responsavel" class="form-control" placeholder="Buscar por responsável" value="<?= htmlspecialchars($criteria['responsavel'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label mb-1">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Todos</option>
                        <option value="Não iniciado" <?= (isset($criteria['status']) && $criteria['status'] == 'Não iniciado') ? 'selected' : '' ?>>Não iniciado</option>
                        <option value="Em andamento" <?= (isset($criteria['status']) && $criteria['status'] == 'Em andamento') ? 'selected' : '' ?>>Em andamento</option>
                        <option value="Concluído" <?= (isset($criteria['status']) && $criteria['status'] == 'Concluído') ? 'selected' : '' ?>>Concluído</option>
                        <option value="Atrasado" <?= (isset($criteria['status']) && $criteria['status'] == 'Atrasado') ? 'selected' : '' ?>>Atrasado</option>
                    </select>
                </div>
                <div class="col-auto mb-2">
                    <label for="per_page" class="form-label mb-1">Mostrar</label>
                    <div class="d-flex align-items-center">
                        <select name="per_page" id="per_page" class="form-select form-select-sm" style="min-width: 80px;" onchange="this.form.submit()">
                            <?php $pp = $per_page ?? 20; ?>
                            <option value="10" <?= $pp == 10 ? 'selected' : '' ?>>10</option>
                            <option value="20" <?= $pp == 20 ? 'selected' : '' ?>>20</option>
                            <option value="50" <?= $pp == 50 ? 'selected' : '' ?>>50</option>
                            <option value="100" <?= $pp == 100 ? 'selected' : '' ?>>100</option>
                        </select>
                        <span class="form-label mb-1 ms-1">registros</span>
                    </div>
                </div>
                <div class="col-md-2 filtros-btns-row w-100 mt-2">
                    <button type="submit" class="btn btn-primary btn-sm btn-filtros-mobile"><i class="fa fa-search"></i> Filtrar</button>
                    <a href="?" class="btn btn-secondary btn-sm btn-filtros-mobile"><i class="fa fa-times"></i> Limpar Filtros</a>
                </div>
            </form>

            <!-- Tabela -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th>Título</th>
                            <th>Departamento</th>
                            <th>Responsável</th>
                            <th>Período</th>
                            <th>Status</th>
                            <th style="width: 140px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($plans)) : ?>
                            <?php foreach ($plans as $plan) : ?>
                                <tr>
                                    <td><?= htmlspecialchars($plan['id']) ?></td>
                                    <td><?= htmlspecialchars($plan['title']) ?></td>
                                    <td><?= htmlspecialchars($plan['department_name'] ?? $plan['department_id'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($plan['responsible_name'] ?? $plan['responsible_id'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($plan['start_date']) ?> a <?= htmlspecialchars($plan['end_date']) ?></td>
                                    <td>
                                        <?php
                                        $status = $plan['status'];
                                        $badge = 'secondary';
                                        if ($status === 'Concluído') $badge = 'success';
                                        elseif ($status === 'Em andamento') $badge = 'primary';
                                        elseif ($status === 'Atrasado') $badge = 'danger';
                                        elseif ($status === 'Não iniciado') $badge = 'warning';
                                        ?>
                                        <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($status) ?></span>
                                    </td>
                                    <td>
                                        <a href="/adms/view-strategic-plan/<?= $plan['id'] ?>" class="btn btn-sm btn-info" title="Visualizar"><i class="fas fa-eye"></i></a>
                                        <a href="/adms/edit-strategic-plan/<?= $plan['id'] ?>" class="btn btn-sm btn-warning" title="Editar"><i class="fas fa-edit"></i></a>
                                        <a href="/adms/delete-strategic-plan-/<?= $plan['id'] ?>" class="btn btn-sm btn-danger" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este plano?');"><i class="fas fa-trash-alt"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="7" class="text-center">Nenhum plano encontrado.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginação e informações abaixo da tabela/cards -->
            <div class="w-100 mt-2">
                <!-- Desktop: frase à esquerda, paginação à direita -->
                <div class="d-none d-md-flex justify-content-between align-items-center w-100">
                    <div class="text-secondary small">
                        <?php if (!empty($pagination['showing'])): ?>
                            <?= $pagination['showing'] ?>
                        <?php else: ?>
                            Exibindo <?= is_array($plans) ? count($plans) : 0; ?> registro(s) nesta página.
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if (!empty($pagination['links'])): ?>
                            <ul class="pagination mb-0">
                                <?= $pagination['links'] ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- CARDS MOBILE -->
            <div class="d-block d-md-none">
                <?php if (!empty($plans)) : ?>
                    <?php foreach ($plans as $i => $plan) : ?>
                        <div class="card mb-3 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title mb-1"><b><?= htmlspecialchars($plan['title']) ?></b></h5>
                                        <div class="mb-1"><b>ID:</b> <?= htmlspecialchars($plan['id']) ?></div>
                                        <div class="mb-1">
                                            <?php
                                            $status = $plan['status'];
                                            $badge = 'secondary';
                                            if ($status === 'Concluído') $badge = 'success';
                                            elseif ($status === 'Em andamento') $badge = 'primary';
                                            elseif ($status === 'Atrasado') $badge = 'danger';
                                            elseif ($status === 'Não iniciado') $badge = 'warning';
                                            ?>
                                            <b>Status:</b> <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($status) ?></span>
                                        </div>
                                    </div>
                                    <button class="btn btn-outline-primary btn-sm ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#cardPlanDetails<?= $i ?>" aria-expanded="false" aria-controls="cardPlanDetails<?= $i ?>">Ver mais</button>
                                </div>
                                <div class="collapse mt-2" id="cardPlanDetails<?= $i ?>">
                                    <div><b>Departamento:</b> <?= htmlspecialchars($plan['department_name'] ?? $plan['department_id'] ?? '') ?></div>
                                    <div><b>Responsável:</b> <?= htmlspecialchars($plan['responsible_name'] ?? $plan['responsible_id'] ?? '') ?></div>
                                    <div><b>Período:</b> <?= htmlspecialchars($plan['start_date']) ?> a <?= htmlspecialchars($plan['end_date']) ?></div>
                                    <div class="mt-2">
                                        <a href="/adms/view-strategic-plan/<?= $plan['id'] ?>" class="btn btn-info btn-sm me-1 mb-1" title="Visualizar"><i class="fas fa-eye"></i> Visualizar</a>
                                        <a href="/adms/edit-strategic-plan/<?= $plan['id'] ?>" class="btn btn-warning btn-sm me-1 mb-1" title="Editar"><i class="fas fa-edit"></i> Editar</a>
                                        <a href="/adms/delete-strategic-plan-/<?= $plan['id'] ?>" class="btn btn-danger btn-sm me-1 mb-1" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este plano?');"><i class="fas fa-trash-alt"></i> Excluir</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="alert alert-danger" role="alert">Nenhum plano encontrado.</div>
                <?php endif; ?>

                <!-- Paginação e informações abaixo dos cards no mobile -->
                <div class="d-flex d-md-none flex-column align-items-center w-100 mt-2">
                    <div class="text-secondary small w-100 text-center mb-1">
                        <?php if (!empty($pagination['showing'])): ?>
                            <?= $pagination['showing'] ?>
                        <?php else: ?>
                            Exibindo <?= is_array($plans) ? count($plans) : 0; ?> registro(s) nesta página.
                        <?php endif; ?>
                    </div>
                    <div class="w-100 d-flex justify-content-center">
                        <?php if (!empty($pagination['links'])): ?>
                            <ul class="pagination mb-0">
                                <?= $pagination['links'] ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php // Rodapé já incluso pelo controller ?> 