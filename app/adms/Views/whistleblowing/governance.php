<?php



$runs = $this->data['runs'] ?? [];

$lastRun = $this->data['last_run'] ?? null;

$page = (int) ($this->data['page'] ?? 1);

$totalPages = (int) ($this->data['total_pages'] ?? 1);

$urlAdm = (string) ($this->data['url_adm'] ?? '');

$csrf = (string) ($this->data['csrf_token'] ?? '');

$attachmentStats = $this->data['attachment_stats'] ?? ['total' => 0, 'encrypted' => 0, 'legacy' => 0];

$config = $this->data['config'] ?? [];

$archiveYears = (int) ($config['archive_years'] ?? 5);

$deleteYears = (int) ($config['delete_years'] ?? 10);

$cronTime = (string) ($config['cron_time'] ?? '02:00');

$cronLine = (string) ($config['cron_line'] ?? '');

$cronEnabled = !empty($config['cron_enabled']);

$tokenOk = !empty($config['token_configured']);

$keyOk = !empty($config['key_configured']);

$rateMax = (int) ($config['rate_limit_max'] ?? 5);

$rateWindow = (int) ($config['rate_limit_window'] ?? 15);

$totalRuns = (int) ($this->data['total_runs'] ?? 0);

$perPage = (int) ($this->data['per_page'] ?? 10);

$originLabels = [
    'auto' => 'Automático (login)',
    'manual' => 'Manual',
    'cron' => 'HTTP (cron)',
];

?>

