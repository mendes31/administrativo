<?php
$committee = $this->data['committee'] ?? null;
$isEdit = $committee !== null;
$memberIds = $this->data['member_ids'] ?? [];
$selectedCategories = $this->data['selected_categories'] ?? [];
$action = $isEdit
    ? $_ENV['URL_ADM'] . 'update-whistleblowing-committee/' . (int)$committee['id']
    : $_ENV['URL_ADM'] . 'create-whistleblowing-committee';
?>

<div class="container-fluid px-4">
    <h2 class="mt-3"><?= $isEdit ? 'Editar comitê' : 'Novo comitê' ?></h2>

    <div class="card border-light shadow">
        <div class="card-body">
            <form method="post" action="<?= htmlspecialchars($action) ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($this->data['csrf_token'] ?? '')) ?>">

                <div class="mb-3">
                    <label class="form-label">Nome *</label>
                    <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars((string)($committee['name'] ?? '')) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Descrição</label>
                    <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars((string)($committee['description'] ?? '')) ?></textarea>
                </div>

                <?php if ($isEdit): ?>
                <div class="mb-3 form-check">
                    <input type="checkbox" name="is_active" class="form-check-input" id="is_active" <?= !empty($committee['is_active']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">Comitê ativo</label>
                </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Membros</label>
                    <select name="member_ids[]" class="form-select" multiple size="6">
                        <?php foreach ($this->data['users'] ?? [] as $u): ?>
                            <option value="<?= (int)$u['id'] ?>" <?= in_array((int)$u['id'], $memberIds, true) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)$u['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Segure Ctrl para selecionar vários. O primeiro membro pode ser atribuído como responsável automático.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Classificações atendidas</label>
                    <div class="row">
                        <?php foreach ($this->data['categories'] ?? [] as $cat): ?>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="categories[]" value="<?= htmlspecialchars($cat) ?>"
                                        id="cat_<?= md5($cat) ?>" <?= in_array($cat, $selectedCategories, true) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="cat_<?= md5($cat) ?>"><?= htmlspecialchars($cat) ?></label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Salvar</button>
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-whistleblowing-committees" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>
