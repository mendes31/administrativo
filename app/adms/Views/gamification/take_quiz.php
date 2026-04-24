<?php
$quiz = $this->data['quiz'] ?? [];
$attempt = $this->data['attempt'] ?? [];
$questions = $this->data['questions'] ?? [];
include __DIR__ . '/partials/module_head.php';
?>
<?php
$quizTitle = isset($quiz['title']) ? (string)$quiz['title'] : '';
$quizSummary = isset($quiz['summary']) ? (string)$quiz['summary'] : '';
?>
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <h2 class="mt-3"><?php echo htmlspecialchars($quizTitle, ENT_QUOTES, 'UTF-8'); ?></h2>
    <?php if ($quizSummary !== ''): ?>
        <p class="text-muted"><?php echo nl2br(htmlspecialchars($quizSummary, ENT_QUOTES, 'UTF-8')); ?></p>
    <?php endif; ?>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <?php if (in_array('SubmitGamificationQuizAttempt', $this->data['buttonPermission'] ?? [], true)) : ?>
        <form method="post" action="<?php echo $_ENV['URL_ADM']; ?>submit-gamification-quiz-attempt" class="mb-5">
            <input type="hidden" name="csrf_token" value="<?php echo \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_submit_gamification_quiz'); ?>">
            <input type="hidden" name="attempt_id" value="<?= (int)($attempt['id'] ?? 0) ?>">
            <input type="hidden" name="quiz_id" value="<?= (int)($quiz['id'] ?? 0) ?>">
            <?php foreach ($questions as $q):
                $qid = (int)($q['id'] ?? 0);
                $type = (string)($q['question_type'] ?? 'single');
                ?>
                <div class="card mb-3 border-light shadow-sm">
                    <div class="card-header fw-semibold">Questão (<?= $type === 'multiple' ? 'múltipla' : 'única' ?>)</div>
                    <div class="card-body">
                        <p><?= nl2br(htmlspecialchars((string)($q['body'] ?? ''))) ?></p>
                        <?php foreach ($q['options'] ?? [] as $o):
                            $oid = (int)($o['id'] ?? 0);
                            if ($oid <= 0) {
                                continue;
                            }
                            ?>
                            <?php if ($type === 'multiple'): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="answer[<?= $qid ?>][]" value="<?= $oid ?>" id="o<?= $oid ?>">
                                    <label class="form-check-label" for="o<?= $oid ?>"><?= htmlspecialchars((string)($o['label'] ?? '')) ?></label>
                                </div>
                            <?php else: ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="answer[<?= $qid ?>]" value="<?= $oid ?>" id="o<?= $oid ?>" required>
                                    <label class="form-check-label" for="o<?= $oid ?>"><?= htmlspecialchars((string)($o['label'] ?? '')) ?></label>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-success"><i class="fas fa-paper-plane me-1"></i>Enviar respostas</button>
            <a href="<?php echo $_ENV['URL_ADM']; ?>gamification-quiz-catalog" class="btn btn-secondary">Cancelar</a>
        </form>
    <?php else : ?>
        <div class="alert alert-warning">Sem permissão para enviar este quiz.</div>
    <?php endif; ?>
</div>
