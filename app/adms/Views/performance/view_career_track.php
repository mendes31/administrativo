<?php
use App\adms\Helpers\CSRFHelper;
$t = $this->data['track'] ?? [];
$canEdit = in_array('UpdateCareerTrack', $this->data['buttonPermission'] ?? [], true);
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><?= htmlspecialchars($t['name'] ?? '') ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>list-career-tracks" class="text-decoration-none">Trilhas</a></li>
            <li class="breadcrumb-item">Ver</li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex justify-content-between flex-wrap gap-2">
            <span>Trilha de carreira</span>
            <div>
                <?php if ($canEdit) { ?><a class="btn btn-sm btn-warning" href="<?php echo $_ENV['URL_ADM']; ?>update-career-track/<?= (int) $t['id'] ?>">Editar</a><?php } ?>
                <?php $log_resumo = $this->data['log_resumo'] ?? []; $log_btn_class = 'btn btn-outline-info btn-sm'; include __DIR__ . '/../partials/button_log_alteracoes.php'; ?>
            </div>
        </div>
        <div class="card-body row g-3">
            <div class="col-md-3"><div class="text-muted small">Status</div><?= ($t['status'] ?? '') === 'active' ? 'Ativo' : 'Inativo' ?></div>
            <div class="col-md-3"><div class="text-muted small">Níveis</div><?= (int) ($t['levels_count'] ?? 0) ?></div>
            <?php if (!empty($t['description'])): ?><div class="col-12"><?= nl2br(htmlspecialchars($t['description'])) ?></div><?php endif; ?>
        </div>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header">Níveis</div>
        <div class="card-body">
            <?php if (empty($this->data['levels'])): ?>
                <div class="alert alert-info">Nenhum nível.</div>
            <?php else: ?>
                <table class="table table-sm"><thead><tr><th>#</th><th>Nome</th><th>Cargo</th><th></th></tr></thead><tbody>
                <?php foreach ($this->data['levels'] as $l): ?>
                    <tr>
                        <td><?= (int) $l['level_order'] ?></td>
                        <td><?= htmlspecialchars($l['name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($l['position_name'] ?? '—') ?></td>
                        <td>
                            <?php if ($canEdit): ?>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Remover?');">
                                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_career_remove_level'); ?>">
                                <input type="hidden" name="form_action" value="remove_level">
                                <input type="hidden" name="level_id" value="<?= (int) $l['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger">Remover</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody></table>
            <?php endif; ?>
            <?php if ($canEdit): ?>
            <form method="POST" class="row g-2 border rounded p-3 mt-2">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_career_add_level'); ?>">
                <input type="hidden" name="form_action" value="add_level">
                <div class="col-md-4"><input name="name" class="form-control form-control-sm" placeholder="Nome do nível *" required></div>
                <div class="col-md-2"><input type="number" name="level_order" class="form-control form-control-sm" value="1" min="1" max="99"></div>
                <div class="col-md-4">
                    <select name="position_id" class="form-select form-select-sm">
                        <option value="">Cargo (opcional)</option>
                        <?php foreach ($this->data['positions'] ?? [] as $p): ?>
                            <option value="<?= (int) $p['id'] ?>"><?= htmlspecialchars($p['name'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2"><button class="btn btn-sm btn-success w-100">Adicionar</button></div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
