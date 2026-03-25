<?php
$csrf = \App\adms\Helpers\CSRFHelper::generateCSRFToken('company_event_create');
$deps = $this->data['departments'] ?? [];
?>
<div class="container-fluid px-4">
    <h2 class="mt-3">Novo evento corporativo</h2>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <form method="post" class="row g-3 mb-5" style="max-width: 720px;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
        <div class="col-12">
            <label class="form-label">Título *</label>
            <input type="text" name="title" class="form-control" required maxlength="255">
        </div>
        <div class="col-12">
            <label class="form-label">Descrição</label>
            <textarea name="description" class="form-control" rows="3"></textarea>
        </div>
        <div class="col-12">
            <label class="form-label">Local</label>
            <input type="text" name="location" class="form-control" maxlength="255">
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Início *</label>
            <input type="datetime-local" name="starts_at" class="form-control" required>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Fim *</label>
            <input type="datetime-local" name="ends_at" class="form-control" required>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Publicar a partir de</label>
            <input type="datetime-local" name="publish_at" class="form-control">
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Expirar visibilidade em</label>
            <input type="datetime-local" name="expire_at" class="form-control">
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Prazo para confirmação (RSVP)</label>
            <input type="datetime-local" name="rsvp_deadline" class="form-control">
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Prazo para cancelamento</label>
            <input type="datetime-local" name="cancellation_deadline" class="form-control">
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Departamento (opcional)</label>
            <select name="department_id" class="form-select">
                <option value="">Todos</option>
                <?php foreach ($deps as $d): ?>
                    <option value="<?php echo (int)$d['id']; ?>"><?php echo htmlspecialchars($d['name'] ?? ''); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Máx. convidados por colaborador</label>
            <input type="number" name="max_guests_per_user" class="form-control" min="0" value="0">
        </div>
        <div class="col-12">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="requires_rsvp" id="requires_rsvp">
                <label class="form-check-label" for="requires_rsvp">Exige confirmação de presença</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="allows_guests" id="allows_guests">
                <label class="form-check-label" for="allows_guests">Permite convidados / familiares (dados no RSVP)</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="ativo" id="ativo" checked>
                <label class="form-check-label" for="ativo">Ativo</label>
            </div>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-success">Salvar</button>
            <a href="<?php echo $_ENV['URL_ADM']; ?>list-company-events" class="btn btn-secondary">Voltar</a>
        </div>
    </form>
</div>
