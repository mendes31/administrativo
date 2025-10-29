<?php
$automation = $this->data['automation'] ?? [];
$logs = $this->data['logs'] ?? [];

// Decodificar JSON
$triggerConditions = !empty($automation['trigger_conditions']) ? json_decode($automation['trigger_conditions'], true) : [];
$actionConfig = !empty($automation['action_config']) ? json_decode($automation['action_config'], true) : [];
?>

<div class="container-fluid px-4">
    
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
        <h1 class="mt-2">
            <i class="fas fa-robot text-primary me-2"></i>
            Detalhes da Automação
        </h1>
        <div>
            <a href="<?= $_ENV['URL_ADM'] ?>crm-delete-automation/<?= $automation['id'] ?>" 
               class="btn btn-danger btn-sm"
               onclick="return confirm('Tem certeza que deseja excluir esta automação?')">
                <i class="fas fa-trash"></i> Excluir
            </a>
            <a href="<?= $_ENV['URL_ADM'] ?>crm-list-automations" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Informações Principais -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header" style="background-color: <?= $automation['is_active'] ? '#28a745' : '#6c757d' ?>; color: white;">
                    <h5 class="mb-0">
                        <i class="fas fa-<?= $automation['is_active'] ? 'check-circle' : 'pause-circle' ?> me-2"></i>
                        <?= htmlspecialchars($automation['name']) ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($automation['description']): ?>
                        <div class="alert alert-secondary">
                            <?= nl2br(htmlspecialchars($automation['description'])) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong>Entidade:</strong>
                            <span class="badge bg-primary"><?= ucfirst($automation['entity_type']) ?></span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Gatilho:</strong> <?= ucfirst(str_replace('_', ' ', $automation['trigger_event'])) ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Ação:</strong> <?= ucfirst(str_replace('_', ' ', $automation['action_type'])) ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Prioridade:</strong> <?= $automation['priority'] ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($triggerConditions)): ?>
                        <hr>
                        <h6 class="text-muted">Condições:</h6>
                        <pre class="bg-light p-3 rounded"><?= json_encode($triggerConditions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></pre>
                    <?php endif; ?>
                    
                    <?php if (!empty($actionConfig)): ?>
                        <hr>
                        <h6 class="text-muted">Configuração da Ação:</h6>
                        <pre class="bg-light p-3 rounded"><?= json_encode($actionConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></pre>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Estatísticas -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Estatísticas</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">Total de Execuções</small>
                        <div class="fs-4 fw-bold text-primary"><?= number_format($automation['execution_count']) ?></div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Última Execução</small>
                        <div><?= $automation['last_execution'] ? date('d/m/Y H:i', strtotime($automation['last_execution'])) : 'Nunca' ?></div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Criado por</small>
                        <div><?= htmlspecialchars($automation['created_by_name'] ?? 'Sistema') ?></div>
                    </div>
                    <div>
                        <small class="text-muted">Criado em</small>
                        <div><?= date('d/m/Y H:i', strtotime($automation['created_at'])) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Logs de Execução -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Histórico de Execuções (Últimas 100)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($logs)): ?>
                <p class="text-muted text-center">Nenhuma execução registrada ainda.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Data/Hora</th>
                                <th>Entidade</th>
                                <th>ID</th>
                                <th>Status</th>
                                <th>Erro (se houver)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i:s', strtotime($log['executed_at'])) ?></td>
                                    <td><?= ucfirst($log['entity_type']) ?></td>
                                    <td><?= $log['entity_id'] ?></td>
                                    <td>
                                        <?php
                                        $statusBadges = [
                                            'success' => '<span class="badge bg-success">Sucesso</span>',
                                            'failed' => '<span class="badge bg-danger">Falhou</span>',
                                            'skipped' => '<span class="badge bg-secondary">Ignorado</span>'
                                        ];
                                        echo $statusBadges[$log['status']] ?? $log['status'];
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($log['error_message']): ?>
                                            <small class="text-danger"><?= htmlspecialchars($log['error_message']) ?></small>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
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

