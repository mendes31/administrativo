<?php
$ev = $this->data['event'] ?? [];
$rows = $this->data['rows'] ?? [];
$id = (int)($ev['id'] ?? 0);
$fmtDt = static function ($v) {
    if (empty($v)) {
        return '';
    }
    return date('d/m/Y H:i', strtotime((string)$v));
};
?>
<div class="container-fluid px-4">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3 mt-3">
        <h2 class="mt-0">Relatório — <?php echo htmlspecialchars($ev['title'] ?? ''); ?></h2>
        <a class="btn btn-sm btn-outline-success" href="<?php echo $_ENV['URL_ADM']; ?>company-event-report/<?php echo $id; ?>?export=csv">Exportar CSV</a>
    </div>
    <p class="text-muted small">Dados de participantes e convidados conforme LGPD — acesso restrito ao criador do evento.</p>

    <div class="table-responsive d-none d-md-block">
        <table class="table table-sm table-striped">
            <thead>
                <tr>
                    <th>Colaborador</th>
                    <th>E-mail</th>
                    <th>Departamento</th>
                    <th>Status</th>
                    <th>Respondido</th>
                    <th>Cancelado</th>
                    <th>Convidados</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
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
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="d-md-none">
        <?php foreach ($rows as $r): ?>
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
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <a href="<?php echo $_ENV['URL_ADM']; ?>list-company-events" class="btn btn-secondary btn-sm">Voltar</a>
</div>
