<?php
use App\adms\Helpers\CSRFHelper;
$form = $this->data['form'] ?? [];
?>
<div class="container-fluid px-4">
    <h2 class="mt-3">Registrar Promoção</h2>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card border-light shadow mb-4"><div class="card-body">
        <form method="POST" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_create_career_promotion'); ?>">
            <div class="col-md-6">
                <label class="form-label">Colaborador *</label>
                <select name="user_id" class="form-select" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($this->data['employees'] ?? [] as $e): ?>
                        <option value="<?= (int) $e['id'] ?>"><?= htmlspecialchars($e['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Cargo destino *</label>
                <select name="to_position_id" class="form-select" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($this->data['positions'] ?? [] as $p): ?>
                        <option value="<?= (int) $p['id'] ?>"><?= htmlspecialchars($p['name'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Cargo origem</label>
                <select name="from_position_id" class="form-select">
                    <option value="">Automático / opcional</option>
                    <?php foreach ($this->data['positions'] ?? [] as $p): ?>
                        <option value="<?= (int) $p['id'] ?>"><?= htmlspecialchars($p['name'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Trilha</label>
                <select name="career_track_id" class="form-select">
                    <option value="">Opcional</option>
                    <?php foreach ($this->data['tracks'] ?? [] as $t): ?>
                        <option value="<?= (int) $t['id'] ?>"><?= htmlspecialchars($t['name'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4"><label class="form-label">Data efetiva *</label><input type="date" name="effective_date" class="form-control" required value="<?= htmlspecialchars($form['effective_date'] ?? date('Y-m-d')) ?>"></div>
            <div class="col-12"><label class="form-label">Notas</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
            <div class="col-12"><button class="btn btn-success">Salvar</button> <a class="btn btn-secondary" href="<?php echo $_ENV['URL_ADM']; ?>list-career-promotions">Cancelar</a></div>
        </form>
    </div></div>
</div>
