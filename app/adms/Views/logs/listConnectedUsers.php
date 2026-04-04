<?php
$total = (int)($this->data['total'] ?? 0);
$sessions = $this->data['sessions'] ?? [];
$currentUserId = (int)($this->data['current_user_id'] ?? 0);
$idleDescription = (string)($this->data['idle_description'] ?? '');
$consultedAt = (string)($this->data['consulted_at'] ?? '');
?>
<style>
    /* Mobile: cartões em largura total; evita tabela cortada */
    .connected-users-mobile .card {
        border-radius: 12px;
        overflow: hidden;
    }
    .connected-users-mobile .connected-user-dl {
        display: grid;
        grid-template-columns: auto 1fr;
        gap: 0.35rem 0.75rem;
        font-size: 0.875rem;
        margin: 0;
    }
    .connected-users-mobile .connected-user-dl dt {
        margin: 0;
        color: var(--bs-secondary-color);
        font-weight: 600;
        white-space: nowrap;
    }
    .connected-users-mobile .connected-user-dl dd {
        margin: 0;
        word-break: break-word;
        overflow-wrap: anywhere;
    }
    .connected-users-desktop.table-responsive {
        -webkit-overflow-scrolling: touch;
    }
</style>
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <div class="mb-2 mb-md-1 d-flex flex-column flex-sm-row gap-2 gap-sm-0 align-items-start align-items-sm-center">
        <h2 class="mt-2 mt-md-3 mb-0 fs-4 fs-md-3">Usuários conectados</h2>
        <ol class="breadcrumb mb-0 mt-1 mt-sm-3 ms-sm-auto small">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item active text-truncate" style="max-width: 11rem;" aria-current="page">Conectados</li>
        </ol>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex flex-column flex-sm-row gap-2 align-items-stretch align-items-sm-center justify-content-between">
            <span class="fw-semibold">Listar</span>
            <div class="d-flex gap-2 align-items-center justify-content-between justify-content-sm-end flex-wrap">
                <a href="?" class="btn btn-outline-primary btn-sm flex-grow-1 flex-sm-grow-0" title="Recarregar a lista a partir do banco">
                    <i class="fas fa-sync-alt me-1"></i> Atualizar
                </a>
                <span class="badge bg-secondary"><?= $total; ?> online</span>
            </div>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <div class="alert alert-light border mb-3 small mb-md-3">
                <i class="fas fa-info-circle text-primary me-1"></i>
                <span class="d-none d-md-inline">Mostra apenas quem teve <strong>requisição recente</strong> no painel (campo <code>updated_at</code> em <code>adms_sessions</code>):</span>
                <span class="d-md-none"><strong>Só quem está ativo</strong> no painel neste intervalo:</span>
                <?= htmlspecialchars($idleDescription, ENT_QUOTES, 'UTF-8'); ?>.
                <span class="d-none d-md-inline"> Quem fechou o navegador ou ficou inativo além desse intervalo deixa de aparecer até nova atividade.</span>
                <?php if ($consultedAt !== ''): ?>
                    <span class="d-block mt-2 mt-md-1 text-muted"><i class="far fa-clock me-1"></i>Consulta: <?= htmlspecialchars($consultedAt, ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endif; ?>
            </div>

            <?php if ($total === 0): ?>
                <p class="text-muted mb-0">Nenhum usuário com atividade nesta janela no momento. Toque em <strong>Atualizar</strong> para consultar de novo.</p>
            <?php else: ?>

                <!-- Desktop: tabela -->
                <div class="table-responsive connected-users-desktop d-none d-md-block">
                    <table class="table table-bordered table-hover table-striped table-sm mb-0 align-middle">
                        <thead class="table-success">
                            <tr>
                                <th class="text-start ps-2" scope="col">Usuário</th>
                                <th class="text-start ps-2" scope="col">E-mail</th>
                                <th class="text-start ps-2" scope="col">@usuário</th>
                                <th class="text-start ps-2" scope="col">Última atividade</th>
                                <th class="text-start ps-2" scope="col">Conectado desde</th>
                                <th class="text-start ps-2" scope="col">ID sessão (parcial)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sessions as $row): ?>
                                <?php
                                $uid = (int)($row['user_id'] ?? 0);
                                $isSelf = $currentUserId > 0 && $uid === $currentUserId;
                                $sid = (string)($row['session_id'] ?? '');
                                $sidShort = $sid !== '' ? (mb_strlen($sid) > 24 ? mb_substr($sid, 0, 12) . '…' . mb_substr($sid, -8) : $sid) : '—';
                                ?>
                                <tr class="<?= $isSelf ? 'table-primary' : ''; ?>">
                                    <td class="text-start ps-2">
                                        <?= htmlspecialchars((string)($row['user_name'] ?? '—')); ?>
                                        <?php if ($isSelf): ?>
                                            <span class="badge bg-primary ms-1">Você</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-start ps-2 small"><?= htmlspecialchars((string)($row['user_email'] ?? '')); ?></td>
                                    <td class="text-start ps-2 small text-muted"><?= htmlspecialchars((string)($row['user_username'] ?? '')); ?></td>
                                    <td class="text-start ps-2 small"><?= !empty($row['updated_at']) ? date('d/m/Y H:i:s', strtotime((string)$row['updated_at'])) : '—'; ?></td>
                                    <td class="text-start ps-2 small"><?= !empty($row['created_at']) ? date('d/m/Y H:i:s', strtotime((string)$row['created_at'])) : '—'; ?></td>
                                    <td class="text-start ps-2 small font-monospace text-break" title="<?= htmlspecialchars($sid); ?>"><?= htmlspecialchars($sidShort); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile: cartões -->
                <div class="connected-users-mobile d-md-none">
                    <?php foreach ($sessions as $row): ?>
                        <?php
                        $uid = (int)($row['user_id'] ?? 0);
                        $isSelf = $currentUserId > 0 && $uid === $currentUserId;
                        $sid = (string)($row['session_id'] ?? '');
                        $sidShort = $sid !== '' ? (mb_strlen($sid) > 24 ? mb_substr($sid, 0, 12) . '…' . mb_substr($sid, -8) : $sid) : '—';
                        $updated = !empty($row['updated_at']) ? date('d/m/Y H:i:s', strtotime((string)$row['updated_at'])) : '—';
                        $created = !empty($row['created_at']) ? date('d/m/Y H:i:s', strtotime((string)$row['created_at'])) : '—';
                        ?>
                        <div class="card mb-3 shadow-sm border <?= $isSelf ? 'border-primary border-2' : ''; ?>">
                            <div class="card-header py-2 <?= $isSelf ? 'bg-primary bg-opacity-10' : 'bg-light'; ?>">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-user-circle text-success"></i>
                                    <span class="fw-semibold text-break"><?= htmlspecialchars((string)($row['user_name'] ?? '—')); ?></span>
                                    <?php if ($isSelf): ?>
                                        <span class="badge bg-primary">Você</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body py-3">
                                <dl class="connected-user-dl">
                                    <dt>E-mail</dt>
                                    <dd><?= htmlspecialchars((string)($row['user_email'] ?? '—')); ?></dd>
                                    <dt>Login</dt>
                                    <dd class="text-muted"><?= htmlspecialchars((string)($row['user_username'] ?? '—')); ?></dd>
                                    <dt>Última atividade</dt>
                                    <dd><span class="badge bg-success bg-opacity-25 text-dark"><?= htmlspecialchars($updated); ?></span></dd>
                                    <dt>Conectado desde</dt>
                                    <dd><span class="badge bg-secondary bg-opacity-25 text-dark"><?= htmlspecialchars($created); ?></span></dd>
                                    <dt>Sessão</dt>
                                    <dd class="font-monospace small" title="<?= htmlspecialchars($sid); ?>"><?= htmlspecialchars($sidShort); ?></dd>
                                </dl>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>
        </div>
    </div>
</div>
