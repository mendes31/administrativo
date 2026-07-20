<?php
use App\adms\Helpers\CSRFHelper;
$p = $this->data['promotion'] ?? [];
?>
<div class="container-fluid px-4">
    <h2 class="mt-3">Editar Promoção</h2>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card border-light shadow mb-4"><div class="card-body">
        <p class="text-muted"><?= htmlspecialchars($p['user_name'] ?? '') ?></p>
        <form method="POST" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_update_career_promotion'); ?>">
            <div class="col-md-4">
                <label class="form-label">Cargo destino *</label>
                <select name="to_position_id" class="form-select" required>
                    <?php foreach ($this->data['positions'] ?? [] as $pos): ?>
                        <option value="<?= (int) $pos['id'] ?>" <?= ((int) ($p['to_position_id'] ?? 0) === (int) $pos['id']) ? 'selected' : '' ?>><?= htmlspecialchars($pos['name'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Cargo origem</label>
                <select name="from_position_id" class="form-select">
                    <option value="">—</option>
                    <?php foreach ($this->data['positions'] ?? [] as $pos): ?>
                        <option value="<?= (int) $pos['id'] ?>" <?= ((int) ($p['from_position_id'] ?? 0) === (int) $pos['id']) ? 'selected' : '' ?>><?= htmlspecialchars($pos['name'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4"><label class="form-label">Data efetiva *</label><input type="date" name="effective_date" class="form-control" required value="<?= htmlspecialchars($p['effective_date'] ?? '') ?>"></div>
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="draft" <?= ($p['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Rascunho</option>
                    <option value="approved" <?= ($p['status'] ?? '') === 'approved' ? 'selected' : '' ?>>Aprovar</option>
                    <option value="applied" <?= ($p['status'] ?? '') === 'applied' ? 'selected' : '' ?>>Aplicar no colaborador</option>
                    <option value="cancelled" <?= ($p['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelar</option>
                </select>
                <small class="text-muted">Aplicar atualiza o cargo do usuário.</small>
            </div>
            <div class="col-md-8"><label class="form-label">Notas</label><input type="text" name="notes" class="form-control" value="<?= htmlspecialchars($p['notes'] ?? '') ?>"></div>
            <div class="col-12"><button class="btn btn-success">Salvar</button> <a class="btn btn-secondary" href="<?php echo $_ENV['URL_ADM']; ?>view-career-promotion/<?= (int) $p['id'] ?>">Cancelar</a></div>
        </form>
    </div></div>
</div>
