<?php include __DIR__ . '/partials/module_head.php'; ?>
<?php $criteriaOptions = $this->data['badge_criteria_options'] ?? []; ?>
<?php $missionEventOptions = $this->data['mission_event_options'] ?? []; ?>
<?php $levelColorOptions = $this->data['level_color_options'] ?? []; ?>
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <div class="mb-2 d-flex flex-column flex-md-row gap-2 align-items-start align-items-md-center">
        <h2 class="mt-2 mt-md-3 mb-0">Configurações da Gamificação</h2>
        <ol class="breadcrumb mb-0 mt-1 ms-md-auto small">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item">Comunicação Interna</li>
            <li class="breadcrumb-item">Gamificação</li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-rules" type="button">Regras timeline</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-levels" type="button">Níveis</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-badges" type="button">Badges</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-missions" type="button">Missões</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-antifraud" type="button">Anti-fraude</button></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="tab-rules">
            <div class="card mb-4 border-light shadow">
                <div class="card-header"><i class="fas fa-sliders-h me-2"></i>Regras configuráveis da timeline</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light"><tr><th>Chave</th><th>Título</th><th>Pontos</th><th>Máx./dia</th><th>Máx. total</th><th>Ativo</th><th class="text-end">Ações</th></tr></thead>
                            <tbody>
                            <?php foreach ($this->data['rules'] ?? [] as $r): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars((string)($r['event_key'] ?? '')) ?></code></td>
                                    <td><?= htmlspecialchars((string)($r['title'] ?? '')) ?></td>
                                    <td><?= (int)($r['points'] ?? 0) ?></td>
                                    <td><?= $r['max_awards_per_user_per_day'] !== null ? (int)$r['max_awards_per_user_per_day'] : '—' ?></td>
                                    <td><?= $r['max_awards_per_user_total'] !== null ? (int)$r['max_awards_per_user_total'] : '—' ?></td>
                                    <td><?= !empty($r['is_active']) ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-secondary">Não</span>' ?></td>
                                    <td class="text-end">
                                        <?php if (in_array('UpdateGamificationTimelineRule', $this->data['buttonPermission'] ?? [], true)) { ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>update-gamification-timeline-rule/<?= (int)$r['id'] ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-levels">
            <div class="card mb-4 border-light shadow">
                <div class="card-header">Níveis de usuário</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light"><tr><th>Nome</th><th>Pontos mín.</th><th>Cor</th><th>Ordem</th><th>Ativo</th><th>Ação</th></tr></thead>
                            <tbody>
                            <?php foreach (($this->data['levels'] ?? []) as $row): ?>
                                <tr><form method="post">
                                    <input type="hidden" name="csrf_token" value="<?php echo \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_gamification_settings'); ?>">
                                    <input type="hidden" name="section" value="level">
                                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                    <td><input class="form-control form-control-sm js-level-name-input" name="name" value="<?= htmlspecialchars((string)$row['name']) ?>"></td>
                                    <td><input class="form-control form-control-sm" type="number" min="0" name="min_points" value="<?= (int)$row['min_points'] ?>"></td>
                                    <td>
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
                                    <td><input class="form-control form-control-sm" type="number" min="0" name="sort_order" value="<?= (int)$row['sort_order'] ?>"></td>
                                    <td><select class="form-select form-select-sm" name="is_active"><option value="1" <?= !empty($row['is_active'])?'selected':'' ?>>Sim</option><option value="0" <?= empty($row['is_active'])?'selected':'' ?>>Não</option></select></td>
                                    <td class="d-flex gap-1">
                                        <button class="btn btn-sm btn-primary" type="submit">Salvar</button>
                                        <button class="btn btn-sm btn-outline-danger" type="submit" onclick="this.form.section.value='level_remove';return confirm('Remover nível? (será desativado)')">Remover</button>
                                    </td>
                                </form></tr>
                            <?php endforeach; ?>
                            <tr><form method="post">
                                <input type="hidden" name="csrf_token" value="<?php echo \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_gamification_settings'); ?>">
                                <input type="hidden" name="section" value="level_create">
                                <td><input class="form-control form-control-sm js-level-name-input" name="name" placeholder="Novo nível"></td>
                                <td><input class="form-control form-control-sm" type="number" min="0" name="min_points" value="0"></td>
                                <td>
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
                                <td><input class="form-control form-control-sm" type="number" min="0" name="sort_order" value="0"></td>
                                <td><span class="badge bg-success">Sim</span></td>
                                <td><button class="btn btn-sm btn-success" type="submit">Criar</button></td>
                            </form></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-badges">
            <div class="card mb-4 border-light shadow">
                <div class="card-header">Badges</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light"><tr><th>Nome</th><th>Slug</th><th>Critério</th><th>Meta</th><th>Ativo</th><th>Ação</th></tr></thead>
                            <tbody>
                            <?php foreach (($this->data['badges'] ?? []) as $row): ?>
                                <tr><form method="post">
                                    <input type="hidden" name="csrf_token" value="<?php echo \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_gamification_settings'); ?>">
                                    <input type="hidden" name="section" value="badge">
                                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                    <input type="hidden" name="description" value="<?= htmlspecialchars((string)($row['description'] ?? '')) ?>">
                                    <input type="hidden" name="icon" value="<?= htmlspecialchars((string)($row['icon'] ?? '')) ?>">
                                    <td><input class="form-control form-control-sm" name="name" value="<?= htmlspecialchars((string)$row['name']) ?>"></td>
                                    <td><input class="form-control form-control-sm" name="slug" value="<?= htmlspecialchars((string)($row['slug'] ?? '')) ?>"></td>
                                    <td>
                                        <select class="form-select form-select-sm" name="criteria_key">
                                            <?php foreach ($criteriaOptions as $ck => $clabel): ?>
                                                <option value="<?= htmlspecialchars((string)$ck) ?>" <?= ((string)$row['criteria_key'] === (string)$ck) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars((string)$clabel) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input class="form-control form-control-sm" type="number" min="0" name="criteria_threshold" value="<?= (int)($row['criteria_threshold'] ?? 0) ?>">
                                        <small class="text-muted">Chave aplicada: <?= htmlspecialchars((string)($row['criteria_threshold_key'] ?? 'min_points')) ?></small>
                                        <input type="hidden" name="criteria_value_json" value="<?= htmlspecialchars((string)($row['criteria_value_json'] ?? '{}')) ?>">
                                    </td>
                                    <td><select class="form-select form-select-sm" name="is_active"><option value="1" <?= !empty($row['is_active'])?'selected':'' ?>>Sim</option><option value="0" <?= empty($row['is_active'])?'selected':'' ?>>Não</option></select></td>
                                    <td class="d-flex gap-1">
                                        <button class="btn btn-sm btn-primary" type="submit">Salvar</button>
                                        <button class="btn btn-sm btn-outline-danger" type="submit" onclick="this.form.section.value='badge_remove';return confirm('Remover badge? (será desativada)')">Remover</button>
                                    </td>
                                </form></tr>
                            <?php endforeach; ?>
                            <tr><form method="post">
                                <input type="hidden" name="csrf_token" value="<?php echo \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_gamification_settings'); ?>">
                                <input type="hidden" name="section" value="badge_create">
                                <td><input class="form-control form-control-sm" name="name" placeholder="Nova badge"></td>
                                <td><input class="form-control form-control-sm" name="slug" placeholder="nova-badge"></td>
                                <td>
                                    <select class="form-select form-select-sm" name="criteria_key">
                                        <?php foreach ($criteriaOptions as $ck => $clabel): ?>
                                            <option value="<?= htmlspecialchars((string)$ck) ?>" <?= (string)$ck === 'total_points' ? 'selected' : '' ?>>
                                                <?= htmlspecialchars((string)$clabel) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input class="form-control form-control-sm" type="number" min="0" name="criteria_threshold" value="100">
                                    <small class="text-muted">A chave do JSON é gerada automaticamente</small>
                                    <input type="hidden" name="criteria_value_json" value='{"min_points":100}'>
                                </td>
                                <td><span class="badge bg-success">Sim</span></td>
                                <td>
                                    <input type="hidden" name="description" value="">
                                    <input type="hidden" name="icon" value="fa-award">
                                    <button class="btn btn-sm btn-success" type="submit">Criar</button>
                                </td>
                            </form></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-missions">
            <div class="card mb-4 border-light shadow">
                <div class="card-header">Missões mensais</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light"><tr><th>Título</th><th>Evento</th><th>Meta</th><th>Recompensa</th><th>Ativo</th><th>Ação</th></tr></thead>
                            <tbody>
                            <?php foreach (($this->data['missions'] ?? []) as $row): ?>
                                <tr><form method="post">
                                    <input type="hidden" name="csrf_token" value="<?php echo \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_gamification_settings'); ?>">
                                    <input type="hidden" name="section" value="mission">
                                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                    <input type="hidden" name="description" value="<?= htmlspecialchars((string)($row['description'] ?? '')) ?>">
                                    <input type="hidden" name="sort_order" value="<?= (int)($row['sort_order'] ?? 0) ?>">
                                    <td><input class="form-control form-control-sm" name="title" value="<?= htmlspecialchars((string)$row['title']) ?>"></td>
                                    <td>
                                        <select class="form-select form-select-sm" name="event_key">
                                            <?php foreach ($missionEventOptions as $eventKey => $eventLabel): ?>
                                                <option value="<?= htmlspecialchars((string)$eventKey) ?>" <?= ((string)$row['event_key'] === (string)$eventKey) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars((string)$eventLabel) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><input class="form-control form-control-sm" type="number" min="1" name="target_value" value="<?= (int)$row['target_value'] ?>"></td>
                                    <td><input class="form-control form-control-sm" type="number" min="0" name="reward_points" value="<?= (int)$row['reward_points'] ?>"></td>
                                    <td><select class="form-select form-select-sm" name="is_active"><option value="1" <?= !empty($row['is_active'])?'selected':'' ?>>Sim</option><option value="0" <?= empty($row['is_active'])?'selected':'' ?>>Não</option></select></td>
                                    <td class="d-flex gap-1">
                                        <button class="btn btn-sm btn-primary" type="submit">Salvar</button>
                                        <button class="btn btn-sm btn-outline-danger" type="submit" onclick="this.form.section.value='mission_remove';return confirm('Remover missão? (será desativada)')">Remover</button>
                                    </td>
                                </form></tr>
                            <?php endforeach; ?>
                            <tr><form method="post">
                                <input type="hidden" name="csrf_token" value="<?php echo \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_gamification_settings'); ?>">
                                <input type="hidden" name="section" value="mission_create">
                                <td><input class="form-control form-control-sm" name="title" placeholder="Nova missão"></td>
                                <td>
                                    <select class="form-select form-select-sm" name="event_key">
                                        <?php foreach ($missionEventOptions as $eventKey => $eventLabel): ?>
                                            <option value="<?= htmlspecialchars((string)$eventKey) ?>" <?= (string)$eventKey === 'timeline_comment_created' ? 'selected' : '' ?>>
                                                <?= htmlspecialchars((string)$eventLabel) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input class="form-control form-control-sm" type="number" min="1" name="target_value" value="1"></td>
                                <td><input class="form-control form-control-sm" type="number" min="0" name="reward_points" value="5"></td>
                                <td><span class="badge bg-success">Sim</span></td>
                                <td>
                                    <input type="hidden" name="description" value="">
                                    <input type="hidden" name="sort_order" value="0">
                                    <button class="btn btn-sm btn-success" type="submit">Criar</button>
                                </td>
                            </form></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-antifraud">
            <div class="card mb-4 border-light shadow">
                <div class="card-header">Parâmetros anti-fraude</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light"><tr><th>Chave</th><th>Valor</th><th>Descrição</th><th>Ação</th></tr></thead>
                            <tbody>
                            <?php foreach (($this->data['settings'] ?? []) as $row): ?>
                                <tr><form method="post">
                                    <input type="hidden" name="csrf_token" value="<?php echo \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_gamification_settings'); ?>">
                                    <input type="hidden" name="section" value="setting">
                                    <input type="hidden" name="setting_key" value="<?= htmlspecialchars((string)$row['setting_key']) ?>">
                                    <td><code><?= htmlspecialchars((string)$row['setting_key']) ?></code></td>
                                    <td><input class="form-control form-control-sm" name="setting_value" value="<?= htmlspecialchars((string)$row['setting_value']) ?>"></td>
                                    <td><?= htmlspecialchars((string)($row['description'] ?? '')) ?></td>
                                    <td><button class="btn btn-sm btn-primary" type="submit">Salvar</button></td>
                                </form></tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
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
});
</script>
