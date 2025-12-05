<?php
use App\adms\Helpers\FormatHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Minhas Solicitações</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>employee-portal" class="text-decoration-none">Portal</a>
            </li>
            <li class="breadcrumb-item">Solicitações</li>
        </ol>
    </div>
    
    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2 flex-wrap">
            <span><i class="fas fa-file-alt me-2"></i>Minhas Solicitações</span>
            <span class="ms-auto">
                <?php if (in_array('CreateEmployeeRequest', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>create-employee-request" class="btn btn-success btn-sm mb-1">
                        <i class="fa-solid fa-plus"></i> Nova Solicitação
                    </a>
                <?php } ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form method="GET" class="row g-2 mb-3 align-items-end">
                <div class="col-md-3">
                    <label for="request_type" class="form-label mb-1">Tipo</label>
                    <select name="request_type" id="request_type" class="form-select">
                        <option value="">Todos</option>
                        <option value="vacation" <?= (($this->data['filters']['request_type'] ?? '') === 'vacation') ? 'selected' : '' ?>>Férias</option>
                        <option value="time_off" <?= (($this->data['filters']['request_type'] ?? '') === 'time_off') ? 'selected' : '' ?>>Afastamento</option>
                        <option value="document" <?= (($this->data['filters']['request_type'] ?? '') === 'document') ? 'selected' : '' ?>>Documento</option>
                        <option value="salary_advance" <?= (($this->data['filters']['request_type'] ?? '') === 'salary_advance') ? 'selected' : '' ?>>Adiantamento</option>
                        <option value="other" <?= (($this->data['filters']['request_type'] ?? '') === 'other') ? 'selected' : '' ?>>Outro</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label mb-1">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Todos</option>
                        <option value="pending_manager_approval" <?= (($this->data['filters']['status'] ?? '') === 'pending_manager_approval') ? 'selected' : '' ?>>Aguardando Gestor</option>
                        <option value="pending_hr_approval" <?= (($this->data['filters']['status'] ?? '') === 'pending_hr_approval') ? 'selected' : '' ?>>Aguardando RH</option>
                        <option value="approved" <?= (($this->data['filters']['status'] ?? '') === 'approved') ? 'selected' : '' ?>>Aprovada</option>
                        <option value="rejected" <?= (($this->data['filters']['status'] ?? '') === 'rejected') ? 'selected' : '' ?>>Rejeitada</option>
                        <option value="cancelled" <?= (($this->data['filters']['status'] ?? '') === 'cancelled') ? 'selected' : '' ?>>Cancelada</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100 mb-2"><i class="fas fa-search"></i> Filtrar</button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-employee-requests?limpar=1" class="btn btn-secondary w-100">
                        <i class="fas fa-times"></i> Limpar
                    </a>
                </div>
            </form>

            <?php if (empty($this->data['requests'])): ?>
                <div class="alert alert-info" role="alert">
                    <i class="fas fa-info-circle me-2"></i>Nenhuma solicitação encontrada.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
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
                                            'approved' => 'success',
                                            'rejected' => 'danger',
                                            'cancelled' => 'secondary',
                                            default => 'warning'
                                        };
                                        $statusLabel = match($request['status']) {
                                            'pending_manager_approval' => 'Aguardando Gestor',
                                            'pending_hr_approval' => 'Aguardando RH',
                                            'approved' => 'Aprovada',
                                            'rejected' => 'Rejeitada',
                                            'cancelled' => 'Cancelada',
                                            default => $request['status']
                                        };
                                        ?>
                                        <span class="badge bg-<?= $statusClass ?>"><?= $statusLabel ?></span>
                                    </td>
                                    <td><?= FormatHelper::formatDate($request['created_at'] ?? '') ?></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <?php if (in_array('ViewEmployeeRequest', $this->data['buttonPermission'] ?? [])) { ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>view-employee-request/<?= $request['id'] ?>" 
                                                   class="btn btn-info" title="Visualizar">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            <?php } ?>
                                            <?php 
                                            // Verificar se pode editar (sem aprovações)
                                            $canEdit = empty($request['manager_approved_by']) && 
                                                      empty($request['hr_approved_by']) && 
                                                      !in_array($request['status'], ['rejected', 'cancelled', 'approved']);
                                            if ($canEdit && in_array('UpdateEmployeeRequest', $this->data['buttonPermission'] ?? [])) { ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>update-employee-request/<?= $request['id'] ?>" 
                                                   class="btn btn-warning" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php } ?>
                                        </div>
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

