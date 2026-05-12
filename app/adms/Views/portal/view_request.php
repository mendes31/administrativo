<?php
use App\adms\Helpers\FormatHelper;
?>
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
            $isManager = !$isSuperAdmin && !empty($request['immediate_supervisor_id']) && $request['immediate_supervisor_id'] == $userId;
            $canApproveAsManager = ($request['status'] === 'pending_manager_approval') && ($isSuperAdmin || $isManager);
            $canApproveAsHR = ($request['status'] === 'pending_hr_approval') && $isSuperAdmin; // TODO: Adicionar verificação de permissão RH
            ?>
            
            <?php if ($request['requires_manager_approval'] || $request['status'] === 'pending_hr_approval' || $request['status'] === 'approved'): ?>
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0"><i class="fas fa-check-double me-2"></i>Fluxo de Aprovação</h5>
                        <?php if ($isSuperAdmin && in_array($request['status'], ['pending_manager_approval', 'pending_hr_approval'])): ?>
                            <span class="badge bg-info">
                                <i class="fas fa-user-shield me-1"></i>Super Admin: Você pode aprovar esta solicitação
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
        </div>
    </div>
</div>


