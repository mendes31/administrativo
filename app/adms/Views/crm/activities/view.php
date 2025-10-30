<?php
$activity = $this->data['activity'] ?? [];
?>

<div class="container-fluid px-4">
    
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">
            <i class="fas fa-calendar-check me-2"></i>Visualizar Atividade
        </h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>crm-list-activities">Atividades</a></li>
            <li class="breadcrumb-item active">Visualizar</li>
        </ol>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Detalhes da Atividade
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tipo:</label>
                            <p>
                                <?php
                                $typeTranslations = [
                                    'call' => '📞 Ligação',
                                    'email' => '✉️ E-mail',
                                    'meeting' => '👥 Reunião',
                                    'task' => '✔️ Tarefa',
                                    'note' => '📝 Nota'
                                ];
                                echo $typeTranslations[$activity['type']] ?? $activity['type'];
                                ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Prioridade:</label>
                            <p>
                                <?php
                                $priorityColors = ['Alta' => 'danger', 'Média' => 'warning', 'Baixa' => 'info', 'Urgente' => 'danger'];
                                $color = $priorityColors[$activity['priority']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $color ?>">
                                    <?= htmlspecialchars($activity['priority']) ?>
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Título:</label>
                        <p><?= htmlspecialchars($activity['title']) ?></p>
                    </div>

                    <?php if ($activity['description']): ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Descrição:</label>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($activity['description'])) ?></p>
                    </div>
                    <?php endif; ?>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Data Agendada:</label>
                            <p>
                                <?php if ($activity['scheduled_date']): ?>
                                    <i class="fas fa-calendar-alt text-primary me-1"></i>
                                    <?= date('d/m/Y', strtotime($activity['scheduled_date'])) ?>
                                    <i class="fas fa-clock text-primary ms-2 me-1"></i>
                                    <?= date('H:i', strtotime($activity['scheduled_date'])) ?>
                                <?php else: ?>
                                    <span class="text-muted">Não agendada</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Duração:</label>
                            <p>
                                <?= $activity['duration_minutes'] ? $activity['duration_minutes'] . ' minutos' : '-' ?>
                            </p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Status:</label>
                            <p>
                                <?php
                                $statusColors = ['Pendente' => 'warning', 'Concluída' => 'success', 'Cancelada' => 'secondary'];
                                $color = $statusColors[$activity['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $color ?>">
                                    <?= htmlspecialchars($activity['status']) ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <?php if ($activity['completed_date']): ?>
                                <label class="form-label fw-bold">Concluída em:</label>
                                <p>
                                    <?= date('d/m/Y H:i', strtotime($activity['completed_date'])) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($activity['partner_name']): ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Parceiro:</label>
                        <p>
                            <i class="fas fa-handshake text-success me-1"></i>
                            <a href="<?= $_ENV['URL_ADM'] ?>crm-view-partner/<?= $activity['partner_id'] ?>">
                                <?= htmlspecialchars($activity['partner_name']) ?>
                            </a>
                        </p>
                    </div>
                    <?php endif; ?>

                    <?php if ($activity['opportunity_title']): ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Oportunidade:</label>
                        <p>
                            <i class="fas fa-bullseye text-primary me-1"></i>
                            <a href="<?= $_ENV['URL_ADM'] ?>crm-view-opportunity/<?= $activity['opportunity_id'] ?>">
                                <?= htmlspecialchars($activity['opportunity_title']) ?>
                            </a>
                        </p>
                    </div>
                    <?php endif; ?>

                    <?php if ($activity['outcome']): ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Resultado:</label>
                        <p><?= htmlspecialchars($activity['outcome']) ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if ($activity['outcome_notes']): ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Notas do Resultado:</label>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($activity['outcome_notes'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4 shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Ações</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?= $_ENV['URL_ADM'] ?>crm-list-activities" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Voltar para Lista
                        </a>
                        
                        <?php if ($activity['status'] === 'Pendente'): ?>
                        <form method="POST" action="<?= $_ENV['URL_ADM'] ?>crm-complete-activity/<?= $activity['id'] ?>">
                            <input type="hidden" name="redirect_to" value="crm-view-activity/<?= $activity['id'] ?>">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-check me-2"></i>Marcar como Concluída
                            </button>
                        </form>
                        <?php endif; ?>
                        
                        <a href="<?= $_ENV['URL_ADM'] ?>crm-delete-activity/<?= $activity['id'] ?>" 
                           class="btn btn-danger"
                           onclick="return confirm('Tem certeza que deseja excluir esta atividade?')">
                            <i class="fas fa-trash me-2"></i>Excluir Atividade
                        </a>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Informações</h5>
                </div>
                <div class="card-body">
                    <p class="small mb-2">
                        <strong>Responsável:</strong><br>
                        <?= htmlspecialchars($activity['responsible_name'] ?? 'N/A') ?>
                    </p>
                    <p class="small mb-2">
                        <strong>Criado em:</strong><br>
                        <?= date('d/m/Y H:i', strtotime($activity['created_at'])) ?>
                    </p>
                    <?php if ($activity['updated_at']): ?>
                    <p class="small mb-0">
                        <strong>Atualizado em:</strong><br>
                        <?= date('d/m/Y H:i', strtotime($activity['updated_at'])) ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

