<?php
use App\adms\Helpers\CSRFHelper;
$c = $this->data['campaign'] ?? [];
$locked = ($c['status'] ?? '') === 'closed';
?>
<div class="container-fluid px-4">
    <h2 class="mt-3">Editar Pesquisa</h2>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card border-light shadow">
        <div class="card-body">
            <?php if ($locked): ?>
                <div class="alert alert-warning">Campanha fechada — somente leitura.</div>
            <?php else: ?>
                <form method="post" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('form_update_pulse_campaign') ?>">
                    <div class="col-md-6">
                        <label class="form-label">Nome</label>
                        <input name="name" class="form-control" required value="<?= htmlspecialchars($c['name'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <?php foreach (['draft'=>'Rascunho','open'=>'Aberta','closed'=>'Fechada'] as $k=>$l): ?>
                                <option value="<?= $k ?>" <?= ($c['status'] ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Abra para coletar respostas; feche ao encerrar.</small>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-check">
                            <input type="hidden" name="anonymous" value="0">
                            <input class="form-check-input" type="checkbox" name="anonymous" value="1" id="anon"
                                <?= !empty($c['anonymous']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="anon">Anônima</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Descrição</label>
                        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars((string)($c['description'] ?? '')) ?></textarea>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-success">Salvar</button>
                        <a href="<?= $_ENV['URL_ADM'] ?>view-pulse-campaign/<?= (int)$c['id'] ?>" class="btn btn-secondary">Voltar</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
