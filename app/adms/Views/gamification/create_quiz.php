<?php include __DIR__ . '/partials/module_head.php'; ?>
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <h2 class="mt-3">Novo quiz</h2>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card border-light shadow mb-4">
        <div class="card-body">
            <form method="post" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_create_gamification_quiz'); ?>">
                <div class="col-md-8">
                    <label class="form-label">Título</label>
                    <input type="text" name="title" class="form-control" required maxlength="191">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Slug (URL)</label>
                    <input type="text" name="slug" class="form-control" required pattern="[a-z0-9_-]+" placeholder="ex.: seguranca-2026">
                </div>
                <div class="col-12">
                    <label class="form-label">Resumo</label>
                    <textarea name="summary" class="form-control" rows="2"></textarea>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select name="status" class="form-select">
                        <option value="draft">Rascunho</option>
                        <option value="published">Publicado</option>
                        <option value="archived">Arquivado</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">% mínimo para aprovação</label>
                    <input type="number" name="passing_percent" class="form-control" min="0" max="100" placeholder="vazio = qualquer">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Máx. tentativas</label>
                    <input type="number" name="max_attempts" class="form-control" min="1" max="50" value="1">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Pontos ao concluir (aprovado)</label>
                    <input type="number" name="points_on_completion" class="form-control" min="0" value="0">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Disponível a partir de</label>
                    <input type="datetime-local" name="available_from" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Disponível até</label>
                    <input type="datetime-local" name="available_until" class="form-control">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Criar</button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-gamification-quizzes" class="btn btn-secondary">Voltar</a>
                </div>
            </form>
        </div>
    </div>
</div>
