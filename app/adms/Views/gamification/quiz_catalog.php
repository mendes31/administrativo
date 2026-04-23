<?php include __DIR__ . '/partials/module_head.php'; ?>
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <h2 class="mt-3">Quizzes disponíveis</h2>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="row g-3">
        <?php foreach ($this->data['quizzes'] ?? [] as $q): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-light shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?= htmlspecialchars((string)($q['title'] ?? '')) ?></h5>
                        <p class="card-text small text-muted flex-grow-1"><?= nl2br(htmlspecialchars((string)($q['summary'] ?? ''))) ?></p>
                        <?php if (in_array('TakeGamificationQuiz', $this->data['buttonPermission'] ?? [], true)) { ?>
                            <a href="<?php echo $_ENV['URL_ADM']; ?>take-gamification-quiz/<?= (int)$q['id'] ?>" class="btn btn-primary btn-sm mt-2">Responder</a>
                        <?php } ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if (($this->data['quizzes'] ?? []) === []): ?>
        <div class="alert alert-info mt-3">Nenhum quiz publicado no momento.</div>
    <?php endif; ?>
</div>
