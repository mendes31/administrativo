<style>
    /* Cabeçalho desktop com gradiente nas cores principais (alinhado a políticas/informativos) */
    .table-company-events thead th {
        background: linear-gradient(135deg, #2E9263 0%, #2C844B 55%, #236D3D 100%) !important;
        color: #ffffff !important;
        border-color: rgba(255, 255, 255, 0.18) !important;
        font-weight: 600;
    }

    .table-company-events thead th.text-end {
        text-align: right;
    }
</style>

<?php
$btnPerms = $this->data['buttonPermission'] ?? [];
// Mesmo padrão de ViewPolicy / ViewInformativo: só exibe "Visualizar" com permissão explícita da página.
$canVisualizar = in_array('ViewCompanyEvent', $btnPerms, true);
$sessionUid = (int)($_SESSION['user_id'] ?? 0);

/** @param array<string, mixed>|null $myRsvp */
$rsvpListBadge = static function (?array $myRsvp, array $ev): string {
    if (\App\adms\Helpers\InstitutionalSystemUserHelper::isInstitutionalSession()) {
        return '<span class="text-muted small">Não participa</span>';
    }
    if (empty($ev['requires_rsvp'])) {
        return '<span class="text-muted small">Não exige</span>';
    }
    $st = is_array($myRsvp) ? (string)($myRsvp['status'] ?? '') : '';
    if ($st === '' || $st === 'pending') {
        return '<span class="badge bg-warning text-dark">Pendente</span>';
    }
    if ($st === 'confirmed') {
        return '<span class="badge bg-success">Confirmado</span>';
    }
    if ($st === 'declined') {
        $created = substr((string)($myRsvp['created_at'] ?? ''), 0, 19);
        $responded = substr((string)($myRsvp['responded_at'] ?? ''), 0, 19);
        $automatic = $created !== '' && $created === $responded;
        $label = $automatic ? 'Sem resposta' : 'Recusado';
        $deadline = !empty($ev['rsvp_deadline']) ? strtotime((string)$ev['rsvp_deadline']) : false;
        $hint = ($deadline !== false && $deadline < time())
            ? '<div class="small text-muted">Prazo de confirmação encerrado</div>'
            : '';
        return '<span class="badge bg-secondary">' . $label . '</span>' . $hint;
    }
    if ($st === 'cancelled') {
        return '<span class="badge bg-light text-dark border">Cancelado</span>';
    }
    return '<span class="badge bg-light text-dark">' . htmlspecialchars($st) . '</span>';
};

/** Situação pelo horário do evento, independente da flag Ativo. */
$eventPhaseBadge = static function (array $ev): string {
    $now = time();
    $start = strtotime((string)($ev['starts_at'] ?? '')) ?: 0;
    $end = strtotime((string)($ev['ends_at'] ?? '')) ?: 0;
    if ($end > 0 && $now > $end) {
        return '<span class="badge bg-dark">Encerrado</span>';
    }
    if ($start > 0 && $now >= $start) {
        return '<span class="badge bg-primary">Em andamento</span>';
    }
    return '<span class="badge bg-info text-dark">Agendado</span>';
};

/** @param 'confirm'|'cancel' $kind */
$deadlineCell = static function (array $ev, string $kind): string {
    if ($kind === 'confirm' && empty($ev['requires_rsvp'])) {
        return '<span class="text-muted small">Não exige</span>';
    }
    $raw = $kind === 'confirm'
        ? (string)($ev['rsvp_deadline'] ?? '')
        : (string)($ev['cancellation_deadline'] ?? '');
    if ($raw === '') {
        return '<span class="text-muted small">Sem prazo</span>';
    }
    $ts = strtotime($raw);
    if ($ts === false) {
        return '<span class="text-muted small">Sem prazo</span>';
    }
    $when = htmlspecialchars(date('d/m/Y H:i', $ts), ENT_QUOTES, 'UTF-8');
    $badge = $ts < time()
        ? '<span class="badge bg-secondary">Encerrado</span>'
        : '<span class="badge bg-success">Em aberto</span>';
    return '<div class="small">' . $when . '</div>' . $badge;
};
?>

<div class="container-fluid px-4">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3 mt-3">
        <h2 class="mt-0">Eventos corporativos</h2>
        <?php if (in_array('CreateCompanyEvent', $btnPerms, true)): ?>
            <a href="<?php echo $_ENV['URL_ADM']; ?>create-company-event" class="btn btn-success btn-sm"><i class="fas fa-plus me-1"></i>Novo evento</a>
        <?php endif; ?>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="table-responsive d-none d-md-block">
        <table class="table table-striped align-middle table-company-events">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Início</th>
                    <th>Fim</th>
                    <th>Situação</th>
                    <th>Prazo confirmação</th>
                    <th>Prazo cancelamento</th>
                    <th>Sua resposta</th>
                    <th>Ativo</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($this->data['events'] ?? []) as $ev): ?>
                    <?php
                    $isCreator = (int)($ev['created_by'] ?? 0) === $sessionUid;
                    $isSuper = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
                    $canEdit = in_array('UpdateCompanyEvent', $btnPerms, true);
                    $canReport = \App\adms\Helpers\CompanyEventRsvpAccessHelper::canAdminRsvpForOthers($ev, $sessionUid, $btnPerms);
                    $canDelete = in_array('DeleteCompanyEvent', $btnPerms, true)
                        && ($isCreator || $isSuper);
                    ?>
                    <tr>
                        <td><?php echo \App\adms\Helpers\TextEncodingHelper::escape($ev['title'] ?? ''); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($ev['starts_at'] ?? '')); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($ev['ends_at'] ?? '')); ?></td>
                        <td><?php echo $eventPhaseBadge($ev); ?></td>
                        <td><?php echo $deadlineCell($ev, 'confirm'); ?></td>
                        <td><?php echo $deadlineCell($ev, 'cancel'); ?></td>
                        <td><?php echo $rsvpListBadge($ev['my_rsvp'] ?? null, $ev); ?></td>
                        <td><?php echo !empty($ev['ativo']) ? 'Sim' : 'Não'; ?></td>
                        <td class="text-end text-nowrap">
                            <?php if ($canVisualizar): ?>
                                <a class="btn btn-sm btn-primary" href="<?php echo $_ENV['URL_ADM']; ?>view-company-event/<?php echo (int)$ev['id']; ?>" title="Visualizar">
                                    <i class="fas fa-eye"></i>
                                </a>
                            <?php endif; ?>
                            <?php if ($canReport): ?>
                                <a class="btn btn-sm btn-outline-primary" href="<?php echo $_ENV['URL_ADM']; ?>company-event-report/<?php echo (int)$ev['id']; ?>">Relatório</a>
                            <?php endif; ?>
                            <?php if ($canEdit): ?>
                                <a class="btn btn-sm btn-warning" href="<?php echo $_ENV['URL_ADM']; ?>update-company-event/<?php echo (int)$ev['id']; ?>">Editar</a>
                            <?php endif; ?>
                            <?php if ($canDelete): ?>
                                <a class="btn btn-sm btn-outline-danger" href="<?php echo $_ENV['URL_ADM']; ?>delete-company-event/<?php echo (int)$ev['id']; ?>" onclick="return confirm('Excluir este evento?');">Excluir</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="d-md-none">
        <?php foreach (($this->data['events'] ?? []) as $ev): ?>
            <?php
            $isCreator = (int)($ev['created_by'] ?? 0) === $sessionUid;
            $isSuper = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
            $canEdit = in_array('UpdateCompanyEvent', $btnPerms, true);
            $canReport = \App\adms\Helpers\CompanyEventRsvpAccessHelper::canAdminRsvpForOthers($ev, $sessionUid, $btnPerms);
            $canDelete = in_array('DeleteCompanyEvent', $btnPerms, true)
                && ($isCreator || $isSuper);
            ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="fw-bold"><?php echo \App\adms\Helpers\TextEncodingHelper::escape($ev['title'] ?? ''); ?></h6>
                    <div class="small text-muted mb-2">
                        <?php echo date('d/m/Y H:i', strtotime($ev['starts_at'] ?? '')); ?> — <?php echo date('d/m/Y H:i', strtotime($ev['ends_at'] ?? '')); ?>
                    </div>
                    <dl class="row small mb-2">
                        <dt class="col-5">Situação</dt>
                        <dd class="col-7"><?php echo $eventPhaseBadge($ev); ?></dd>
                        <dt class="col-5">Confirmação</dt>
                        <dd class="col-7"><?php echo $deadlineCell($ev, 'confirm'); ?></dd>
                        <dt class="col-5">Cancelamento</dt>
                        <dd class="col-7"><?php echo $deadlineCell($ev, 'cancel'); ?></dd>
                        <dt class="col-5">Sua resposta</dt>
                        <dd class="col-7"><?php echo $rsvpListBadge($ev['my_rsvp'] ?? null, $ev); ?></dd>
                        <dt class="col-5">Ativo</dt>
                        <dd class="col-7"><?php echo !empty($ev['ativo']) ? 'Sim' : 'Não'; ?></dd>
                    </dl>
                    <div class="d-grid gap-2">
                        <?php if ($canVisualizar): ?>
                            <a class="btn btn-sm btn-primary" href="<?php echo $_ENV['URL_ADM']; ?>view-company-event/<?php echo (int)$ev['id']; ?>">
                                <i class="fas fa-eye me-1"></i>Visualizar
                            </a>
                        <?php endif; ?>
                        <?php if ($canReport): ?>
                            <a class="btn btn-sm btn-outline-primary" href="<?php echo $_ENV['URL_ADM']; ?>company-event-report/<?php echo (int)$ev['id']; ?>">Relatório</a>
                        <?php endif; ?>
                        <?php if ($canEdit): ?>
                            <a class="btn btn-sm btn-warning" href="<?php echo $_ENV['URL_ADM']; ?>update-company-event/<?php echo (int)$ev['id']; ?>">Editar</a>
                        <?php endif; ?>
                        <?php if ($canDelete): ?>
                            <a class="btn btn-sm btn-outline-danger" href="<?php echo $_ENV['URL_ADM']; ?>delete-company-event/<?php echo (int)$ev['id']; ?>" onclick="return confirm('Excluir este evento?');">Excluir</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($this->data['pagination']['html'])): ?>
        <div class="mt-3"><?php echo $this->data['pagination']['html']; ?></div>
    <?php endif; ?>
</div>
