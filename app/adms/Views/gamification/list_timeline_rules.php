<?php include __DIR__ . '/partials/module_head.php'; ?>
<?php
$criteriaOptions = $this->data['badge_criteria_options'] ?? [];
$missionEventOptions = $this->data['mission_event_options'] ?? [];
$levelColorOptions = $this->data['level_color_options'] ?? [];
$readOnly = !empty($this->data['gamification_read_only']);
$gamiCollabFold = static function (string $text, int $maxLen = 140): array {
    $t = trim($text);
    if ($t === '') {
        return ['needs' => false, 'preview' => '', 'full' => ''];
    }
    if (mb_strlen($t) <= $maxLen) {
        return ['needs' => false, 'preview' => $t, 'full' => $t];
    }

    return ['needs' => true, 'preview' => rtrim(mb_substr($t, 0, $maxLen)) . '…', 'full' => $t];
};
?>
<style>
    .gami-rules-page .gami-rules-tabs-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 0.75rem;
        border-bottom: 1px solid #dee2e6;
    }
    .gami-rules-page .gami-rules-tabs-wrap .nav-tabs {
        flex-wrap: nowrap;
        white-space: nowrap;
        min-width: min-content;
        border-bottom: none;
    }
    .gami-rules-page .gami-rules-tabs-wrap .nav-item { flex-shrink: 0; }
    .gami-rules-page .gami-rules-tabs-wrap .nav-link {
        white-space: nowrap;
        font-size: 0.875rem;
        padding: 0.45rem 0.65rem;
    }
    @media (max-width: 575.98px) {
        .gami-rules-page .gami-rules-tabs-wrap .nav-link { font-size: 0.8rem; padding: 0.4rem 0.5rem; }
        .gami-rules-page .gami-rules-title { font-size: 1.15rem; line-height: 1.25; }
        .gami-rules-page .gami-rules-breadcrumb { font-size: 0.72rem; max-width: 100%; }
        .gami-rules-page .gami-rules-breadcrumb .breadcrumb-item { max-width: 100%; }
    }
    @media (max-width: 767.98px) {
        .gami-rules-page .gami-table-mobile-cards thead { display: none; }
        .gami-rules-page .gami-table-mobile-cards tbody tr {
            display: block;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            margin-bottom: 0.65rem;
            padding: 0.15rem 0;
            background: #fff;
            box-shadow: 0 1px 2px rgba(0,0,0,.04);
        }
        .gami-rules-page .gami-table-mobile-cards tbody tr:last-child { margin-bottom: 0; }
        .gami-rules-page .gami-table-mobile-cards tbody td {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 0.5rem;
            border: 0;
            padding: 0.45rem 0.65rem;
            text-align: right;
            width: 100% !important;
        }
        .gami-rules-page .gami-table-mobile-cards tbody td::before {
            content: attr(data-label);
            font-weight: 600;
            font-size: 0.72rem;
            color: #6c757d;
            text-align: left;
            flex: 0 0 38%;
            max-width: 42%;
            line-height: 1.35;
        }
        .gami-rules-page .gami-table-mobile-cards tbody td .form-control,
        .gami-rules-page .gami-table-mobile-cards tbody td .form-select { min-width: 0; flex: 1 1 auto; }
        .gami-rules-page .gami-table-mobile-cards tbody td .d-flex { flex-wrap: wrap; justify-content: flex-end; }
        .gami-rules-page .gami-table-mobile-cards tbody td code { word-break: break-all; text-align: right; }
        .gami-rules-page .gami-table-mobile-cards tbody td .btn { flex-shrink: 0; }
        .gami-rules-page .gami-table-mobile-cards tbody td.d-flex.gap-1 {
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .gami-rules-page .gami-table-mobile-cards tbody td.d-flex.gap-1::before { align-self: center; }
    }
    /* Cards modo consulta — padrão mobile das listas (utilizadores): compacto + Ver mais outline */
    .gami-rules-page .gami-collab-cards.row { --bs-gutter-y: 0.5rem; }
    .gami-rules-page .gami-collab-compact.card {
        border-radius: 10px;
    }
    .gami-rules-page .gami-collab-compact .card-body {
        padding: 0.5rem 0.6rem;
    }
    @media (min-width: 768px) {
        .gami-rules-page .gami-collab-compact .card-body {
            padding: 0.75rem 1rem;
        }
    }
    .gami-rules-page .gami-collab-mobile-header {
        gap: 0.35rem;
    }
    .gami-rules-page .gami-collab-mobile-info {
        min-width: 0;
    }
    .gami-rules-page .gami-collab-mobile-info .card-title {
        font-size: 0.95rem;
        line-height: 1.25;
    }
    .gami-rules-page .gami-collab-ico {
        width: 36px;
        height: 36px;
        font-size: 0.85rem;
        border: 1px solid #e9ecef;
    }
    .gami-rules-page .gami-collab-title-clamp {
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
        overflow: hidden;
        word-break: break-word;
    }
    .gami-rules-page .gami-collab-more-btn {
        white-space: nowrap;
        padding-left: 0.45rem;
        padding-right: 0.45rem;
    }
    .gami-rules-page .gami-collab-compact.border-inactive {
        border-color: #dee2e6 !important;
        opacity: 0.92;
    }
    .gami-rules-page .gami-collab-compact .collapse .text-break {
        line-height: 1.4;
    }
</style>
<div class="container-fluid gami-rules-page px-2 px-sm-3 px-md-4">
    <div class="mb-2 d-flex flex-column flex-md-row gap-2 align-items-start align-items-md-center">
        <h2 class="gami-rules-title mt-2 mt-md-3 mb-0"><?= $readOnly ? 'Regras e metas da gamificação' : 'Configurações da Gamificação' ?></h2>
        <ol class="gami-rules-breadcrumb breadcrumb mb-0 mt-1 ms-md-auto small text-md-end">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item">Comunicação Interna</li>
            <li class="breadcrumb-item">Gamificação</li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <?php if ($readOnly): ?>
        <div class="alert alert-info mb-3 small">Modo <strong>consulta</strong>: pode ver regras de pontos, níveis, badges, missões e anti-fraude. Para alterar, é necessária permissão de edição nas regras de gamificação.</div>
    <?php endif; ?>

    <div class="gami-rules-tabs-wrap mb-3">
    <ul class="nav nav-tabs mb-0 pb-0" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-rules" type="button">Regras timeline</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-levels" type="button">Níveis</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-badges" type="button">Badges</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-missions" type="button">Missões</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-antifraud" type="button">Anti-fraude</button></li>
    </ul>
    </div>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="tab-rules">
            <div class="card mb-4 border-light shadow">
                <div class="card-header"><i class="fas fa-sliders-h me-2"></i><?= $readOnly ? 'Como ganha pontos na timeline' : 'Regras configuráveis da timeline' ?></div>
                <div class="card-body <?= $readOnly ? 'p-2 p-md-3' : 'p-0' ?>">
                    <?php if ($readOnly): ?>
                    <div class="row g-2 gami-collab-cards">
                        <?php foreach ($this->data['rules'] ?? [] as $r):
                            $rid = (int)($r['id'] ?? 0);
                            $title = (string)($r['title'] ?? '');
                            $fold = $gamiCollabFold($title, 130);
                            $moreId = 'gami-rule-more-' . $rid;
                            $inactive = empty($r['is_active']);
                            $cardExtra = $inactive ? ' border-inactive' : '';
                            ?>
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="card mb-0 shadow-sm border-light gami-collab-compact<?= $cardExtra ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start gami-collab-mobile-header">
                                        <div class="gami-collab-mobile-info flex-grow-1 min-w-0 pe-1">
                                            <h6 class="card-title mb-2 d-flex align-items-center gap-2">
                                                <span class="gami-collab-ico rounded-circle bg-light text-primary d-inline-flex align-items-center justify-content-center flex-shrink-0" aria-hidden="true"><i class="fas fa-sliders-h"></i></span>
                                                <span class="gami-collab-title-clamp"><b><?= htmlspecialchars($title !== '' ? $title : 'Regra') ?></b></span>
                                            </h6>
                                            <?php if ($inactive): ?>
                                                <div class="mb-2"><span class="badge bg-secondary"><i class="fas fa-pause me-1" aria-hidden="true"></i>Inativa</span></div>
                                            <?php endif; ?>
                                            <div class="mb-1 small"><b>Pontos:</b> <?= (int)($r['points'] ?? 0) ?> pts</div>
                                            <div class="mb-1 small"><b>Ativa:</b> <?= !$inactive ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-secondary">Não</span>' ?></div>
                                        </div>
                                        <button type="button" class="btn btn-outline-primary btn-sm ms-2 flex-shrink-0 gami-collab-more-btn" data-bs-toggle="collapse" data-bs-target="#<?= htmlspecialchars($moreId) ?>" aria-expanded="false" aria-controls="<?= htmlspecialchars($moreId) ?>">Ver mais</button>
                                    </div>
                                    <div class="collapse mt-2 small" id="<?= htmlspecialchars($moreId) ?>">
                                        <?php if ($fold['needs']): ?>
                                            <div class="mb-2 text-break"><b>Descrição completa:</b><br><?= nl2br(htmlspecialchars($fold['full'])) ?></div>
                                        <?php endif; ?>
                                        <div class="mb-1"><b>Máximo por dia:</b> <?= $r['max_awards_per_user_per_day'] !== null ? (int)$r['max_awards_per_user_per_day'] . ' vezes' : '—' ?></div>
                                        <div class="mb-1"><b>Limite total:</b> <?= $r['max_awards_per_user_total'] !== null ? (int)$r['max_awards_per_user_total'] . ' vezes' : '—' ?></div>
                                        <div class="text-break mb-0"><b>Identificador técnico:</b> <code class="small"><?= htmlspecialchars((string)($r['event_key'] ?? '')) ?></code></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 gami-table-mobile-cards">
                            <thead class="table-light"><tr><th>Chave</th><th>Título</th><th>Pontos</th><th>Máx./dia</th><th>Máx. total</th><th>Ativo</th><th class="text-end">Ações</th></tr></thead>
                            <tbody>
                            <?php foreach ($this->data['rules'] ?? [] as $r): ?>
                                <tr>
                                    <td data-label="Chave"><code><?= htmlspecialchars((string)($r['event_key'] ?? '')) ?></code></td>
                                    <td data-label="Título"><?= htmlspecialchars((string)($r['title'] ?? '')) ?></td>
                                    <td data-label="Pontos"><?= (int)($r['points'] ?? 0) ?></td>
                                    <td data-label="Máx./dia"><?= $r['max_awards_per_user_per_day'] !== null ? (int)$r['max_awards_per_user_per_day'] : '—' ?></td>
                                    <td data-label="Máx. total"><?= $r['max_awards_per_user_total'] !== null ? (int)$r['max_awards_per_user_total'] : '—' ?></td>
                                    <td data-label="Ativo"><?= !empty($r['is_active']) ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-secondary">Não</span>' ?></td>
                                    <td class="text-end" data-label="Ações">
                                        <?php if (in_array('UpdateGamificationTimelineRule', $this->data['buttonPermission'] ?? [], true)) { ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>update-gamification-timeline-rule/<?= (int)$r['id'] ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-levels">
            <div class="card mb-4 border-light shadow">
                <div class="card-header"><?= $readOnly ? 'Níveis no ranking' : 'Níveis de usuário' ?></div>
                <div class="card-body <?= $readOnly ? 'p-2 p-md-3' : 'p-0' ?>">
                        <?php if ($readOnly): ?>
                        <div class="row g-2 gami-collab-cards">
                            <?php foreach (($this->data['levels'] ?? []) as $row):
                                $lid = (int)($row['id'] ?? 0);
                                $lname = (string)($row['name'] ?? '');
                                $lmin = (int)($row['min_points'] ?? 0);
                                $descPlain = $lname !== ''
                                    ? sprintf('O nível «%s» é atingido com %s pontos ou mais no ranking mensal.', $lname, $lmin)
                                    : sprintf('Este nível exige %s pontos ou mais no ranking mensal.', $lmin);
                                $fold = $gamiCollabFold($descPlain, 140);
                                $moreId = 'gami-level-more-' . $lid;
                                $bc = (string)($row['badge_color'] ?? 'secondary');
                                $colorLabel = $levelColorOptions[$bc] ?? $bc;
                                $inactive = empty($row['is_active']);
                                $cardExtra = $inactive ? ' border-inactive' : '';
                                ?>
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="card mb-0 shadow-sm border-light gami-collab-compact<?= $cardExtra ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start gami-collab-mobile-header">
                                            <div class="gami-collab-mobile-info flex-grow-1 min-w-0 pe-1">
                                                <h6 class="card-title mb-2 d-flex align-items-center gap-2 flex-wrap">
                                                    <span class="gami-collab-ico rounded-circle bg-light text-warning d-inline-flex align-items-center justify-content-center flex-shrink-0" aria-hidden="true"><i class="fas fa-star"></i></span>
                                                    <span class="badge bg-<?= htmlspecialchars($bc) ?> gami-collab-title-clamp"><?= htmlspecialchars($lname !== '' ? $lname : 'Nível') ?></span>
                                                </h6>
                                                <div class="mb-1 small"><b>Pontos mínimos:</b> <?= $lmin ?></div>
                                                <div class="mb-1 small"><b>Ativo:</b> <?= !$inactive ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-secondary">Não</span>' ?></div>
                                            </div>
                                            <button type="button" class="btn btn-outline-primary btn-sm ms-2 flex-shrink-0 gami-collab-more-btn" data-bs-toggle="collapse" data-bs-target="#<?= htmlspecialchars($moreId) ?>" aria-expanded="false" aria-controls="<?= htmlspecialchars($moreId) ?>">Ver mais</button>
                                        </div>
                                        <div class="collapse mt-2 small" id="<?= htmlspecialchars($moreId) ?>">
                                            <div class="mb-2 text-break"><?= nl2br(htmlspecialchars($fold['full'])) ?></div>
                                            <div class="mb-1"><b>Cor no perfil:</b> <?= htmlspecialchars($colorLabel) ?></div>
                                            <div class="mb-0"><b>Ordem:</b> <?= (int)($row['sort_order'] ?? 0) ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0 gami-table-mobile-cards">
                            <thead class="table-light"><tr><th>Nome</th><th>Pontos mín.</th><th>Cor</th><th>Ordem</th><th>Ativo</th><th>Ação</th></tr></thead>
                            <tbody>
                            <?php foreach (($this->data['levels'] ?? []) as $row): ?>
                                <tr><form method="post">
                                    <input type="hidden" name="csrf_token" value="<?php echo \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_gamification_settings'); ?>">
                                    <input type="hidden" name="section" value="level">
                                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                    <td data-label="Nome"><input class="form-control form-control-sm js-level-name-input" name="name" value="<?= htmlspecialchars((string)$row['name']) ?>"></td>
                                    <td data-label="Pontos mín."><input class="form-control form-control-sm" type="number" min="0" name="min_points" value="<?= (int)$row['min_points'] ?>"></td>
                                    <td data-label="Cor">
                                        <div class="d-flex align-items-center gap-2">
                                        <select class="form-select form-select-sm js-level-color-select" name="badge_color">
                                            <?php foreach ($levelColorOptions as $colorKey => $colorLabel): ?>
                                                <option value="<?= htmlspecialchars((string)$colorKey) ?>" <?= ((string)($row['badge_color'] ?? '') === (string)$colorKey) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars((string)$colorLabel) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <span class="badge js-level-color-preview bg-<?= htmlspecialchars((string)($row['badge_color'] ?? 'secondary')) ?>"><?= htmlspecialchars((string)$row['name']) ?></span>
                                        </div>
                                    </td>
                                    <td data-label="Ordem"><input class="form-control form-control-sm" type="number" min="0" name="sort_order" value="<?= (int)$row['sort_order'] ?>"></td>
                                    <td data-label="Ativo"><select class="form-select form-select-sm" name="is_active"><option value="1" <?= !empty($row['is_active'])?'selected':'' ?>>Sim</option><option value="0" <?= empty($row['is_active'])?'selected':'' ?>>Não</option></select></td>
                                    <td class="d-flex gap-1" data-label="Ação">
                                        <button class="btn btn-sm btn-primary" type="submit">Salvar</button>
                                        <button class="btn btn-sm btn-outline-danger" type="submit" onclick="this.form.section.value='level_remove';return confirm('Remover nível? (será desativado)')">Remover</button>
                                    </td>
                                </form></tr>
                            <?php endforeach; ?>
                            <tr><form method="post">
                                <input type="hidden" name="csrf_token" value="<?php echo \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_gamification_settings'); ?>">
                                <input type="hidden" name="section" value="level_create">
                                <td data-label="Nome"><input class="form-control form-control-sm js-level-name-input" name="name" placeholder="Novo nível"></td>
                                <td data-label="Pontos mín."><input class="form-control form-control-sm" type="number" min="0" name="min_points" value="0"></td>
                                <td data-label="Cor">
                                    <div class="d-flex align-items-center gap-2">
                                    <select class="form-select form-select-sm js-level-color-select" name="badge_color">
                                        <?php foreach ($levelColorOptions as $colorKey => $colorLabel): ?>
                                            <option value="<?= htmlspecialchars((string)$colorKey) ?>" <?= (string)$colorKey === 'secondary' ? 'selected' : '' ?>>
                                                <?= htmlspecialchars((string)$colorLabel) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="badge js-level-color-preview bg-secondary">Novo nível</span>
                                    </div>
                                </td>
                                <td data-label="Ordem"><input class="form-control form-control-sm" type="number" min="0" name="sort_order" value="0"></td>
                                <td data-label="Ativo"><span class="badge bg-success">Sim</span></td>
                                <td data-label="Ação"><button class="btn btn-sm btn-success" type="submit">Criar</button></td>
                            </form></tr>
                            </tbody>
                        </table>
                    </div>
                        <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-badges">
            <div class="card mb-4 border-light shadow">
                <div class="card-header">Badges</div>
                <div class="card-body <?= $readOnly ? 'p-2 p-md-3' : 'p-0' ?>">
                        <?php if ($readOnly): ?>
                        <div class="row g-2 gami-collab-cards">
                            <?php foreach (($this->data['badges'] ?? []) as $row):
                                $bid = (int)($row['id'] ?? 0);
                                $bname = (string)($row['name'] ?? '');
                                $bdesc = trim((string)($row['description'] ?? ''));
                                $descPlain = $bdesc !== '' ? $bdesc : ($bname !== '' ? $bname : 'Badge sem descrição.');
                                $fold = $gamiCollabFold($descPlain, 140);
                                $moreId = 'gami-badge-more-' . $bid;
                                $ck = (string)($row['criteria_key'] ?? '');
                                $critLabel = $criteriaOptions[$ck] ?? $ck;
                                $threshKey = (string)($row['criteria_threshold_key'] ?? '');
                                $metaLine = (int)($row['criteria_threshold'] ?? 0) . ($threshKey !== '' ? ' · ' . $threshKey : '');
                                $inactive = empty($row['is_active']);
                                $cardExtra = $inactive ? ' border-inactive' : '';
                                ?>
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="card mb-0 shadow-sm border-light gami-collab-compact<?= $cardExtra ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start gami-collab-mobile-header">
                                            <div class="gami-collab-mobile-info flex-grow-1 min-w-0 pe-1">
                                                <h6 class="card-title mb-2 d-flex align-items-center gap-2">
                                                    <span class="gami-collab-ico rounded-circle bg-light text-success d-inline-flex align-items-center justify-content-center flex-shrink-0" aria-hidden="true"><i class="fas fa-award"></i></span>
                                                    <span class="gami-collab-title-clamp"><b><?= htmlspecialchars($bname !== '' ? $bname : 'Badge') ?></b></span>
                                                </h6>
                                                <div class="mb-1 small text-break"><b>Critério:</b> <?= htmlspecialchars($critLabel) ?></div>
                                                <div class="mb-1 small"><b>Ativa:</b> <?= !$inactive ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-secondary">Não</span>' ?></div>
                                            </div>
                                            <button type="button" class="btn btn-outline-primary btn-sm ms-2 flex-shrink-0 gami-collab-more-btn" data-bs-toggle="collapse" data-bs-target="#<?= htmlspecialchars($moreId) ?>" aria-expanded="false" aria-controls="<?= htmlspecialchars($moreId) ?>">Ver mais</button>
                                        </div>
                                        <div class="collapse mt-2 small" id="<?= htmlspecialchars($moreId) ?>">
                                            <?php if ($bdesc !== ''): ?>
                                                <div class="mb-2 text-break"><b>Descrição:</b><br><?= nl2br(htmlspecialchars($bdesc)) ?></div>
                                            <?php elseif ($bdesc === '' && $fold['needs']): ?>
                                                <div class="mb-2 text-break"><?= nl2br(htmlspecialchars($fold['full'])) ?></div>
                                            <?php endif; ?>
                                            <div class="mb-0 text-break"><b>Meta:</b> <?= htmlspecialchars($metaLine) ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0 gami-table-mobile-cards">
                            <thead class="table-light"><tr><th>Nome</th><th>Slug</th><th>Critério</th><th>Meta</th><th>Ativo</th><th>Ação</th></tr></thead>
                            <tbody>
                            <?php foreach (($this->data['badges'] ?? []) as $row): ?>
                                <tr><form method="post">
                                    <input type="hidden" name="csrf_token" value="<?php echo \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_gamification_settings'); ?>">
                                    <input type="hidden" name="section" value="badge">
                                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                    <input type="hidden" name="description" value="<?= htmlspecialchars((string)($row['description'] ?? '')) ?>">
                                    <input type="hidden" name="icon" value="<?= htmlspecialchars((string)($row['icon'] ?? '')) ?>">
                                    <td data-label="Nome"><input class="form-control form-control-sm" name="name" value="<?= htmlspecialchars((string)$row['name']) ?>"></td>
                                    <td data-label="Slug"><input class="form-control form-control-sm" name="slug" value="<?= htmlspecialchars((string)($row['slug'] ?? '')) ?>"></td>
                                    <td data-label="Critério">
                                        <select class="form-select form-select-sm" name="criteria_key">
                                            <?php foreach ($criteriaOptions as $ck => $clabel): ?>
                                                <option value="<?= htmlspecialchars((string)$ck) ?>" <?= ((string)$row['criteria_key'] === (string)$ck) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars((string)$clabel) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td data-label="Meta">
                                        <input class="form-control form-control-sm" type="number" min="0" name="criteria_threshold" value="<?= (int)($row['criteria_threshold'] ?? 0) ?>">
                                        <small class="text-muted">Chave aplicada: <?= htmlspecialchars((string)($row['criteria_threshold_key'] ?? 'min_points')) ?></small>
                                        <input type="hidden" name="criteria_value_json" value="<?= htmlspecialchars((string)($row['criteria_value_json'] ?? '{}')) ?>">
                                    </td>
                                    <td data-label="Ativo"><select class="form-select form-select-sm" name="is_active"><option value="1" <?= !empty($row['is_active'])?'selected':'' ?>>Sim</option><option value="0" <?= empty($row['is_active'])?'selected':'' ?>>Não</option></select></td>
                                    <td class="d-flex gap-1" data-label="Ação">
                                        <button class="btn btn-sm btn-primary" type="submit">Salvar</button>
                                        <button class="btn btn-sm btn-outline-danger" type="submit" onclick="this.form.section.value='badge_remove';return confirm('Remover badge? (será desativada)')">Remover</button>
                                    </td>
                                </form></tr>
                            <?php endforeach; ?>
                            <tr><form method="post">
                                <input type="hidden" name="csrf_token" value="<?php echo \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_gamification_settings'); ?>">
                                <input type="hidden" name="section" value="badge_create">
                                <td data-label="Nome"><input class="form-control form-control-sm" name="name" placeholder="Nova badge"></td>
                                <td data-label="Slug"><input class="form-control form-control-sm" name="slug" placeholder="nova-badge"></td>
                                <td data-label="Critério">
                                    <select class="form-select form-select-sm" name="criteria_key">
                                        <?php foreach ($criteriaOptions as $ck => $clabel): ?>
                                            <option value="<?= htmlspecialchars((string)$ck) ?>" <?= (string)$ck === 'total_points' ? 'selected' : '' ?>>
                                                <?= htmlspecialchars((string)$clabel) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td data-label="Meta">
                                    <input class="form-control form-control-sm" type="number" min="0" name="criteria_threshold" value="100">
                                    <small class="text-muted">A chave do JSON é gerada automaticamente</small>
                                    <input type="hidden" name="criteria_value_json" value='{"min_points":100}'>
                                </td>
                                <td data-label="Ativo"><span class="badge bg-success">Sim</span></td>
                                <td data-label="Ação">
                                    <input type="hidden" name="description" value="">
                                    <input type="hidden" name="icon" value="fa-award">
                                    <button class="btn btn-sm btn-success" type="submit">Criar</button>
                                </td>
                            </form></tr>
                            </tbody>
                        </table>
                    </div>
                        <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-missions">
            <div class="card mb-4 border-light shadow">
                <div class="card-header">Missões mensais</div>
                <div class="card-body <?= $readOnly ? 'p-2 p-md-3' : 'p-0' ?>">
                        <?php if ($readOnly): ?>
                        <div class="row g-2 gami-collab-cards">
                            <?php foreach (($this->data['missions'] ?? []) as $row):
                                $mid = (int)($row['id'] ?? 0);
                                $mtitle = (string)($row['title'] ?? '');
                                $mdesc = trim((string)($row['description'] ?? ''));
                                $descPlain = $mdesc !== '' ? $mdesc : ($mtitle !== '' ? $mtitle : 'Missão sem descrição.');
                                $fold = $gamiCollabFold($descPlain, 140);
                                $moreId = 'gami-mission-more-' . $mid;
                                $ek = (string)($row['event_key'] ?? '');
                                $evLabel = $missionEventOptions[$ek] ?? $ek;
                                $inactive = empty($row['is_active']);
                                $cardExtra = $inactive ? ' border-inactive' : '';
                                ?>
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="card mb-0 shadow-sm border-light gami-collab-compact<?= $cardExtra ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start gami-collab-mobile-header">
                                            <div class="gami-collab-mobile-info flex-grow-1 min-w-0 pe-1">
                                                <h6 class="card-title mb-2 d-flex align-items-center gap-2">
                                                    <span class="gami-collab-ico rounded-circle bg-light text-info d-inline-flex align-items-center justify-content-center flex-shrink-0" aria-hidden="true"><i class="fas fa-flag-checkered"></i></span>
                                                    <span class="gami-collab-title-clamp"><b><?= htmlspecialchars($mtitle !== '' ? $mtitle : 'Missão') ?></b></span>
                                                </h6>
                                                <div class="mb-1 small"><b>Meta:</b> <?= (int)($row['target_value'] ?? 0) ?></div>
                                                <div class="mb-1 small"><b>Recompensa:</b> <?= (int)($row['reward_points'] ?? 0) ?> pts</div>
                                                <div class="mb-1 small"><b>Ativa:</b> <?= !$inactive ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-secondary">Não</span>' ?></div>
                                            </div>
                                            <button type="button" class="btn btn-outline-primary btn-sm ms-2 flex-shrink-0 gami-collab-more-btn" data-bs-toggle="collapse" data-bs-target="#<?= htmlspecialchars($moreId) ?>" aria-expanded="false" aria-controls="<?= htmlspecialchars($moreId) ?>">Ver mais</button>
                                        </div>
                                        <div class="collapse mt-2 small" id="<?= htmlspecialchars($moreId) ?>">
                                            <?php if ($mdesc !== ''): ?>
                                                <div class="mb-2 text-break"><b>Descrição:</b><br><?= nl2br(htmlspecialchars($mdesc)) ?></div>
                                            <?php elseif ($mdesc === '' && $fold['needs']): ?>
                                                <div class="mb-2 text-break"><b>Detalhe:</b><br><?= nl2br(htmlspecialchars($fold['full'])) ?></div>
                                            <?php endif; ?>
                                            <div class="mb-1 text-break"><b>Evento:</b> <?= htmlspecialchars($evLabel) ?></div>
                                            <div class="text-break mb-0"><b>Identificador técnico:</b> <code class="small"><?= htmlspecialchars($ek) ?></code></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0 gami-table-mobile-cards">
                            <thead class="table-light"><tr><th>Título</th><th>Evento</th><th>Meta</th><th>Recompensa</th><th>Ativo</th><th>Ação</th></tr></thead>
                            <tbody>
                            <?php foreach (($this->data['missions'] ?? []) as $row): ?>
                                <tr><form method="post">
                                    <input type="hidden" name="csrf_token" value="<?php echo \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_gamification_settings'); ?>">
                                    <input type="hidden" name="section" value="mission">
                                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                    <input type="hidden" name="description" value="<?= htmlspecialchars((string)($row['description'] ?? '')) ?>">
                                    <input type="hidden" name="sort_order" value="<?= (int)($row['sort_order'] ?? 0) ?>">
                                    <td data-label="Título"><input class="form-control form-control-sm" name="title" value="<?= htmlspecialchars((string)$row['title']) ?>"></td>
                                    <td data-label="Evento">
                                        <select class="form-select form-select-sm" name="event_key">
                                            <?php foreach ($missionEventOptions as $eventKey => $eventLabel): ?>
                                                <option value="<?= htmlspecialchars((string)$eventKey) ?>" <?= ((string)$row['event_key'] === (string)$eventKey) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars((string)$eventLabel) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td data-label="Meta"><input class="form-control form-control-sm" type="number" min="1" name="target_value" value="<?= (int)$row['target_value'] ?>"></td>
                                    <td data-label="Recompensa"><input class="form-control form-control-sm" type="number" min="0" name="reward_points" value="<?= (int)$row['reward_points'] ?>"></td>
                                    <td data-label="Ativo"><select class="form-select form-select-sm" name="is_active"><option value="1" <?= !empty($row['is_active'])?'selected':'' ?>>Sim</option><option value="0" <?= empty($row['is_active'])?'selected':'' ?>>Não</option></select></td>
                                    <td class="d-flex gap-1" data-label="Ação">
                                        <button class="btn btn-sm btn-primary" type="submit">Salvar</button>
                                        <button class="btn btn-sm btn-outline-danger" type="submit" onclick="this.form.section.value='mission_remove';return confirm('Remover missão? (será desativada)')">Remover</button>
                                    </td>
                                </form></tr>
                            <?php endforeach; ?>
                            <tr><form method="post">
                                <input type="hidden" name="csrf_token" value="<?php echo \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_gamification_settings'); ?>">
                                <input type="hidden" name="section" value="mission_create">
                                <td data-label="Título"><input class="form-control form-control-sm" name="title" placeholder="Nova missão"></td>
                                <td data-label="Evento">
                                    <select class="form-select form-select-sm" name="event_key">
                                        <?php foreach ($missionEventOptions as $eventKey => $eventLabel): ?>
                                            <option value="<?= htmlspecialchars((string)$eventKey) ?>" <?= (string)$eventKey === 'timeline_comment_created' ? 'selected' : '' ?>>
                                                <?= htmlspecialchars((string)$eventLabel) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td data-label="Meta"><input class="form-control form-control-sm" type="number" min="1" name="target_value" value="1"></td>
                                <td data-label="Recompensa"><input class="form-control form-control-sm" type="number" min="0" name="reward_points" value="5"></td>
                                <td data-label="Ativo"><span class="badge bg-success">Sim</span></td>
                                <td data-label="Ação">
                                    <input type="hidden" name="description" value="">
                                    <input type="hidden" name="sort_order" value="0">
                                    <button class="btn btn-sm btn-success" type="submit">Criar</button>
                                </td>
                            </form></tr>
                            </tbody>
                        </table>
                    </div>
                        <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-antifraud">
            <div class="card mb-4 border-light shadow">
                <div class="card-header">Parâmetros anti-fraude</div>
                <div class="card-body <?= $readOnly ? 'p-2 p-md-3' : 'p-0' ?>">
                        <?php if ($readOnly): ?>
                        <div class="row g-2 gami-collab-cards">
                            <?php
                            $afIdx = 0;
                            foreach (($this->data['settings'] ?? []) as $row):
                                $afIdx++;
                                $sk = (string)($row['setting_key'] ?? '');
                                $safeId = $sk !== '' ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $sk) : 'row';
                                $moreId = 'gami-af-more-' . $safeId . '-' . $afIdx;
                                $afDesc = trim((string)($row['description'] ?? ''));
                                $headline = $afDesc !== ''
                                    ? (mb_strlen($afDesc) > 72 ? rtrim(mb_substr($afDesc, 0, 72)) . '…' : $afDesc)
                                    : ($sk !== '' ? $sk : 'Parâmetro');
                                $fold = $gamiCollabFold($afDesc !== '' ? $afDesc : 'Parâmetro de proteção do sistema de pontos.', 140);
                                $sval = (string)($row['setting_value'] ?? '');
                                ?>
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="card mb-0 shadow-sm border-light gami-collab-compact">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start gami-collab-mobile-header">
                                            <div class="gami-collab-mobile-info flex-grow-1 min-w-0 pe-1">
                                                <h6 class="card-title mb-2 d-flex align-items-center gap-2">
                                                    <span class="gami-collab-ico rounded-circle bg-light text-danger d-inline-flex align-items-center justify-content-center flex-shrink-0" aria-hidden="true"><i class="fas fa-shield-alt"></i></span>
                                                    <span class="gami-collab-title-clamp"><b><?= htmlspecialchars($headline) ?></b></span>
                                                </h6>
                                                <div class="mb-1 small text-break"><b>Valor:</b> <?= htmlspecialchars($sval !== '' ? $sval : '—') ?></div>
                                            </div>
                                            <button type="button" class="btn btn-outline-primary btn-sm ms-2 flex-shrink-0 gami-collab-more-btn" data-bs-toggle="collapse" data-bs-target="#<?= htmlspecialchars($moreId) ?>" aria-expanded="false" aria-controls="<?= htmlspecialchars($moreId) ?>">Ver mais</button>
                                        </div>
                                        <div class="collapse mt-2 small" id="<?= htmlspecialchars($moreId) ?>">
                                            <?php if ($afDesc !== ''): ?>
                                                <div class="mb-2 text-break"><b>Descrição completa:</b><br><?= nl2br(htmlspecialchars($afDesc)) ?></div>
                                            <?php elseif ($fold['needs']): ?>
                                                <div class="mb-2 text-break"><?= nl2br(htmlspecialchars($fold['full'])) ?></div>
                                            <?php endif; ?>
                                            <div class="text-break mb-0"><b>Chave técnica:</b> <code class="small"><?= htmlspecialchars($sk) ?></code></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0 gami-table-mobile-cards">
                            <thead class="table-light"><tr><th>Chave</th><th>Valor</th><th>Descrição</th><th>Ação</th></tr></thead>
                            <tbody>
                            <?php foreach (($this->data['settings'] ?? []) as $row): ?>
                                <tr><form method="post">
                                    <input type="hidden" name="csrf_token" value="<?php echo \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_gamification_settings'); ?>">
                                    <input type="hidden" name="section" value="setting">
                                    <input type="hidden" name="setting_key" value="<?= htmlspecialchars((string)$row['setting_key']) ?>">
                                    <td data-label="Chave"><code><?= htmlspecialchars((string)$row['setting_key']) ?></code></td>
                                    <td data-label="Valor"><input class="form-control form-control-sm" name="setting_value" value="<?= htmlspecialchars((string)$row['setting_value']) ?>"></td>
                                    <td data-label="Descrição"><?= htmlspecialchars((string)($row['description'] ?? '')) ?></td>
                                    <td data-label="Ação"><button class="btn btn-sm btn-primary" type="submit">Salvar</button></td>
                                </form></tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                        <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var hash = window.location.hash || '';
    if (hash.indexOf('#tab-') === 0) {
        var trigger = document.querySelector('button.nav-link[data-bs-target="' + hash + '"]');
        if (trigger && typeof bootstrap !== 'undefined' && bootstrap.Tab) {
            try {
                bootstrap.Tab.getOrCreateInstance(trigger).show();
            } catch (e) {}
        }
    }
<?php if (!$readOnly): ?>
    var allowed = ['secondary', 'info', 'primary', 'warning', 'success', 'danger', 'dark'];
    var selects = document.querySelectorAll('.js-level-color-select');
    selects.forEach(function (select) {
        var wrapper = select.closest('.d-flex');
        if (!wrapper) return;
        var preview = wrapper.querySelector('.js-level-color-preview');
        if (!preview) return;
        var nameInput = null;
        var row = select.closest('tr');
        if (row) {
            nameInput = row.querySelector('.js-level-name-input');
        }
        var paint = function () {
            var color = String(select.value || 'secondary');
            if (allowed.indexOf(color) === -1) color = 'secondary';
            preview.className = 'badge js-level-color-preview bg-' + color;
            var levelName = nameInput ? String(nameInput.value || '').trim() : '';
            preview.textContent = levelName !== '' ? levelName : 'Nível';
        };
        select.addEventListener('change', paint);
        if (nameInput) {
            nameInput.addEventListener('input', paint);
        }
        paint();
    });
<?php endif; ?>
});
</script>
