<?php
$plan = $this->data['plan'] ?? [];
$observations = $this->data['observations'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Observações - <?= htmlspecialchars($plan['title'] ?? 'Plano Estratégico') ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-strategic-plans" class="text-decoration-none">Planos Estratégicos</a>
            </li>
            <li class="breadcrumb-item">Observações</li>
        </ol>
    </div>

    <div class="row">
        <!-- Formulário para nova observação -->
        <div class="col-12 mb-4">
            <div class="card border-light shadow">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-comment-plus me-2"></i>Adicionar Nova Observação</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo $_ENV['URL_ADM']; ?>add-strategic-plan-observation">
                        <input type="hidden" name="strategic_plan_id" value="<?= $plan['id'] ?>">
                        <div class="mb-3">
                            <label for="observation" class="form-label">Observação:</label>
                            <textarea name="observation" id="observation" class="form-control" rows="4" 
                                      placeholder="Digite sua observação aqui..." required></textarea>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i>Enviar Observação
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Histórico de observações -->
        <div class="col-12">
            <div class="card border-light shadow">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Histórico de Observações</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($observations)) : ?>
                        <div class="timeline">
                            <?php foreach ($observations as $index => $obs) : ?>
                                <div class="timeline-item <?= $index === 0 ? 'timeline-item-latest' : '' ?>">
                                    <div class="timeline-marker">
                                        <div class="timeline-marker-icon">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    </div>
                                    <div class="timeline-content">
                                        <div class="timeline-header">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1">
                                                        <?= htmlspecialchars($obs['user_name'] ?? 'Usuário') ?>
                                                        <?php if ($index === 0) : ?>
                                                            <span class="badge bg-primary ms-2">Mais Recente</span>
                                                        <?php endif; ?>
                                                    </h6>
                                                    <small class="text-muted">
                                                        <?= htmlspecialchars($obs['department_name'] ?? 'Departamento') ?> | 
                                                        <?= date('d/m/Y H:i', strtotime($obs['created_at'])) ?>
                                                    </small>
                                                </div>
                                                <div class="timeline-actions">
                                                    <span class="badge bg-secondary">#<?= $obs['id'] ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="timeline-body">
                                            <p class="mb-0"><?= nl2br(htmlspecialchars($obs['observation'])) ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <div class="text-center py-4">
                            <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Nenhuma observação registrada</h5>
                            <p class="text-muted">Seja o primeiro a adicionar uma observação para este plano!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 30px;
}

.timeline-item-latest {
    background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
    border-radius: 10px;
    padding: 15px;
    margin: -10px -15px 20px -15px;
    border-left: 4px solid #2196f3;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 5px;
}

.timeline-marker-icon {
    width: 30px;
    height: 30px;
    background: #2196f3;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 12px;
    border: 3px solid white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.timeline-content {
    background: white;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: 1px solid #e9ecef;
}

.timeline-header h6 {
    color: #2c3e50;
    font-weight: 600;
}

.timeline-body {
    margin-top: 10px;
    line-height: 1.6;
}

.timeline-actions {
    display: flex;
    gap: 5px;
}

@media (max-width: 768px) {
    .timeline {
        padding-left: 20px;
    }
    
    .timeline::before {
        left: 10px;
    }
    
    .timeline-marker {
        left: -15px;
    }
    
    .timeline-marker-icon {
        width: 20px;
        height: 20px;
        font-size: 10px;
    }
}
</style>
