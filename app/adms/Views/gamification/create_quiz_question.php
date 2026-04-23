<?php
$quiz = $this->data['quiz'] ?? [];
$quizId = (int)($quiz['id'] ?? 0);
$nextSort = (int)($this->data['next_sort'] ?? 0);
include __DIR__ . '/partials/module_head.php';
?>
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <h2 class="mt-3">Nova questão</h2>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card border-light shadow mb-4">
        <div class="card-body">
            <form method="post" class="row g-3" id="qform">
                <input type="hidden" name="csrf_token" value="<?php echo \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_create_gamification_quiz_question'); ?>">
                <div class="col-12">
                    <label class="form-label">Enunciado</label>
                    <textarea name="body" class="form-control" rows="4" required></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tipo</label>
                    <select name="question_type" class="form-select" id="qtype">
                        <option value="single">Escolha única</option>
                        <option value="multiple">Múltipla escolha</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Ordem</label>
                    <input type="number" name="sort_order" class="form-control" min="0" value="<?= $nextSort ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Pontos se correta</label>
                    <input type="number" name="points_correct" class="form-control" min="0" value="1">
                </div>
                <div class="col-12">
                    <h6 class="mt-2">Opções (mínimo 2)</h6>
                    <div id="opts">
                        <?php for ($i = 0; $i < 4; $i++): ?>
                            <div class="row g-2 mb-2 align-items-center">
                                <div class="col">
                                    <input type="text" name="option_label[]" class="form-control" placeholder="Texto da opção">
                                </div>
                                <div class="col-auto">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="option_correct[<?= $i ?>]" value="1" id="c<?= $i ?>">
                                        <label class="form-check-label" for="c<?= $i ?>">Correta</label>
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-gamification-quiz-questions/<?= $quizId ?>" class="btn btn-secondary">Voltar</a>
                </div>
            </form>
        </div>
    </div>
</div>
