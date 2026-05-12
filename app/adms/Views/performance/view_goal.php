<?php
use App\adms\Helpers\FormatHelper;
use App\adms\Helpers\CSRFHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Visualizar Meta de Desempenho</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-performance-goals" class="text-decoration-none">Metas</a>
            </li>
            <li class="breadcrumb-item">Visualizar</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-bullseye me-2"></i>Detalhes da Meta</span>
            <div>
                <?php if (in_array('ListPerformanceGoals', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-performance-goals" class="btn btn-sm btn-secondary">
                        <i class="fas fa-list me-1"></i>Listar
                    </a>
                <?php } ?>
                <?php if (in_array('UpdatePerformanceGoal', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>update-performance-goal/<?= $this->data['goal']['id'] ?>" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit me-1"></i>Editar
                    </a>
                <?php } ?>
                <?php
                $log_resumo = $this->data['log_resumo'] ?? [];
                $log_btn_class = 'btn btn-outline-info btn-sm';
                include __DIR__ . '/../partials/button_log_alteracoes.php';
                ?>
            </div>
        </div>
        <div class="card-body">
            <?php 
            $goal = $this->data['goal'];
            $progress = (int)($goal['progress_percentage'] ?? 0);
            $statusLabels = [
                'pending' => ['label' => 'Pendente', 'color' => 'secondary', 'icon' => 'clock'],
                'in_progress' => ['label' => 'Em Andamento', 'color' => 'info', 'icon' => 'spinner'],
                'achieved' => ['label' => 'Alcançada', 'color' => 'success', 'icon' => 'check-circle'],
                'failed' => ['label' => 'Não Alcançada', 'color' => 'danger', 'icon' => 'times-circle']
            ];
            $statusInfo = $statusLabels[$goal['status']] ?? ['label' => $goal['status'], 'color' => 'secondary', 'icon' => 'question'];
            
            $typeLabels = [
                'individual' => 'Individual',
                'team' => 'Equipe',
                'company' => 'Empresa'
            ];
            $typeLabel = $typeLabels[$goal['goal_type']] ?? $goal['goal_type'];
            
            $deadlineClass = '';
            $deadlineWarning = '';
            if (!empty($goal['deadline'])) {
                $deadlineDate = new \DateTime($goal['deadline']);
                $today = new \DateTime();
                $diff = $deadlineDate->diff($today);
                if ($deadlineDate < $today && $goal['status'] !== 'achieved') {
                    $deadlineClass = 'text-danger';
                    $deadlineWarning = '<span class="badge bg-danger ms-2">Atrasada</span>';
                } elseif ($diff->days <= 7 && $goal['status'] !== 'achieved') {
                    $deadlineClass = 'text-warning';
                    $deadlineWarning = '<span class="badge bg-warning ms-2">Próximo do prazo</span>';
                }
            }
            ?>
            
            <div class="row g-4">
                <div class="col-md-8">
                    <h4 class="mb-3"><?= htmlspecialchars($goal['goal_title']) ?></h4>
                    
                    <?php if (!empty($goal['goal_description'])): ?>
                        <div class="mb-4">
                            <h6 class="text-muted">Descrição</h6>
                            <p><?= nl2br(htmlspecialchars($goal['goal_description'])) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted">Colaborador</h6>
                            <p class="mb-0"><i class="fas fa-user me-2"></i><?= htmlspecialchars($goal['employee_name'] ?? '') ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Tipo</h6>
                            <p class="mb-0"><span class="badge bg-info"><?= $typeLabel ?></span></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Status</h6>
                            <p class="mb-0">
                                <span class="badge bg-<?= $statusInfo['color'] ?>">
                                    <i class="fas fa-<?= $statusInfo['icon'] ?> me-1"></i>
                                    <?= $statusInfo['label'] ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Prazo <?= $deadlineWarning ?></h6>
                            <p class="mb-0 <?= $deadlineClass ?>">
                                <?php if (!empty($goal['deadline'])): ?>
                                    <i class="fas fa-calendar me-2"></i>
                                    <?= date('d/m/Y', strtotime($goal['deadline'])) ?>
                                <?php else: ?>
                                    <span class="text-muted">Não definido</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Progresso</h6>
                        </div>
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <div class="progress" style="height: 30px;">
                                    <div class="progress-bar <?= $progress >= 100 ? 'bg-success' : ($progress >= 70 ? 'bg-info' : ($progress >= 40 ? 'bg-warning' : 'bg-danger')) ?>" 
                                         role="progressbar" 
                                         style="width: <?= $progress ?>%"
                                         aria-valuenow="<?= $progress ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        <strong><?= $progress ?>%</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!empty($goal['target_value']) && !empty($goal['current_value'])): ?>
                                <div class="mb-2">
                                    <small class="text-muted">Valor Atual / Valor Alvo</small>
                                    <h5 class="mb-0">
                                        <?= number_format($goal['current_value'], 0) ?> / <?= number_format($goal['target_value'], 0) ?>
                                        <?php if (!empty($goal['unit'])): ?>
                                            <small class="text-muted"><?= htmlspecialchars($goal['unit']) ?></small>
                                        <?php endif; ?>
                                    </h5>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($goal['weight'])): ?>
                                <div class="mt-3">
                                    <small class="text-muted">Peso (Importância)</small>
                                    <p class="mb-0"><strong><?= number_format($goal['weight'], 1) ?></strong></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($goal['achieved_at'])): ?>
                <div class="alert alert-success mt-3">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Meta alcançada em:</strong> <?= date('d/m/Y H:i', strtotime($goal['achieved_at'])) ?>
                </div>
            <?php endif; ?>
            
            <div class="mt-4">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Criada em: <?= date('d/m/Y H:i', strtotime($goal['created_at'])) ?>
                    <?php if (!empty($goal['updated_at']) && $goal['updated_at'] !== $goal['created_at']): ?>
                        | Atualizada em: <?= date('d/m/Y H:i', strtotime($goal['updated_at'])) ?>
                    <?php endif; ?>
                </small>
            </div>
        </div>
    </div>
</div>

