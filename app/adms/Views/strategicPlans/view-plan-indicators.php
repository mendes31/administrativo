<?php
$plan = $this->data['plan'] ?? [];
$indicators = $this->data['indicators'] ?? [];
$planMetrics = $this->data['planMetrics'] ?? [];
?>

<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Indicadores do Plano: <?= htmlspecialchars($plan['title'] ?? '') ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-strategic-plans" class="text-decoration-none">Planos Estratégicos</a>
            </li>
            <li class="breadcrumb-item">Indicadores</li>
        </ol>
    </div>

    <!-- Informações do Plano -->
    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <span><i class="fas fa-project-diagram me-2"></i>Informações do Plano</span>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <strong>Título:</strong> <?= htmlspecialchars($plan['title'] ?? '') ?>
                </div>
                <div class="col-md-6">
                    <strong>Status:</strong> 
                    <span class="badge bg-<?= ($plan['status'] ?? '') == 'Concluído' ? 'success' : (($plan['status'] ?? '') == 'Em andamento' ? 'warning' : 'secondary') ?>">
                        <?= htmlspecialchars($plan['status'] ?? '') ?>
                    </span>
                </div>
                <div class="col-md-6">
                    <strong>Período:</strong> 
                    <?= date('d/m/Y', strtotime($plan['start_date'])) ?> a <?= date('d/m/Y', strtotime($plan['end_date'])) ?>
                </div>
                <div class="col-md-6">
                    <strong>Investimento:</strong> 
                    <?= $plan['how_much'] ? 'R$ ' . number_format((float)$plan['how_much'], 2, ',', '.') : 'Não informado' ?>
                </div>
                <div class="col-12">
                    <strong>Descrição:</strong> <?= htmlspecialchars($plan['description'] ?? '') ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Métricas do Plano -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-start border-primary border-4 h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="small fw-medium text-primary mb-1">Total de Indicadores</div>
                            <div class="h4 mb-0"><?= $planMetrics['total_indicators'] ?? 0 ?></div>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-chart-line text-primary fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-start border-success border-4 h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="small fw-medium text-success mb-1">Indicadores Ativos</div>
                            <div class="h4 mb-0"><?= $planMetrics['active_indicators'] ?? 0 ?></div>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-success fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-start border-info border-4 h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="small fw-medium text-info mb-1">Progresso Médio</div>
                            <div class="h4 mb-0"><?= $planMetrics['average_progress'] ?? 0 ?>%</div>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-percentage text-info fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-start border-warning border-4 h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="small fw-medium text-warning mb-1">Meta Alcançada</div>
                            <div class="h4 mb-0"><?= $planMetrics['on_target_indicators'] ?? 0 ?></div>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-bullseye text-warning fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Indicadores do Plano -->
    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2 flex-wrap">
            <span><i class="fas fa-chart-line me-2"></i>Indicadores do Plano</span>
            <span class="ms-auto d-sm-flex flex-row flex-wrap gap-1">
                <a href="<?php echo $_ENV['URL_ADM']; ?>strategic-indicators-create?plan_id=<?= $plan['id'] ?>" class="btn btn-success btn-sm mb-1">
                    <i class="fas fa-plus"></i> Adicionar Indicador
                </a>
                <a href="<?php echo $_ENV['URL_ADM']; ?>view-strategic-plan/<?= $plan['id'] ?>" class="btn btn-info btn-sm mb-1">
                    <i class="fas fa-eye"></i> Ver Plano
                </a>
            </span>
        </div>
        <div class="card-body">
            <?php if (empty($indicators)): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    Nenhum indicador cadastrado para este plano. 
                    <a href="<?php echo $_ENV['URL_ADM']; ?>strategic-indicators-create?plan_id=<?= $plan['id'] ?>" class="alert-link">Clique aqui para adicionar o primeiro indicador.</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Nome</th>
                                <th>Meta</th>
                                <th>Atual</th>
                                <th>Progresso</th>
                                <th>Unidade</th>
                                <th>Frequência</th>
                                <th>Status</th>
                                <th style="width: 140px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($indicators as $indicator): ?>
                                <?php
                                $target = (float)($indicator['target_value'] ?? 0);
                                $current = (float)($indicator['current_value'] ?? 0);
                                $progress = $target > 0 ? ($current / $target) * 100 : 0;
                                $progressColor = $progress >= 100 ? 'success' : ($progress >= 80 ? 'warning' : 'danger');
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($indicator['name'] ?? '') ?></div>
                                        <small class="text-muted"><?= htmlspecialchars(substr($indicator['description'] ?? '', 0, 50)) ?><?= strlen($indicator['description'] ?? '') > 50 ? '...' : '' ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($indicator['target_value'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($indicator['current_value'] ?? '') ?></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-<?= $progressColor ?>" role="progressbar" 
                                                 style="width: <?= min($progress, 100) ?>%"
                                                 aria-valuenow="<?= $progress ?>" 
                                                 aria-valuemin="0" aria-valuemax="100">
                                                <?= round($progress, 1) ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($indicator['unit'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($indicator['frequency'] ?? '') ?></td>
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
            <?php endif; ?>
        </div>
    </div>
</div>



