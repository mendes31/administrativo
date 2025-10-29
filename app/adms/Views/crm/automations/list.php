<?php
$automations = $this->data['automations'] ?? [];
$filters = $this->data['filters'] ?? [];
?>

<div class="container-fluid px-4">
    
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
        <h1 class="mt-2">
            <i class="fas fa-robot text-primary me-2"></i>
            Automações e Workflows
        </h1>
        <div>
            <a href="<?= $_ENV['URL_ADM'] ?>crm-create-automation" class="btn btn-success btn-sm">
                <i class="fas fa-plus"></i> Nova Automação
            </a>
        </div>
    </div>

    <!-- Info Box -->
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        <strong>Automações</strong> executam ações automaticamente quando eventos específicos ocorrem (criar parceiro, mudar etapa, etc).
    </div>

    <!-- Filtros -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Entidade</label>
                    <select name="entity_type" class="form-select" onchange="this.form.submit()">
                        <option value="">Todas</option>
                        <option value="partner" <?= $filters['entity_type'] === 'partner' ? 'selected' : '' ?>>Parceiros</option>
                        <option value="opportunity" <?= $filters['entity_type'] === 'opportunity' ? 'selected' : '' ?>>Oportunidades</option>
                        <option value="activity" <?= $filters['entity_type'] === 'activity' ? 'selected' : '' ?>>Atividades</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="is_active" class="form-select" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        <option value="1" <?= $filters['is_active'] === 1 ? 'selected' : '' ?>>Ativas</option>
                        <option value="0" <?= $filters['is_active'] === 0 ? 'selected' : '' ?>>Inativas</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela -->
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($automations)): ?>
                <div class="alert alert-warning text-center">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Nenhuma automação cadastrada.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 25%;">Nome</th>
                                <th style="width: 12%;">Entidade</th>
                                <th style="width: 15%;">Gatilho</th>
                                <th style="width: 15%;">Ação</th>
                                <th style="width: 8%;" class="text-center">Execuções</th>
                                <th style="width: 8%;" class="text-center">Status</th>
                                <th style="width: 12%;" class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($automations as $automation): ?>
                                <tr>
                                    <td><?= $automation['id'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($automation['name']) ?></strong>
                                        <?php if ($automation['description']): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars(substr($automation['description'], 0, 60)) ?>...</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $entityBadges = [
                                            'partner' => '<span class="badge bg-primary">Parceiro</span>',
                                            'opportunity' => '<span class="badge bg-success">Oportunidade</span>',
                                            'activity' => '<span class="badge bg-info">Atividade</span>'
                                        ];
                                        echo $entityBadges[$automation['entity_type']] ?? $automation['entity_type'];
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $triggers = [
                                            'created' => '➕ Criado',
                                            'updated' => '✏️ Atualizado',
                                            'deleted' => '🗑️ Deletado',
                                            'stage_changed' => '🔄 Mudou Etapa',
                                            'status_changed' => '📊 Mudou Status',
                                            'date_reached' => '📅 Data Atingida'
                                        ];
                                        echo $triggers[$automation['trigger_event']] ?? $automation['trigger_event'];
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $actions = [
                                            'send_email' => '📧 Enviar E-mail',
                                            'send_whatsapp' => '💬 Enviar WhatsApp',
                                            'create_activity' => '✅ Criar Atividade',
                                            'create_note' => '📝 Criar Nota',
                                            'send_notification' => '🔔 Notificar',
                                            'update_field' => '✏️ Atualizar Campo'
                                        ];
                                        echo $actions[$automation['action_type']] ?? $automation['action_type'];
                                        ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary"><?= number_format($automation['execution_count']) ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($automation['is_active']): ?>
                                            <span class="badge bg-success">Ativa</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inativa</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= $_ENV['URL_ADM'] ?>crm-view-automation/<?= $automation['id'] ?>" 
                                           class="btn btn-sm btn-info" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?= $_ENV['URL_ADM'] ?>crm-delete-automation/<?= $automation['id'] ?>" 
                                           class="btn btn-sm btn-danger" title="Excluir"
                                           onclick="return confirm('Tem certeza que deseja excluir esta automação?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
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

