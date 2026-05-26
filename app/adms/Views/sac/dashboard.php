<?php

?>

<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3 mobile-hide-page-title"><i class="fas fa-chart-line me-2"></i>Dashboard SAC</h2>

        <ol class="breadcrumb mb-3 ms-auto mobile-hide-breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>sac-dashboard" class="text-decoration-none">SAC</a></li>
            <li class="breadcrumb-item">Dashboard</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <!-- KPI Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                        <i class="fas fa-folder-open text-primary fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Chamados Abertos</h6>
                        <h3 class="mb-0 fw-bold"><?= (int)($this->data['open_count'] ?? 0) ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-danger bg-opacity-10 p-3 me-3">
                        <i class="fas fa-exclamation-triangle text-danger fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Chamados Críticos</h6>
                        <h3 class="mb-0 fw-bold"><?= (int)($this->data['critical_count'] ?? 0) ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-warning bg-opacity-10 p-3 me-3">
                        <i class="fas fa-clock text-warning fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">SLA Violados</h6>
                        <h3 class="mb-0 fw-bold"><?= (int)($this->data['sla_breached']['breached'] ?? 0) ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                        <i class="fas fa-hourglass-half text-success fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Tempo Médio Resolução</h6>
                        <h3 class="mb-0 fw-bold"><?= number_format($this->data['avg_resolution_time'] ?? 0, 1) ?>h</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Por Status / Por Categoria -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card mb-4 border-light shadow">
                <div class="card-header fw-semibold"><i class="fas fa-list-check me-2"></i>Por Status</div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th class="text-end">Quantidade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($this->data['counts_by_status'])): ?>
                                <?php foreach ($this->data['counts_by_status'] as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['status'] ?? '') ?></td>
                                        <td class="text-end"><?= (int)($row['total'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="2" class="text-muted text-center">Nenhum dado</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card mb-4 border-light shadow">
                <div class="card-header fw-semibold"><i class="fas fa-tags me-2"></i>Por Categoria</div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Categoria</th>
                                <th class="text-end">Quantidade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($this->data['counts_by_category'])): ?>
                                <?php foreach ($this->data['counts_by_category'] as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['category_name'] ?? '') ?></td>
                                        <td class="text-end"><?= (int)($row['total'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="2" class="text-muted text-center">Nenhum dado</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Por Prioridade / Por Canal -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card mb-4 border-light shadow">
                <div class="card-header fw-semibold"><i class="fas fa-flag me-2"></i>Por Prioridade</div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Prioridade</th>
                                <th class="text-end">Quantidade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($this->data['counts_by_priority'])): ?>
                                <?php foreach ($this->data['counts_by_priority'] as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['priority'] ?? '') ?></td>
                                        <td class="text-end"><?= (int)($row['total'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="2" class="text-muted text-center">Nenhum dado</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card mb-4 border-light shadow">
                <div class="card-header fw-semibold"><i class="fas fa-headset me-2"></i>Por Canal</div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Canal</th>
                                <th class="text-end">Quantidade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($this->data['counts_by_channel'])): ?>
                                <?php foreach ($this->data['counts_by_channel'] as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['channel'] ?? '') ?></td>
                                        <td class="text-end"><?= (int)($row['total'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="2" class="text-muted text-center">Nenhum dado</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Últimos Chamados -->
    <?php
    $statusBadges = [
        'Aberto' => 'primary',
        'Em análise' => 'info',
        'Em atendimento' => 'warning',
        'Aguardando cliente' => 'secondary',
        'Resolvido' => 'success',
        'Encerrado' => 'dark',
    ];
    $priorityBadges = [
        'Baixa' => 'secondary',
        'Média' => 'primary',
        'Alta' => 'warning',
        'Urgente' => 'danger',
    ];
    ?>
    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><i class="fas fa-history me-2"></i>Últimos Chamados</span>
            <a href="<?= $_ENV['URL_ADM'] ?>sac-list-tickets" class="btn btn-outline-primary btn-sm">Ver todos</a>
        </div>
        <div class="card-body p-0">

            <!-- Desktop: tabela -->
            <div class="d-none d-md-block">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Cliente</th>
                                <th>Assunto</th>
                                <th>Status</th>
                                <th>Prioridade</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($this->data['recent_tickets'])): ?>
                                <?php foreach ($this->data['recent_tickets'] as $ticket): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= $_ENV['URL_ADM'] ?>sac-view-ticket/<?= $ticket['id'] ?>" class="text-decoration-none fw-semibold">
                                                #<?= htmlspecialchars($ticket['code'] ?? '') ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($ticket['client_razao_social'] ?? $ticket['client_nome_fantasia'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($ticket['subject'] ?? '') ?></td>
                                        <td><span class="badge bg-<?= $statusBadges[$ticket['status'] ?? ''] ?? 'secondary' ?>"><?= htmlspecialchars($ticket['status'] ?? '') ?></span></td>
                                        <td><span class="badge bg-<?= $priorityBadges[$ticket['priority'] ?? ''] ?? 'secondary' ?>"><?= htmlspecialchars($ticket['priority'] ?? '') ?></span></td>
                                        <td><?= !empty($ticket['created_at']) ? date('d/m/Y H:i', strtotime($ticket['created_at'])) : '' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-muted text-center">Nenhum chamado recente</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Mobile: cards -->
            <div class="d-block d-md-none p-3">
                <?php if (!empty($this->data['recent_tickets'])): ?>
                    <?php foreach ($this->data['recent_tickets'] as $ticket):
                        $ticketUrl = $_ENV['URL_ADM'] . 'sac-view-ticket/' . $ticket['id'];
                        $isUrgent = ($ticket['priority'] ?? '') === 'Urgente';
                    ?>
                        <div class="card mb-2 shadow-sm<?= $isUrgent ? ' border-danger' : '' ?>" onclick="window.location.href='<?= $ticketUrl ?>';" style="cursor:pointer;">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1 min-width-0">
                                        <div class="fw-bold small">
                                            <span class="text-primary">#<?= htmlspecialchars($ticket['code'] ?? '') ?></span>
                                            <span class="ms-1"><?= htmlspecialchars($ticket['subject'] ?? '') ?></span>
                                        </div>
                                        <div class="small text-muted">
                                            <i class="fas fa-user me-1"></i><?= htmlspecialchars($ticket['client_razao_social'] ?? $ticket['client_nome_fantasia'] ?? '—') ?>
                                        </div>
                                    </div>
                                    <div class="text-end ms-2 flex-shrink-0">
                                        <span class="badge bg-<?= $statusBadges[$ticket['status'] ?? ''] ?? 'secondary' ?> d-block mb-1"><?= htmlspecialchars($ticket['status'] ?? '') ?></span>
                                        <span class="badge bg-<?= $priorityBadges[$ticket['priority'] ?? ''] ?? 'secondary' ?>"><?= htmlspecialchars($ticket['priority'] ?? '') ?></span>
                                    </div>
                                </div>
                                <div class="text-muted mt-1" style="font-size:.7rem;">
                                    <i class="fas fa-calendar me-1"></i><?= !empty($ticket['created_at']) ? date('d/m/Y H:i', strtotime($ticket['created_at'])) : '' ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-muted text-center py-3">Nenhum chamado recente</div>
                <?php endif; ?>
            </div>

        </div>
    </div>

</div>
