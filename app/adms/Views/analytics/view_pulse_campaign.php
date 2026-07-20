<?php
use App\adms\Helpers\CSRFHelper;
$c = $this->data['campaign'] ?? [];
$enps = $this->data['enps'] ?? [];
$cid = (int) ($c['id'] ?? 0);
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><?= htmlspecialchars($c['name'] ?? 'Campanha') ?></h2>
        <div class="ms-auto">
            <?php if (in_array('UpdatePulseCampaign', $this->data['buttonPermission'] ?? [], true) && ($c['status'] ?? '') !== 'closed'): ?>
                <a href="<?= $_ENV['URL_ADM'] ?>update-pulse-campaign/<?= $cid ?>" class="btn btn-sm btn-warning">Editar</a>
            <?php endif; ?>
            <?php if (($c['status'] ?? '') === 'open' && in_array('RespondPulseCampaign', $this->data['buttonPermission'] ?? [], true)): ?>
                <a href="<?= $_ENV['URL_ADM'] ?>respond-pulse-campaign/<?= $cid ?>" class="btn btn-sm btn-primary">Responder</a>
            <?php endif; ?>
            <a href="<?= $_ENV['URL_ADM'] ?>list-pulse-campaigns" class="btn btn-sm btn-secondary">Listar</a>
        </div>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card border-light shadow mb-3">
        <div class="card-body row g-3">
            <div class="col-md-3"><div class="text-muted small">Tipo</div><?= htmlspecialchars($c['campaign_type'] ?? '') ?></div>
            <div class="col-md-3"><div class="text-muted small">Status</div><?= htmlspecialchars($c['status'] ?? '') ?></div>
            <div class="col-md-3"><div class="text-muted small">Anônima</div><?= !empty($c['anonymous']) ? 'Sim' : 'Não' ?></div>
            <div class="col-md-3"><div class="text-muted small">Respostas</div><?= (int) ($this->data['responses_count'] ?? 0) ?></div>
            <?php if (($c['campaign_type'] ?? '') === 'enps'): ?>
                <div class="col-12">
                    <div class="alert alert-info mb-0">
                        <strong>eNPS:</strong>
                        <?= $enps['enps'] !== null ? htmlspecialchars((string) $enps['enps']) : '—' ?>
                        (promotores <?= (int) ($enps['promoters'] ?? 0) ?>,
                        passivos <?= (int) ($enps['passives'] ?? 0) ?>,
                        detratores <?= (int) ($enps['detractors'] ?? 0) ?>,
                        n=<?= (int) ($enps['total'] ?? 0) ?>)
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="card border-light shadow mb-3">
        <div class="card-header">Perguntas</div>
        <div class="card-body">
            <ul class="list-group mb-3">
                <?php foreach ($this->data['questions'] ?? [] as $q): ?>
                    <li class="list-group-item">
                        <span class="badge bg-secondary me-2"><?= htmlspecialchars($q['question_type'] ?? '') ?></span>
                        <?= htmlspecialchars($q['question_text'] ?? '') ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if (($c['status'] ?? '') !== 'closed' && ($c['campaign_type'] ?? '') === 'pulse'): ?>
                <form method="post" class="row g-2">
                    <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('form_add_pulse_question') ?>">
                    <input type="hidden" name="action" value="add_question">
                    <div class="col-md-7"><input name="question_text" class="form-control form-control-sm" placeholder="Nova pergunta" required></div>
                    <div class="col-md-3">
                        <select name="question_type" class="form-select form-select-sm">
                            <option value="likert">Likert 0–5</option>
                            <option value="nps">NPS 0–10</option>
                            <option value="text">Texto</option>
                        </select>
                    </div>
                    <div class="col-md-2"><button class="btn btn-sm btn-outline-primary w-100">Adicionar</button></div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
