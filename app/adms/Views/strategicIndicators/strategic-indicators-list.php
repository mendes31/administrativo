<?php
$indicators = $this->data['indicators'] ?? [];
// Cabeçalho já incluso pelo controller
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Indicadores Estratégicos</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Indicadores Estratégicos</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2 flex-wrap">
            <span><i class="fas fa-chart-line me-2"></i>Listar Indicadores Estratégicos</span>
            <span class="ms-auto d-sm-flex flex-row flex-wrap gap-1">
                <a href="<?php echo $_ENV['URL_ADM']; ?>strategic-indicators-create" class="btn btn-success btn-sm mb-1"><i class="fas fa-plus"></i> Cadastrar</a>
            </span>
        </div>
        <div class="card-body">
            <form method="get" class="row g-2 mb-3 align-items-end">
                <div class="col-md-4">
                    <label for="name" class="form-label mb-1">Nome</label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="Buscar por nome">
                </div>
                <div class="col-md-3">
                    <label for="unit" class="form-label mb-1">Unidade</label>
                    <input type="text" name="unit" id="unit" class="form-control" placeholder="Buscar por unidade">
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label mb-1">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Todos</option>
                        <option value="Ativo">Ativo</option>
                        <option value="Inativo">Inativo</option>
                    </select>
                </div>
                <div class="col-auto mb-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Buscar</button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>strategic-indicators-list" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i> Limpar</a>
                </div>
            </form>

            <?php if (empty($indicators)): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    Nenhum indicador estratégico encontrado.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 60px;">ID</th>
                                <th>Nome</th>
                                <th>Descrição</th>
                                <th>Unidade</th>
                                <th>Meta</th>
                                <th>Status</th>
                                <th style="width: 140px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($indicators as $indicator): ?>
                                <tr>
                                    <td><?= $indicator['id'] ?></td>
                                    <td><?= htmlspecialchars($indicator['name'] ?? '') ?></td>
                                    <td><?= htmlspecialchars(substr($indicator['description'] ?? '', 0, 50)) ?><?= strlen($indicator['description'] ?? '') > 50 ? '...' : '' ?></td>
                                    <td><?= htmlspecialchars($indicator['unit'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($indicator['target_value'] ?? '') ?></td>
                                    <td>
                                        <span class="badge bg-<?= ($indicator['status'] ?? '') == 'Ativo' ? 'success' : 'secondary' ?>">
                                            <?= htmlspecialchars($indicator['status'] ?? 'Ativo') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo $_ENV['URL_ADM']; ?>view-strategic-indicator/<?= $indicator['id'] ?>" class="btn btn-sm btn-info" title="Visualizar"><i class="fas fa-eye"></i></a>
                                        <a href="<?php echo $_ENV['URL_ADM']; ?>strategic-indicators-edit/<?= $indicator['id'] ?>" class="btn btn-sm btn-warning" title="Editar"><i class="fas fa-edit"></i></a>
                                        <a href="<?php echo $_ENV['URL_ADM']; ?>delete-strategic-indicator/<?= $indicator['id'] ?>" class="btn btn-sm btn-danger" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este indicador?');"><i class="fas fa-trash-alt"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (isset($pagination) && !empty($pagination)): ?>
                    <div class="d-flex justify-content-center mt-3">
                        <?= $pagination ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
