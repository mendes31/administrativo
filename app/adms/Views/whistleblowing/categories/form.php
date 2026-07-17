<?php
$category = $this->data['category'] ?? null;
$isEdit = $category !== null;
$action = $isEdit
    ? $_ENV['URL_ADM'] . 'update-whistleblowing-category/' . (int)$category['id']
    : $_ENV['URL_ADM'] . 'create-whistleblowing-category';
?>

<div class="container-fluid px-4">
    <h2 class="mt-3 mobile-hide-page-title"><?= $isEdit ? 'Editar classificação' : 'Nova classificação' ?></h2>

    <div class="card border-light shadow">
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <?php if ($isEdit && (int)($this->data['reports_count'] ?? 0) > 0): ?>
                <div class="alert alert-info small">
                    Esta classificação está vinculada a <?= (int)$this->data['reports_count'] ?> denúncia(s).
                    Ao renomear, os registros existentes serão atualizados automaticamente.
                </div>
            <?php endif; ?>

            <form method="post" action="<?= htmlspecialchars($action) ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($this->data['csrf_token'] ?? '')) ?>">

                <div class="mb-3">
                    <label class="form-label">Nome *</label>
                    <input type="text" name="name" class="form-control" required maxlength="120"
                        value="<?= htmlspecialchars((string)($category['name'] ?? '')) ?>"
                        placeholder="Ex.: Assédio Moral">
                </div>

                <div class="mb-3">
                    <label class="form-label">Descrição (opcional)</label>
                    <textarea name="description" class="form-control" rows="2"
                        placeholder="Texto interno para orientar o comitê..."><?= htmlspecialchars((string)($category['description'] ?? '')) ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Ordem de exibição</label>
                    <input type="number" name="sort_order" class="form-control" min="0" step="1"
                        value="<?= (int)($category['sort_order'] ?? 0) ?>">
                    <div class="form-text">Menor número aparece primeiro no canal público.</div>
                </div>

                <?php if ($isEdit): ?>
                <div class="mb-3">
                    <label class="form-label">SLA 1ª resposta (horas)</label>
                    <input type="number" name="sla_first_response_hours" class="form-control" min="1" max="720" step="1"
                        value="<?= htmlspecialchars((string)($category['sla_first_response_hours'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Vazio = usar risco/global">
                    <div class="form-text">Opcional. Sobrescreve o SLA por risco e o padrão global.</div>
                </div>
                <?php endif; ?>

                <?php if ($isEdit): ?>
                <div class="mb-3 form-check">
                    <input type="checkbox" name="is_active" class="form-check-input" id="is_active"
                        <?= !empty($category['is_active']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">Classificação ativa no canal público</label>
                </div>
                <?php endif; ?>

                <div class="d-grid d-sm-flex gap-2">
                    <button type="submit" class="btn btn-primary">Salvar</button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-whistleblowing-categories" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
@media (max-width: 767.98px) {
    .container-fluid.px-4 { padding-left: .75rem !important; padding-right: .75rem !important; }
}
</style>
