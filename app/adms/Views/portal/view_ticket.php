<?php
use App\adms\Helpers\FormatHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Chamado #<?= $this->data['ticket']['id'] ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-employee-tickets" class="text-decoration-none">Chamados</a>
            </li>
            <li class="breadcrumb-item">Visualizar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span><i class="fas fa-ticket-alt me-2"></i><?= htmlspecialchars($this->data['ticket']['title']) ?></span>
            <span class="ms-auto">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-employee-tickets" class="btn btn-sm btn-secondary">
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
                                <span class="badge bg-info"><?= htmlspecialchars($this->data['ticket']['ticket_type']) ?></span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Prioridade:</strong></td>
                            <td>
                                <?php
                                $priorityClass = match($this->data['ticket']['priority']) {
                                    'urgent' => 'danger',
                                    'high' => 'warning',
                                    'medium' => 'info',
                                    'low' => 'secondary',
                                    default => 'secondary'
                                };
                                $priorityLabel = match($this->data['ticket']['priority']) {
                                    'urgent' => 'Urgente',
                                    'high' => 'Alta',
                                    'medium' => 'Média',
                                    'low' => 'Baixa',
                                    default => $this->data['ticket']['priority']
                                };
                                ?>
                                <span class="badge bg-<?= $priorityClass ?> fs-6"><?= $priorityLabel ?></span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Colaborador:</strong></td>
                            <td><?= htmlspecialchars($this->data['ticket']['employee_name'] ?? '') ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td width="40%"><strong>Status:</strong></td>
                            <td>
                                <?php
                                $statusClass = match($this->data['ticket']['status']) {
                                    'open' => 'warning',
                                    'in_progress' => 'info',
                                    'resolved' => 'success',
                                    'closed' => 'secondary',
                                    default => 'secondary'
                                };
                                $statusLabel = match($this->data['ticket']['status']) {
                                    'open' => 'Aberto',
                                    'in_progress' => 'Em Andamento',
                                    'resolved' => 'Resolvido',
                                    'closed' => 'Fechado',
                                    default => $this->data['ticket']['status']
                                };
                                ?>
                                <span class="badge bg-<?= $statusClass ?> fs-6"><?= $statusLabel ?></span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Data de Abertura:</strong></td>
                            <td><?= FormatHelper::formatDateTime($this->data['ticket']['created_at'] ?? '') ?></td>
                        </tr>
                        <?php if ($this->data['ticket']['resolved_at']): ?>
                        <tr>
                            <td><strong>Data de Resolução:</strong></td>
                            <td><?= FormatHelper::formatDateTime($this->data['ticket']['resolved_at']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($this->data['ticket']['assigned_to']): ?>
                        <tr>
                            <td><strong>Atribuído a:</strong></td>
                            <td><?= htmlspecialchars($this->data['ticket']['assignee_name'] ?? '') ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <?php if (!empty($this->data['ticket']['description'])): ?>
                <div class="mb-3">
                    <h5><i class="fas fa-align-left me-2"></i>Descrição</h5>
                    <div class="p-3 bg-light rounded">
                        <?= nl2br(htmlspecialchars($this->data['ticket']['description'])) ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($this->data['ticket']['resolution'])): ?>
                <div class="mb-3">
                    <h5><i class="fas fa-check-circle text-success me-2"></i>Resolução</h5>
                    <div class="p-3 bg-success bg-opacity-10 rounded border border-success">
                        <?= nl2br(htmlspecialchars($this->data['ticket']['resolution'])) ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($this->data['history'])): ?>
                <div class="mb-3">
                    <h5><i class="fas fa-history me-2"></i>Histórico</h5>
                    <div class="list-group">
                        <?php foreach ($this->data['history'] as $entry): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?= htmlspecialchars($entry['action'] ?? '') ?></h6>
                                    <small><?= FormatHelper::formatDateTime($entry['created_at'] ?? '') ?></small>
                                </div>
                                <?php if (!empty($entry['comment'])): ?>
                                    <p class="mb-1"><?= nl2br(htmlspecialchars($entry['comment'])) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($entry['user_name'])): ?>
                                    <small class="text-muted">Por: <?= htmlspecialchars($entry['user_name']) ?></small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

