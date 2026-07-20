<?php
use App\adms\Helpers\CSRFHelper;
$c = $this->data['campaign'] ?? [];
$open = ($c['status'] ?? '') === 'open';
?>
<div class="container-fluid px-4">
    <h2 class="mt-3">Responder: <?= htmlspecialchars($c['name'] ?? '') ?></h2>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <?php if (!$open): ?>
        <div class="alert alert-warning">Esta campanha não está aberta.</div>
    <?php else: ?>
        <div class="card border-light shadow">
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('form_respond_pulse_campaign') ?>">
                    <?php foreach ($this->data['questions'] ?? [] as $q):
                        $qid = (int) $q['id'];
                        $type = $q['question_type'] ?? 'nps';
                        $max = $type === 'likert' ? 5 : 10;
                        ?>
                        <div class="mb-4">
                            <label class="form-label fw-semibold"><?= htmlspecialchars($q['question_text'] ?? '') ?></label>
                            <?php if ($type === 'text'): ?>
                                <textarea name="answers[<?= $qid ?>][comment]" class="form-control" rows="3"></textarea>
                            <?php else: ?>
                                <input type="number" min="0" max="<?= $max ?>" class="form-control" style="max-width:8rem"
                                       name="answers[<?= $qid ?>][score]" required>
                                <small class="text-muted">0 a <?= $max ?></small>
                                <textarea name="answers[<?= $qid ?>][comment]" class="form-control mt-2" rows="2" placeholder="Comentário opcional"></textarea>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <button class="btn btn-primary">Enviar respostas</button>
                    <a href="<?= $_ENV['URL_ADM'] ?>list-pulse-campaigns" class="btn btn-secondary">Cancelar</a>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>
