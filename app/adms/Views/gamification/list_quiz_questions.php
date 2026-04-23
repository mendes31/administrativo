<?php
$quiz = $this->data['quiz'] ?? [];
$qid = (int)($quiz['id'] ?? 0);
include __DIR__ . '/partials/module_head.php';
?>
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <h2 class="mt-3">Questões — <?= htmlspecialchars((string)($quiz['title'] ?? '')) ?></h2>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-2">
        <?php if (in_array('CreateGamificationQuizQuestion', $this->data['buttonPermission'] ?? [], true)) { ?>
            <a href="<?php echo $_ENV['URL_ADM']; ?>create-gamification-quiz-question/<?= $qid ?>" class="btn btn-sm btn-success"><i class="fas fa-plus me-1"></i>Nova questão</a>
        <?php } ?>
        <?php if (in_array('UpdateGamificationQuiz', $this->data['buttonPermission'] ?? [], true)) { ?>
            <a href="<?php echo $_ENV['URL_ADM']; ?>update-gamification-quiz/<?= $qid ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit me-1"></i>Editar quiz</a>
        <?php } ?>
        <a href="<?php echo $_ENV['URL_ADM']; ?>list-gamification-quizzes" class="btn btn-sm btn-secondary">Lista de quizzes</a>
    </div>
    <div class="card border-light shadow">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Enunciado</th>
                        <th>Tipo</th>
                        <th>Pontos</th>
                        <th class="text-end">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($this->data['questions'] ?? [] as $row): ?>
                        <tr>
                            <td><?= (int)($row['sort_order'] ?? 0) ?></td>
                            <td><?= nl2br(htmlspecialchars(mb_substr((string)($row['body'] ?? ''), 0, 200))) ?></td>
                            <td><?= htmlspecialchars((string)($row['question_type'] ?? '')) ?></td>
                            <td><?= (int)($row['points_correct'] ?? 0) ?></td>
                            <td class="text-end">
                                <?php if (in_array('UpdateGamificationQuizQuestion', $this->data['buttonPermission'] ?? [], true)) { ?>
                                    <a class="btn btn-sm btn-warning" href="<?php echo $_ENV['URL_ADM']; ?>update-gamification-quiz-question/<?= (int)$row['id'] ?>"><i class="fas fa-edit"></i></a>
                                <?php } ?>
                                <?php if (in_array('DeleteGamificationQuizQuestion', $this->data['buttonPermission'] ?? [], true)) { ?>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="delQ(<?= (int)$row['id'] ?>)"><i class="fas fa-trash"></i></button>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<form id="formDelQ" method="post" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?php echo \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_delete_gamification_quiz_question'); ?>">
</form>
<script>
function delQ(id) {
    if (!confirm('Excluir esta questão?')) return;
    var f = document.getElementById('formDelQ');
    f.action = '<?php echo $_ENV['URL_ADM']; ?>delete-gamification-quiz-question/' + id;
    f.submit();
}
</script>
