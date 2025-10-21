<?php
$plans = $this->data['plans'] ?? [];
// Cabeçalho já incluso pelo controller

/**
 * Verifica se o usuário tem permissão para acessar um plano específico
 */
function canAccessPlan($plan, $userDepartmentId, $userAccessLevelId, $userDepartment) {
    // Super administrador (nível 1) tem acesso total
    if ($userAccessLevelId == 1 || $userAccessLevelId === '1') {
        return true;
    }
    
    // Usuários do departamento "Diretoria" também têm acesso total
    if ($userDepartment === 'Diretoria') {
        return true;
    }
    
    // Outros usuários só podem acessar planos do seu departamento
    return $userDepartmentId && $plan['department_id'] == $userDepartmentId;
}

// Dados do usuário logado
$userDepartmentId = $_SESSION['user_department_id'] ?? null;
$userAccessLevelId = $_SESSION['user_access_level_id'] ?? null;
$userDepartment = $_SESSION['user_department'] ?? null;
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
                <a href="<?php echo $_ENV['URL_ADM']; ?>create-strategic-plan" class="btn btn-success btn-sm mb-1"><i class="fas fa-plus"></i> Cadastrar</a>
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
            <div class="table-responsive d-none d-md-block list-desktop">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Título</th>
                            <th>Departamento</th>
                            <th>Responsável</th>
                            <th>Período</th>
                            <th>Status</th>
                            <th>Última Observação</th>
                            <th style="width: 280px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($plans)) : ?>
                            <?php foreach ($plans as $plan) : ?>
                                <tr>
                                    <td class="title-cell"><?= htmlspecialchars($plan['title']) ?></td>
                                    <td class="department-cell"><?= htmlspecialchars($plan['dep_name'] ?? 'Não informado') ?></td>
                                    <td class="responsible-cell"><?= htmlspecialchars($plan['user_name'] ?? 'Não informado') ?></td>
                                    <td><?= date('d/m/Y', strtotime($plan['start_date'])) ?> a <?= date('d/m/Y', strtotime($plan['end_date'])) ?></td>
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
                                    <td class="observation-cell">
                                        <?php if (!empty($plan['last_observation'])) : ?>
                                            <div class="observation-preview">
                                                <div class="observation-text-container">
                                                    <div class="observation-text-short" id="obs-short-<?= $plan['id'] ?>">
                                                        <?= htmlspecialchars(substr($plan['last_observation'], 0, 80)) ?><?= strlen($plan['last_observation']) > 80 ? '...' : '' ?>
                                                    </div>
                                                    <div class="observation-text-full" id="obs-full-<?= $plan['id'] ?>">
                                                        <?= htmlspecialchars($plan['last_observation']) ?>
                                                    </div>
                                                    <?php if (strlen($plan['last_observation']) > 80) : ?>
                                                        <button type="button" class="observation-toggle-btn" onclick="toggleObservation(<?= $plan['id'] ?>)">
                                                            <span id="toggle-text-<?= $plan['id'] ?>">Ver mais</span>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="observation-meta">
                                                    <div class="text-muted observation-meta-user">
                                                        <strong>Por:</strong> <?= htmlspecialchars($plan['last_observation_user'] ?? 'N/A') ?>
                                                    </div>
                                                    <div class="text-muted observation-meta-date">
                                                        <?= date('d/m/Y H:i', strtotime($plan['last_observation_date'])) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else : ?>
                                            <span class="text-muted">Nenhuma observação</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions-cell">
                                        <?php 
                                        // Verificar se o usuário tem acesso ao plano (departamento)
                                        $hasAccess = canAccessPlan($plan, $userDepartmentId, $userAccessLevelId, $userDepartment);
                                        
                                        // Verificar permissões de botões
                                        $buttonPermission = $this->data['buttonPermission'] ?? [];
                                        ?>
                                        <?php if ($hasAccess): ?>
                                            <?php if (in_array('ViewStrategicPlan', $buttonPermission)): ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>view-strategic-plan/<?= $plan['id'] ?>" class="btn btn-sm btn-info" title="Visualizar"><i class="fas fa-eye"></i></a>
                                            <?php endif; ?>
                                            <?php if (in_array('ViewPlanIndicators', $buttonPermission)): ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>view-plan-indicators/<?= $plan['id'] ?>" class="btn btn-sm btn-primary" title="Indicadores"><i class="fas fa-chart-line"></i></a>
                                            <?php endif; ?>
                                            <?php if (in_array('ViewStrategicPlanObservations', $buttonPermission)): ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>view-strategic-plan-observations/<?= $plan['id'] ?>" class="btn btn-sm btn-success" title="Observações"><i class="fas fa-comments"></i></a>
                                            <?php endif; ?>
                                            <?php if (in_array('UpdateStrategicPlan', $buttonPermission)): ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>edit-strategic-plan/<?= $plan['id'] ?>" class="btn btn-sm btn-warning" title="Editar"><i class="fas fa-edit"></i></a>
                                            <?php endif; ?>
                                            <?php if (in_array('DeleteStrategicPlan', $buttonPermission)): ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>delete-strategic-plan/<?= $plan['id'] ?>" class="btn btn-sm btn-danger" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este plano?');"><i class="fas fa-trash-alt"></i></a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted small">Sem permissão</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="8" class="text-center">Nenhum plano encontrado.</td></tr>
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
            <div class="d-block d-md-none list-mobile">
                <?php if (!empty($plans)) : ?>
                    <?php foreach ($plans as $i => $plan) : ?>
                        <div class="card mb-3 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title mb-1 title-mobile"><b><?= htmlspecialchars($plan['title']) ?></b></h5>
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
                                    <div><b>Departamento:</b> <?= htmlspecialchars($plan['dep_name'] ?? 'Não informado') ?></div>
                                    <div><b>Responsável:</b> <?= htmlspecialchars($plan['user_name'] ?? 'Não informado') ?></div>
                                    <div><b>Período:</b> <?= date('d/m/Y', strtotime($plan['start_date'])) ?> a <?= date('d/m/Y', strtotime($plan['end_date'])) ?></div>
                                    <div class="mt-2">
                                        <?php 
                                        // Verificar se o usuário tem acesso ao plano (departamento)
                                        $hasAccess = canAccessPlan($plan, $userDepartmentId, $userAccessLevelId, $userDepartment);
                                        
                                        // Verificar permissões de botões
                                        $buttonPermission = $this->data['buttonPermission'] ?? [];
                                        ?>
                                        <?php if ($hasAccess): ?>
                                            <?php if (in_array('ViewStrategicPlan', $buttonPermission)): ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>view-strategic-plan/<?= $plan['id'] ?>" class="btn btn-info btn-sm me-1 mb-1" title="Visualizar"><i class="fas fa-eye"></i> Visualizar</a>
                                            <?php endif; ?>
                                            <?php if (in_array('ViewPlanIndicators', $buttonPermission)): ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>view-plan-indicators/<?= $plan['id'] ?>" class="btn btn-primary btn-sm me-1 mb-1" title="Indicadores"><i class="fas fa-chart-line"></i> Indicadores</a>
                                            <?php endif; ?>
                                            <?php if (in_array('ViewStrategicPlanObservations', $buttonPermission)): ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>view-strategic-plan-observations/<?= $plan['id'] ?>" class="btn btn-success btn-sm me-1 mb-1" title="Observações"><i class="fas fa-comments"></i> Observações</a>
                                            <?php endif; ?>
                                            <?php if (in_array('UpdateStrategicPlan', $buttonPermission)): ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>edit-strategic-plan/<?= $plan['id'] ?>" class="btn btn-warning btn-sm me-1 mb-1" title="Editar"><i class="fas fa-edit"></i> Editar</a>
                                            <?php endif; ?>
                                            <?php if (in_array('DeleteStrategicPlan', $buttonPermission)): ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>delete-strategic-plan/<?= $plan['id'] ?>" class="btn btn-danger btn-sm me-1 mb-1" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este plano?');"><i class="fas fa-trash-alt"></i> Excluir</a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted small">Sem permissão para acessar este plano</span>
                                        <?php endif; ?>
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
                            <?php
                            $links = $pagination['links'];
                            $links = str_replace(
                                ['>Primeiro<','>Anterior<','>Próximo<','>Último<'],
                                ['>&laquo;<','>&lsaquo;<','>&rsaquo;<','>&raquo;<'],
                                $links
                            );
                            ?>
                            <ul class="pagination pagination-sm mb-0">
                                <?= $links ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.observation-preview {
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 2px 4px;
    background-color: #f8f9fa;
    margin: 0;
    text-align: left;
    max-width: 250px;
}

