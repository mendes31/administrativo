<?php
$total = (int)($this->data['total'] ?? 0);
$sessions = $this->data['sessions'] ?? [];
$currentUserId = (int)($this->data['current_user_id'] ?? 0);
$idleDescription = (string)($this->data['idle_description'] ?? '');
$consultedAt = (string)($this->data['consulted_at'] ?? '');
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Usuários conectados</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Usuários conectados</li>
        </ol>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2 flex-wrap align-items-center justify-content-between">
            <span>Listar</span>
            <div class="hstack gap-2 ms-auto flex-wrap align-items-center">
                <a href="?" class="btn btn-outline-primary btn-sm" title="Recarregar a lista a partir do banco">
                    <i class="fas fa-sync-alt me-1"></i> Atualizar
                </a>
                <span class="badge bg-secondary"><?= $total; ?> online</span>
            </div>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <div class="alert alert-light border mb-3 small">
                <i class="fas fa-info-circle text-primary me-1"></i>
                Mostra apenas quem teve <strong>requisição recente</strong> no painel (campo <code>updated_at</code> em <code>adms_sessions</code>):
                <?= htmlspecialchars($idleDescription, ENT_QUOTES, 'UTF-8'); ?>.
                Quem fechou o navegador ou ficou inativo além desse intervalo deixa de aparecer até nova atividade.
                <?php if ($consultedAt !== ''): ?>
                    <span class="d-block mt-1 text-muted"><i class="far fa-clock me-1"></i>Consulta: <?= htmlspecialchars($consultedAt, ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endif; ?>
            </div>

            <?php if ($total === 0): ?>
                <p class="text-muted mb-0">Nenhum usuário com atividade nesta janela no momento. Clique em <strong>Atualizar</strong> para consultar de novo.</p>
            <?php else: ?>
                <div class="table-responsive">
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
            <?php endif; ?>
        </div>
    </div>
</div>
