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
    <p class="text-muted small">Classificação com desempate por quem atingiu a pontuação primeiro.</p>
    <form method="get" class="row g-2 mb-3">
        <div class="col-12 col-md-3">
            <label class="form-label small mb-1">Escopo</label>
            <select name="scope" class="form-select form-select-sm">
                <option value="general" <?= $scope === 'general' ? 'selected' : '' ?>>Geral</option>
                <option value="monthly" <?= $scope === 'monthly' ? 'selected' : '' ?>>Mensal</option>
                <option value="department" <?= $scope === 'department' ? 'selected' : '' ?>>Por setor</option>
            </select>
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label small mb-1">Mês</label>
            <input type="month" name="month" class="form-control form-control-sm" value="<?= htmlspecialchars($monthRef) ?>">
        </div>
        <div class="col-12 col-md-4">
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
        <div class="col-12 col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-sm btn-primary w-100">Aplicar</button>
        </div>
    </form>
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

    <?php if ($myMissions !== []): ?>
        <div class="card border-light shadow mt-3">
            <div class="card-header">Missões da semana</div>
            <div class="card-body p-0">
                <div class="table-responsive">
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
            </div>
        </div>
    <?php endif; ?>
</div>