.observation-text-container {
    position: relative;
    margin: 0;
    padding: 0;
}

.observation-text-short {
    color: #495057;
    font-size: 0.9em;
    line-height: 1.3;
    white-space: pre-wrap;
    word-wrap: break-word;
    margin: 0;
    padding: 0;
    display: block;
}

.observation-text-full {
    color: #495057;
    font-size: 0.9em;
    line-height: 1.3;
    white-space: pre-wrap;
    word-wrap: break-word;
    margin: 0;
    padding: 0;
    display: none;
    max-height: 120px;
    overflow-y: auto;
}

.observation-meta {
    border-top: 1px solid #dee2e6;
    padding-top: 1px;
    margin-top: 1px;
    margin-bottom: 0;
    padding-bottom: 0;
}

.observation-meta-user {
    font-size: 0.85em !important;
    margin: 0 !important;
    padding: 0 !important;
}

.observation-meta-date {
    font-size: 0.8em !important;
    margin: 0 !important;
    padding: 0 !important;
}

/* Botão Ver mais */
.observation-toggle-btn {
    color: #007bff !important;
    text-decoration: none !important;
    font-size: 0.8em !important;
    margin-top: 4px !important;
    display: inline-block !important;
    cursor: pointer !important;
}

.observation-toggle-btn:hover {
    color: #0056b3 !important;
    text-decoration: underline !important;
}

