<?php
use App\adms\Helpers\CSRFHelper;
$notifications = $this->data['notifications'] ?? [];
$csrf_token = $this->data['csrf_token'] ?? '';
$hasUnreadSocial = !empty($this->data['has_unread_social']);

$urlAdm = rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/';

$now = new DateTimeImmutable('now');
$todayKey = $now->format('Y-m-d');

$buildMarkedUrl = static function (?string $linkUrl, int $id, bool $unread): string {
    if (!$unread) {
        return (string)($linkUrl ?? '#');
    }
    $base = (string)($linkUrl ?? '#');
    if ($base === '#' || $base === '') {
        return '#';
    }
    $sep = (strpos($base, '?') !== false) ? '&' : '?';
    return $base . $sep . 'mark_notification=' . $id;
};

$iconByType = static function (string $type): string {
    return match ($type) {
        'timeline_mention', 'comentario_mencao' => 'fas fa-at text-warning',
        'timeline_comment' => 'far fa-comment text-primary',
        'timeline_comment_reaction' => 'fas fa-heart text-danger',
        'timeline_reaction' => 'fas fa-heart text-danger',
        'timeline_share' => 'fas fa-retweet text-success',
        'projeto_etapa' => 'fas fa-tasks text-primary',
        default => 'far fa-bell text-secondary',
    };
};

/** Menções (@): prioridade alta na ordenação e destaque visual. */
$isMentionHighPriority = static function (array $n): bool {
    if ((int)($n['priority'] ?? 0) >= \App\adms\Models\Repository\NotificationsRepository::PRIORITY_MENTION) {
        return true;
    }
    $t = (string)($n['type'] ?? '');

    return $t === 'timeline_mention' || $t === 'comentario_mencao';
};

$reactionLabel = static function (?string $message): string {
    $raw = trim((string)$message);
    if ($raw === '') {
        return '';
    }
    $raw = preg_replace('/^rea..o:\s*/iu', '', $raw) ?? $raw;
    $key = strtolower(trim($raw));
    return match ($key) {
        'like', 'curtir' => 'curtiu',
        'love', 'amei' => 'amou',
        'haha', 'risada' => 'achou engraçado',
        'wow', 'uau' => 'se surpreendeu',
        'sad', 'triste' => 'ficou triste',
        'angry', 'grr' => 'ficou bravo',
        default => 'reagiu',
    };
};

$isReactionNotification = static function (string $type, string $title, string $message): bool {
    $t = strtolower(trim($type));
    if ($t === 'timeline_reaction' || $t === 'timeline_comment_reaction' || $t === 'timeline_like' || $t === 'reaction') {
        return true;
    }

    $haystackTitle = mb_strtolower($title, 'UTF-8');
    $haystackMessage = mb_strtolower($message, 'UTF-8');

    if (str_contains($haystackMessage, 'reação:')
        || str_contains($haystackMessage, 'reacao:')
        || str_contains($haystackTitle, 'reagiu')) {
        return true;
    }

    return false;
};

$normalizeLegacyTitle = static function (string $title): string {
    $t = trim($title);
    if ($t === '') {
        return 'Notificação';
    }

    // Remove caracteres de controle invisíveis.
    $t = preg_replace('/[\x00-\x1F\x7F]/u', '', $t) ?? $t;

    // Remove qualquer prefixo legado até o primeiro caractere alfanumérico válido.
    if (preg_match('/[\p{L}\p{N}]/u', $t, $m, PREG_OFFSET_CAPTURE)) {
        $firstPos = (int)($m[0][1] ?? 0);
        if ($firstPos > 0) {
            $t = mb_substr($t, $firstPos);
        }
    }
    $t = ltrim($t);

    return $t !== '' ? $t : 'Notificação';
};

$relativeTime = static function (?string $createdAt) use ($now): string {
    if (empty($createdAt)) return '-';
    try {
        $dt = new DateTimeImmutable($createdAt);
        $diff = $now->getTimestamp() - $dt->getTimestamp();
        if ($diff < 60) return 'agora';
        if ($diff < 3600) return floor($diff / 60) . ' min';
        if ($diff < 86400) return floor($diff / 3600) . ' h';
        if ($diff < 604800) return floor($diff / 86400) . ' d';
        return $dt->format('d/m/Y');
    } catch (Throwable) {
        return '-';
    }
};

