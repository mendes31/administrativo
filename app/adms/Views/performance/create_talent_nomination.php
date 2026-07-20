<?php
use App\adms\Helpers\CSRFHelper;
$form = $this->data['form'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Nomear no Talent Pool</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>list-talent-nominations" class="text-decoration-none">Talent Pool</a></li>
            <li class="breadcrumb-item">Nomear</li>
        </ol>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header"><i class="fas fa-star me-2"></i>Nova nomeação HiPo</div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form method="POST" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_create_talent_nomination'); ?>">
                <div class="col-md-6">
                    <label class="form-label">Colaborador <span class="text-danger">*</span></label>
                    <select name="user_id" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($this->data['employees'] ?? [] as $emp): ?>
                            <option value="<?= (int) $emp['id'] ?>" <?= ((int) ($form['user_id'] ?? 0) === (int) $emp['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($emp['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Ciclo <span class="text-danger">*</span></label>
                    <select name="performance_cycle_id" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($this->data['cycles'] ?? [] as $cycle): ?>
                            <option value="<?= (int) $cycle['id'] ?>" <?= ((int) ($form['performance_cycle_id'] ?? 0) === (int) $cycle['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cycle['name']) ?> (<?= htmlspecialchars($cycle['status']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Box Nine Box</label>
                    <select name="nine_box" class="form-select">
                        <option value="">Não informado</option>
                        <?php for ($b = 1; $b <= 9; $b++): ?>
                            <option value="<?= $b ?>" <?= ((int) ($form['nine_box'] ?? 0) === $b) ? 'selected' : '' ?>><?= $b ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-9">
                    <label class="form-label">Notas / justificativa</label>
                    <input type="text" name="notes" class="form-control" value="<?= htmlspecialchars($form['notes'] ?? '') ?>">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-success">Salvar</button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-talent-nominations" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
