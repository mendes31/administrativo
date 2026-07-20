<?php
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\FormatHelper;
$item = $this->data['item'] ?? [];
$canEdit = in_array('UpdateCriticalPosition', $this->data['buttonPermission'] ?? [], true);
$riskLabels = ['high' => ['Alto', 'danger'], 'medium' => ['Médio', 'warning'], 'low' => ['Baixo', 'secondary']];
$rk = $riskLabels[$item['risk_level'] ?? ''] ?? ['—', 'secondary'];
$readinessLabels = [
    'ready_now' => 'Pronto agora',
    'ready_1_2y' => '1–2 anos',
    'ready_3y' => '3+ anos',
    'emergency' => 'Emergência',
];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Sucessão</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>list-critical-positions" class="text-decoration-none">Cargos críticos</a></li>
            <li class="breadcrumb-item">Visualizar</li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span><i class="fas fa-sitemap me-2"></i><?= htmlspecialchars($item['position_name'] ?? '') ?></span>
            <div>
                <?php if (in_array('ListTalentNominations', $this->data['buttonPermission'] ?? [], true)) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-talent-nominations" class="btn btn-sm btn-outline-warning">Talent Pool</a>
                <?php } ?>
                <?php if ($canEdit) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>update-critical-position/<?= (int) $item['id'] ?>" class="btn btn-sm btn-warning">Editar</a>
                <?php } ?>
                <?php
                $log_resumo = $this->data['log_resumo'] ?? [];
                $log_btn_class = 'btn btn-outline-info btn-sm';
                include __DIR__ . '/../partials/button_log_alteracoes.php';
                ?>
            </div>
        </div>
        <div class="card-body row g-3">
            <div class="col-md-3"><div class="text-muted small">Risco</div><span class="badge bg-<?= $rk[1] ?>"><?= $rk[0] ?></span></div>
            <div class="col-md-3"><div class="text-muted small">Status</div><?= ($item['status'] ?? '') === 'active' ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-secondary">Inativo</span>' ?></div>
            <div class="col-md-3"><div class="text-muted small">Criado por</div><?= htmlspecialchars($item['created_by_name'] ?? '—') ?></div>
            <?php if (!empty($item['notes'])): ?>
                <div class="col-12"><div class="text-muted small">Notas</div><?= nl2br(htmlspecialchars($item['notes'])) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header"><i class="fas fa-users me-2"></i>Sucessores</div>
        <div class="card-body">
            <?php if (empty($this->data['successors'])): ?>
                <div class="alert alert-info">Nenhum sucessor indicado.</div>
            <?php else: ?>
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-hover align-middle">
                        <thead><tr><th>Ordem</th><th>Colaborador</th><th>Readiness</th><th>Nomeado por</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($this->data['successors'] as $s): ?>
                                <tr>
                                    <td><?= (int) ($s['priority_order'] ?? 1) ?></td>
                                    <td><?= htmlspecialchars($s['user_name'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($readinessLabels[$s['readiness'] ?? ''] ?? ($s['readiness'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars($s['nominated_by_name'] ?? '—') ?></td>
                                    <td class="text-nowrap">
                                        <?php if (in_array('ListPdiPlans', $this->data['buttonPermission'] ?? [], true)) { ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>list-pdi-plans?user_id=<?= (int) $s['user_id'] ?>" class="btn btn-sm btn-outline-secondary">PDI</a>
                                        <?php } ?>
                                        <?php if ($canEdit): ?>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#edit-succ-<?= (int) $s['id'] ?>">Editar</button>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Remover sucessor?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_succ_remove'); ?>">
                                                <input type="hidden" name="form_action" value="remove_successor">
                                                <input type="hidden" name="successor_id" value="<?= (int) $s['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Remover</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php if ($canEdit): ?>
                                <tr class="collapse" id="edit-succ-<?= (int) $s['id'] ?>">
                                    <td colspan="5">
                                        <form method="POST" class="row g-2 border rounded p-3 bg-light">
                                            <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_succ_update'); ?>">
                                            <input type="hidden" name="form_action" value="update_successor">
                                            <input type="hidden" name="successor_id" value="<?= (int) $s['id'] ?>">
                                            <div class="col-md-3">
                                                <label class="form-label small">Readiness</label>
                                                <select name="readiness" class="form-select form-select-sm">
                                                    <?php foreach ($readinessLabels as $code => $label): ?>
                                                        <option value="<?= $code ?>" <?= ($s['readiness'] ?? '') === $code ? 'selected' : '' ?>><?= $label ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Ordem</label>
                                                <input type="number" name="priority_order" class="form-control form-control-sm" min="1" max="99" value="<?= (int) ($s['priority_order'] ?? 1) ?>">
                                            </div>
                                            <div class="col-md-5">
                                                <label class="form-label small">Notas</label>
                                                <input type="text" name="notes" class="form-control form-control-sm" value="<?= htmlspecialchars($s['notes'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-2 d-flex align-items-end">
                                                <button type="submit" class="btn btn-sm btn-success">Salvar</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if ($canEdit): ?>
                <h6 class="mb-2">Adicionar sucessor</h6>
                <form method="POST" class="row g-2 border rounded p-3">
                    <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_succ_add'); ?>">
                    <input type="hidden" name="form_action" value="add_successor">
                    <div class="col-md-5">
                        <label class="form-label small">Colaborador <span class="text-danger">*</span></label>
                        <select name="user_id" class="form-select form-select-sm" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($this->data['employees'] ?? [] as $emp): ?>
                                <option value="<?= (int) $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Readiness</label>
                        <select name="readiness" class="form-select form-select-sm">
                            <?php foreach ($readinessLabels as $code => $label): ?>
                                <option value="<?= $code ?>" <?= $code === 'ready_1_2y' ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Ordem</label>
                        <input type="number" name="priority_order" class="form-control form-control-sm" min="1" max="99" value="1">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-plus me-1"></i>Adicionar</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
