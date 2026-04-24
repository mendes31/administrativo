<?php include __DIR__ . '/partials/module_head.php'; ?>
<style>
    @media (max-width: 767.98px) {
        .gami-quiz-title { font-size: 1.05rem; line-height: 1.2; }
        .gami-quiz-summary { font-size: .84rem; line-height: 1.35; }
        .gami-quiz-card .card-body { padding: .8rem; }
        .gami-quiz-cta { width: 100%; }
    }
</style>
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-2 mt-3 mb-2">
        <h2 class="mb-0">Quizzes disponíveis</h2>
        <?php if (in_array('GamificationLeaderboard', $this->data['menuPermission'] ?? [], true)) { ?>
            <a href="<?php echo $_ENV['URL_ADM']; ?>gamification-leaderboard" class="btn btn-outline-warning btn-sm ms-md-auto">
                <i class="fas fa-trophy me-1"></i>Ver ranking
            </a>
        <?php } ?>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="row g-3">
        <?php foreach ($this->data['quizzes'] ?? [] as $q): ?>
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="card h-100 border-light shadow-sm gami-quiz-card">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title gami-quiz-title"><?= htmlspecialchars((string)($q['title'] ?? '')) ?></h5>
                        <p class="card-text small text-muted flex-grow-1 gami-quiz-summary"><?= nl2br(htmlspecialchars((string)($q['summary'] ?? ''))) ?></p>
                        <?php if (in_array('TakeGamificationQuiz', $this->data['buttonPermission'] ?? [], true)) { ?>
                            <a href="<?php echo $_ENV['URL_ADM']; ?>take-gamification-quiz/<?= (int)$q['id'] ?>" class="btn btn-primary btn-sm mt-2 gami-quiz-cta">Responder</a>
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
