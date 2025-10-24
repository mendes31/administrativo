<!-- CSS Moderno para Formulários de Avaliação -->
<link rel="stylesheet" href="<?= $_ENV['URL_ADM'] ?>public/adms/css/evaluation-forms-modern.css">

<?php
use App\adms\Helpers\CSRFHelper;
$csrf_token = CSRFHelper::generateCSRFToken('form_cancel_assignment');
$assignments = $this->data['assignments'] ?? [];
$models = $this->data['models'] ?? [];
$filters = $this->data['filters'] ?? [];
?>

<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">
            <i class="fas fa-tasks"></i> Gerenciar Atribuições de Avaliações
        </h2>
        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?= $_ENV['URL_ADM'] ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item active">Atribuições</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <!-- FILTROS -->
    <div class="card mb-4 shadow">
        <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-filter"></i> Filtros de Busca</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="<?= $_ENV['URL_ADM'] ?>list-evaluation-assignments" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Buscar por usuário..." 
                           value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <select name="model_id" class="form-select">
                        <option value="">Todos os questionários</option>
                        <?php foreach ($models as $model): ?>
                            <option value="<?= $model['id'] ?>" 
                                    <?= ($filters['model_id'] ?? '') == $model['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($model['titulo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Todos os status</option>
                        <option value="pendente" <?= ($filters['status'] ?? '') == 'pendente' ? 'selected' : '' ?>>Pendente</option>
                        <option value="em_andamento" <?= ($filters['status'] ?? '') == 'em_andamento' ? 'selected' : '' ?>>Em Andamento</option>
                        <option value="aprovado" <?= ($filters['status'] ?? '') == 'aprovado' ? 'selected' : '' ?>>Aprovado</option>
                        <option value="reprovado" <?= ($filters['status'] ?? '') == 'reprovado' ? 'selected' : '' ?>>Reprovado</option>
                        <option value="cancelado" <?= ($filters['status'] ?? '') == 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- TABELA DE ATRIBUIÇÕES -->
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Usuário</th>
                            <th>Questionário</th>
                            <th>Status</th>
                            <th>Tentativas</th>
                            <th>Nota</th>
                            <th>Prazo</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($assignments)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Nenhuma atribuição encontrada.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($assignments as $assign): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($assign['user_name']) ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($assign['user_email']) ?></small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($assign['model_titulo']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($assign['training_name'] ?? 'N/A') ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $statusColors = [
                                            'pendente' => 'warning', 'em_andamento' => 'info',
                                            'aprovado' => 'success', 'reprovado' => 'danger',
                                            'concluido' => 'secondary', 'cancelado' => 'dark'
                                        ];
                                        $color = $statusColors[$assign['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $color ?>">
                                            <?= ucfirst($assign['status']) ?>
                                        </span>
                                        
                                        <?php if ($assign['status'] === 'cancelado'): ?>
                                            <br><small class="text-muted">
                                                <?= date('d/m/Y', strtotime($assign['cancelado_em'])) ?><br>
                                                por <?= htmlspecialchars($assign['cancelado_por_name'] ?? 'Sistema') ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?= $assign['tentativas'] ?>
                                        <?php if (isset($assign['max_tentativas']) && $assign['max_tentativas']): ?>
                                            / <?= $assign['max_tentativas'] ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($assign['nota_maxima'] !== null): ?>
                                            <span class="badge bg-<?= $assign['nota_maxima'] >= $assign['nota_minima_aprovacao'] ? 'success' : 'danger' ?>">
                                                <?= number_format($assign['nota_maxima'], 2) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($assign['data_limite']): ?>
                                            <?php
                                            $hoje = new DateTime();
                                            $prazo = new DateTime($assign['data_limite']);
                                            $vencido = $prazo < $hoje;
                                            ?>
                                            <span class="badge bg-<?= $vencido ? 'danger' : 'info' ?>">
                                                <?= date('d/m/Y', strtotime($assign['data_limite'])) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">Sem prazo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($assign['tentativas'] > 0): ?>
                                                <a href="<?= $_ENV['URL_ADM'] ?>evaluation-history/<?= $assign['id'] ?>" 
                                                   class="btn btn-info" title="Ver Histórico">
                                                    <i class="fas fa-history"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if (!in_array($assign['status'], ['aprovado', 'concluido', 'cancelado'])): ?>
                                                <button type="button" class="btn btn-danger" 
                                                        onclick="mostrarModalCancelar(<?= $assign['id'] ?>, '<?= htmlspecialchars($assign['model_titulo'], ENT_QUOTES) ?>', '<?= htmlspecialchars($assign['user_name'], ENT_QUOTES) ?>')"
                                                        title="Cancelar Atribuição">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include './app/adms/Views/partials/pagination.php'; ?>
</div>

<!-- MODAL DE CANCELAMENTO -->
<div class="modal fade" id="modalCancelar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= $_ENV['URL_ADM'] ?>cancel-evaluation-assignment">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="assignment_id" id="cancel_assignment_id">
                
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle"></i> Cancelar Atribuição
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <p>Você está prestes a cancelar a seguinte atribuição:</p>
                    <div class="alert alert-warning">
                        <strong>Questionário:</strong> <span id="cancel_model_titulo"></span><br>
                        <strong>Usuário:</strong> <span id="cancel_user_name"></span>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>O que acontecerá:</strong>
                        <ul class="mb-0 mt-2">
                            <li>O usuário não poderá mais responder</li>
                            <li>Histórico de tentativas será preservado</li>
                            <li>Ação será auditada</li>
                        </ul>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Motivo do Cancelamento: <span class="text-danger">*</span></label>
                        <textarea name="motivo_cancelamento" class="form-control" rows="3" 
                                  placeholder="Descreva o motivo..." required></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Voltar
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-ban"></i> Confirmar Cancelamento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function mostrarModalCancelar(assignmentId, modelTitulo, userName) {
    document.getElementById('cancel_assignment_id').value = assignmentId;
    document.getElementById('cancel_model_titulo').textContent = modelTitulo;
    document.getElementById('cancel_user_name').textContent = userName;
    
    const modal = new bootstrap.Modal(document.getElementById('modalCancelar'));
    modal.show();
}
</script>