<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">

        <h2 class="mt-3"><i class="fas fa-balance-scale me-2"></i>Governança LGPD</h2>

        <ol class="breadcrumb mb-3 ms-auto">

            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($urlAdm) ?>denuncias-dashboard">Canal de Denúncias</a></li>

            <li class="breadcrumb-item active">Governança LGPD</li>

        </ol>

    </div>



    <?php include './app/adms/Views/partials/alerts.php'; ?>



    <div class="row g-3 mb-3">

        <div class="col-lg-3">

            <div class="card border-light shadow-sm h-100">

                <div class="card-header fw-semibold small">Status operacional</div>

                <div class="card-body small">

                    <ul class="list-unstyled mb-0">

                        <li class="mb-2">

                            Chave criptografia:

                            <span class="badge bg-<?= $keyOk ? 'success' : 'danger' ?>"><?= $keyOk ? 'OK' : 'Pendente' ?></span>

                        </li>

                        <li class="mb-2">

                            Token cron:

                            <span class="badge bg-<?= $tokenOk ? 'success' : 'warning text-dark' ?>"><?= $tokenOk ? 'OK' : 'Pendente' ?></span>

                        </li>

                        <li class="mb-2">

                            Retenção automática:

                            <span class="badge bg-<?= $cronEnabled ? 'primary' : 'secondary' ?>"><?= $cronEnabled ? 'Ativa (login)' : 'Desativada' ?></span>

                        </li>

                        <li>

                            Rate limit acompanhamento:

                            <?= $rateMax ?> tentativas / <?= $rateWindow ?> min

                        </li>

                    </ul>

                    <a href="<?= htmlspecialchars($urlAdm) ?>whistleblowing-config" class="btn btn-outline-secondary btn-sm mt-3">Editar em Configuração</a>

                </div>

            </div>

        </div>

        <div class="col-lg-3">

            <div class="card border-light shadow-sm h-100">

                <div class="card-header fw-semibold small"><i class="fas fa-paperclip me-1"></i> Anexos em disco</div>

                <div class="card-body small">

                    <ul class="list-unstyled mb-0">

                        <li><strong>Total:</strong> <?= (int)($attachmentStats['total'] ?? 0) ?></li>

                        <li>

                            <span class="badge bg-success">Cifrados</span>

                            <?= (int)($attachmentStats['encrypted'] ?? 0) ?>

                        </li>

                        <li>

                            <span class="badge bg-warning text-dark">Legado</span>

                            <?= (int)($attachmentStats['legacy'] ?? 0) ?>

                        </li>

                    </ul>

                </div>

            </div>

        </div>

        <div class="col-lg-3">

            <div class="card border-light shadow-sm h-100">

                <div class="card-header fw-semibold small">Política de retenção</div>

                <div class="card-body small">

                    <ul class="mb-0">

                        <li>Arquivar após <strong><?= $archiveYears ?></strong> ano(s)</li>

                        <li>Excluir após <strong><?= $deleteYears ?></strong> ano(s)</li>

                        <li>Anexos removidos na exclusão definitiva</li>

                    </ul>

                </div>

            </div>

        </div>

        <div class="col-lg-3">

            <div class="card border-light shadow-sm h-100">

                <div class="card-header fw-semibold small">Última execução</div>

                <div class="card-body small">

                    <?php if ($lastRun): ?>

                        <p class="mb-1"><strong>Data:</strong> <?= htmlspecialchars((string)($lastRun['finished_at'] ?: $lastRun['started_at']), ENT_QUOTES, 'UTF-8') ?></p>

                        <p class="mb-1"><strong>Origem:</strong> <?= htmlspecialchars((string)($lastRun['triggered_by'] ?? 'cron'), ENT_QUOTES, 'UTF-8') ?></p>

                        <p class="mb-2">

                            <?= (int)($lastRun['archived_count'] ?? 0) ?> arquiv. /

                            <?= (int)($lastRun['deleted_count'] ?? 0) ?> excl. /

                            <?= (int)($lastRun['attachments_deleted'] ?? 0) ?> anexos

                        </p>

                        <?php

                        $st = (string)($lastRun['status'] ?? '');

                        $badge = $st === 'success' ? 'success' : ($st === 'error' ? 'danger' : 'secondary');

                        ?>

                        <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($st, ENT_QUOTES, 'UTF-8') ?></span>

                    <?php else: ?>

                        <p class="text-muted mb-0">Nenhuma execução ainda.</p>

                    <?php endif; ?>

                    <form method="post" class="mt-3" onsubmit="return confirm('Executar a retenção LGPD agora?');">

                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

                        <button type="submit" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-play me-1"></i>Executar agora</button>

                    </form>

                </div>

            </div>

        </div>

    </div>



    <?php if ($cronLine !== '' && $tokenOk): ?>

    <div class="card border-light shadow-sm mb-3">

        <div class="card-header fw-semibold small">Agendamento externo (opcional — Linux)</div>

        <div class="card-body small">

            <p class="mb-2">No Windows, a retenção já roda no login. Use o crontab abaixo apenas se quiser disparo adicional no servidor:</p>

            <pre class="bg-light p-2 rounded text-break user-select-all mb-0"><?= htmlspecialchars($cronLine) ?></pre>

        </div>

    </div>

    <?php endif; ?>



    <div class="card border-light shadow-sm">

        <div class="card-header fw-semibold d-flex justify-content-between align-items-center flex-wrap gap-2">

            <span>Histórico de execuções</span>

            <?php if ($totalRuns > 0): ?>

                <span class="small fw-normal text-muted">

                    Exibindo <?= min($perPage, count($runs)) ?> de <?= $totalRuns ?>

                    <?php if ($page > 1): ?> (página <?= $page ?>)<?php endif; ?>

                </span>

            <?php endif; ?>

        </div>

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-sm table-hover mb-0">

                    <thead class="table-light">

                        <tr>

                            <th>Início</th>

                            <th>Fim</th>

                            <th>Origem</th>

                            <th>Status</th>

                            <th class="text-end">Arquivadas</th>

                            <th class="text-end">Excluídas</th>

                            <th class="text-end">Anexos</th>

                            <th class="text-end">Tempo</th>

                            <th>Observação</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php if ($runs === []): ?>

                            <tr><td colspan="9" class="text-center text-muted py-4">Nenhuma execução registrada.</td></tr>

                        <?php else: ?>

                            <?php foreach ($runs as $run): ?>

                                <tr>

                                    <td><?= htmlspecialchars((string)($run['started_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>

                                    <td><?= htmlspecialchars((string)($run['finished_at'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>

                                    <td><?php

                                        $origin = (string)($run['triggered_by'] ?? 'cron');

                                        echo htmlspecialchars($originLabels[$origin] ?? $origin, ENT_QUOTES, 'UTF-8');

                                    ?></td>

                                    <td>

                                        <?php

                                        $st = (string)($run['status'] ?? '');

                                        $badge = $st === 'success' ? 'success' : ($st === 'error' ? 'danger' : 'secondary');

                                        ?>

                                        <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($st, ENT_QUOTES, 'UTF-8') ?></span>

                                    </td>

                                    <td class="text-end"><?= (int)($run['archived_count'] ?? 0) ?></td>

                                    <td class="text-end"><?= (int)($run['deleted_count'] ?? 0) ?></td>

                                    <td class="text-end"><?= (int)($run['attachments_deleted'] ?? 0) ?></td>

                                    <td class="text-end"><?= round(((int)($run['duration_ms'] ?? 0)) / 1000, 2) ?> s</td>

                                    <td class="small text-muted"><?= htmlspecialchars((string)($run['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

        <?php if ($totalPages > 1): ?>

        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">

            <?php if ($page > 1): ?>

                <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars($urlAdm) ?>whistleblowing-governance?page=<?= $page - 1 ?>">

                    <i class="fas fa-chevron-left me-1"></i>Anterior

                </a>

            <?php else: ?>

                <span></span>

            <?php endif; ?>

            <?php if ($page < $totalPages): ?>

                <a class="btn btn-outline-primary btn-sm" href="<?= htmlspecialchars($urlAdm) ?>whistleblowing-governance?page=<?= $page + 1 ?>">

                    Ver mais execuções<i class="fas fa-chevron-right ms-1"></i>

                </a>

            <?php endif; ?>

        </div>

        <?php endif; ?>

    </div>

</div>


