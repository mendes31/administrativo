<?php
use App\adms\Helpers\CSRFHelper;
$type = $this->data['requestType'] ?? [];
$stages = $this->data['stages'] ?? [];
$accessLevels = $this->data['accessLevels'] ?? [];
$skipRequester = $this->data['skipRequesterLevelIds'] ?? [];
$skipSupervisor = $this->data['skipSupervisorLevelIds'] ?? [];
$users = $this->data['users'] ?? [];

$kindMeta = [
    'immediate' => [
        'title' => 'Gestor na hierarquia',
        'short' => 'Nível N acima de quem abriu o pedido (1º = André, 2º = Nathiele…)',
        'color' => '#0d6efd',
        'icon' => 'fa-sitemap',
    ],
    'hr' => [
        'title' => 'Fila do RH',
        'short' => 'Quem tem permissão de aprovar como RH — não é por cargo',
        'color' => '#6f42c1',
        'icon' => 'fa-users',
    ],
    'fixed_user' => [
        'title' => 'Pessoa específica',
        'short' => 'Você escolhe exatamente quem aprova',
        'color' => '#198754',
        'icon' => 'fa-user-check',
    ],
];

if ($stages === []) {
    $stages = [
        [
            'approver_kind' => 'immediate',
            'stage_label' => 'Aprovação do gestor',
            'escalate_after_hours' => 72,
            'escalate_policy' => 'next_level',
            'max_escalation_levels' => 1,
            'fixed_user_id' => null,
        ],
        [
            'approver_kind' => 'hr',
            'stage_label' => 'Aprovação do RH',
            'escalate_after_hours' => 0,
            'escalate_policy' => 'none',
            'max_escalation_levels' => 0,
            'fixed_user_id' => null,
        ],
    ];
}

$userNameById = [];
foreach ($users as $u) {
    $userNameById[(int) ($u['id'] ?? 0)] = (string) ($u['name'] ?? '');
}
?>
<style>
    .wf-legend .wf-pill {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        border-radius: 999px;
        padding: .35rem .75rem;
        font-size: .85rem;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
    }
    .wf-legend .wf-dot {
        width: .65rem;
        height: .65rem;
        border-radius: 50%;
        display: inline-block;
    }
    .wf-preview {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .5rem;
        padding: .85rem 1rem;
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: .5rem;
    }
    .wf-preview .wf-node {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .4rem .7rem;
        border-radius: .4rem;
        background: #fff;
        border: 1px solid #e2e8f0;
        font-size: .875rem;
        font-weight: 600;
    }
    .wf-preview .wf-arrow {
        color: #94a3b8;
        font-size: .8rem;
    }
    .stage-row {
        border-left-width: 4px !important;
        position: relative;
    }
    .stage-row .stage-badge {
        width: 2rem;
        height: 2rem;
        border-radius: 50%;
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: .9rem;
        flex-shrink: 0;
    }
    .stage-hint {
        font-size: .82rem;
        color: #64748b;
        margin-top: .25rem;
    }
    .stage-warn-dup {
        display: none;
    }
    .stage-row.is-dup-immediate .stage-warn-dup {
        display: block;
    }
</style>

