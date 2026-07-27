<?php
use App\adms\Helpers\FormatHelper;
?>
<style>
    .wf-timeline {
        position: relative;
        padding-left: 1.75rem;
    }
    .wf-timeline::before {
        content: '';
        position: absolute;
        left: .55rem;
        top: .35rem;
        bottom: .35rem;
        width: 2px;
        background: #dee2e6;
    }
    .wf-timeline-item {
        position: relative;
        padding-bottom: 1.1rem;
    }
    .wf-timeline-item:last-child {
        padding-bottom: 0;
    }
    .wf-timeline-dot {
        position: absolute;
        left: -1.75rem;
        top: .15rem;
        width: 1.15rem;
        height: 1.15rem;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 0 0 2px #dee2e6;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .55rem;
        color: #fff;
        z-index: 1;
    }
    .wf-timeline-item.is-current .wf-timeline-dot {
        box-shadow: 0 0 0 2px #0d6efd;
        animation: wf-pulse 1.5s ease-in-out infinite;
    }
    @keyframes wf-pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.08); }
    }
    .wf-timeline-card {
        border: 1px solid #e9ecef;
        border-radius: .5rem;
        padding: .65rem .85rem;
        background: #fff;
    }
    .wf-timeline-item.is-current .wf-timeline-card {
        border-color: #b6d4fe;
        background: #f8fbff;
    }
