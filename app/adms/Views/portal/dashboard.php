<?php
use App\adms\Helpers\FormatHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Portal do Colaborador</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Portal do Colaborador</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <!-- Informações do Colaborador -->
    <?php if (!empty($this->data['employee_info'])): ?>
        <div class="card mb-4 border-light shadow">
            <div class="card-header">
                <span><i class="fas fa-user me-2"></i>Minhas Informações</span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Nome:</dt>
                            <dd class="col-sm-7"><strong><?= htmlspecialchars($this->data['employee_info']['name'] ?? '') ?></strong></dd>
                            
                            <dt class="col-sm-5">Departamento:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($this->data['employee_info']['dep_name'] ?? 'N/A') ?></dd>
                            
                            <?php if (!empty($this->data['employee_info']['data_admissao'])): ?>
                                <dt class="col-sm-5">Data de Admissão:</dt>
                                <dd class="col-sm-7"><?= date('d/m/Y', strtotime($this->data['employee_info']['data_admissao'])) ?></dd>
                            <?php endif; ?>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row mb-0">
                            <?php if (!empty($this->data['total_tenure'])): ?>
                                <dt class="col-sm-5">Tempo de Casa:</dt>
                                <dd class="col-sm-7">
                                    <strong class="text-success"><?= htmlspecialchars($this->data['total_tenure']['formatted']) ?></strong>
                                    <small class="text-muted d-block">(<?= $this->data['total_tenure']['total_periodos'] ?> período(s))</small>
                                </dd>
                            <?php endif; ?>
                            
                            <dt class="col-sm-5">Status:</dt>
                            <dd class="col-sm-7">
                                <span class="badge bg-<?= ($this->data['employee_info']['status'] ?? '') === 'Ativo' ? 'success' : 'secondary' ?>">
                                    <?= htmlspecialchars($this->data['employee_info']['status'] ?? 'N/A') ?>
                                </span>
                            </dd>
                            
                            <dt class="col-sm-5">E-mail:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($this->data['employee_info']['email'] ?? '') ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Cards de Resumo -->
        <div class="col-md-4">
            <div class="card border-primary shadow">
                <div class="card-body text-center">
                    <i class="fas fa-file-alt fa-3x text-primary mb-3"></i>
                    <h3 class="mb-0"><?= $this->data['total_requests'] ?? 0 ?></h3>
                    <p class="text-muted mb-0">Solicitações</p>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-employee-requests" class="btn btn-primary btn-sm mt-2">
                        Ver Todas
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-warning shadow">
                <div class="card-body text-center">
                    <i class="fas fa-ticket-alt fa-3x text-warning mb-3"></i>
                    <h3 class="mb-0"><?= $this->data['total_tickets'] ?? 0 ?></h3>
                    <p class="text-muted mb-0">Chamados</p>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-employee-tickets" class="btn btn-warning btn-sm mt-2">
                        Ver Todos
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-info shadow">
                <div class="card-body text-center">
                    <i class="fas fa-clock fa-3x text-info mb-3"></i>
                    <h3 class="mb-0"><?= count($this->data['pending_requests'] ?? []) ?></h3>
                    <p class="text-muted mb-0">Pendentes</p>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-employee-requests?status=pending" class="btn btn-info btn-sm mt-2">
                        Ver Pendentes
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-2">
        <!-- Solicitações Pendentes -->
        <div class="col-md-6">
            <div class="card mb-4 border-light shadow">
                <div class="card-header hstack gap-2">
                    <span><i class="fas fa-file-alt me-2"></i>Solicitações Pendentes</span>
                    <span class="ms-auto">
                        <?php if (in_array('CreateEmployeeRequest', $this->data['buttonPermission'] ?? [])) { ?>
                            <a href="<?php echo $_ENV['URL_ADM']; ?>create-employee-request" class="btn btn-sm btn-success">
                                <i class="fas fa-plus"></i> Nova
                            </a>
                        <?php } ?>
                    </span>
                </div>
                <div class="card-body">
                    <?php if (empty($this->data['pending_requests'])): ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>Nenhuma solicitação pendente.
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($this->data['pending_requests'] as $request): ?>
                                <a href="<?php echo $_ENV['URL_ADM']; ?>view-employee-request/<?= $request['id'] ?>" 
                                   class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?= htmlspecialchars($request['title']) ?></h6>
                                        <small>
                                            <span class="badge bg-warning"><?= htmlspecialchars($request['status']) ?></span>
                                        </small>
                                    </div>
                                    <p class="mb-1 text-muted">
                                        <small>
                                            <i class="fas fa-tag me-1"></i><?= htmlspecialchars($request['request_type']) ?>
                                            <?php if ($request['start_date']): ?>
                                                | <i class="fas fa-calendar me-1"></i><?= FormatHelper::formatDate($request['start_date']) ?>
                                            <?php endif; ?>
                                        </small>
                                    </p>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Chamados Abertos -->
        <div class="col-md-6">
            <div class="card mb-4 border-light shadow">
                <div class="card-header hstack gap-2">
                    <span><i class="fas fa-ticket-alt me-2"></i>Chamados Abertos</span>
                    <span class="ms-auto">
                        <?php if (in_array('CreateEmployeeTicket', $this->data['buttonPermission'] ?? [])) { ?>
                            <a href="<?php echo $_ENV['URL_ADM']; ?>create-employee-ticket" class="btn btn-sm btn-success">
                                <i class="fas fa-plus"></i> Novo
                            </a>
                        <?php } ?>
                    </span>
                </div>
                <div class="card-body">
                    <?php if (empty($this->data['open_tickets'])): ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>Nenhum chamado aberto.
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($this->data['open_tickets'] as $ticket): ?>
                                <a href="<?php echo $_ENV['URL_ADM']; ?>view-employee-ticket/<?= $ticket['id'] ?>" 
                                   class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?= htmlspecialchars($ticket['title']) ?></h6>
                                        <small>
                                            <span class="badge bg-<?= $ticket['priority'] === 'urgent' ? 'danger' : ($ticket['priority'] === 'high' ? 'warning' : 'info') ?>">
                                                <?= htmlspecialchars($ticket['priority']) ?>
                                            </span>
                                        </small>
                                    </div>
                                    <p class="mb-1 text-muted">
                                        <small>
                                            <i class="fas fa-tag me-1"></i><?= htmlspecialchars($ticket['ticket_type']) ?>
                                            | <i class="fas fa-clock me-1"></i><?= FormatHelper::formatDate($ticket['created_at']) ?>
                                        </small>
                                    </p>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Ações Rápidas -->
    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <span><i class="fas fa-bolt me-2"></i>Ações Rápidas</span>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <?php if (in_array('CreateEmployeeRequest', $this->data['buttonPermission'] ?? [])) { ?>
                    <div class="col-md-3">
                        <a href="<?php echo $_ENV['URL_ADM']; ?>create-employee-request?request_type=vacation" class="btn btn-outline-primary w-100">
                            <i class="fas fa-umbrella-beach me-2"></i>Solicitar Férias
                        </a>
                    </div>
                <?php } ?>
                <?php if (in_array('CreateEmployeeRequest', $this->data['buttonPermission'] ?? [])) { ?>
                    <div class="col-md-3">
                        <a href="<?php echo $_ENV['URL_ADM']; ?>create-employee-request?request_type=time_off" class="btn btn-outline-info w-100">
                            <i class="fas fa-calendar-times me-2"></i>Solicitar Afastamento
                        </a>
                    </div>
                <?php } ?>
                <?php if (in_array('CreateEmployeeTicket', $this->data['buttonPermission'] ?? [])) { ?>
                    <div class="col-md-3">
                        <a href="<?php echo $_ENV['URL_ADM']; ?>create-employee-ticket" class="btn btn-outline-warning w-100">
                            <i class="fas fa-ticket-alt me-2"></i>Abrir Chamado
                        </a>
                    </div>
                <?php } ?>
                <?php if (in_array('ListEmployeeRequests', $this->data['buttonPermission'] ?? [])) { ?>
                    <div class="col-md-3">
                        <a href="<?php echo $_ENV['URL_ADM']; ?>list-employee-requests" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-list me-2"></i>Ver Todas Solicitações
                        </a>
                    </div>
                <?php } ?>
                <?php if (in_array('MyPayrollDocuments', $this->data['buttonPermission'] ?? [])) { ?>
                    <div class="col-md-3">
                        <a href="<?php echo $_ENV['URL_ADM']; ?>my-payroll-documents" class="btn btn-outline-dark w-100">
                            <i class="fas fa-file-invoice-dollar me-2"></i>Meus documentos (folha)
                        </a>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

