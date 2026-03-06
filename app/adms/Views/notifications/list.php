<?php
use App\adms\Helpers\CSRFHelper;
$notifications = $this->data['notifications'] ?? [];
$csrf_token = $this->data['csrf_token'] ?? '';
?>
<div class="container-fluid px-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <div>
            <h2 class="mb-1">Minhas Notificações</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item active">Notificações</li>
                </ol>
            </nav>
        </div>
        <?php if (array_filter($notifications, fn($n) => empty($n['read_at']))): ?>
        <form method="post" class="d-inline">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="mark_all_read" value="1">
            <button type="submit" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-check-double me-1"></i> Marcar todas como lidas
            </button>
        </form>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../partials/alerts.php'; ?>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <?php if (empty($notifications)): ?>
            <div class="p-4 text-center text-muted">
                <i class="fas fa-bell fa-3x mb-3 opacity-50"></i>
                <p class="mb-0">Você não possui notificações.</p>
                <a href="<?= $_ENV['URL_ADM']; ?>dashboard" class="btn btn-outline-secondary btn-sm mt-3">Voltar ao Dashboard</a>
            </div>
            <?php else: ?>
            <ul class="list-group list-group-flush">
                <?php foreach ($notifications as $n): ?>
                <li class="list-group-item d-flex align-items-start <?= empty($n['read_at']) ? 'bg-light' : ''; ?>">
                    <div class="flex-grow-1">
                        <?php if (($n['type'] ?? '') === 'projeto_etapa'): ?>
                        <i class="fas fa-tasks text-primary me-2"></i>
                        <?php endif; ?>
                        <a href="<?= !empty($n['link_url']) ? htmlspecialchars($n['link_url']) : '#'; ?><?= empty($n['read_at']) ? '?mark=' . (int)$n['id'] : ''; ?>"
                           class="text-decoration-none <?= empty($n['read_at']) ? 'fw-semibold text-dark' : 'text-secondary'; ?>">
                            <?= htmlspecialchars($n['title'] ?? ''); ?>
                        </a>
                        <?php if (!empty($n['message'])): ?>
                        <div class="small text-muted mt-1"><?= htmlspecialchars($n['message']); ?></div>
                        <?php endif; ?>
                        <div class="small text-muted mt-1">
                            <?= date('d/m/Y H:i', strtotime($n['created_at'] ?? 'now')); ?>
                            <?php if (empty($n['read_at'])): ?>
                            <span class="badge bg-warning text-dark ms-2">Não lida</span>
                            <a href="<?= $_ENV['URL_ADM']; ?>list-notifications?mark=<?= (int)$n['id']; ?>" class="ms-2 small">Marcar como lida</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (!empty($n['link_url'])): ?>
                    <a href="<?= htmlspecialchars($n['link_url']); ?><?= empty($n['read_at']) ? '?mark=' . (int)$n['id'] : ''; ?>" class="btn btn-sm btn-outline-primary">Abrir</a>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
