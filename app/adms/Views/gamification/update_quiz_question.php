<?php
$quiz = $this->data['quiz'] ?? [];
$quizId = (int)($quiz['id'] ?? 0);
$q = $this->data['question'] ?? [];
$opts = $this->data['options'] ?? [];
include __DIR__ . '/partials/module_head.php';
?>
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <h2 class="mt-3">Editar questão</h2>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card border-light shadow mb-4">
        <div class="card-body">
            <form method="post" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_update_gamification_quiz_question'); ?>">
                <div class="col-12">
                    <label class="form-label">Enunciado</label>
                    <textarea name="body" class="form-control" rows="4" required><?= htmlspecialchars((string)($q['body'] ?? '')) ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tipo</label>
                    <?php $qt = (string)($q['question_type'] ?? 'single'); ?>
                    <select name="question_type" class="form-select">
                        <option value="single" <?= $qt === 'single' ? 'selected' : '' ?>>Escolha única</option>
                        <option value="multiple" <?= $qt === 'multiple' ? 'selected' : '' ?>>Múltipla escolha</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Ordem</label>
                    <input type="number" name="sort_order" class="form-control" min="0" value="<?= (int)($q['sort_order'] ?? 0) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Pontos se correta</label>
                    <input type="number" name="points_correct" class="form-control" min="0" value="<?= (int)($q['points_correct'] ?? 1) ?>">
                </div>
                <div class="col-12">
                    <h6 class="mt-2">Opções</h6>
                    <?php foreach ($opts as $i => $o): ?>
                        <div class="row g-2 mb-2 align-items-center">
                            <div class="col">
                                <input type="text" name="option_label[]" class="form-control" required
                                       value="<?= htmlspecialchars((string)($o['label'] ?? '')) ?>">
                            </div>
                            <div class="col-auto">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="option_correct[<?= (int)$i ?>]" value="1" id="c<?= (int)$i ?>"
                                        <?= !empty($o['is_correct']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="c<?= (int)$i ?>">Correta</label>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php for ($j = count($opts); $j < 4; $j++): ?>
                        <div class="row g-2 mb-2 align-items-center">
                            <div class="col">
                                <input type="text" name="option_label[]" class="form-control" placeholder="Nova opção (opcional)">
                            </div>
                            <div class="col-auto">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="option_correct[<?= (int)$j ?>]" value="1" id="e<?= (int)$j ?>">
                                    <label class="form-check-label" for="e<?= (int)$j ?>">Correta</label>
                                </div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-gamification-quiz-questions/<?= $quizId ?>" class="btn btn-secondary">Voltar</a>
                </div>
            </form>
        </div>
    </div>
</div>
