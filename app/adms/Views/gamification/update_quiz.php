<?php
$q = $this->data['quiz'] ?? [];
include __DIR__ . '/partials/module_head.php';
?>
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <h2 class="mt-3">Editar quiz</h2>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card border-light shadow mb-4">
        <div class="card-body">
            <form method="post" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_update_gamification_quiz'); ?>">
                <div class="col-md-8">
                    <label class="form-label">Título</label>
                    <input type="text" name="title" class="form-control" required maxlength="191"
                           value="<?= htmlspecialchars((string)($q['title'] ?? '')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" class="form-control" required pattern="[a-z0-9_-]+"
                           value="<?= htmlspecialchars((string)($q['slug'] ?? '')) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Resumo</label>
                    <textarea name="summary" class="form-control" rows="2"><?= htmlspecialchars((string)($q['summary'] ?? '')) ?></textarea>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <?php $st = (string)($q['status'] ?? 'draft'); ?>
                    <select name="status" class="form-select">
                        <option value="draft" <?= $st === 'draft' ? 'selected' : '' ?>>Rascunho</option>
                        <option value="published" <?= $st === 'published' ? 'selected' : '' ?>>Publicado</option>
                        <option value="archived" <?= $st === 'archived' ? 'selected' : '' ?>>Arquivado</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">% aprovação</label>
                    <input type="number" name="passing_percent" class="form-control" min="0" max="100"
                           value="<?= $q['passing_percent'] !== null && $q['passing_percent'] !== '' ? (int)$q['passing_percent'] : '' ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Máx. tentativas</label>
                    <input type="number" name="max_attempts" class="form-control" min="1" max="50"
                           value="<?= (int)($q['max_attempts'] ?? 1) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Pontos ao concluir</label>
                    <input type="number" name="points_on_completion" class="form-control" min="0"
                           value="<?= (int)($q['points_on_completion'] ?? 0) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Disponível a partir de</label>
                    <input type="datetime-local" name="available_from" class="form-control"
                           value="<?= !empty($q['available_from']) ? date('Y-m-d\TH:i', strtotime((string)$q['available_from'])) : '' ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Disponível até</label>
                    <input type="datetime-local" name="available_until" class="form-control"
                           value="<?= !empty($q['available_until']) ? date('Y-m-d\TH:i', strtotime((string)$q['available_until'])) : '' ?>">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-gamification-quiz-questions/<?= (int)($q['id'] ?? 0) ?>" class="btn btn-outline-primary">Questões</a>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-gamification-quizzes" class="btn btn-secondary">Voltar</a>
                </div>
            </form>
        </div>
    </div>
</div>
