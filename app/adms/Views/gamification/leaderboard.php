<?php include __DIR__ . '/partials/module_head.php'; ?>
<?php
$rows = $this->data['leaderboard'] ?? [];
$scope = (string)($this->data['scope'] ?? 'general');
$monthRef = (string)($this->data['month_ref'] ?? date('Y-m'));
$departmentId = (int)($this->data['department_id'] ?? 0);
$departments = $this->data['departments'] ?? [];
$myLevel = $this->data['my_level'] ?? null;
$myBadges = $this->data['my_badges'] ?? [];
$myMissions = $this->data['my_weekly_missions'] ?? [];
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
            'style' => '',
            'title' => $name,
        ]);
    };
?>
<style>
    .gami-podium-wrap {
        background: linear-gradient(180deg, #e9f6ff 0%, #edf7ff 100%);
        border-radius: 16px;
        padding: 18px 14px 8px;
    }
    .gami-podium-col { text-align: center; min-height: 188px; display: flex; flex-direction: column; justify-content: flex-end; }
    .gami-podium-avatar { width: 96px; height: 96px; border-radius: 999px; margin: 0 auto 8px; border: 3px solid #d1d5db; }
    .gami-podium-first .gami-podium-avatar { width: 132px; height: 132px; border: 4px solid #facc15; }
    .gami-podium-second .gami-podium-avatar { border-color: #9ca3af; }
    .gami-podium-third .gami-podium-avatar { border-color: #d97706; }
    .gami-crown { font-size: 1.5rem; line-height: 1; color: #facc15; margin-bottom: 4px; }
    .gami-badge-rank {
        width: 34px; height: 34px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center;
        font-weight: 800; margin: -10px auto 4px;
        border: 2px solid rgba(255,255,255,.8);
    }
    .gami-rank-1 { background: #facc15; color: #111827; }
    .gami-rank-2 { background: #9ca3af; color: #111827; }
    .gami-rank-3 { background: #d97706; color: #fff; }
    .gami-top-name { font-size: .88rem; color: #374151; line-height: 1.2; min-height: 2.1rem; }
    .gami-top-name-main { font-size: .95rem; font-weight: 700; }
    .gami-top-points { font-size: 2rem; font-weight: 900; line-height: 1; color: #111827; }
    .gami-top-points-sm { font-size: 1.65rem; font-weight: 800; line-height: 1; color: #1f2937; }
    .gami-podium-second { padding-top: 22px; }
    .gami-podium-first { padding-top: 0; }
    .gami-podium-third { padding-top: 30px; }
    .gami-list-row {
        background: #f3f4f6;
        border-radius: 12px;
        padding: 10px 14px;
        margin-bottom: 8px;
        border: 1px solid #e5e7eb;
    }
    .gami-list-rank {
        min-width: 28px;
        font-weight: 700;
        color: #4b5563;
        background: #e5e7eb;
        border-radius: 999px;
        text-align: center;
        height: 28px;
        line-height: 28px;
    }
    .gami-list-avatar { width: 32px; height: 32px; border-radius: 999px; display: inline-flex; overflow: hidden; }
    .gami-list-name { font-size: .92rem; color: #111827; }
    .gami-list-points { font-weight: 900; color: #111827; font-size: 1.1rem; }
    .gami-missions-mobile { display: none; }
    @media (min-width: 768px) {
        .gami-filter-row { flex-wrap: nowrap; }
        .gami-filter-actions { display: flex; gap: .4rem; width: 100%; }
        .gami-filter-actions .btn { white-space: nowrap; font-size: .8rem; padding: .32rem .5rem; }
        .gami-filter-row .form-select-sm,
        .gami-filter-row .form-control-sm { font-size: .84rem; }
    }
    .gami-filter-toggle-mobile { display: none; }
    @media (max-width: 767.98px) {
        .gami-podium-wrap { padding: 14px 8px 6px; }
        .gami-podium-col { min-height: 160px; }
        .gami-podium-avatar { width: 74px; height: 74px; }
        .gami-podium-first .gami-podium-avatar { width: 96px; height: 96px; }
        .gami-top-name { font-size: .75rem; min-height: 1.7rem; }
        .gami-top-name-main { font-size: .8rem; }
        .gami-top-points { font-size: 1.5rem; }
        .gami-top-points-sm { font-size: 1.2rem; }
        .gami-badge-rank { width: 28px; height: 28px; font-size: .82rem; }
        .gami-crown { font-size: 1.1rem; margin-bottom: 2px; }
        .gami-podium-second { padding-top: 14px; }
        .gami-podium-third { padding-top: 18px; }
        .gami-list-row { padding: 8px 10px; border-radius: 10px; }
        .gami-list-avatar { width: 28px; height: 28px; }
        .gami-list-name { font-size: .84rem; }
        .gami-list-points { font-size: .95rem; }
        .gami-missions-table { display: none; }
        .gami-missions-mobile { display: block; padding: 8px; }
        .gami-mission-card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 9px 10px; margin-bottom: 8px; background: #fff; }
        .gami-mission-top { font-size: .86rem; font-weight: 600; color: #1f2937; margin-bottom: 4px; }
        .gami-mission-meta { font-size: .78rem; color: #4b5563; display: flex; justify-content: space-between; }
        .gami-filter-toggle-mobile { display: inline-flex; }
        .gami-filters-wrap.collapse:not(.show) { display: none; }
    }
</style>
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <div class="mb-2 d-flex flex-column flex-md-row gap-2 align-items-start align-items-md-center">
        <h2 class="mt-2 mt-md-3 mb-0"><i class="fas fa-trophy text-warning me-2"></i>Ranking de pontos</h2>
        <button class="btn btn-outline-secondary btn-sm gami-filter-toggle-mobile" type="button"
                data-bs-toggle="collapse" data-bs-target="#gamiFiltersCollapse"
                aria-expanded="false" aria-controls="gamiFiltersCollapse">
            <i class="fas fa-sliders-h me-1"></i>Filtros
        </button>
        <ol class="breadcrumb mb-0 mt-1 ms-md-auto small">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item">Gamificação</li>
        </ol>
    </div>
    <p class="text-muted small">Classificação com desempate por quem atingiu a pontuação primeiro.</p>
    <div id="gamiFiltersCollapse" class="gami-filters-wrap collapse d-md-block mb-3">
    <form method="get" class="row g-2 mb-0 align-items-end gami-filter-row">
        <div class="col-12 col-md-3 col-lg-2">
            <label class="form-label small mb-1">Escopo</label>
            <select name="scope" class="form-select form-select-sm">
                <option value="general" <?= $scope === 'general' ? 'selected' : '' ?>>Geral</option>
                <option value="monthly" <?= $scope === 'monthly' ? 'selected' : '' ?>>Mensal</option>
                <option value="department" <?= $scope === 'department' ? 'selected' : '' ?>>Por setor</option>
            </select>
        </div>
        <div class="col-12 col-md-3 col-lg-2">
            <label class="form-label small mb-1">Mês</label>
            <input type="month" name="month" class="form-control form-control-sm" value="<?= htmlspecialchars($monthRef) ?>">
        </div>
        <div class="col-12 col-md-4 col-lg-3">
            <label class="form-label small mb-1">Setor</label>
            <select name="department_id" class="form-select form-select-sm">
                <option value="0">Todos</option>
                <?php foreach ($departments as $dep): ?>
                    <option value="<?= (int)$dep['id'] ?>" <?= $departmentId === (int)$dep['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)$dep['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-2 col-lg-3 d-flex align-items-end">
            <div class="gami-filter-actions">
                <button type="submit" class="btn btn-sm btn-primary w-100">Aplicar</button>
                <a href="<?php echo $_ENV['URL_ADM']; ?>gamification-leaderboard" class="btn btn-sm btn-outline-secondary w-100">Limpar</a>
            </div>
        </div>
    </form>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-6">
            <div class="card border-light shadow h-100">
                <div class="card-header">Meu nível atual</div>
                <div class="card-body">
                    <?php if ($myLevel): ?>
                        <span class="badge bg-<?= htmlspecialchars((string)($myLevel['badge_color'] ?? 'secondary')) ?>">
                            <?= htmlspecialchars((string)($myLevel['name'] ?? '')) ?>
                        </span>
                        <span class="text-muted small ms-2">mín. <?= (int)($myLevel['min_points'] ?? 0) ?> pontos</span>
                    <?php else: ?>
                        <span class="text-muted small">Sem nível definido.</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card border-light shadow h-100">
                <div class="card-header">Minhas badges</div>
                <div class="card-body">
                    <?php if ($myBadges !== []): ?>
                        <?php foreach (array_slice($myBadges, 0, 4) as $badge): ?>
                            <span class="badge bg-light text-dark border me-1 mb-1"><?= htmlspecialchars((string)($badge['name'] ?? '')) ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="text-muted small">Nenhuma badge conquistada ainda.</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($rows === []): ?>
        <div class="alert alert-info">Ainda não há pontos registados.</div>
    <?php else: ?>
        <div class="card border-light shadow mb-3">
            <div class="card-body">
                <div class="gami-podium-wrap">
                    <div class="row g-2 align-items-end">
                        <div class="col-4 gami-podium-col gami-podium-second">
                            <?php if ($top2): ?>
                                <?= $renderAvatar($top2, 92, 'gami-podium-avatar') ?>
                                <div class="gami-badge-rank gami-rank-2">2</div>
                                <div class="gami-top-name"><?= htmlspecialchars((string)$top2['user_name']) ?></div>
                                <div class="gami-top-points-sm"><?= (int)$top2['total_points'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-4 gami-podium-col gami-podium-first">
                            <?php if ($top1): ?>
                                <div class="gami-crown"><i class="fas fa-crown"></i></div>
                                <?= $renderAvatar($top1, 118, 'gami-podium-avatar') ?>
                                <div class="gami-badge-rank gami-rank-1">1</div>
                                <div class="gami-top-name gami-top-name-main"><?= htmlspecialchars((string)$top1['user_name']) ?></div>
                                <div class="gami-top-points"><?= (int)$top1['total_points'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-4 gami-podium-col gami-podium-third">
                            <?php if ($top3): ?>
                                <?= $renderAvatar($top3, 92, 'gami-podium-avatar') ?>
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
                            <?= $renderAvatar($r, 28, 'gami-list-avatar me-2') ?>
                            <div class="gami-list-name flex-grow-1"><?= htmlspecialchars((string)$r['user_name']) ?></div>
                            <div class="gami-list-points"><?= (int)$r['total_points'] ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($myMissions !== []): ?>
            <div class="card border-light shadow mt-3">
            <div class="card-header">Missões da semana</div>
            <div class="card-body p-0">
                <div class="table-responsive gami-missions-table">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Missão</th><th>Progresso</th><th class="text-end">Recompensa</th></tr></thead>
                        <tbody>
                        <?php foreach ($myMissions as $mission): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)($mission['title'] ?? '')) ?></td>
                                <td>
                                    <?= (int)($mission['current_value'] ?? 0) ?>/<?= (int)($mission['target_value'] ?? 0) ?>
                                    <?php if (!empty($mission['is_completed'])): ?><span class="badge bg-success ms-1">Concluída</span><?php endif; ?>
                                </td>
                                <td class="text-end"><?= (int)($mission['reward_points'] ?? 0) ?> pts</td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="gami-missions-mobile">
                    <?php foreach ($myMissions as $mission): ?>
                        <div class="gami-mission-card">
                            <div class="gami-mission-top"><?= htmlspecialchars((string)($mission['title'] ?? '')) ?></div>
                            <div class="gami-mission-meta">
                                <span>Progresso: <?= (int)($mission['current_value'] ?? 0) ?>/<?= (int)($mission['target_value'] ?? 0) ?></span>
                                <span><?= (int)($mission['reward_points'] ?? 0) ?> pts</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
