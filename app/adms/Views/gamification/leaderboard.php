<?php include __DIR__ . '/partials/module_head.php'; ?>
<?php
$rows = $this->data['leaderboard'] ?? [];
$scope = (string)($this->data['scope'] ?? 'general');
$monthRef = (string)($this->data['month_ref'] ?? date('Y-m'));
$departmentId = (int)($this->data['department_id'] ?? 0);
$departments = $this->data['departments'] ?? [];
$myLevel = $this->data['my_level'] ?? null;
$myLevelPrev = $this->data['my_level_previous_month'] ?? null;
$myMonthlyPoints = (int)($this->data['my_monthly_points'] ?? 0);
$myPrevMonthlyPoints = (int)($this->data['my_previous_month_points'] ?? 0);
$levelPrevMonthRef = (string)($this->data['level_prev_month_ref'] ?? '');
$myBadges = $this->data['my_badges'] ?? [];
$myMissions = $this->data['my_missions'] ?? [];
$missionMonthRange = (string)($this->data['mission_month_range_label'] ?? '');
$btnPerm = $this->data['buttonPermission'] ?? [];
$sessionUserId = (int)($_SESSION['user_id'] ?? 0);
$canRules = in_array('ListGamificationTimelineRules', $btnPerm, true);
$canLedger = in_array('ListGamificationPointLedger', $btnPerm, true) && $sessionUserId > 0;
$canQuizCatalog = in_array('GamificationQuizCatalog', $btnPerm, true);
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
    /* Uma linha estável em telas largas: larguras min/max por campo */
    @media (min-width: 992px) {
        .gami-filter-row {
            display: flex !important;
            flex-wrap: nowrap !important;
            align-items: flex-end;
            gap: .55rem;
            margin-left: 0;
            margin-right: 0;
        }
        .gami-filter-row > [class*="col-"] {
            flex: 0 0 auto !important;
            width: auto !important;
            max-width: none !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
        .gami-filter-row .form-select-sm,
        .gami-filter-row .form-control-sm { width: 100%; }
        .gami-filter-scope { min-width: 120px; max-width: 180px; flex: 0 0 150px; }
        .gami-filter-month { min-width: 140px; max-width: 200px; flex: 0 0 165px; }
        .gami-filter-sector { min-width: 180px; max-width: 380px; flex: 1 1 240px; }
        .gami-filter-actions-wrap { min-width: 150px; max-width: 220px; flex: 0 0 190px; }
        .gami-filter-actions { flex-wrap: nowrap; }
    }
    .gami-filter-toggle-mobile { display: none; }
    @media (max-width: 767.98px) {
        .gami-podium-wrap { padding: 14px 8px 6px; }
        .gami-podium-wrap .row { --bs-gutter-x: .55rem; }
        .gami-podium-col { min-height: 0; }
        .gami-podium-avatar { width: 74px; height: 74px; }
        .gami-podium-first .gami-podium-avatar { width: 96px; height: 96px; }
        .gami-podium-second { padding-right: 4px; }
        .gami-podium-third { padding-left: 4px; }
        .gami-top-name { font-size: .75rem; min-height: 2.2rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .gami-top-name-main { font-size: .8rem; }
        .gami-top-name,
        .gami-top-name-main { max-width: 96%; margin-left: auto; margin-right: auto; overflow-wrap: anywhere; }
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
        .gami-title-filter-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 0.5rem; }
        .gami-title-filter-row h2 { min-width: 0; }
    }
    @media (max-width: 430px) {
        .gami-podium-wrap { padding: 12px 6px 6px; }
        .gami-podium-wrap .row { --bs-gutter-x: .75rem; }
        .gami-podium-avatar { width: 68px; height: 68px; }
        .gami-podium-first .gami-podium-avatar { width: 88px; height: 88px; }
        .gami-podium-col { min-height: 0; }
        .gami-top-name { font-size: .72rem; }
        .gami-top-name-main { font-size: .78rem; }
        .gami-top-name { min-height: 2.05rem; }
    }
</style>
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <div class="mb-2 d-flex flex-column flex-md-row gap-2 align-items-start align-items-md-center">
        <div class="gami-title-filter-row w-100 w-md-auto">
        <h2 class="mt-2 mt-md-3 mb-0"><i class="fas fa-trophy text-warning me-2"></i>Ranking de pontos</h2>
        <button class="adm-filter-mobile-trigger adm-filter-mobile-trigger--compact gami-filter-toggle-mobile mt-2 mt-md-0 align-self-center" type="button"
                data-bs-toggle="collapse" data-bs-target="#gamiFiltersCollapse"
                aria-expanded="false" aria-controls="gamiFiltersCollapse">
            <span class="adm-filter-mobile-trigger__leading">
                <i class="fas fa-sliders-h" aria-hidden="true"></i>
                <span>Filtros</span>
            </span>
            <span class="adm-filter-mobile-trigger__chevron" aria-hidden="true"><i class="fas fa-chevron-down"></i></span>
        </button>
        </div>
        <ol class="breadcrumb mb-0 mt-1 ms-md-auto small">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item">Gamificação</li>
        </ol>
    </div>
    <p class="text-muted small mb-1">Classificação com desempate por quem atingiu a pontuação primeiro.</p>
    <p class="text-muted small">Os cartões de <strong>nível</strong> usam sempre os pontos do <strong>mês selecionado</strong> no filtro e do <strong>mês civil imediatamente anterior</strong> (limiares configurados em Gamificação). A <strong>lista</strong> do ranking segue o escopo: <strong>Geral</strong> = pontuação total acumulada; <strong>Mensal</strong> ou <strong>Por setor</strong> = só pontos do mês selecionado<?= $scope !== 'general' ? ' (<strong>' . htmlspecialchars($monthRef) . '</strong>)' : '' ?>.</p>
    <?php if ($canRules || $canLedger || $canQuizCatalog): ?>
        <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
            <span class="text-muted small">Consultar:</span>
            <?php if ($canRules): ?>
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-gamification-timeline-rules" class="btn btn-sm btn-outline-primary"><i class="fas fa-book me-1"></i>Regras e pontuação</a>
            <?php endif; ?>
            <?php if ($canLedger): ?>
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-gamification-point-ledger?user_id=<?= $sessionUserId ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-receipt me-1"></i>Extrato dos meus pontos</a>
            <?php endif; ?>
            <?php if ($canQuizCatalog): ?>
                <a href="<?php echo $_ENV['URL_ADM']; ?>gamification-quiz-catalog" class="btn btn-sm btn-outline-secondary"><i class="fas fa-question-circle me-1"></i>Quizzes</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <div id="gamiFiltersCollapse" class="gami-filters-wrap collapse d-md-block mb-3">
    <form method="get" class="row g-2 mb-0 align-items-end gami-filter-row">
        <div class="col-12 col-md-3 col-lg-2 gami-filter-scope">
            <label class="form-label small mb-1">Escopo</label>
            <select name="scope" class="form-select form-select-sm">
                <option value="general" <?= $scope === 'general' ? 'selected' : '' ?>>Geral</option>
                <option value="monthly" <?= $scope === 'monthly' ? 'selected' : '' ?>>Mensal</option>
                <option value="department" <?= $scope === 'department' ? 'selected' : '' ?>>Por setor</option>
            </select>
        </div>
        <div class="col-12 col-md-3 col-lg-2 gami-filter-month">
            <label class="form-label small mb-1">Mês</label>
            <input type="month" name="month" class="form-control form-control-sm" value="<?= htmlspecialchars($monthRef) ?>">
        </div>
        <div class="col-12 col-md-4 col-lg-3 gami-filter-sector">
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
        <div class="col-12 col-md-2 col-lg-3 d-flex align-items-end gami-filter-actions-wrap">
            <div class="gami-filter-actions">
                <button type="submit" class="btn btn-sm btn-primary w-100">Aplicar</button>
                <a href="<?php echo $_ENV['URL_ADM']; ?>gamification-leaderboard" class="btn btn-sm btn-outline-secondary w-100">Limpar</a>
            </div>
        </div>
    </form>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card border-light shadow h-100">
                <div class="card-header">Nível no mês selecionado</div>
                <div class="card-body">
                    <div class="text-muted small mb-2">Referência: <strong><?= htmlspecialchars($monthRef) ?></strong></div>
                    <?php if ($myLevel): ?>
                        <span class="badge bg-<?= htmlspecialchars((string)($myLevel['badge_color'] ?? 'secondary')) ?> fs-6">
                            <?= htmlspecialchars((string)($myLevel['name'] ?? '')) ?>
                        </span>
                        <div class="small mt-2"><span class="fw-semibold"><?= $myMonthlyPoints ?></span> pts neste mês · limiar do nível: mín. <?= (int)($myLevel['min_points'] ?? 0) ?> pts no mês</div>
                    <?php else: ?>
                        <span class="text-muted small">Sem nível definido (nenhum limiar ativo).</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card border-light shadow h-100">
                <div class="card-header">Nível no mês anterior</div>
                <div class="card-body">
                    <?php if ($levelPrevMonthRef !== ''): ?>
                        <div class="text-muted small mb-2">Referência: <strong><?= htmlspecialchars($levelPrevMonthRef) ?></strong></div>
                    <?php endif; ?>
                    <?php if ($myLevelPrev): ?>
                        <span class="badge bg-<?= htmlspecialchars((string)($myLevelPrev['badge_color'] ?? 'secondary')) ?> fs-6">
                            <?= htmlspecialchars((string)($myLevelPrev['name'] ?? '')) ?>
                        </span>
                        <div class="small mt-2"><span class="fw-semibold"><?= $myPrevMonthlyPoints ?></span> pts naquele mês · limiar do nível: mín. <?= (int)($myLevelPrev['min_points'] ?? 0) ?> pts</div>
                    <?php else: ?>
                        <span class="text-muted small">Sem nível definido (nenhum limiar ativo).</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
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
            <div class="card-header">Missões do mês</div>
            <?php if ($missionMonthRange !== ''): ?>
                <p class="small text-muted px-3 pt-2 mb-0">
                    Período <strong><?= htmlspecialchars($missionMonthRange) ?></strong>
                    (mês do filtro acima). O filtro de <strong>setor</strong> aplica-se ao ranking, não às suas missões.
                </p>
            <?php endif; ?>
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
