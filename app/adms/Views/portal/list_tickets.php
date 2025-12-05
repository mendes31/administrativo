<?php
use App\adms\Helpers\FormatHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Meus Chamados</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>employee-portal" class="text-decoration-none">Portal</a>
            </li>
            <li class="breadcrumb-item">Chamados</li>
        </ol>
    </div>
    
    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2 flex-wrap">
            <span><i class="fas fa-ticket-alt me-2"></i>Meus Chamados</span>
            <span class="ms-auto">
                <?php if (in_array('CreateEmployeeTicket', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>create-employee-ticket" class="btn btn-success btn-sm mb-1">
                        <i class="fa-solid fa-plus"></i> Novo Chamado
                    </a>
                <?php } ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form method="GET" class="row g-2 mb-3 align-items-end">
                <div class="col-md-3">
                    <label for="ticket_type" class="form-label mb-1">Tipo</label>
                    <select name="ticket_type" id="ticket_type" class="form-select">
                        <option value="">Todos</option>
                        <option value="technical" <?= (($this->data['filters']['ticket_type'] ?? '') === 'technical') ? 'selected' : '' ?>>Técnico</option>
                        <option value="hr" <?= (($this->data['filters']['ticket_type'] ?? '') === 'hr') ? 'selected' : '' ?>>RH</option>
                        <option value="it" <?= (($this->data['filters']['ticket_type'] ?? '') === 'it') ? 'selected' : '' ?>>TI</option>
                        <option value="other" <?= (($this->data['filters']['ticket_type'] ?? '') === 'other') ? 'selected' : '' ?>>Outro</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label mb-1">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Todos</option>
                        <option value="open" <?= (($this->data['filters']['status'] ?? '') === 'open') ? 'selected' : '' ?>>Aberto</option>
                        <option value="in_progress" <?= (($this->data['filters']['status'] ?? '') === 'in_progress') ? 'selected' : '' ?>>Em Andamento</option>
                        <option value="resolved" <?= (($this->data['filters']['status'] ?? '') === 'resolved') ? 'selected' : '' ?>>Resolvido</option>
                        <option value="closed" <?= (($this->data['filters']['status'] ?? '') === 'closed') ? 'selected' : '' ?>>Fechado</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100 mb-2"><i class="fas fa-search"></i> Filtrar</button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-employee-tickets?limpar=1" class="btn btn-secondary w-100">
                        <i class="fas fa-times"></i> Limpar
                    </a>
                </div>
            </form>

            <?php if (empty($this->data['tickets'])): ?>
                <div class="alert alert-info" role="alert">
                    <i class="fas fa-info-circle me-2"></i>Nenhum chamado encontrado.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Título</th>
                                <th>Tipo</th>
                                <th>Prioridade</th>
                                <th>Status</th>
                                <th>Data</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->data['tickets'] as $ticket): ?>
                                <tr>
                                    <td><?= $ticket['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($ticket['title']) ?></strong></td>
                                    <td>
                                        <span class="badge bg-info"><?= htmlspecialchars($ticket['ticket_type']) ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $priorityClass = match($ticket['priority']) {
                                            'urgent' => 'danger',
                                            'high' => 'warning',
                                            'medium' => 'info',
                                            'low' => 'secondary',
                                            default => 'secondary'
                                        };
                                        $priorityLabel = match($ticket['priority']) {
                                            'urgent' => 'Urgente',
                                            'high' => 'Alta',
                                            'medium' => 'Média',
                                            'low' => 'Baixa',
                                            default => $ticket['priority']
                                        };
                                        ?>
                                        <span class="badge bg-<?= $priorityClass ?>"><?= $priorityLabel ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = match($ticket['status']) {
                                            'open' => 'warning',
                                            'in_progress' => 'info',
                                            'resolved' => 'success',
                                            'closed' => 'secondary',
                                            default => 'secondary'
                                        };
                                        $statusLabel = match($ticket['status']) {
                                            'open' => 'Aberto',
                                            'in_progress' => 'Em Andamento',
                                            'resolved' => 'Resolvido',
                                            'closed' => 'Fechado',
                                            default => $ticket['status']
                                        };
                                        ?>
                                        <span class="badge bg-<?= $statusClass ?>"><?= $statusLabel ?></span>
                                    </td>
                                    <td><?= FormatHelper::formatDate($ticket['created_at'] ?? '') ?></td>
                                    <td class="text-center">
                                        <?php if (in_array('ViewEmployeeTicket', $this->data['buttonPermission'] ?? [])) { ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>view-employee-ticket/<?= $ticket['id'] ?>" 
                                               class="btn btn-sm btn-info" title="Visualizar">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        <?php } ?>
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

