<?php
use App\adms\Helpers\FormatHelper;
$userRole = $this->data['userRole'] ?? 'none';
$roleLabel = match($userRole) {
    'manager' => 'Gestor',
    'hr' => 'RH',
    default => 'Usuário'
};
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Solicitações Pendentes de Aprovação</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>employee-portal" class="text-decoration-none">Portal</a>
            </li>
            <li class="breadcrumb-item">Aprovações Pendentes</li>
        </ol>
    </div>
    
    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <span>
                    <i class="fas fa-clock me-2"></i>
                    <?php if ($userRole === 'manager'): ?>
                        Solicitações Aguardando Minha Aprovação (Gestor)
                    <?php elseif ($userRole === 'hr'): ?>
                        Solicitações Aguardando Aprovação do RH
                    <?php else: ?>
                        Solicitações Pendentes
                    <?php endif; ?>
                </span>
                <span class="ms-auto d-flex gap-2 align-items-center">
                    <?php if ($userRole === 'manager'): ?>
                        <?php $mode = $_GET['mode'] ?? 'action'; ?>
                        <a href="<?= $_ENV['URL_ADM']; ?>pending-approvals?mode=action"
                           class="btn btn-sm <?= $mode !== 'team' ? 'btn-info' : 'btn-outline-info' ?>">Para aprovar</a>
                        <a href="<?= $_ENV['URL_ADM']; ?>pending-approvals?mode=team"
                           class="btn btn-sm <?= $mode === 'team' ? 'btn-info' : 'btn-outline-info' ?>">Acompanhar equipe</a>
                    <?php endif; ?>
                    <?php if (in_array('ListApprovalDelegations', $this->data['buttonPermission'] ?? [], true)): ?>
                        <a href="<?= $_ENV['URL_ADM']; ?>list-approval-delegations" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-user-clock me-1"></i>Delegações
                        </a>
                    <?php endif; ?>
                    <span class="badge bg-<?= $userRole === 'manager' ? 'info' : 'primary' ?> fs-6">
                        <?= $this->data['totalRecords'] ?? 0 ?> pendente(s)
                    </span>
                </span>
            </div>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            
            <?php if ($userRole === 'none'): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Você não possui permissões para aprovar solicitações.
                </div>
            <?php elseif (empty($this->data['requests'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Ótimo!</strong> Não há solicitações pendentes de aprovação no momento.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Colaborador</th>
                                <th>Título</th>
                                <th>Tipo</th>
                                <th>Período</th>
                                <th>Status</th>
                                <th>Data</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->data['requests'] as $request): ?>
                                <tr>
                                    <td><?= $request['id'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($request['employee_name'] ?? '') ?></strong>
                                        <?php if (!empty($request['employee_email'])): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($request['employee_email']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= htmlspecialchars($request['title']) ?></strong></td>
                                    <td>
                                        <?php
                                        $typeLabels = [
                                            'vacation' => 'Férias',
                                            'time_off' => 'Afastamento',
                                            'document' => 'Documento',
                                            'salary_advance' => 'Adiantamento',
                                            'other' => 'Outro'
                                        ];
                                        ?>
                                        <span class="badge bg-info"><?= $typeLabels[$request['request_type']] ?? $request['request_type'] ?></span>
                                    </td>
                                    <td>
                                        <?php if ($request['start_date'] && $request['end_date']): ?>
                                            <?= FormatHelper::formatDate($request['start_date']) ?> a 
                                            <?= FormatHelper::formatDate($request['end_date']) ?>
                                            <?php if ($request['days_requested']): ?>
                                                <br><small class="text-muted"><?= $request['days_requested'] ?> dia(s)</small>
                                            <?php endif; ?>
                                        <?php elseif ($request['start_date']): ?>
                                            <?= FormatHelper::formatDate($request['start_date']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = match($request['status']) {
                                            'pending_manager_approval' => 'info',
                                            'pending_hr_approval' => 'primary',
                                            default => 'warning'
                                        };
                                        $statusLabel = match($request['status']) {
                                            'pending_manager_approval' => 'Aguardando Gestor',
                                            'pending_hr_approval' => 'Aguardando RH',
                                            default => $request['status']
                                        };
                                        ?>
                                        <span class="badge bg-<?= $statusClass ?>"><?= $statusLabel ?></span>
                                    </td>
                                    <td><?= FormatHelper::formatDate($request['created_at'] ?? '') ?></td>
                                    <td class="text-center">
                                        <a href="<?php echo $_ENV['URL_ADM']; ?>view-employee-request/<?= $request['id'] ?>" 
                                           class="btn btn-sm btn-primary" title="Visualizar e Aprovar">
                                            <i class="fas fa-eye me-1"></i>Ver e Aprovar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (isset($this->data['pagination'])): ?>
                    <div class="mt-3">
                        <?= $this->data['pagination'] ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