.observation-meta div:first-child {
    margin-bottom: 2px;
}

/* Botão "Ver mais" */
.btn-link {
    color: #007bff !important;
    text-decoration: none !important;
    font-size: 0.8em;
    padding: 0 !important;
    margin: 0 !important;
}

.btn-link:hover {
    color: #0056b3 !important;
    text-decoration: underline !important;
}

/* Estilos para título e responsável com quebra de linha */
.title-cell {
    max-width: 150px !important;
    min-width: 120px !important;
    width: 15% !important;
    word-wrap: break-word !important;
    word-break: break-word !important;
    white-space: normal !important;
    line-height: 1.3 !important;
    vertical-align: middle !important;
    padding: 6px 8px !important;
    overflow-wrap: break-word !important;
    hyphens: auto !important;
}

.responsible-cell {
    max-width: 150px !important;
    min-width: 120px !important;
    width: 15% !important;
    word-wrap: break-word !important;
    word-break: break-word !important;
    white-space: normal !important;
    line-height: 1.4 !important;
    vertical-align: middle !important;
    padding: 8px 10px !important;
    overflow-wrap: break-word !important;
    hyphens: auto !important;
}

/* Estilo para coluna departamento */
.department-cell {
    max-width: 120px !important;
    min-width: 100px !important;
    width: 12% !important;
    word-wrap: break-word !important;
    word-break: break-word !important;
    white-space: normal !important;
    vertical-align: middle !important;
    padding: 6px 8px !important;
    overflow-wrap: break-word !important;
}

/* Estilo para coluna ações */
.actions-cell {
    width: 280px !important;
    min-width: 280px !important;
    max-width: 280px !important;
    white-space: nowrap !important;
    vertical-align: middle !important;
    padding: 6px 8px !important;
}

/* Garantir que a tabela tenha largura adequada */
.table-responsive {
    overflow-x: auto;
}

.table {
    min-width: 100%;
    table-layout: auto;
}

/* Forçar quebra de linha nas células da tabela */
.table td {
    word-wrap: break-word !important;
    word-break: break-word !important;
    white-space: normal !important;
    overflow-wrap: break-word !important;
    vertical-align: middle !important;
}

/* Estilo específico para célula de observação */
.observation-cell {
    vertical-align: middle;
    padding: 2px 4px;
}

/* Responsividade para mobile */
@media (max-width: 768px) {
    .observation-preview {
        max-width: 100% !important;
        margin-bottom: 10px;
    }
    
    .observation-text-full {
        max-height: 80px;
    }
    
    .title-cell {
        max-width: 120px !important;
        min-width: 100px !important;
        width: 100% !important;
    }
    
    .department-cell {
        max-width: 100px !important;
        min-width: 80px !important;
    }
    
    .responsible-cell {
        max-width: 120px !important;
        min-width: 100px !important;
    }
    
    .title-mobile {
        word-wrap: break-word !important;
        word-break: break-word !important;
        white-space: normal !important;
        line-height: 1.3 !important;
        font-size: 0.95rem !important;
        max-width: 100% !important;
        display: block !important;
        overflow-wrap: break-word !important;
    }
}
</style>

<script>
function toggleObservation(planId) {
    const shortDiv = document.getElementById('obs-short-' + planId);
    const fullDiv = document.getElementById('obs-full-' + planId);
    const toggleText = document.getElementById('toggle-text-' + planId);
    
    if (fullDiv.style.display === 'none' || fullDiv.style.display === '') {
        // Mostrar texto completo
        shortDiv.style.display = 'none';
        fullDiv.style.display = 'block';
        toggleText.textContent = 'Ver menos';
    } else {
        // Mostrar texto resumido
        shortDiv.style.display = 'block';
        fullDiv.style.display = 'none';
        toggleText.textContent = 'Ver mais';
    }
}
</script>
<?php // Rodapé já incluso pelo controller ?> 