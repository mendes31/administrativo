<?php
$ev = $this->data['event'] ?? [];
$rows = $this->data['rows'] ?? [];
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
?>
<div class="container-fluid px-4">
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2 mb-3 mt-3">
        <h2 class="mt-0 mb-0">Relatório — <?php echo htmlspecialchars($ev['title'] ?? ''); ?></h2>
        <div class="d-flex flex-wrap gap-2 ms-md-auto">
            <a class="btn btn-sm btn-outline-secondary" href="<?php echo htmlspecialchars($urlAdm); ?>list-company-events" title="Voltar para a listagem de eventos">
                <i class="fas fa-arrow-left me-1"></i>Voltar à listagem
            </a>
            <a class="btn btn-sm btn-outline-success" href="<?php echo htmlspecialchars($urlAdm); ?>company-event-report/<?php echo $id; ?>?export=csv">Exportar CSV</a>
        </div>
    </div>
    <p class="text-muted small">Dados de participantes e convidados conforme LGPD — acesso restrito a criadores e equipe autorizada.</p>

    <div class="table-responsive d-none d-md-block">
        <table class="table table-sm table-striped align-middle">
            <thead>
                <tr>
                    <th>Colaborador</th>
                    <th>E-mail</th>
                    <th>Departamento</th>
                    <th>Status</th>
                    <th>Respondido</th>
                    <th>Cancelado</th>
                    <th>Convidados</th>
                    <?php if ($canEditRsvp): ?>
                    <th class="text-end">Ações</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <?php $uidRow = (int)($r['user_id'] ?? 0); ?>
                    <tr>
                        <td><?php echo htmlspecialchars($r['user_name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($r['user_email'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($r['department_name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($r['status'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($fmtDt($r['responded_at'] ?? null)); ?></td>
                        <td><?php echo htmlspecialchars($fmtDt($r['cancelled_at'] ?? null)); ?></td>
                        <td>
                            <?php foreach ($r['guests'] ?? [] as $g): ?>
                                <div class="small"><?php echo htmlspecialchars($g['full_name'] ?? ''); ?>
                                    <?php if (!empty($g['relationship'])): ?>(<?php echo htmlspecialchars($g['relationship']); ?>)<?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </td>
                        <?php if ($canEditRsvp): ?>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars($editBase . $uidRow); ?>">
                                <i class="fas fa-user-edit me-1"></i>Editar resposta
                            </a>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="d-md-none">
        <?php foreach ($rows as $r): ?>
            <?php $uidRow = (int)($r['user_id'] ?? 0); ?>
            <div class="card mb-3">
                <div class="card-body small">
                    <div class="fw-bold"><?php echo htmlspecialchars($r['user_name'] ?? ''); ?></div>
                    <div class="text-muted"><?php echo htmlspecialchars($r['user_email'] ?? ''); ?></div>
                    <div>Status: <?php echo htmlspecialchars($r['status'] ?? ''); ?></div>
                    <?php if (!empty($r['cancelled_at'])): ?>
                        <div class="text-muted">Cancelado: <?php echo htmlspecialchars($fmtDt($r['cancelled_at'])); ?></div>
                    <?php endif; ?>
                    <?php foreach ($r['guests'] ?? [] as $g): ?>
                        <div class="mt-1">· <?php echo htmlspecialchars($g['full_name'] ?? ''); ?></div>
                    <?php endforeach; ?>
                    <?php if ($canEditRsvp): ?>
                    <div class="mt-2">
                        <a class="btn btn-sm btn-outline-primary w-100" href="<?php echo htmlspecialchars($editBase . $uidRow); ?>">
                            <i class="fas fa-user-edit me-1"></i>Editar resposta
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
