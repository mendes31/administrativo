<?php include __DIR__ . '/partials/module_head.php'; ?>
<?php
$rows = $this->data['leaderboard'] ?? [];
$top1 = $rows[0] ?? null;
$top2 = $rows[1] ?? null;
$top3 = $rows[2] ?? null;
$rest = array_slice($rows, 3);
$initials = static function (string $name): string {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $first = $parts[0] ?? '';
    $last = $parts[count($parts) - 1] ?? '';
    $txt = mb_strtoupper(mb_substr($first, 0, 1) . mb_substr($last, 0, 1));
    return $txt !== '' ? $txt : 'U';
};
    $renderAvatar = static function (array $row, int $sizePx, string $className = '') use ($initials): string {
        $uid = (int)($row['user_id'] ?? 0);
        $img = (string)($row['user_image'] ?? '');
        $name = (string)($row['user_name'] ?? 'Usuário');
        if (\App\adms\Helpers\ImageHelper::userImageExists($uid, $img)) {
            $avatarPath = 'users/' . $uid . '/' . $img;
            return \App\adms\Helpers\ImageHelper::displayImage($avatarPath, [
                'class' => trim('rounded-circle ' . $className),
                'style' => 'width:' . $sizePx . 'px;height:' . $sizePx . 'px;object-fit:cover;',
                'alt' => 'Avatar de ' . $name,
                'title' => $name,
            ]);
        }
        return \App\adms\Helpers\ImageHelper::renderInitialsAvatar($name, $sizePx, [
            'class' => $className,
            'style' => 'border: 3px solid #d1d5db;',
            'title' => $name,
        ]);
    };
?>
<style>
    .gami-podium-wrap { background: #e9f6ff; border-radius: 16px; padding: 22px 12px 14px; }
    .gami-podium-col { text-align: center; min-height: 150px; }
    .gami-podium-avatar { width: 92px; height: 92px; border-radius: 999px; margin: 0 auto 8px; }
    .gami-podium-first .gami-podium-avatar { width: 118px; height: 118px; border-color: #facc15; }
    .gami-badge-rank {
        width: 30px; height: 30px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center;
        font-weight: 700; margin-top: -8px; margin-bottom: 4px;
    }
    .gami-rank-1 { background: #facc15; color: #111827; }
    .gami-rank-2 { background: #9ca3af; color: #111827; }
    .gami-rank-3 { background: #d97706; color: #fff; }
    .gami-top-name { font-size: .85rem; color: #374151; line-height: 1.2; min-height: 2rem; }
    .gami-top-points { font-size: 1.8rem; font-weight: 800; line-height: 1; color: #1f2937; }
    .gami-top-points-sm { font-size: 1.55rem; font-weight: 800; line-height: 1; color: #1f2937; }
    .gami-list-row { background: #f3f4f6; border-radius: 999px; padding: 8px 14px; margin-bottom: 8px; }
    .gami-list-rank { min-width: 26px; font-weight: 700; color: #4b5563; }
    .gami-list-avatar { width: 28px; height: 28px; border-radius: 999px; display: inline-flex; overflow: hidden; }
    .gami-list-name { font-size: .9rem; color: #111827; }
    .gami-list-points { font-weight: 800; color: #111827; }
</style>
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <div class="mb-2 d-flex flex-column flex-md-row gap-2 align-items-start align-items-md-center">
        <h2 class="mt-2 mt-md-3 mb-0"><i class="fas fa-trophy text-warning me-2"></i>Ranking de pontos</h2>
        <ol class="breadcrumb mb-0 mt-1 ms-md-auto small">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item">Gamificação</li>
        </ol>
    </div>
    <p class="text-muted small">Classificação geral por pontos acumulados (timeline + quizzes concluídos).</p>
    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <?php if ($rows === []): ?>
        <div class="alert alert-info">Ainda não há pontos registados.</div>
    <?php else: ?>
        <div class="card border-light shadow mb-3">
            <div class="card-body">
                <div class="gami-podium-wrap">
                    <div class="row g-2 align-items-end">
                        <div class="col-4 gami-podium-col">
                            <?php if ($top2): ?>
                                <div class="gami-podium-avatar"><?= $renderAvatar($top2, 92, 'gami-podium-avatar') ?></div>
                                <div class="gami-badge-rank gami-rank-2">2</div>
                                <div class="gami-top-name"><?= htmlspecialchars((string)$top2['user_name']) ?></div>
                                <div class="gami-top-points-sm"><?= (int)$top2['total_points'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-4 gami-podium-col gami-podium-first">
                            <?php if ($top1): ?>
                                <div class="gami-podium-avatar"><?= $renderAvatar($top1, 118, 'gami-podium-avatar') ?></div>
                                <div class="gami-badge-rank gami-rank-1">1</div>
                                <div class="gami-top-name fw-semibold"><?= htmlspecialchars((string)$top1['user_name']) ?></div>
                                <div class="gami-top-points"><?= (int)$top1['total_points'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-4 gami-podium-col">
                            <?php if ($top3): ?>
                                <div class="gami-podium-avatar"><?= $renderAvatar($top3, 92, 'gami-podium-avatar') ?></div>
                                <div class="gami-badge-rank gami-rank-3">3</div>
                                <div class="gami-top-name"><?= htmlspecialchars((string)$top3['user_name']) ?></div>
                                <div class="gami-top-points-sm"><?= (int)$top3['total_points'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($rest !== []): ?>
            <div class="card border-light shadow">
                <div class="card-body">
                    <?php foreach ($rest as $idx => $r): ?>
                        <?php $rank = $idx + 4; ?>
                        <div class="d-flex align-items-center gami-list-row">
                            <div class="gami-list-rank"><?= $rank ?></div>
                            <div class="gami-list-avatar me-2"><?= $renderAvatar($r, 28, 'gami-list-avatar') ?></div>
                            <div class="gami-list-name flex-grow-1"><?= htmlspecialchars((string)$r['user_name']) ?></div>
                            <div class="gami-list-points"><?= (int)$r['total_points'] ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
