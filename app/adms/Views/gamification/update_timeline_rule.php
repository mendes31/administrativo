<?php
$r = $this->data['rule'] ?? [];
include __DIR__ . '/partials/module_head.php';
?>
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <div class="mb-2 d-flex flex-column flex-md-row gap-2 align-items-start align-items-md-center">
        <h2 class="mt-2 mt-md-3 mb-0">Editar regra</h2>
        <ol class="breadcrumb mb-0 mt-1 ms-md-auto small">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>list-gamification-timeline-rules" class="text-decoration-none">Regras</a></li>
            <li class="breadcrumb-item">Editar</li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card border-light shadow mb-4">
        <div class="card-body">
            <form method="post" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_update_gamification_timeline_rule'); ?>">
                <div class="col-12">
                    <label class="form-label">Chave do evento</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars((string)($r['event_key'] ?? '')) ?>" disabled>
                    <div class="form-text">Identificador fixo usado pelo sistema ao registar pontos.</div>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Título</label>
                    <input type="text" name="title" class="form-control" required maxlength="191"
                           value="<?= htmlspecialchars((string)($r['title'] ?? '')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Pontos por ocorrência</label>
                    <input type="number" name="points" class="form-control" min="0" max="999999" required
                           value="<?= (int)($r['points'] ?? 0) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Descrição</label>
                    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars((string)($r['description'] ?? '')) ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Máx. por utilizador / dia</label>
                    <input type="number" name="max_awards_per_user_per_day" class="form-control" min="0"
                           value="<?= $r['max_awards_per_user_per_day'] !== null ? (int)$r['max_awards_per_user_per_day'] : '' ?>"
                           placeholder="vazio = ilimitado">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Máx. por utilizador (total)</label>
                    <input type="number" name="max_awards_per_user_total" class="form-control" min="0"
                           value="<?= $r['max_awards_per_user_total'] !== null ? (int)$r['max_awards_per_user_total'] : '' ?>"
                           placeholder="vazio = ilimitado">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                            <?= !empty($r['is_active']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Regra ativa</label>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Guardar</button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-gamification-timeline-rules" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
