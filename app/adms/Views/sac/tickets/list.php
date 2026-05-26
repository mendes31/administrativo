<?php

use App\adms\Helpers\CSRFHelper;

$csrf_token = CSRFHelper::generateCSRFToken('form_delete_ticket');

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

<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3 mobile-hide-page-title"><i class="fas fa-ticket-alt me-2"></i>Chamados SAC</h2>

        <ol class="breadcrumb mb-3 ms-auto mobile-hide-breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>sac-dashboard" class="text-decoration-none">SAC</a></li>
            <li class="breadcrumb-item">Chamados</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Listar</span>
            <span class="ms-auto d-flex flex-wrap gap-1">
                <?php
                if (in_array('SacCreateTicket', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}sac-create-ticket' class='btn btn-success btn-sm'><i class='fa-regular fa-square-plus'></i> Novo Chamado</a> ";
                }
                ?>
            </span>
        </div>

        <div class="card-body">

            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <!-- Filtros -->
            <div class="d-md-none mb-2">
                <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#sacTicketsFiltersCollapse" aria-expanded="false" aria-controls="sacTicketsFiltersCollapse">
                    <i class="fa fa-filter me-1"></i> Abrir filtros
                </button>
            </div>
            <div class="collapse d-md-block" id="sacTicketsFiltersCollapse">
                <form method="get" class="row g-2 mb-3 align-items-end">
                    <div class="col-6 col-sm-4 col-md-2">
                        <label for="search" class="form-label" style="font-size:.7rem;">Busca</label>
                        <input type="text" name="search" id="search" class="form-control form-control-sm" placeholder="Código, assunto..." value="<?= htmlspecialchars($this->data['filtros']['search'] ?? '') ?>">
                    </div>
                    <div class="col-6 col-sm-4 col-md-2">
                        <label for="status" class="form-label" style="font-size:.7rem;">Status</label>
                        <select name="status" id="status" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <?php foreach ($statusBadges as $st => $c): ?>
                                <option value="<?= $st ?>" <?= ($this->data['filtros']['status'] ?? '') === $st ? 'selected' : '' ?>><?= $st ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-sm-4 col-md-2">
                        <label for="priority" class="form-label" style="font-size:.7rem;">Prioridade</label>
                        <select name="priority" id="priority" class="form-select form-select-sm">
                            <option value="">Todas</option>
                            <?php foreach ($priorityBadges as $pr => $c): ?>
                                <option value="<?= $pr ?>" <?= ($this->data['filtros']['priority'] ?? '') === $pr ? 'selected' : '' ?>><?= $pr ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-sm-4 col-md-2">
                        <label for="category_id" class="form-label" style="font-size:.7rem;">Categoria</label>
                        <select name="category_id" id="category_id" class="form-select form-select-sm">
                            <option value="">Todas</option>
                            <?php foreach ($this->data['categories'] ?? [] as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($this->data['filtros']['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-sm-4 col-md-2">
                        <label for="channel" class="form-label" style="font-size:.7rem;">Canal</label>
                        <select name="channel" id="channel" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <?php foreach (['WhatsApp', 'E-mail', 'Telefone', 'Portal'] as $ch): ?>
                                <option value="<?= $ch ?>" <?= ($this->data['filtros']['channel'] ?? '') === $ch ? 'selected' : '' ?>><?= $ch ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-sm-4 col-md-2">
                        <label for="date_from" class="form-label" style="font-size:.7rem;">Data de</label>
                        <input type="date" name="date_from" id="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($this->data['filtros']['date_from'] ?? '') ?>">
                    </div>
                    <div class="col-6 col-sm-4 col-md-2">
                        <label for="date_to" class="form-label" style="font-size:.7rem;">Data até</label>
                        <input type="date" name="date_to" id="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($this->data['filtros']['date_to'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-sm-auto d-flex gap-2 flex-wrap align-items-end">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Filtrar</button>
                        <a href="?limpar_filtros=1" class="btn btn-secondary btn-sm"><i class="fa fa-times"></i> Limpar</a>
                    </div>
                </form>
            </div>

            <?php if (!empty($this->data['tickets'])): ?>

                <?php
                // SLA calculation shared by desktop and mobile
                $ticketsWithSla = [];
                foreach ($this->data['tickets'] as $ticket) {
                    $now = time();
                    $slaRespDeadline = $ticket['sla_response_deadline'] ?? null;
                    $slaResolDeadline = $ticket['sla_resolution_deadline'] ?? null;
                    $slaRespBreached = !empty($ticket['sla_response_breached']);
                    $slaResolBreached = !empty($ticket['sla_resolution_breached']);
                    $isFinished = in_array($ticket['status'] ?? '', ['Resolvido', 'Encerrado']);

                    $slaRespIcon = '';
                    $slaResolIcon = '';
                    $slaRespLabel = '';
                    $slaResolLabel = '';

                    if ($isFinished) {
                        $slaRespIcon = $slaRespBreached
                            ? '<i class="fas fa-circle text-danger"></i>'
                            : '<i class="fas fa-circle text-success"></i>';
                        $slaResolIcon = $slaResolBreached
                            ? '<i class="fas fa-circle text-danger"></i>'
                            : '<i class="fas fa-circle text-success"></i>';
                        $slaRespLabel = $slaRespBreached ? 'Resp: violado' : 'Resp: OK';
                        $slaResolLabel = $slaResolBreached ? 'Resol: violado' : 'Resol: OK';
                    } elseif ($slaRespDeadline || $slaResolDeadline) {
                        if ($slaRespBreached) {
                            $slaRespIcon = '<i class="fas fa-exclamation-circle text-danger"></i>';
                            $slaRespLabel = 'Resp: VIOLADO';
                        } elseif ($slaRespDeadline) {
                            $respTs = strtotime($slaRespDeadline);
                            $hoursLeft = ($respTs - $now) / 3600;
                            if ($hoursLeft <= 0) {
                                $slaRespIcon = '<i class="fas fa-exclamation-circle text-danger"></i>';
                                $slaRespLabel = 'Resp: expirado';
                            } elseif ($hoursLeft <= 2) {
                                $slaRespIcon = '<i class="fas fa-clock text-warning"></i>';
                                $slaRespLabel = 'Resp: ' . round($hoursLeft, 1) . 'h';
                            } else {
                                $slaRespIcon = '<i class="fas fa-clock text-success"></i>';
                                $slaRespLabel = 'Resp: ' . round($hoursLeft, 1) . 'h';
                            }
                        }

                        if ($slaResolBreached) {
                            $slaResolIcon = '<i class="fas fa-exclamation-circle text-danger"></i>';
                            $slaResolLabel = 'Resol: VIOLADO';
                        } elseif ($slaResolDeadline) {
                            $resolTs = strtotime($slaResolDeadline);
                            $hoursLeft = ($resolTs - $now) / 3600;
                            if ($hoursLeft <= 0) {
                                $slaResolIcon = '<i class="fas fa-exclamation-circle text-danger"></i>';
                                $slaResolLabel = 'Resol: expirado';
                            } elseif ($hoursLeft <= 4) {
                                $slaResolIcon = '<i class="fas fa-clock text-warning"></i>';
                                $slaResolLabel = 'Resol: ' . round($hoursLeft, 1) . 'h';
                            } else {
                                $slaResolIcon = '<i class="fas fa-clock text-success"></i>';
                                $slaResolLabel = 'Resol: ' . round($hoursLeft, 1) . 'h';
                            }
                        }
                    }

                    $ticket['_slaRespIcon'] = $slaRespIcon;
                    $ticket['_slaResolIcon'] = $slaResolIcon;
                    $ticket['_slaRespLabel'] = $slaRespLabel;
                    $ticket['_slaResolLabel'] = $slaResolLabel;
                    $ticketsWithSla[] = $ticket;
                }
                ?>

                <!-- Tabela Desktop -->
                <div class="d-none d-md-block">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Cliente</th>
                                    <th>Assunto</th>
                                    <th>Produto</th>
                                    <th>Lote</th>
                                    <th>Categoria</th>
                                    <th>Prioridade</th>
                                    <th>Canal</th>
                                    <th>Status</th>
                                    <th>Atendente</th>
                                    <th class="text-center">SLA</th>
                                    <th>Data</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ticketsWithSla as $ticket): ?>
                                    <tr>
                                        <td><a href="<?= $_ENV['URL_ADM'] ?>sac-view-ticket/<?= $ticket['id'] ?>" class="text-decoration-none fw-semibold">#<?= htmlspecialchars($ticket['code'] ?? '') ?></a></td>
                                        <td><?= htmlspecialchars($ticket['client_razao_social'] ?? $ticket['client_nome_fantasia'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($ticket['subject'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($ticket['product'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($ticket['batch'] ?? '') ?></td>
                                        <td>
                                            <?php if (!empty($ticket['category_name'])): ?>
                                                <span class="badge" style="background-color:<?= htmlspecialchars($ticket['category_color'] ?? '#6c757d') ?>"><?= htmlspecialchars($ticket['category_name']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge bg-<?= $priorityBadges[$ticket['priority'] ?? ''] ?? 'secondary' ?>"><?= htmlspecialchars($ticket['priority'] ?? '') ?></span></td>
                                        <td><?= htmlspecialchars($ticket['channel'] ?? '') ?></td>
                                        <td><span class="badge bg-<?= $statusBadges[$ticket['status'] ?? ''] ?? 'secondary' ?>"><?= htmlspecialchars($ticket['status'] ?? '') ?></span></td>
                                        <td><?= htmlspecialchars($ticket['assigned_name'] ?? '') ?></td>
                                        <td class="text-center text-nowrap">
                                            <?php if ($ticket['_slaRespIcon'] || $ticket['_slaResolIcon']): ?>
                                                <span title="Resposta"><?= $ticket['_slaRespIcon'] ?></span>
                                                <span title="Resolução" class="ms-1"><?= $ticket['_slaResolIcon'] ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-nowrap"><?= !empty($ticket['created_at']) ? date('d/m/Y H:i', strtotime($ticket['created_at'])) : '' ?></td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <?php if (in_array('SacViewTicket', $this->data['buttonPermission'])): ?>
                                                    <a href="<?= $_ENV['URL_ADM'] ?>sac-view-ticket/<?= $ticket['id'] ?>" class="btn btn-info btn-sm" title="Visualizar"><i class="fa-regular fa-eye"></i></a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex flex-wrap gap-3 small text-muted mt-2 mb-2">
                        <span><i class="fas fa-clock text-success"></i> SLA no prazo</span>
                        <span><i class="fas fa-clock text-warning"></i> SLA próximo do limite</span>
                        <span><i class="fas fa-exclamation-circle text-danger"></i> SLA violado / expirado</span>
                        <span><i class="fas fa-circle text-success"></i> Concluído dentro do SLA</span>
                        <span><i class="fas fa-circle text-danger"></i> Concluído com SLA violado</span>
                        <span class="ms-2"><i class="fas fa-info-circle"></i> 1º = Resposta &middot; 2º = Resolução</span>
                    </div>
                </div>

                <!-- Cards Mobile -->
                <div class="d-block d-md-none">
                    <?php foreach ($ticketsWithSla as $ticket):
                        $canView = in_array('SacViewTicket', $this->data['buttonPermission']);
                        $canUpdate = in_array('SacUpdateTicket', $this->data['buttonPermission']);
                        $canDelete = in_array('SacDeleteTicket', $this->data['buttonPermission']);
                        $hasActions = $canUpdate || $canDelete;
                        $ticketUrl = $_ENV['URL_ADM'] . 'sac-view-ticket/' . $ticket['id'];
                        $priorityClass = $priorityBadges[$ticket['priority'] ?? ''] ?? 'secondary';
                        $isUrgent = ($ticket['priority'] ?? '') === 'Urgente';
                    ?>
                        <div class="card mb-3 shadow-sm<?= $isUrgent ? ' border-danger' : '' ?>">
                            <div class="card-body pb-2"<?php if ($canView): ?> onclick="window.location.href='<?= $ticketUrl ?>';" style="cursor:pointer;"<?php endif; ?>>
                                <h6 class="card-title mb-1">
                                    <span class="fw-bold text-primary">#<?= htmlspecialchars($ticket['code'] ?? '') ?></span>
                                    <span class="fw-semibold ms-1"><?= htmlspecialchars($ticket['subject'] ?? '') ?></span>
                                </h6>

                                <div class="mb-1 d-flex flex-wrap gap-1">
                                    <span class="badge bg-<?= $statusBadges[$ticket['status'] ?? ''] ?? 'secondary' ?>"><?= htmlspecialchars($ticket['status'] ?? '') ?></span>
                                    <span class="badge bg-<?= $priorityClass ?>"><?= htmlspecialchars($ticket['priority'] ?? '') ?></span>
                                    <?php if (!empty($ticket['category_name'])): ?>
                                        <span class="badge" style="background-color:<?= htmlspecialchars($ticket['category_color'] ?? '#6c757d') ?>"><?= htmlspecialchars($ticket['category_name']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($ticket['channel'])): ?>
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($ticket['channel']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="small text-muted mb-1">
                                    <i class="fas fa-user me-1"></i><?= htmlspecialchars($ticket['client_razao_social'] ?? $ticket['client_nome_fantasia'] ?? '—') ?>
                                </div>

                                <?php if (!empty($ticket['product']) || !empty($ticket['batch'])): ?>
                                    <div class="small text-muted mb-1">
                                        <?php if (!empty($ticket['product'])): ?>
                                            <i class="fas fa-box me-1"></i><?= htmlspecialchars($ticket['product']) ?>
                                        <?php endif; ?>
                                        <?php if (!empty($ticket['batch'])): ?>
                                            <span class="ms-2"><i class="fas fa-layer-group me-1"></i><?= htmlspecialchars($ticket['batch']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($ticket['assigned_name'])): ?>
                                    <div class="small text-muted mb-1">
                                        <i class="fas fa-headset me-1"></i><?= htmlspecialchars($ticket['assigned_name']) ?>
                                    </div>
                                <?php endif; ?>

                                <div class="d-flex flex-wrap align-items-center gap-2 mt-1">
                                    <?php if ($ticket['_slaRespIcon'] || $ticket['_slaResolIcon']): ?>
                                        <span class="small">
                                            <?= $ticket['_slaRespIcon'] ?> <small><?= $ticket['_slaRespLabel'] ?></small>
                                            <span class="ms-2"><?= $ticket['_slaResolIcon'] ?> <small><?= $ticket['_slaResolLabel'] ?></small></span>
                                        </span>
                                    <?php endif; ?>
                                    <span class="small text-muted ms-auto">
                                        <i class="fas fa-calendar me-1"></i><?= !empty($ticket['created_at']) ? date('d/m/Y H:i', strtotime($ticket['created_at'])) : '' ?>
                                    </span>
                                </div>
                                <?php if ($hasActions): ?>
                                    <div class="d-flex gap-1 mt-2 pt-2 border-top" onclick="event.stopPropagation();">
                                        <?php if ($canView): ?>
                                            <a href="<?= $ticketUrl ?>" class="btn btn-outline-info btn-sm flex-fill"><i class="fas fa-eye me-1"></i>Ver</a>
                                        <?php endif; ?>
                                        <?php if ($canUpdate): ?>
                                            <a href="<?= $_ENV['URL_ADM'] ?>sac-update-ticket/<?= $ticket['id'] ?>" class="btn btn-outline-warning btn-sm flex-fill"><i class="fas fa-edit me-1"></i>Editar</a>
                                        <?php endif; ?>
                                        <?php if ($canDelete): ?>
                                            <form action="<?= $_ENV['URL_ADM'] ?>sac-delete-ticket" method="POST" class="flex-fill" onclick="event.stopPropagation();">
                                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                <input type="hidden" name="id" value="<?= $ticket['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm w-100" onclick="return confirm('Excluir chamado #<?= htmlspecialchars($ticket['code'] ?? '') ?>?');"><i class="fas fa-trash me-1"></i>Excluir</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="d-flex flex-wrap gap-2 small text-muted mt-1 mb-2 px-1">
                        <span><i class="fas fa-clock text-success"></i> No prazo</span>
                        <span><i class="fas fa-clock text-warning"></i> Limite</span>
                        <span><i class="fas fa-exclamation-circle text-danger"></i> Violado</span>
                        <span><i class="fas fa-circle text-success"></i> OK</span>
                        <span><i class="fas fa-circle text-danger"></i> Violado</span>
                    </div>
                </div>

                <!-- Paginação -->
                <div class="d-flex justify-content-between align-items-center mt-2 d-none d-md-flex">
                    <div class="text-secondary small">
                        <?php if (!empty($this->data['pagination']['total'])): ?>
                            Mostrando <?= $this->data['pagination']['first_item'] ?? '' ?> até <?= $this->data['pagination']['last_item'] ?? '' ?> de <?= $this->data['pagination']['total'] ?> registro(s)
                        <?php endif; ?>
                    </div>
                    <div>
                        <?= $this->data['pagination']['html'] ?? '' ?>
                    </div>
                </div>
                <div class="d-flex flex-column align-items-center mt-2 d-md-none">
                    <div class="text-secondary small mb-1">
                        <?php if (!empty($this->data['pagination']['total'])): ?>
                            Mostrando até de <?= $this->data['pagination']['total'] ?> registro(s)
                        <?php endif; ?>
                    </div>
                    <?php
                    $paginationHtml = $this->data['pagination']['html'] ?? '';
                    if ($paginationHtml) {
                        $paginationHtml = preg_replace('/class="pagination(.*?)"/', 'class="pagination pagination-sm$1"', $paginationHtml, 1);
                        echo $paginationHtml;
                    }
                    ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info" role="alert">Nenhum chamado encontrado.</div>
            <?php endif; ?>

        </div>
    </div>
</div>
