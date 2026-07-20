<?php
use App\adms\Helpers\CSRFHelper;
$form = $this->data['form'] ?? [];
?>
<div class="container-fluid px-4">
    <h2 class="mt-3">Nova Pesquisa Pulse / eNPS</h2>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card border-light shadow">
        <div class="card-body">
            <form method="post" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('form_create_pulse_campaign') ?>">
                <div class="col-md-6">
                    <label class="form-label">Nome *</label>
                    <input name="name" class="form-control" required value="<?= htmlspecialchars($form['name'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tipo *</label>
                    <select name="campaign_type" class="form-select">
                        <option value="enps" <?= ($form['campaign_type'] ?? '') === 'enps' ? 'selected' : '' ?>>eNPS</option>
                        <option value="pulse" <?= ($form['campaign_type'] ?? '') === 'pulse' ? 'selected' : '' ?>>Pulse</option>
                    </select>
                    <small class="text-muted">eNPS já cria a pergunta 0–10 padrão.</small>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check">
                        <input type="hidden" name="anonymous" value="0">
                        <input class="form-check-input" type="checkbox" name="anonymous" value="1" id="anon"
                            <?= (($form['anonymous'] ?? '1') !== '0') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="anon">Respostas anônimas</label>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Descrição</label>
                    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($form['description'] ?? '') ?></textarea>
                </div>
                <div class="col-12">
                    <button class="btn btn-success">Salvar</button>
                    <a href="<?= $_ENV['URL_ADM'] ?>list-pulse-campaigns" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