</style>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Solicitação #<?= $this->data['request']['id'] ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-employee-requests" class="text-decoration-none">Solicitações</a>
            </li>
            <li class="breadcrumb-item">Visualizar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span><i class="fas fa-file-alt me-2"></i><?= htmlspecialchars($this->data['request']['title']) ?></span>
            <span class="ms-auto">
                <?php 
                // Verificar se pode editar (sem aprovações)
                $canEdit = empty($this->data['request']['manager_approved_by']) && 
                          empty($this->data['request']['hr_approved_by']) && 
                          !in_array($this->data['request']['status'], ['rejected', 'cancelled', 'approved']);
                if ($canEdit) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>update-employee-request/<?= $this->data['request']['id'] ?>" 
                       class="btn btn-sm btn-warning me-2">
                        <i class="fas fa-edit me-1"></i>Editar
                    </a>
                <?php } ?>
                <?php
                $log_resumo = $this->data['log_resumo'] ?? [];
                $log_btn_class = 'btn btn-outline-info btn-sm me-2';
                include __DIR__ . '/../partials/button_log_alteracoes.php';
                ?>
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-employee-requests" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Voltar
                </a>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td width="40%"><strong>Tipo:</strong></td>
                            <td>
                                <?php
                                $typeLabels = [
                                    'vacation' => 'Férias',
                                    'time_off' => 'Afastamento',
                                    'document' => 'Documento',
                                    'salary_advance' => 'Adiantamento Salarial',
                                    'other' => 'Outro'
                                ];
                                ?>
                                <span class="badge bg-info"><?= $typeLabels[$this->data['request']['request_type']] ?? $this->data['request']['request_type'] ?></span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Colaborador:</strong></td>
                            <td><?= htmlspecialchars($this->data['request']['employee_name'] ?? '') ?></td>
                        </tr>
                        <?php if ($this->data['request']['start_date']): ?>
                        <tr>
                            <td><strong>Data de Início:</strong></td>
                            <td><?= FormatHelper::formatDate($this->data['request']['start_date']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($this->data['request']['end_date']): ?>
                        <tr>
                            <td><strong>Data de Término:</strong></td>
                            <td><?= FormatHelper::formatDate($this->data['request']['end_date']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($this->data['request']['days_requested']): ?>
                        <tr>
                            <td><strong>Dias Solicitados:</strong></td>
                            <td><span class="badge bg-primary"><?= $this->data['request']['days_requested'] ?> dias</span></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td width="40%"><strong>Status:</strong></td>
                            <td>
                                <?php
                                $statusClass = match($this->data['request']['status']) {
                                    'pending' => 'warning',
                                    'pending_manager_approval' => 'info',
                                    'pending_hr_approval' => 'primary',
                                    'approved' => 'success',
                                    'rejected' => 'danger',
                                    'cancelled' => 'secondary',
                                    default => 'secondary'
                                };
                                $statusLabel = match($this->data['request']['status']) {
                                    'pending' => 'Pendente',
                                    'pending_manager_approval' => 'Aguardando Aprovação do Gestor',
                                    'pending_hr_approval' => 'Aguardando Aprovação do RH',
                                    'approved' => 'Aprovada',
                                    'rejected' => 'Rejeitada',
                                    'cancelled' => 'Cancelada',
                                    default => $this->data['request']['status']
                                };
                                ?>
                                <span class="badge bg-<?= $statusClass ?> fs-6"><?= $statusLabel ?></span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Data de Criação:</strong></td>
                            <td><?= FormatHelper::formatDate($this->data['request']['created_at'] ?? '') ?></td>
                        </tr>
                        <?php if ($this->data['request']['approved_at']): ?>
                        <tr>
                            <td><strong>Data de Aprovação:</strong></td>
                            <td><?= FormatHelper::formatDate($this->data['request']['approved_at']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($this->data['request']['approved_by']): ?>
                        <tr>
                            <td><strong>Aprovado por:</strong></td>
                            <td><?= htmlspecialchars($this->data['request']['approver_name'] ?? '') ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($this->data['request']['amount']): ?>
                        <tr>
                            <td><strong>Valor:</strong></td>
                            <td><span class="badge bg-success">R$ <?= number_format((float)$this->data['request']['amount'], 2, ',', '.') ?></span></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <?php if (!empty($this->data['request']['description'])): ?>
                <div class="mb-3">
                    <h5><i class="fas fa-align-left me-2"></i>Descrição</h5>
                    <div class="p-3 bg-light rounded">
                        <?= nl2br(htmlspecialchars($this->data['request']['description'])) ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Fluxo de Aprovação -->
            <?php
            $request = $this->data['request'];
            $userId = $_SESSION['user_id'] ?? 0;
            $isSuperAdmin = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
            $canApproveAsManager = !empty($this->data['can_approve_as_manager']);
            $canApproveAsHR = !empty($this->data['can_approve_as_hr']);
            ?>
            
            <?php if (!empty($request['current_approver_name']) && $request['status'] === 'pending_manager_approval'): ?>
                <div class="alert alert-info py-2">
                    Aprovador atual:
                    <strong><?= htmlspecialchars($request['current_approver_name']) ?></strong>
                    <?php if (!empty($request['original_approver_name'])
                        && $request['original_approver_name'] !== $request['current_approver_name']): ?>
                        <span class="text-muted">(original: <?= htmlspecialchars($request['original_approver_name']) ?>)</span>
                    <?php endif; ?>
                    <?php if (!empty($request['escalate_after_hours']) && !empty($request['stage_started_at'])): ?>
                        · SLA <?= (int) $request['escalate_after_hours'] ?>h desde
                        <?= date('d/m/Y H:i', strtotime($request['stage_started_at'])) ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($request['requires_manager_approval'] || $request['status'] === 'pending_hr_approval' || $request['status'] === 'approved'): ?>
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0"><i class="fas fa-check-double me-2"></i>Fluxo de Aprovação</h5>
                        <?php if (($isSuperAdmin || $canApproveAsHR) && in_array($request['status'], ['pending_manager_approval', 'pending_hr_approval'])): ?>
                            <span class="badge bg-info">
                                <i class="fas fa-user-shield me-1"></i>
                                <?= $isSuperAdmin ? 'Super Admin: ' : '' ?>Você pode aprovar esta solicitação
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="row g-3">
                        <!-- Aprovação do Gestor -->
                        <div class="col-md-6">
                            <div class="card <?= in_array($request['status'], ['pending_manager_approval', 'pending_hr_approval', 'approved']) ? 'border-primary' : 'border-secondary' ?>">
                                <div class="card-header bg-<?= !empty($request['manager_approved_by']) ? 'success' : ($request['status'] === 'pending_manager_approval' ? 'info' : 'secondary') ?> text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-user-tie me-2"></i>
                                        Aprovação do Gestor
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($request['manager_approved_by'])): ?>
                                        <p class="mb-1"><strong>Aprovado por:</strong> <?= htmlspecialchars($request['manager_name'] ?? '') ?></p>
                                        <p class="mb-0"><small class="text-muted">Em: <?= date('d/m/Y H:i', strtotime($request['manager_approved_at'])) ?></small></p>
                                    <?php elseif (!empty($request['manager_rejection_reason'])): ?>
                                        <p class="mb-1 text-danger"><strong>Rejeitado</strong></p>
                                        <p class="mb-1"><strong>Por:</strong> <?= htmlspecialchars($request['manager_name'] ?? '') ?></p>
                                        <p class="mb-1"><small class="text-muted">Em: <?= date('d/m/Y H:i', strtotime($request['manager_approved_at'])) ?></small></p>
                                        <div class="mt-2 p-2 bg-danger bg-opacity-10 rounded">
                                            <strong>Motivo:</strong> <?= nl2br(htmlspecialchars($request['manager_rejection_reason'])) ?>
                                        </div>
                                    <?php elseif ($request['status'] === 'pending_manager_approval'): ?>
                                        <p class="mb-2 text-info"><i class="fas fa-clock me-1"></i>Aguardando aprovação do gestor</p>
                                        <?php if ($canApproveAsManager): ?>
                                            <?php include './app/adms/Views/portal/partials/approve_manager_form.php'; ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <p class="mb-0 text-muted">Não requer aprovação do gestor</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Aprovação do RH -->
                        <div class="col-md-6">
                            <div class="card <?= in_array($request['status'], ['pending_hr_approval', 'approved']) ? 'border-primary' : 'border-secondary' ?>">
                                <div class="card-header bg-<?= !empty($request['hr_approved_by']) ? 'success' : ($request['status'] === 'pending_hr_approval' ? 'info' : 'secondary') ?> text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-users me-2"></i>
                                        Aprovação do RH
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($request['hr_approved_by'])): ?>
                                        <p class="mb-1"><strong>Aprovado por:</strong> <?= htmlspecialchars($request['hr_name'] ?? '') ?></p>
                                        <p class="mb-0"><small class="text-muted">Em: <?= date('d/m/Y H:i', strtotime($request['hr_approved_at'])) ?></small></p>
                                    <?php elseif (!empty($request['hr_rejection_reason'])): ?>
                                        <p class="mb-1 text-danger"><strong>Rejeitado</strong></p>
                                        <p class="mb-1"><strong>Por:</strong> <?= htmlspecialchars($request['hr_name'] ?? '') ?></p>
                                        <p class="mb-1"><small class="text-muted">Em: <?= date('d/m/Y H:i', strtotime($request['hr_approved_at'])) ?></small></p>
                                        <div class="mt-2 p-2 bg-danger bg-opacity-10 rounded">
                                            <strong>Motivo:</strong> <?= nl2br(htmlspecialchars($request['hr_rejection_reason'])) ?>
                                        </div>
                                    <?php elseif ($request['status'] === 'pending_hr_approval'): ?>
                                        <p class="mb-2 text-info"><i class="fas fa-clock me-1"></i>Aguardando aprovação do RH</p>
                                        <?php if ($canApproveAsHR): ?>
                                            <?php include './app/adms/Views/portal/partials/approve_hr_form.php'; ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <p class="mb-0 text-muted">Aguardando aprovação do gestor</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($this->data['request']['rejection_reason'])): ?>
                <div class="mb-3">
                    <h5><i class="fas fa-times-circle text-danger me-2"></i>Motivo da Rejeição</h5>
                    <div class="p-3 bg-danger bg-opacity-10 rounded border border-danger">
                        <?= nl2br(htmlspecialchars($this->data['request']['rejection_reason'])) ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php
            $events = $this->data['approval_events'] ?? [];
            $requestStatus = (string) ($request['status'] ?? '');
            $actionMeta = [
                'approved' => ['label' => 'Aprovado', 'icon' => 'fa-check', 'color' => '#198754', 'badge' => 'success'],
                'delegated_act' => ['label' => 'Aprovado por delegação', 'icon' => 'fa-user-friends', 'color' => '#0dcaf0', 'badge' => 'info'],
                'rejected' => ['label' => 'Rejeitado', 'icon' => 'fa-times', 'color' => '#dc3545', 'badge' => 'danger'],
                'escalated' => ['label' => 'Escalado', 'icon' => 'fa-level-up-alt', 'color' => '#fd7e14', 'badge' => 'warning'],
            ];
            $timelineItems = [];
            $timelineItems[] = [
                'label' => 'Solicitação criada',
                'icon' => 'fa-file-alt',
                'color' => '#6c757d',
                'badge' => 'secondary',
                'actor' => (string) ($request['employee_name'] ?? 'Colaborador'),
                'on_behalf' => null,
                'notes' => null,
                'created_at' => $request['created_at'] ?? null,
                'is_current' => false,
            ];
            foreach ($events as $ev) {
                $action = (string) ($ev['action'] ?? '');
                $meta = $actionMeta[$action] ?? ['label' => ucfirst($action), 'icon' => 'fa-circle', 'color' => '#6c757d', 'badge' => 'secondary'];
                $timelineItems[] = [
                    'label' => $meta['label'],
                    'icon' => $meta['icon'],
                    'color' => $meta['color'],
                    'badge' => $meta['badge'],
                    'actor' => (string) ($ev['actor_name'] ?? 'sistema'),
                    'on_behalf' => $ev['on_behalf_name'] ?? null,
                    'notes' => $ev['notes'] ?? null,
                    'stage_code' => $ev['stage_code'] ?? null,
                    'created_at' => $ev['created_at'] ?? null,
                    'is_current' => false,
                ];
            }
            if ($requestStatus === 'pending_manager_approval') {
                $pendingLabel = 'Aguardando aprovação';
                if (!empty($request['current_approver_name'])) {
                    $pendingLabel .= ' de ' . $request['current_approver_name'];
                } elseif (!empty($request['current_stage_code'])) {
                    $pendingLabel .= ' (' . $request['current_stage_code'] . ')';
                }
                $timelineItems[] = [
                    'label' => $pendingLabel,
                    'icon' => 'fa-hourglass-half',
                    'color' => '#0d6efd',
                    'badge' => 'primary',
                    'actor' => null,
                    'on_behalf' => null,
                    'notes' => !empty($request['stage_started_at'])
                        ? 'Desde ' . date('d/m/Y H:i', strtotime((string) $request['stage_started_at']))
                        : null,
                    'stage_code' => $request['current_stage_code'] ?? null,
                    'created_at' => null,
                    'is_current' => true,
                ];
            } elseif ($requestStatus === 'pending_hr_approval') {
                $timelineItems[] = [
                    'label' => 'Aguardando aprovação do RH',
                    'icon' => 'fa-hourglass-half',
                    'color' => '#6f42c1',
                    'badge' => 'primary',
                    'actor' => null,
                    'on_behalf' => null,
                    'notes' => !empty($request['stage_started_at'])
                        ? 'Desde ' . date('d/m/Y H:i', strtotime((string) $request['stage_started_at']))
                        : null,
                    'stage_code' => $request['current_stage_code'] ?? null,
                    'created_at' => null,
                    'is_current' => true,
                ];
            } elseif ($requestStatus === 'approved') {
                $timelineItems[] = [
                    'label' => 'Solicitação finalizada',
                    'icon' => 'fa-flag-checkered',
                    'color' => '#198754',
                    'badge' => 'success',
                    'actor' => null,
                    'on_behalf' => null,
                    'notes' => null,
                    'created_at' => $request['approved_at'] ?? null,
                    'is_current' => false,
                ];
            } elseif ($requestStatus === 'rejected') {
                $timelineItems[] = [
                    'label' => 'Solicitação rejeitada',
                    'icon' => 'fa-ban',
                    'color' => '#dc3545',
                    'badge' => 'danger',
                    'actor' => null,
                    'on_behalf' => null,
                    'notes' => $request['rejection_reason'] ?? null,
                    'created_at' => $request['manager_approved_at'] ?? $request['hr_approved_at'] ?? null,
                    'is_current' => false,
                ];
            }
            ?>
            <?php if (!empty($timelineItems)): ?>
                <div class="mb-3">
                    <h5 class="mb-3"><i class="fas fa-stream me-2"></i>Linha do tempo do workflow</h5>
                    <div class="wf-timeline">
                        <?php foreach ($timelineItems as $item): ?>
                            <div class="wf-timeline-item<?= !empty($item['is_current']) ? ' is-current' : '' ?>">
                                <div class="wf-timeline-dot" style="background: <?= htmlspecialchars((string) $item['color']) ?>">
                                    <i class="fas <?= htmlspecialchars((string) $item['icon']) ?>"></i>
                                </div>
                                <div class="wf-timeline-card">
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                        <span class="badge bg-<?= htmlspecialchars((string) $item['badge']) ?>">
                                            <?= htmlspecialchars((string) $item['label']) ?>
                                        </span>
                                        <?php if (!empty($item['stage_code'])): ?>
                                            <code class="small"><?= htmlspecialchars((string) $item['stage_code']) ?></code>
                                        <?php endif; ?>
                                        <?php if (!empty($item['created_at'])): ?>
                                            <small class="text-muted ms-auto"><?= date('d/m/Y H:i', strtotime((string) $item['created_at'])) ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($item['actor'])): ?>
                                        <div class="small">
                                            <i class="fas fa-user me-1 text-muted"></i>
                                            <?= htmlspecialchars((string) $item['actor']) ?>
                                            <?php if (!empty($item['on_behalf'])): ?>
                                                <span class="text-muted">(em nome de <?= htmlspecialchars((string) $item['on_behalf']) ?>)</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($item['notes'])): ?>
                                        <div class="small text-muted mt-1"><?= htmlspecialchars((string) $item['notes']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>


