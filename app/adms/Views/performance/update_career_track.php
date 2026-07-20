<?php
use App\adms\Helpers\CSRFHelper;
$t = $this->data['track'] ?? [];
?>
<div class="container-fluid px-4">
    <h2 class="mt-3">Editar Trilha</h2>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card border-light shadow mb-4">
        <div class="card-body">
            <form method="POST" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_update_career_track'); ?>">
                <div class="col-md-8"><label class="form-label">Nome *</label><input name="name" class="form-control" required value="<?= htmlspecialchars($t['name'] ?? '') ?>"></div>
                <div class="col-md-4"><label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= ($t['status'] ?? '') === 'active' ? 'selected' : '' ?>>Ativo</option>
                        <option value="inactive" <?= ($t['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inativo</option>
                    </select>
                </div>
                <div class="col-12"><label class="form-label">Descrição</label><textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($t['description'] ?? '') ?></textarea></div>
                <div class="col-12"><button class="btn btn-success">Salvar</button> <a class="btn btn-secondary" href="<?php echo $_ENV['URL_ADM']; ?>view-career-track/<?= (int) ($t['id'] ?? 0) ?>">Cancelar</a></div>
            </form>
        </div>
    </div>
</div>