<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Editar Tipo de Solicitação</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>list-request-types" class="text-decoration-none">Tipos de Solicitação</a></li>
            <li class="breadcrumb-item">Editar</li>
        </ol>
    </div>

    <form action="" method="POST" class="row g-3" id="form-update-request-type">
        <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('form_update_request_type'); ?>">

        <div class="col-12">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
        </div>

        <div class="col-lg-5">
            <div class="card border-light shadow h-100">
                <div class="card-header"><i class="fas fa-tag me-2"></i>Dados do tipo</div>
                <div class="card-body row g-3">
                    <div class="col-md-6">
                        <label for="code" class="form-label">Código</label>
                        <input type="text" name="code" id="code" class="form-control" value="<?= htmlspecialchars($type['code'] ?? '') ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label for="name" class="form-label">Nome <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" required value="<?= htmlspecialchars($type['name'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label for="description" class="form-label">Descrição</label>
                        <textarea name="description" id="description" class="form-control" rows="2"><?= htmlspecialchars($type['description'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label for="icon" class="form-label">Ícone</label>
                        <input type="text" name="icon" id="icon" class="form-control" value="<?= htmlspecialchars($type['icon'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="color" class="form-label">Cor</label>
                        <select name="color" id="color" class="form-select">
                            <?php foreach (['primary','success','warning','danger','info','secondary'] as $c): ?>
                                <option value="<?= $c ?>" <?= ($type['color'] ?? 'primary') === $c ? 'selected' : '' ?>><?= ucfirst($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="sort_order" class="form-label">Ordem na lista</label>
                        <input type="number" name="sort_order" id="sort_order" class="form-control" value="<?= (int) ($type['sort_order'] ?? 0) ?>" min="0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="status" id="status" value="1" <?= !empty($type['status']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="status">Ativo</label>
                        </div>
                    </div>

                    <div class="col-12"><hr class="my-1"><span class="text-muted small">Campos no formulário do colaborador</span></div>
                    <div class="col-12">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="requires_dates" id="requires_dates" value="1" <?= !empty($type['requires_dates']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="requires_dates">Requer datas</label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="requires_days" id="requires_days" value="1" <?= !empty($type['requires_days']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="requires_days">Requer dias</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="requires_amount" id="requires_amount" value="1" <?= !empty($type['requires_amount']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="requires_amount">Requer valor</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-light shadow mb-3">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <span><i class="fas fa-project-diagram me-2"></i>Fluxo de aprovação</span>
                    <button type="button" class="btn btn-primary btn-sm" id="btn-add-stage">
                        <i class="fas fa-plus me-1"></i>Adicionar etapa
                    </button>
                </div>
                <div class="card-body">
                    <div class="alert alert-light border small mb-3">
                        <strong class="d-block mb-2">Exemplo: André → Nathiele → RH</strong>
                        <ol class="mb-2 ps-3">
                            <li>Etapa 1: <em>Gestor na hierarquia</em> · nível <strong>1º</strong> (André = gestor do solicitante)</li>
                            <li>Etapa 2: <em>Gestor na hierarquia</em> · nível <strong>2º</strong> (Nathiele = chefe do André)</li>
                            <li>Etapa 3: <em>Fila do RH</em></li>
                        </ol>
                        <ul class="mb-0 ps-3">
                            <li class="mb-1"><strong>Gestor na hierarquia</strong> — sobe N níveis a partir de <em>quem abriu o pedido</em> (1º = imediato, 2º = chefe do gestor…). Cada etapa exige aprovação; não é a mesma coisa que escalação por prazo.</li>
                            <li class="mb-1"><strong>Fila do RH</strong> — permissão de RH, <u>não</u> por cargo.</li>
                            <li><strong>Pessoa específica</strong> — escolhe o usuário à mão (quando não for organograma).</li>
                        </ul>
                    </div>

                    <div class="wf-legend d-flex flex-wrap gap-2 mb-3">
                        <?php foreach ($kindMeta as $meta): ?>
                            <span class="wf-pill">
                                <span class="wf-dot" style="background: <?= htmlspecialchars($meta['color']) ?>"></span>
                                <?= htmlspecialchars($meta['title']) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>

                    <label class="form-label small text-muted mb-1">Pré-visualização do caminho</label>
                    <div id="wf-preview" class="wf-preview mb-3" aria-live="polite"></div>

                    <div id="stages-editor" class="vstack gap-3">
                        <?php foreach ($stages as $idx => $stage): ?>
                            <?php
                            $kind = (string) ($stage['approver_kind'] ?? 'immediate');
                            if (!isset($kindMeta[$kind])) {
                                $kind = 'immediate';
                            }
                            $meta = $kindMeta[$kind];
                            $label = (string) ($stage['stage_label'] ?? '');
                            $hours = (int) ($stage['escalate_after_hours'] ?? ($kind === 'immediate' ? 72 : 0));
                            $policy = (string) ($stage['escalate_policy'] ?? ($kind === 'immediate' ? 'next_level' : 'none'));
                            $maxLevels = (int) ($stage['max_escalation_levels'] ?? ($kind === 'immediate' ? 1 : 0));
                            $fixedUserId = (int) ($stage['fixed_user_id'] ?? 0);
                            $hierarchyLevel = max(1, (int) ($stage['hierarchy_level'] ?? 1));
                            $hierarchyHint = $hierarchyLevel === 1
                                ? '1º nível = gestor imediato de quem abriu (ex.: André)'
                                : ($hierarchyLevel === 2
                                    ? '2º nível = chefe do gestor (ex.: Nathiele)'
                                    : $hierarchyLevel . 'º nível acima de quem abriu o pedido');
                            ?>
                            <div class="card border stage-row" data-stage-index="<?= (int) $idx ?>" style="border-left-color: <?= htmlspecialchars($meta['color']) ?> !important;">
                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="stage-badge" style="background: <?= htmlspecialchars($meta['color']) ?>"><?= (int) $idx + 1 ?></span>
                                            <div>
                                                <div class="fw-semibold stage-title">Etapa <?= (int) $idx + 1 ?></div>
                                                <div class="stage-kind-summary text-muted small"><?= htmlspecialchars($kind === 'immediate' ? $hierarchyHint : $meta['short']) ?></div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-stage" title="Remover etapa">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-md-5">
                                            <label class="form-label">Quem aprova nesta etapa?</label>
                                            <select name="stages[<?= (int) $idx ?>][approver_kind]" class="form-select stage-kind" required>
                                                <option value="immediate" <?= $kind === 'immediate' ? 'selected' : '' ?>>Gestor na hierarquia do solicitante</option>
                                                <option value="hr" <?= $kind === 'hr' ? 'selected' : '' ?>>Fila do RH</option>
                                                <option value="fixed_user" <?= $kind === 'fixed_user' ? 'selected' : '' ?>>Pessoa específica</option>
                                            </select>
                                            <div class="stage-hint stage-kind-hint"><?= htmlspecialchars($kind === 'immediate' ? $hierarchyHint : $meta['short']) ?></div>
                                            <div class="alert alert-warning py-2 px-3 mt-2 mb-0 small stage-warn-dup">
                                                Já existe outra etapa com o <strong>mesmo nível</strong> na hierarquia — as duas pedem a mesma pessoa. Mude o nível (1º, 2º…) ou remova a duplicata.
                                            </div>
                                        </div>
                                        <div class="col-md-7">
                                            <label class="form-label">Nome da etapa (opcional)</label>
                                            <input type="text" name="stages[<?= (int) $idx ?>][stage_label]" class="form-control stage-label-input"
                                                   value="<?= htmlspecialchars($label) ?>"
                                                   placeholder="Ex.: Aprovação do gestor">
                                        </div>

                                        <div class="col-md-6 stage-hierarchy <?= $kind === 'immediate' ? '' : 'd-none' ?>">
                                            <label class="form-label">Qual nível acima do solicitante?</label>
                                            <select name="stages[<?= (int) $idx ?>][hierarchy_level]" class="form-select stage-hierarchy-level">
                                                <?php for ($lv = 1; $lv <= 5; $lv++): ?>
                                                    <?php
                                                    $lvLabel = match ($lv) {
                                                        1 => '1º — gestor imediato (ex.: André)',
                                                        2 => '2º — chefe do gestor (ex.: Nathiele)',
                                                        default => $lv . 'º nível acima do solicitante',
                                                    };
                                                    ?>
                                                    <option value="<?= $lv ?>" <?= $hierarchyLevel === $lv ? 'selected' : '' ?>><?= htmlspecialchars($lvLabel) ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>

                                        <div class="col-12 stage-fixed-user <?= $kind === 'fixed_user' ? '' : 'd-none' ?>">
                                            <label class="form-label">Escolha a pessoa</label>
                                            <select name="stages[<?= (int) $idx ?>][fixed_user_id]" class="form-select stage-fixed-select">
                                                <option value="">Selecione…</option>
                                                <?php foreach ($users as $u): ?>
                                                    <?php $uid = (int) ($u['id'] ?? 0); ?>
                                                    <option value="<?= $uid ?>" <?= $fixedUserId === $uid ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars(($u['name'] ?? '') . ' (#' . $uid . ')') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-12 stage-sla <?= $kind === 'immediate' ? '' : 'd-none' ?>">
                                            <div class="border rounded p-3 bg-light">
                                                <div class="fw-semibold small mb-2">Se esta pessoa não responder (escalação por prazo)</div>
                                                <p class="small text-muted mb-3 mb-md-2">
                                                    Diferente de criar outra etapa: aqui o sistema sobe níveis <em>só se estourar o prazo</em>.
                                                    Para André <strong>e depois</strong> Nathiele obrigatórios, use duas etapas com níveis 1º e 2º.
                                                </p>
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <label class="form-label">Prazo (horas)</label>
                                                        <input type="number" min="0" max="720" class="form-control"
                                                               name="stages[<?= (int) $idx ?>][escalate_after_hours]"
                                                               value="<?= $hours ?>">
                                                        <small class="text-muted">0 = não escala por tempo</small>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Máx. níveis acima</label>
                                                        <input type="number" min="0" max="10" class="form-control"
                                                               name="stages[<?= (int) $idx ?>][max_escalation_levels]"
                                                               value="<?= $maxLevels ?>">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Depois do prazo</label>
                                                        <select class="form-select" name="stages[<?= (int) $idx ?>][escalate_policy]">
                                                            <option value="next_level" <?= $policy === 'next_level' ? 'selected' : '' ?>>Subir na hierarquia</option>
                                                            <option value="next_stage" <?= $policy === 'next_stage' ? 'selected' : '' ?>>Ir à próxima etapa</option>
                                                            <option value="hr" <?= $policy === 'hr' ? 'selected' : '' ?>>Ir à próxima etapa RH</option>
                                                            <option value="none" <?= $policy === 'none' ? 'selected' : '' ?>>Não escalar</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="card border-light shadow mb-3">
                <div class="card-header">
                    <button class="btn btn-link text-decoration-none p-0 text-dark fw-semibold" type="button"
                            data-bs-toggle="collapse" data-bs-target="#wf-exceptions" aria-expanded="false">
                        <i class="fas fa-user-slash me-2"></i>Exceções — quem pula o gestor do solicitante
                        <i class="fas fa-chevron-down ms-1 small"></i>
                    </button>
                </div>
                <div id="wf-exceptions" class="collapse">
                    <div class="card-body row g-3">
                        <div class="col-12">
                            <p class="small text-muted mb-0">
                                Ao pular, a solicitação segue para a <strong>próxima etapa</strong> do fluxo (não “inventa” outro gestor).
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="skip_immediate_requester_level_ids">
                                Solicitantes destes níveis pulam o gestor
                            </label>
                            <select name="skip_immediate_requester_level_ids[]" id="skip_immediate_requester_level_ids" class="form-select" multiple size="7">
                                <?php foreach ($accessLevels as $level): ?>
                                    <?php $lid = (int) ($level['id'] ?? 0); ?>
                                    <option value="<?= $lid ?>" <?= in_array($lid, $skipRequester, true) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($level['name'] ?? ('#' . $lid)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="skip_immediate_supervisor_level_ids">
                                Se o gestor for destes níveis, pular
                            </label>
                            <select name="skip_immediate_supervisor_level_ids[]" id="skip_immediate_supervisor_level_ids" class="form-select" multiple size="7">
                                <?php foreach ($accessLevels as $level): ?>
                                    <?php $lid = (int) ($level['id'] ?? 0); ?>
                                    <option value="<?= $lid ?>" <?= in_array($lid, $skipSupervisor, true) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($level['name'] ?? ('#' . $lid)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-success"><i class="fas fa-save me-2"></i>Salvar fluxo e tipo</button>
            <a href="<?= $_ENV['URL_ADM']; ?>list-request-types" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<template id="stage-row-template">
    <div class="card border stage-row" data-stage-index="__INDEX__" style="border-left-color: #0d6efd !important;">
        <div class="card-body">
            <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="stage-badge" style="background: #0d6efd">__NUM__</span>
                    <div>
                        <div class="fw-semibold stage-title">Etapa __NUM__</div>
                        <div class="stage-kind-summary text-muted small">1º nível = gestor imediato de quem abriu (ex.: André)</div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-stage" title="Remover etapa">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Quem aprova nesta etapa?</label>
                    <select name="stages[__INDEX__][approver_kind]" class="form-select stage-kind" required>
                        <option value="immediate" selected>Gestor na hierarquia do solicitante</option>
                        <option value="hr">Fila do RH</option>
                        <option value="fixed_user">Pessoa específica</option>
                    </select>
                    <div class="stage-hint stage-kind-hint">1º nível = gestor imediato de quem abriu (ex.: André)</div>
                    <div class="alert alert-warning py-2 px-3 mt-2 mb-0 small stage-warn-dup">
                        Já existe outra etapa com o <strong>mesmo nível</strong> na hierarquia — as duas pedem a mesma pessoa. Mude o nível (1º, 2º…) ou remova a duplicata.
                    </div>
                </div>
                <div class="col-md-7">
                    <label class="form-label">Nome da etapa (opcional)</label>
                    <input type="text" name="stages[__INDEX__][stage_label]" class="form-control stage-label-input" value="" placeholder="Ex.: Aprovação do gestor">
                </div>
                <div class="col-md-6 stage-hierarchy">
                    <label class="form-label">Qual nível acima do solicitante?</label>
                    <select name="stages[__INDEX__][hierarchy_level]" class="form-select stage-hierarchy-level">
                        <option value="1" selected>1º — gestor imediato (ex.: André)</option>
                        <option value="2">2º — chefe do gestor (ex.: Nathiele)</option>
                        <option value="3">3º nível acima do solicitante</option>
                        <option value="4">4º nível acima do solicitante</option>
                        <option value="5">5º nível acima do solicitante</option>
                    </select>
                </div>
                <div class="col-12 stage-fixed-user d-none">
                    <label class="form-label">Escolha a pessoa</label>
                    <select name="stages[__INDEX__][fixed_user_id]" class="form-select stage-fixed-select">
                        <option value="">Selecione…</option>
                        <?php foreach ($users as $u): ?>
                            <?php $uid = (int) ($u['id'] ?? 0); ?>
                            <option value="<?= $uid ?>"><?= htmlspecialchars(($u['name'] ?? '') . ' (#' . $uid . ')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 stage-sla">
                    <div class="border rounded p-3 bg-light">
                        <div class="fw-semibold small mb-2">Se esta pessoa não responder (escalação por prazo)</div>
                        <p class="small text-muted mb-3 mb-md-2">
                            Para André <strong>e depois</strong> Nathiele obrigatórios, use duas etapas (níveis 1º e 2º) — não confunda com esta escalação por prazo.
                        </p>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Prazo (horas)</label>
                                <input type="number" min="0" max="720" class="form-control" name="stages[__INDEX__][escalate_after_hours]" value="72">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Máx. níveis acima</label>
                                <input type="number" min="0" max="10" class="form-control" name="stages[__INDEX__][max_escalation_levels]" value="1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Depois do prazo</label>
                                <select class="form-select" name="stages[__INDEX__][escalate_policy]">
                                    <option value="next_level" selected>Subir na hierarquia</option>
                                    <option value="next_stage">Ir à próxima etapa</option>
                                    <option value="hr">Ir à próxima etapa RH</option>
                                    <option value="none">Não escalar</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
(function () {
    const editor = document.getElementById('stages-editor');
    const tpl = document.getElementById('stage-row-template');
    const addBtn = document.getElementById('btn-add-stage');
    const preview = document.getElementById('wf-preview');
    if (!editor || !tpl || !addBtn || !preview) return;

    const KIND = {
        immediate: {
            short: 'Nível N acima de quem abriu o pedido',
            color: '#0d6efd',
            preview: 'Gestor na hierarquia'
        },
        hr: {
            short: 'Quem tem permissão de aprovar como RH — não é por cargo',
            color: '#6f42c1',
            preview: 'Fila do RH'
        },
        fixed_user: {
            short: 'Você escolhe exatamente quem aprova',
            color: '#198754',
            preview: 'Pessoa específica'
        }
    };

    const LEVEL_HINT = {
        1: '1º nível = gestor imediato de quem abriu (ex.: André)',
        2: '2º nível = chefe do gestor (ex.: Nathiele)'
    };

    function levelHint(level) {
        const n = parseInt(level, 10) || 1;
        return LEVEL_HINT[n] || (n + 'º nível acima de quem abriu o pedido');
    }

    function levelPreview(level) {
        const n = parseInt(level, 10) || 1;
        if (n === 1) return '1º nível (ex.: André)';
        if (n === 2) return '2º nível (ex.: Nathiele)';
        return n + 'º nível na hierarquia';
    }

    function renumber() {
        editor.querySelectorAll('.stage-row').forEach((row, idx) => {
            row.dataset.stageIndex = String(idx);
            const title = row.querySelector('.stage-title');
            if (title) title.textContent = 'Etapa ' + (idx + 1);
            const badge = row.querySelector('.stage-badge');
            if (badge) badge.textContent = String(idx + 1);
            row.querySelectorAll('[name^="stages["]').forEach((el) => {
                el.name = el.name.replace(/stages\[\d+]/, 'stages[' + idx + ']');
            });
        });
        markDuplicateImmediate();
        renderPreview();
    }

    function toggleRow(row) {
        const kind = row.querySelector('.stage-kind')?.value || 'immediate';
        const meta = KIND[kind] || KIND.immediate;
        const level = row.querySelector('.stage-hierarchy-level')?.value || '1';
        const fixed = row.querySelector('.stage-fixed-user');
        const hierarchy = row.querySelector('.stage-hierarchy');
        const sla = row.querySelector('.stage-sla');
        const hint = row.querySelector('.stage-kind-hint');
        const summary = row.querySelector('.stage-kind-summary');
        const badge = row.querySelector('.stage-badge');
        if (fixed) fixed.classList.toggle('d-none', kind !== 'fixed_user');
        if (hierarchy) hierarchy.classList.toggle('d-none', kind !== 'immediate');
        if (sla) sla.classList.toggle('d-none', kind !== 'immediate');
        const text = kind === 'immediate' ? levelHint(level) : meta.short;
        if (hint) hint.textContent = text;
        if (summary) summary.textContent = text;
        if (badge) badge.style.background = meta.color;
        row.style.borderLeftColor = meta.color;
        markDuplicateImmediate();
        renderPreview();
    }

    function markDuplicateImmediate() {
        const rows = [...editor.querySelectorAll('.stage-row')];
        rows.forEach((r) => r.classList.remove('is-dup-immediate'));
        const seen = {};
        rows.forEach((r) => {
            if (r.querySelector('.stage-kind')?.value !== 'immediate') return;
            const level = r.querySelector('.stage-hierarchy-level')?.value || '1';
            if (!seen[level]) seen[level] = [];
            seen[level].push(r);
        });
        Object.values(seen).forEach((list) => {
            if (list.length > 1) list.forEach((r) => r.classList.add('is-dup-immediate'));
        });
    }

    function renderPreview() {
        const parts = ['<span class="wf-node"><i class="fas fa-user text-secondary"></i> Solicitante</span>'];
        editor.querySelectorAll('.stage-row').forEach((row) => {
            const kind = row.querySelector('.stage-kind')?.value || 'immediate';
            const meta = KIND[kind] || KIND.immediate;
            let label = meta.preview;
            if (kind === 'immediate') {
                label = levelPreview(row.querySelector('.stage-hierarchy-level')?.value || '1');
            } else if (kind === 'fixed_user') {
                const sel = row.querySelector('.stage-fixed-select');
                const opt = sel?.selectedOptions?.[0];
                if (opt && opt.value) {
                    label = opt.textContent.trim().replace(/\s*\(#\d+\)\s*$/, '');
                }
            }
            const custom = (row.querySelector('.stage-label-input')?.value || '').trim();
            if (custom) label = custom;
            parts.push('<span class="wf-arrow"><i class="fas fa-arrow-right"></i></span>');
            parts.push(
                '<span class="wf-node" style="border-color:' + meta.color + ';color:' + meta.color + '">' +
                '<span class="wf-dot" style="width:.55rem;height:.55rem;border-radius:50%;background:' + meta.color + ';display:inline-block"></span> ' +
                escapeHtml(label) +
                '</span>'
            );
        });
        parts.push('<span class="wf-arrow"><i class="fas fa-arrow-right"></i></span>');
        parts.push('<span class="wf-node text-success"><i class="fas fa-check"></i> Aprovado</span>');
        preview.innerHTML = parts.join('');
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    editor.addEventListener('change', (e) => {
        const t = e.target;
        if (!t) return;
        if (t.classList.contains('stage-kind') || t.classList.contains('stage-hierarchy-level')) {
            toggleRow(t.closest('.stage-row'));
            return;
        }
        if (t.classList.contains('stage-fixed-select') || t.classList.contains('stage-label-input')) {
            renderPreview();
        }
    });

    editor.addEventListener('input', (e) => {
        if (e.target && e.target.classList.contains('stage-label-input')) {
            renderPreview();
        }
    });

    editor.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-remove-stage');
        if (!btn) return;
        const rows = editor.querySelectorAll('.stage-row');
        if (rows.length <= 1) {
            alert('O fluxo precisa ter ao menos uma etapa.');
            return;
        }
        btn.closest('.stage-row')?.remove();
        renumber();
    });

    addBtn.addEventListener('click', () => {
        const idx = editor.querySelectorAll('.stage-row').length;
        const html = tpl.innerHTML
            .replaceAll('__INDEX__', String(idx))
            .replaceAll('__NUM__', String(idx + 1));
        const wrap = document.createElement('div');
        wrap.innerHTML = html.trim();
        const row = wrap.firstElementChild;
        // Sugere 2º nível se já existe um 1º
        const hasLevel1 = [...editor.querySelectorAll('.stage-row')].some((r) =>
            r.querySelector('.stage-kind')?.value === 'immediate'
            && (r.querySelector('.stage-hierarchy-level')?.value || '1') === '1'
        );
        if (hasLevel1) {
            const levelSel = row.querySelector('.stage-hierarchy-level');
            if (levelSel) levelSel.value = '2';
        }
        editor.appendChild(row);
        toggleRow(row);
        renumber();
    });

    editor.querySelectorAll('.stage-row').forEach(toggleRow);
    renderPreview();
})();
</script>
