<?php
$ev = $this->data['event'] ?? [];
$rows = $this->data['rows'] ?? [];
$rowsInativos = $this->data['rows_inativos'] ?? [];
$id = (int)($ev['id'] ?? 0);
$canEditRsvp = !empty($this->data['can_edit_rsvp_responses']);
$urlAdm = $_ENV['URL_ADM'] ?? '';
$fmtDt = static function ($v) {
    if (empty($v)) {
        return '';
    }
    return date('d/m/Y H:i', strtotime((string)$v));
};
$editBase = $urlAdm . 'view-company-event/' . $id . '?manage_rsvp=';
$kpis = $this->data['kpis'] ?? [];
$total = (int) ($kpis['total'] ?? 0);
$confirmados = (int) ($kpis['confirmados'] ?? 0);
$recusados = (int) ($kpis['recusados'] ?? 0);
$pendentes = (int) ($kpis['pendentes'] ?? 0);
$cancelados = (int) ($kpis['cancelados'] ?? 0);
$pct = static function (int $part, int $whole): string {
    if ($whole <= 0) {
        return '0%';
    }
    return number_format(($part / $whole) * 100, 1, ',', '') . '%';
};
$statusLabel = static function ($status): string {
    return match ((string) $status) {
        'confirmed' => 'Confirmado',
        'declined' => 'Recusado',
        'cancelled' => 'Cancelado',
        default => 'Pendente',
    };
};
$statusClass = static function ($status): string {
    return match ((string) $status) {
        'confirmed' => 'bg-success',
        'declined' => 'bg-danger',
        'cancelled' => 'bg-secondary',
        default => 'bg-warning text-dark',
    };
};
$renderRows = static function (array $list) use ($fmtDt, $canEditRsvp, $editBase, $statusLabel, $statusClass): void {
    if ($list === []) {
        echo '<tr><td colspan="' . ($canEditRsvp ? '8' : '7') . '" class="text-muted text-center py-3">Nenhum colaborador nesta lista.</td></tr>';
        return;
    }
    foreach ($list as $r) {
        $uidRow = (int) ($r['user_id'] ?? 0);
        $status = (string) ($r['status'] ?? '');
        if ($status === '') {
            $status = 'pending';
        }
        $search = mb_strtolower(trim((string) ($r['user_name'] ?? '') . ' ' . (string) ($r['user_email'] ?? '')));
        echo '<tr class="js-event-report-row" data-search="' . htmlspecialchars($search) . '" data-status="' . htmlspecialchars($status) . '">';
        echo '<td>' . htmlspecialchars((string) ($r['user_name'] ?? '')) . '</td>';
        echo '<td>' . htmlspecialchars((string) ($r['user_email'] ?? '')) . '</td>';
        echo '<td>' . htmlspecialchars((string) ($r['department_name'] ?? '')) . '</td>';
        echo '<td><span class="badge ' . $statusClass($status) . '">' . htmlspecialchars($statusLabel($status)) . '</span></td>';
        echo '<td>' . htmlspecialchars($fmtDt($r['responded_at'] ?? null)) . '</td>';
        echo '<td>' . htmlspecialchars($fmtDt($r['cancelled_at'] ?? null)) . '</td>';
        echo '<td>';
        foreach ($r['guests'] ?? [] as $g) {
            echo '<div class="small">' . htmlspecialchars((string) ($g['full_name'] ?? ''));
            if (!empty($g['relationship'])) {
                echo ' (' . htmlspecialchars((string) $g['relationship']) . ')';
            }
            echo '</div>';
        }
        echo '</td>';
        if ($canEditRsvp) {
            echo '<td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-primary" href="' . htmlspecialchars($editBase . $uidRow) . '"><i class="fas fa-user-edit me-1"></i>Editar resposta</a></td>';
        }
        echo '</tr>';
    }
};
$renderCards = static function (array $list) use ($fmtDt, $canEditRsvp, $editBase, $statusLabel, $statusClass): void {
    if ($list === []) {
        echo '<p class="text-muted small mb-0">Nenhum colaborador nesta lista.</p>';
        return;
    }
    foreach ($list as $r) {
        $uidRow = (int) ($r['user_id'] ?? 0);
        $status = (string) ($r['status'] ?? '');
        if ($status === '') {
            $status = 'pending';
        }
        $search = mb_strtolower(trim((string) ($r['user_name'] ?? '') . ' ' . (string) ($r['user_email'] ?? '')));
        echo '<div class="card mb-3 js-event-report-card" data-search="' . htmlspecialchars($search) . '" data-status="' . htmlspecialchars($status) . '"><div class="card-body small">';
        echo '<div class="fw-bold">' . htmlspecialchars((string) ($r['user_name'] ?? '')) . '</div>';
        echo '<div class="text-muted">' . htmlspecialchars((string) ($r['user_email'] ?? '')) . '</div>';
        echo '<div class="mt-1"><span class="badge ' . $statusClass($status) . '">' . htmlspecialchars($statusLabel($status)) . '</span></div>';
        $responded = $fmtDt($r['responded_at'] ?? null);
        if ($responded !== '') {
            echo '<div class="text-muted mt-1">Respondido: ' . htmlspecialchars($responded) . '</div>';
        }
        if (!empty($r['cancelled_at'])) {
            echo '<div class="text-muted">Cancelado: ' . htmlspecialchars($fmtDt($r['cancelled_at'])) . '</div>';
        }
        foreach ($r['guests'] ?? [] as $g) {
            echo '<div class="mt-1">· ' . htmlspecialchars((string) ($g['full_name'] ?? '')) . '</div>';
        }
        if ($canEditRsvp) {
            echo '<div class="mt-2"><a class="btn btn-sm btn-outline-primary w-100" href="' . htmlspecialchars($editBase . $uidRow) . '"><i class="fas fa-user-edit me-1"></i>Editar resposta</a></div>';
        }
        echo '</div></div>';
    }
};
$requiresRsvp = !empty($ev['requires_rsvp']);
$audience = !empty($ev['department_id']) ? 'do departamento do evento' : 'de todos os departamentos';
?>
<div class="container-fluid px-4">
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2 mb-3 mt-3">
        <h2 class="mt-0 mb-0">Relatório — <?php echo htmlspecialchars($ev['title'] ?? ''); ?></h2>
        <div class="d-flex flex-wrap gap-2 ms-md-auto">
            <a class="btn btn-sm btn-outline-secondary" href="<?php echo htmlspecialchars($urlAdm); ?>list-company-events" title="Voltar para a listagem de eventos">
                <i class="fas fa-arrow-left me-1"></i>Voltar à listagem
            </a>
            <a class="btn btn-sm btn-outline-success" href="<?php echo htmlspecialchars($urlAdm); ?>company-event-report/<?php echo $id; ?>?export=csv">Exportar CSV</a>
            <a class="btn btn-sm btn-outline-success" href="<?php echo htmlspecialchars($urlAdm); ?>company-event-report/<?php echo $id; ?>?export=csv-externos">CSV convidados externos</a>
        </div>
    </div>
    <p class="text-muted small mb-3">Os percentuais usam colaboradores <strong>ativos</strong> <?php echo $audience; ?>. O usuário manager não entra na contagem.<?php if (!$requiresRsvp): ?> Este evento não exige confirmação de presença.<?php endif; ?></p>

    <div class="row g-2 mb-4 relatorio-kpi row-cols-1 row-cols-sm-2 row-cols-xl-5">
        <div class="col">
            <div class="card bg-primary text-white h-100">
                <div class="card-body text-center">
                    <h3><?php echo $total; ?></h3>
                    <p class="mb-0">Total de ativos</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card bg-success text-white h-100">
                <div class="card-body text-center">
                    <h3 class="mb-1"><?php echo $confirmados; ?></h3>
                    <p class="mb-0">Confirmados <span class="fw-semibold"><?php echo htmlspecialchars($pct($confirmados, $total)); ?></span></p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card bg-danger text-white h-100">
                <div class="card-body text-center">
                    <h3 class="mb-1"><?php echo $recusados; ?></h3>
                    <p class="mb-0">Recusados <span class="fw-semibold"><?php echo htmlspecialchars($pct($recusados, $total)); ?></span></p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body text-center">
                    <h3 class="mb-1"><?php echo $pendentes; ?></h3>
                    <p class="mb-0">Pendentes <span class="fw-semibold"><?php echo htmlspecialchars($pct($pendentes, $total)); ?></span></p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card bg-secondary text-white h-100">
                <div class="card-body text-center">
                    <h3 class="mb-1"><?php echo $cancelados; ?></h3>
                    <p class="mb-0">Cancelados <span class="fw-semibold"><?php echo htmlspecialchars($pct($cancelados, $total)); ?></span></p>
                </div>
            </div>
        </div>
    </div>
    <style>
        .relatorio-kpi .card-body { padding: .85rem .5rem; }
        .relatorio-kpi h3 { font-size: 1.7rem; }
        .relatorio-kpi p { font-size: .82rem; line-height: 1.3; }
    </style>

    <p class="text-muted small">Dados de participantes e convidados conforme LGPD — acesso restrito a criadores e equipe autorizada. A aba <strong>Ativos</strong> lista todos os colaboradores ativos do público, com a resposta ou a pendência. A aba <strong>Inativos com histórico</strong> lista quem está inativo e já tinha confirmado, recusado ou cancelado. Inativos sem resposta não aparecem e não entram no percentual.</p>

    <div class="card">
        <div class="card-body">
            <div class="row g-2 mb-3">
                <div class="col-md-8">
                    <label for="eventReportUserFilter" class="form-label mb-1">Filtrar por usuário</label>
                    <input type="text" id="eventReportUserFilter" class="form-control" placeholder="Digite nome ou e-mail" oninput="filterEventReportUsers()">
                </div>
                <div class="col-md-4">
                    <label for="eventReportStatusFilter" class="form-label mb-1">Filtrar por status</label>
                    <select id="eventReportStatusFilter" class="form-select" onchange="filterEventReportUsers()">
                        <option value="">Todos</option>
                        <option value="pending">Pendente</option>
                        <option value="confirmed">Confirmado</option>
                        <option value="declined">Recusado</option>
                        <option value="cancelled">Cancelado</option>
                    </select>
                </div>
            </div>

            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-event-ativos" data-bs-toggle="tab" data-bs-target="#pane-event-ativos" type="button" role="tab">
                        Ativos (<?php echo count($rows); ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-event-inativos" data-bs-toggle="tab" data-bs-target="#pane-event-inativos" type="button" role="tab">
                        Inativos com histórico (<?php echo count($rowsInativos); ?>)
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="pane-event-ativos" role="tabpanel">
                    <div class="table-responsive d-none d-md-block">
                        <table class="table table-sm table-striped align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Colaborador</th>
                                    <th>E-mail</th>
                                    <th>Departamento</th>
                                    <th>Status</th>
                                    <th>Respondido</th>
                                    <th>Cancelado</th>
                                    <th>Convidados</th>
                                    <?php if ($canEditRsvp): ?><th class="text-end">Ações</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody><?php $renderRows($rows); ?></tbody>
                        </table>
                    </div>
                    <div class="d-md-none"><?php $renderCards($rows); ?></div>
                </div>
                <div class="tab-pane fade" id="pane-event-inativos" role="tabpanel">
                    <p class="text-muted small">Colaboradores inativos que confirmaram, recusaram ou cancelaram. Não entram no percentual.</p>
                    <div class="table-responsive d-none d-md-block">
                        <table class="table table-sm table-striped align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Colaborador</th>
                                    <th>E-mail</th>
                                    <th>Departamento</th>
                                    <th>Status</th>
                                    <th>Respondido</th>
                                    <th>Cancelado</th>
                                    <th>Convidados</th>
                                    <?php if ($canEditRsvp): ?><th class="text-end">Ações</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody><?php $renderRows($rowsInativos); ?></tbody>
                        </table>
                    </div>
                    <div class="d-md-none"><?php $renderCards($rowsInativos); ?></div>
                </div>
            </div>
        </div>
    </div>

    <?php
    $externalInvites = $this->data['external_invites'] ?? [];
    $extConfirmed = 0;
    $extDeclined = 0;
    $extPending = 0;
    foreach ($externalInvites as $extRow) {
        $extStatus = (string) ($extRow['status'] ?? '');
        if ($extStatus === 'confirmed') {
            $extConfirmed++;
        } elseif ($extStatus === 'declined') {
            $extDeclined++;
        } else {
            $extPending++;
        }
    }
    ?>
    <div class="card mt-3">
        <div class="card-body">
            <h3 class="h5">Convidados de fora da empresa</h3>
            <p class="text-muted small">Lista separada dos colaboradores. Não entra no percentual dos cartões acima. A presença é confirmada ou recusada pelo link enviado, em geral pelo WhatsApp.</p>
            <p class="small mb-3">Total <?php echo count($externalInvites); ?> · Confirmados <?php echo $extConfirmed; ?> · Recusados <?php echo $extDeclined; ?> · Pendentes <?php echo $extPending; ?></p>
            <?php if ($externalInvites === []): ?>
                <p class="text-muted small mb-0">Nenhum convidado externo neste evento.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Nome</th>
                                <th>Motivo do convite</th>
                                <th>Celular</th>
                                <th>Status</th>
                                <th>Respondido</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($externalInvites as $extRow): ?>
                            <tr>
                                <td><?php echo htmlspecialchars((string) ($extRow['full_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string) ($extRow['invite_reason'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars(\App\adms\Models\Services\CompanyEventExternalInviteMessage::formatPhone((string) ($extRow['phone'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><span class="badge <?php echo $statusClass((string) ($extRow['status'] ?? '')); ?>"><?php echo htmlspecialchars($statusLabel((string) ($extRow['status'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td><?php echo htmlspecialchars($fmtDt($extRow['responded_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
function filterEventReportUsers() {
    var q = (document.getElementById('eventReportUserFilter')?.value || '').toLowerCase().trim();
    var status = document.getElementById('eventReportStatusFilter')?.value || '';
    document.querySelectorAll('.js-event-report-row, .js-event-report-card').forEach(function (el) {
        var hay = (el.getAttribute('data-search') || '');
        var rowStatus = el.getAttribute('data-status') || '';
        var nameOk = q === '' || hay.indexOf(q) !== -1;
        var statusOk = status === '' || rowStatus === status;
        el.style.display = (nameOk && statusOk) ? '' : 'none';
    });
}
</script>
