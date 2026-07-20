<?php
use App\adms\Helpers\CSRFHelper;
$n = $this->data['nomination'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Editar Nomeação HiPo</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>list-talent-nominations" class="text-decoration-none">Talent Pool</a></li>
            <li class="breadcrumb-item">Editar</li>
        </ol>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header"><?= htmlspecialchars($n['user_name'] ?? '') ?> — <?= htmlspecialchars($n['cycle_name'] ?? '') ?></div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form method="POST" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_update_talent_nomination'); ?>">
                <div class="col-md-3">
                    <label class="form-label">Box Nine Box</label>
                    <select name="nine_box" class="form-select">
                        <option value="">Não informado</option>
                        <?php for ($b = 1; $b <= 9; $b++): ?>
                            <option value="<?= $b ?>" <?= ((int) ($n['nine_box'] ?? 0) === $b) ? 'selected' : '' ?>><?= $b ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= ($n['status'] ?? '') === 'active' ? 'selected' : '' ?>>Ativo</option>
                        <option value="removed" <?= ($n['status'] ?? '') === 'removed' ? 'selected' : '' ?>>Removido</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Notas</label>
                    <input type="text" name="notes" class="form-control" value="<?= htmlspecialchars($n['notes'] ?? '') ?>">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-success">Salvar</button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>view-talent-nomination/<?= (int) ($n['id'] ?? 0) ?>" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
