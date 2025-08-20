<?php
// Dashboard de Treinamentos
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Dashboard de Treinamentos</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item">Dashboard de Treinamentos</li>
        </ol>
    </div>

    <!-- Filtros -->
    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="colaborador" class="form-label">Colaborador</label>
                    <select name="colaborador" id="colaborador" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach (($this->data['users'] ?? []) as $user): ?>
                            <option value="<?= $user['id'] ?>" <?= ($this->data['filters']['colaborador'] ?? '') == $user['id'] ? 'selected' : '' ?>><?= $user['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="departamento" class="form-label">Departamento</label>
                    <select name="departamento" id="departamento" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach (($this->data['departments'] ?? []) as $dep): ?>
                            <option value="<?= $dep['id'] ?>" <?= ($this->data['filters']['departamento'] ?? '') == $dep['id'] ? 'selected' : '' ?>><?= $dep['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="cargo" class="form-label">Cargo</label>
                    <select name="cargo" id="cargo" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach (($this->data['positions'] ?? []) as $pos): ?>
                            <option value="<?= $pos['id'] ?>" <?= ($this->data['filters']['cargo'] ?? '') == $pos['id'] ? 'selected' : '' ?>><?= $pos['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="treinamento" class="form-label">Treinamento</label>
                    <select name="treinamento" id="treinamento" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach (($this->data['trainings'] ?? []) as $trein): ?>
                            <option value="<?= $trein['id'] ?>" <?= ($this->data['filters']['treinamento'] ?? '') == $trein['id'] ? 'selected' : '' ?>><?= $trein['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Filtrar
                    </button>
                    <a href="<?= $_ENV['URL_ADM'] ?>training-dashboard" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>Limpar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Resumo Estatístico -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= number_format($this->data['summary']['total_colaboradores'] ?? 0) ?></h4>
                            <div>Colaboradores</div>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= number_format($this->data['summary']['total_pendentes'] ?? 0) ?></h4>
                            <div>Pendentes</div>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= number_format($this->data['summary']['total_em_dia'] ?? 0) ?></h4>
                            <div>Em Dia</div>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= number_format($this->data['summary']['total_vencidos'] ?? 0) ?></h4>
                            <div>Vencidos</div>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Treinamentos Próximos do Vencimento -->
    <?php if (!empty($this->data['expiring'])): ?>
    <div class="card mb-4 border-warning">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Treinamentos Próximos do Vencimento (30 dias)
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Colaborador</th>
                            <th>Departamento</th>
                            <th>Cargo</th>
                            <th>Treinamento</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->data['expiring'] as $training): ?>
                        <tr>
                            <td><?= htmlspecialchars($training['user_name']) ?></td>
                            <td><?= htmlspecialchars($training['department']) ?></td>
                            <td><?= htmlspecialchars($training['position']) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($training['training_name']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($training['training_code']) ?></small>
                            </td>
                            <td>
                                <?php if ($training['status'] === 'vencido'): ?>
                                    <span class="badge bg-danger">Vencido</span>
                                <?php elseif ($training['status'] === 'proximo_vencimento'): ?>
                                    <span class="badge bg-warning">Próximo Vencimento</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($training['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-success" onclick="markCompleted(<?= $training['user_id'] ?>, <?= $training['training_id'] ?>)">
                                    <i class="fas fa-check me-1"></i>Concluir
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Matriz de Treinamentos -->
    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-table me-2"></i>
                Matriz de Treinamentos
            </h5>
        </div>
        <div class="card-body">
            <?php if (!empty($this->data['matrix'])): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover" style="table-layout: fixed; width: 100%;" id="matrixTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th class="col-nome">Colaborador</th>
                            <th>Departamento</th>
                            <th>Cargo</th>
                            <th>Código</th>
                            <th>Treinamento</th>
                            <th>Status</th>
                            <th>Data Realização</th>
                            <th>Validade</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->data['matrix'] as $item): ?>
                        <tr>
                            <td><?= $item['user_id'] ?></td>
                            <td class="col-nome">
                                <a href="<?= $_ENV['URL_ADM'] ?>training-history/<?= $item['user_id'] ?>-<?= $item['training_id'] ?>" class="text-decoration-none" title="Ver histórico de reciclagem">
                                    <?= htmlspecialchars($item['user_name']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($item['department']) ?></td>
                            <td><?= htmlspecialchars($item['position']) ?></td>
                            <td><?= htmlspecialchars($item['codigo']) ?></td>
                            <td>
                                <a href="<?= $_ENV['URL_ADM'] ?>training-history/<?= $item['user_id'] ?>-<?= $item['training_id'] ?>" class="text-decoration-none" title="Ver histórico de reciclagem">
                                    <?= htmlspecialchars($item['training_name']) ?><?= !empty($item['versao']) ? ' (v' . htmlspecialchars($item['versao']) . ')' : '' ?>
                                </a>
                            </td>
                            <td>
                                <?php
                                $statusClass = match($item['status_dinamico']) {
                                    'em_dia' => 'bg-success',
                                    'pendente' => 'bg-warning',
                                    'vencido' => 'bg-danger',
                                    'proximo_vencimento' => 'bg-warning',
                                    'agendado' => 'bg-info',
                                    default => 'bg-secondary'
                                };
                                $statusText = match($item['status_dinamico']) {
                                    'em_dia' => 'Em Dia',
                                    'pendente' => 'Pendente',
                                    'vencido' => 'Vencido',
                                    'proximo_vencimento' => 'Próximo Vencimento',
                                    'agendado' => 'Agendado',
                                    default => ucfirst($item['status_dinamico'])
                                };
                                ?>
                                <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                            </td>
                            <td>
                                <?php if ($item['data_realizacao']): ?>
                                    <?= date('d/m/Y', strtotime($item['data_realizacao'])) ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($item['reciclagem']): ?>
                                    <span class="badge bg-info">Sim (<?= $item['reciclagem_periodo'] ?> meses)</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Não</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-success" onclick="markCompleted(<?= $item['user_id'] ?>, <?= $item['training_id'] ?>)">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <a href="<?= $_ENV['URL_ADM'] ?>apply-training?user_id=<?= $item['user_id'] ?>&training_id=<?= $item['training_id'] ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?= $_ENV['URL_ADM'] ?>training-history/<?= $item['user_id'] ?>-<?= $item['training_id'] ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-history"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Nenhum treinamento encontrado</h5>
                <p class="text-muted">Não há treinamentos vinculados aos filtros selecionados.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sincronizar Vínculos -->
    <div class="card mb-4 border-info">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">
                <i class="fas fa-sync me-2"></i>
                Sincronizar Vínculos de Treinamentos
            </h5>
        </div>
        <div class="card-body">
            <form method="POST" action="<?= $_ENV['URL_ADM'] ?>training-dashboard/syncUserLinks" class="row g-3">
                <div class="col-md-5">
                    <label for="sync_user_id" class="form-label">Colaborador</label>
                    <select name="user_id" id="sync_user_id" class="form-select" required>
                        <option value="">Selecione um colaborador...</option>
                        <?php foreach (($this->data['users'] ?? []) as $user): ?>
                            <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label for="sync_position_id" class="form-label">Cargo</label>
                    <select name="position_id" id="sync_position_id" class="form-select" required>
                        <option value="">Selecione um cargo...</option>
                        <?php foreach (($this->data['positions'] ?? []) as $position): ?>
                            <option value="<?= $position['id'] ?>"><?= htmlspecialchars($position['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-info w-100">
                        <i class="fas fa-sync me-2"></i>Sincronizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Confirmação -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Conclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja marcar este treinamento como concluído?</p>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="createNewCycle" checked>
                    <label class="form-check-label" for="createNewCycle">
                        Criar novo ciclo (se aplicável)
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="confirmButton">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentUserId = null;
let currentTrainingId = null;

function markCompleted(userId, trainingId) {
    currentUserId = userId;
    currentTrainingId = trainingId;
    
    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    modal.show();
}

document.getElementById('confirmButton').addEventListener('click', function() {
    const createNewCycle = document.getElementById('createNewCycle').checked;
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= $_ENV['URL_ADM'] ?>training-dashboard/markCompleted';
    
    const userIdInput = document.createElement('input');
    userIdInput.type = 'hidden';
    userIdInput.name = 'user_id';
    userIdInput.value = currentUserId;
    
    const trainingIdInput = document.createElement('input');
    trainingIdInput.type = 'hidden';
    trainingIdInput.name = 'training_id';
    trainingIdInput.value = currentTrainingId;
    
    const createNewCycleInput = document.createElement('input');
    createNewCycleInput.type = 'hidden';
    createNewCycleInput.name = 'create_new_cycle';
    createNewCycleInput.value = createNewCycle ? '1' : '0';
    
    form.appendChild(userIdInput);
    form.appendChild(trainingIdInput);
    form.appendChild(createNewCycleInput);
    
    document.body.appendChild(form);
    form.submit();
});

// Inicializar DataTable
$(document).ready(function() {
    $('#matrixTable').DataTable({
        pageLength: 25,
        order: [[0, 'asc'], [3, 'asc']]
    });
});
</script> 