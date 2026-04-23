<?php include __DIR__ . '/partials/module_head.php'; ?>
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <div class="mb-2 d-flex flex-column flex-md-row gap-2 align-items-start align-items-md-center">
        <h2 class="mt-2 mt-md-3 mb-0">Quizzes — Gamificação</h2>
        <ol class="breadcrumb mb-0 mt-1 ms-md-auto small">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item">Gamificação</li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-center gap-2">
            <span><i class="fas fa-question-circle me-2"></i>Quizzes</span>
            <div>
                <?php if (in_array('CreateGamificationQuiz', $this->data['buttonPermission'] ?? [], true)) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>create-gamification-quiz" class="btn btn-sm btn-success">
                        <i class="fas fa-plus me-1"></i>Novo quiz
                    </a>
                <?php } ?>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>Título</th>
                        <th>Slug</th>
                        <th>Estado</th>
                        <th>Questões</th>
                        <th class="text-end">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($this->data['quizzes'] ?? [] as $q): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars((string)($q['title'] ?? '')) ?></strong></td>
                            <td><code><?= htmlspecialchars((string)($q['slug'] ?? '')) ?></code></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars((string)($q['status'] ?? '')) ?></span></td>
                            <td><?= (int)($q['questions_count'] ?? 0) ?></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <?php if (in_array('ListGamificationQuizQuestions', $this->data['buttonPermission'] ?? [], true)) { ?>
                                        <a class="btn btn-outline-primary" title="Questões"
                                           href="<?php echo $_ENV['URL_ADM']; ?>list-gamification-quiz-questions/<?= (int)$q['id'] ?>"><i class="fas fa-list"></i></a>
                                    <?php } ?>
                                    <?php if (in_array('UpdateGamificationQuiz', $this->data['buttonPermission'] ?? [], true)) { ?>
                                        <a class="btn btn-warning" title="Editar"
                                           href="<?php echo $_ENV['URL_ADM']; ?>update-gamification-quiz/<?= (int)$q['id'] ?>"><i class="fas fa-edit"></i></a>
                                    <?php } ?>
                                    <?php if (in_array('DeleteGamificationQuiz', $this->data['buttonPermission'] ?? [], true)) { ?>
                                        <button type="button" class="btn btn-danger" title="Excluir"
                                                onclick="confirmDelQuiz(<?= (int)$q['id'] ?>, <?= json_encode((string)($q['title'] ?? ''), JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>)"><i class="fas fa-trash"></i></button>
                                    <?php } ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="delQuizModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="delQuizForm" method="post">
                <input type="hidden" name="csrf_token" id="del_quiz_csrf" value="">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Excluir o quiz <strong id="del_quiz_name"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function confirmDelQuiz(id, name) {
    document.getElementById('del_quiz_name').textContent = name;
    document.getElementById('delQuizForm').action = '<?php echo $_ENV['URL_ADM']; ?>delete-gamification-quiz/' + id;
    document.getElementById('del_quiz_csrf').value = '<?php echo \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_delete_gamification_quiz'); ?>';
    new bootstrap.Modal(document.getElementById('delQuizModal')).show();
}
</script>