$today = [];
$older = [];
foreach ($notifications as $n) {
    $dateKey = '';
    try {
        $dateKey = !empty($n['created_at']) ? (new DateTimeImmutable((string)$n['created_at']))->format('Y-m-d') : '';
    } catch (Throwable) {
        $dateKey = '';
    }
    if ($dateKey === $todayKey) {
        $today[] = $n;
    } else {
        $older[] = $n;
    }
}
?>
<div class="container-fluid px-2 px-md-4 notifications-page">
    <link rel="stylesheet" href="<?= htmlspecialchars($urlAdm); ?>public/adms/css/notifications-modern.css?v=2">
    <style>
        @media (min-width: 993px) {
            .notifications-page .notif-item {
                display: grid !important;
                grid-template-columns: 10px 48px minmax(360px, 1fr) auto !important;
                align-items: start;
                gap: 1rem;
            }

            .notifications-page .notif-item__body {
                width: auto !important;
                min-width: 320px !important;
                max-width: none !important;
            }

            .notifications-page .notif-item__title,
            .notifications-page .notif-item__message,
            .notifications-page .notif-item__meta {
                word-break: normal !important;
                overflow-wrap: break-word !important;
            }

            .notifications-page .notif-item__actions {
                grid-column: 4 !important;
                margin-left: 0 !important;
                margin-top: 0 !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: flex-start !important;
                align-items: flex-end !important;
                gap: 0.2rem;
                width: auto !important;
            }

            .notifications-page .notif-read-state {
                font-size: 0.72rem;
                font-weight: 600;
                line-height: 1;
                margin-top: 0.05rem;
            }

            .notifications-page .notif-read-state--unread {
                color: #0d6efd;
            }

            .notifications-page .notif-read-state--read {
                color: #6c757d;
            }

            .notifications-page .notif-item__icon {
                width: 38px;
                height: 38px;
                margin-top: 0.1rem;
                margin-left: 0.2rem;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .notifications-page .notif-item__dot.is-hidden {
                visibility: hidden;
                background: transparent;
            }

            .notifications-page .notif-item__body {
                padding-left: 0.35rem;
            }

            .notifications-page .notif-item--mention-priority {
                border-left: 3px solid #e8590c;
                padding-left: 0.75rem;
                margin-left: -0.25rem;
                background: linear-gradient(90deg, rgba(232, 89, 12, 0.06) 0%, transparent 48%);
            }
            .notifications-page .notif-item--mention-priority .notif-item__title {
                font-weight: 600;
            }
            .notifications-page .notif-item--reaction .notif-item__title {
                white-space: normal;
                overflow: visible;
                text-overflow: clip;
                display: block;
            }
        }

        @media (max-width: 992px) {
            .notifications-page .notif-item {
                display: flex !important;
                flex-wrap: wrap;
                align-items: flex-start;
                gap: 0.55rem;
            }

            .notifications-page .notif-item__dot {
                flex: 0 0 8px;
                margin-top: 0.65rem;
            }

            .notifications-page .notif-item__dot.is-hidden {
                visibility: hidden;
                background: transparent;
            }

            .notifications-page .notif-item__icon {
                flex: 0 0 30px;
                width: 30px;
                height: 30px;
            }

            .notifications-page .notif-item__body {
                flex: 1 1 0;
                min-width: 0;
            }

            .notifications-page .notif-item__title,
            .notifications-page .notif-item__message,
            .notifications-page .notif-item__meta {
                word-break: normal !important;
                overflow-wrap: break-word !important;
            }

            .notifications-page .notif-item__actions {
                flex: 1 0 100%;
                margin-left: 38px;
                margin-top: 0.2rem;
                display: flex !important;
                flex-direction: row !important;
                justify-content: flex-start !important;
                align-items: center;
                gap: 0.45rem;
            }

            .notifications-page .notif-read-state {
                font-size: 0.72rem;
                font-weight: 600;
                margin-left: 0.1rem;
            }

            .notifications-page .notif-read-state--unread {
                color: #0d6efd;
            }

            .notifications-page .notif-read-state--read {
                color: #6c757d;
            }
        }
    </style>

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
        <?php if ($hasUnreadSocial): ?>
        <form method="post" class="d-inline">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="mark_all_read" value="1">
            <button type="submit" class="btn btn-outline-primary btn-sm"
                    title="Zera curtidas, comentários, menções e compartilhamentos da Timeline. Comunicados, políticas e avisos com ciência não são alterados.">
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
            <div class="p-3 p-md-4">
                <?php if (!empty($today)): ?>
                    <h6 class="text-uppercase text-muted small fw-bold mb-3">Hoje</h6>
                    <div class="d-grid gap-2 mb-4">
                        <?php foreach ($today as $n): ?>
                            <?php
                                $unread = empty($n['read_at']);
                                $type = (string)($n['type'] ?? '');
                                $openUrl = $buildMarkedUrl($n['link_url'] ?? null, (int)$n['id'], $unread);
                                $baseTitle = (string)($n['title'] ?? 'Notificação');
                                $baseMessage = (string)($n['message'] ?? '');
                                $displayTitle = $normalizeLegacyTitle($baseTitle);
                                $displayMessage = $baseMessage;
                                $isReaction = $isReactionNotification($type, $baseTitle, $baseMessage);
                                if ($isReaction) {
                                    $displayTitle = preg_replace('/\s+reagiu\s+.+$/iu', '', $baseTitle) ?: $baseTitle;
                                    $displayTitle = $normalizeLegacyTitle($displayTitle);
                                    $reactionWhere = ($type === 'timeline_comment_reaction')
                                        ? ' ao seu comentário'
                                        : ' na sua publicação';
                                    $displayTitle = trim($displayTitle) . ' ' . $reactionLabel($baseMessage) . $reactionWhere;
                                    $displayMessage = '';
                                }
                            ?>
                            <article class="notif-item <?= $unread ? 'notif-item--unread' : ''; ?> <?= $isReaction ? 'notif-item--reaction' : ''; ?> <?= $isMentionHighPriority($n) ? 'notif-item--mention-priority' : ''; ?>">
                                <div class="notif-item__dot <?= $unread ? '' : 'is-hidden'; ?>"></div>
                                <div class="notif-item__icon"><i class="<?= $iconByType($type); ?>"></i></div>
                                <div class="notif-item__body">
                                    <a class="notif-item__title" href="<?= htmlspecialchars($openUrl); ?>">
                                        <?= htmlspecialchars($displayTitle); ?>
                                    </a>
                                    <?php if ($displayMessage !== ''): ?>
                                        <div class="notif-item__message"><?= htmlspecialchars($displayMessage); ?></div>
                                    <?php endif; ?>
                                    <div class="notif-item__meta">
                                        <span><?= htmlspecialchars($relativeTime((string)($n['created_at'] ?? ''))); ?></span>
                                        <span class="mx-1">·</span>
                                        <span><?= date('d/m/Y H:i', strtotime($n['created_at'] ?? 'now')); ?></span>
                                        <?php if ($isMentionHighPriority($n)): ?>
                                            <span class="badge bg-warning text-dark ms-2">Menção</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="notif-item__actions">
                                    <?php if (!empty($n['link_url'])): ?>
                                        <a href="<?= htmlspecialchars($openUrl); ?>" class="btn btn-sm btn-outline-primary">Abrir</a>
                                    <?php endif; ?>
                                    <?php if ($unread): ?>
                                        <a href="<?= $urlAdm; ?>notificacoes?mark=<?= (int)$n['id']; ?>" class="btn btn-sm btn-link text-decoration-none">Marcar como lida</a>
                                    <?php else: ?>
                                        <span class="notif-read-state notif-read-state--read">Lida</span>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($older)): ?>
                    <h6 class="text-uppercase text-muted small fw-bold mb-3">Anteriores</h6>
                    <div class="d-grid gap-2">
                        <?php foreach ($older as $n): ?>
                            <?php
                                $unread = empty($n['read_at']);
                                $type = (string)($n['type'] ?? '');
                                $openUrl = $buildMarkedUrl($n['link_url'] ?? null, (int)$n['id'], $unread);
                                $baseTitle = (string)($n['title'] ?? 'Notificação');
                                $baseMessage = (string)($n['message'] ?? '');
                                $displayTitle = $normalizeLegacyTitle($baseTitle);
                                $displayMessage = $baseMessage;
                                $isReaction = $isReactionNotification($type, $baseTitle, $baseMessage);
                                if ($isReaction) {
                                    $displayTitle = preg_replace('/\s+reagiu\s+.+$/iu', '', $baseTitle) ?: $baseTitle;
                                    $displayTitle = $normalizeLegacyTitle($displayTitle);
                                    $reactionWhere = ($type === 'timeline_comment_reaction')
                                        ? ' ao seu comentário'
                                        : ' na sua publicação';
                                    $displayTitle = trim($displayTitle) . ' ' . $reactionLabel($baseMessage) . $reactionWhere;
                                    $displayMessage = '';
                                }
                            ?>
                            <article class="notif-item <?= $unread ? 'notif-item--unread' : ''; ?> <?= $isReaction ? 'notif-item--reaction' : ''; ?> <?= $isMentionHighPriority($n) ? 'notif-item--mention-priority' : ''; ?>">
                                <div class="notif-item__dot <?= $unread ? '' : 'is-hidden'; ?>"></div>
                                <div class="notif-item__icon"><i class="<?= $iconByType($type); ?>"></i></div>
                                <div class="notif-item__body">
                                    <a class="notif-item__title" href="<?= htmlspecialchars($openUrl); ?>">
                                        <?= htmlspecialchars($displayTitle); ?>
                                    </a>
                                    <?php if ($displayMessage !== ''): ?>
                                        <div class="notif-item__message"><?= htmlspecialchars($displayMessage); ?></div>
                                    <?php endif; ?>
                                    <div class="notif-item__meta">
                                        <span><?= htmlspecialchars($relativeTime((string)($n['created_at'] ?? ''))); ?></span>
                                        <span class="mx-1">·</span>
                                        <span><?= date('d/m/Y H:i', strtotime($n['created_at'] ?? 'now')); ?></span>
                                        <?php if ($isMentionHighPriority($n)): ?>
                                            <span class="badge bg-warning text-dark ms-2">Menção</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="notif-item__actions">
                                    <?php if (!empty($n['link_url'])): ?>
                                        <a href="<?= htmlspecialchars($openUrl); ?>" class="btn btn-sm btn-outline-primary">Abrir</a>
                                    <?php endif; ?>
                                    <?php if ($unread): ?>
                                        <a href="<?= $urlAdm; ?>notificacoes?mark=<?= (int)$n['id']; ?>" class="btn btn-sm btn-link text-decoration-none">Marcar como lida</a>
                                    <?php else: ?>
                                        <span class="notif-read-state notif-read-state--read">Lida</span>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
