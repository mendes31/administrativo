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
    <h2 class="mt-3 mobile-hide-page-title"><?= $isEdit ? 'Editar comitê' : 'Novo comitê' ?></h2>

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

                <div class="mb-4">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                        <label class="form-label fw-semibold mb-0">Membros do comitê</label>
                        <span class="badge rounded-pill text-bg-primary" id="committeeMemberCount">0 vinculados</span>
                    </div>

                    <div class="row g-3 committee-members-manager">
                        <div class="col-lg-7">
                            <div class="border rounded-3 p-3 h-100 bg-light">
                                <div class="input-group mb-2">
                                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                                    <input type="search" class="form-control" id="committeeMemberSearch"
                                        placeholder="Buscar por nome ou e-mail" autocomplete="off">
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="text-muted" id="committeeMemberSearchResult"></small>
                                    <button type="button" class="btn btn-link btn-sm text-decoration-none p-0"
                                        id="committeeClearMembers">Desmarcar todos</button>
                                </div>

                                <div class="committee-members-list" id="committeeMembersList">
                                    <?php foreach ($this->data['users'] ?? [] as $u): ?>
                                        <?php
                                        $userId = (int) $u['id'];
                                        $userName = (string) ($u['name'] ?? '');
                                        $userEmail = trim((string) ($u['email'] ?? ''));
                                        $selected = in_array($userId, $memberIds, true);
                                        ?>
                                        <label class="committee-member-option d-flex align-items-center gap-3 p-2 rounded-3"
                                            data-search="<?= htmlspecialchars(mb_strtolower($userName . ' ' . $userEmail, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>">
                                            <input class="form-check-input flex-shrink-0 committee-member-checkbox"
                                                type="checkbox" name="member_ids[]" value="<?= $userId ?>"
                                                data-name="<?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>"
                                                data-email="<?= htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8') ?>"
                                                <?= $selected ? 'checked' : '' ?>>
                                            <span class="committee-member-avatar flex-shrink-0" aria-hidden="true">
                                                <?= htmlspecialchars(mb_strtoupper(mb_substr($userName, 0, 1, 'UTF-8'), 'UTF-8')) ?>
                                            </span>
                                            <span class="min-width-0">
                                                <strong class="d-block text-truncate"><?= htmlspecialchars($userName) ?></strong>
                                                <small class="text-muted d-block text-truncate">
                                                    <?= $userEmail !== '' ? htmlspecialchars($userEmail) : 'Sem e-mail cadastrado' ?>
                                                </small>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <div class="text-center text-muted py-4 d-none" id="committeeMembersEmpty">
                                    Nenhum usuário encontrado.
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="border rounded-3 p-3 h-100">
                                <h6 class="mb-1"><i class="fas fa-user-check me-2 text-success"></i>Membros vinculados</h6>
                                <p class="small text-muted mb-3">Marque pessoas na lista para vinculá-las ao comitê.</p>
                                <div class="committee-selected-members" id="committeeSelectedMembers"></div>
                                <div class="text-center text-muted small py-4" id="committeeSelectedEmpty">
                                    Nenhum membro selecionado.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-text mt-2">
                        Ao salvar, o sistema concede automaticamente o nível secundário
                        <strong><?= htmlspecialchars(\App\adms\Models\Services\WhistleblowingPermissionService::OPERATOR_LEVEL_NAME) ?></strong>.
                        A remoção do último comitê revoga esse nível. Para Configuração e Governança LGPD, atribua manualmente
                        <strong><?= htmlspecialchars(\App\adms\Models\Services\WhistleblowingPermissionService::ADMIN_LEVEL_NAME) ?></strong>.
                    </div>
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

                <div class="d-grid d-sm-flex gap-2">
                    <button type="submit" class="btn btn-primary">Salvar</button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-whistleblowing-committees" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.committee-members-manager .min-width-0 { min-width: 0; }
.committee-members-list { max-height: 360px; overflow-y: auto; }
.committee-member-option { cursor: pointer; transition: background-color .15s ease; }
.committee-member-option:hover { background: #fff; }
.committee-member-option:has(.committee-member-checkbox:checked) {
    background: #e8f4ff;
    box-shadow: inset 3px 0 #0d6efd;
}
.committee-member-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #0d6efd;
    background: #dbeafe;
    font-weight: 700;
}
.committee-selected-members { max-height: 360px; overflow-y: auto; }
.committee-selected-item { background: #f8f9fa; }
@media (max-width: 767.98px) {
    .container-fluid.px-4 { padding-left: 1rem !important; padding-right: 1rem !important; }
    .committee-members-list, .committee-selected-members { max-height: 300px; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const search = document.getElementById('committeeMemberSearch');
    const list = document.getElementById('committeeMembersList');
    const options = Array.from(list?.querySelectorAll('.committee-member-option') || []);
    const checkboxes = Array.from(list?.querySelectorAll('.committee-member-checkbox') || []);
    const selectedList = document.getElementById('committeeSelectedMembers');
    const selectedEmpty = document.getElementById('committeeSelectedEmpty');
    const count = document.getElementById('committeeMemberCount');
    const result = document.getElementById('committeeMemberSearchResult');
    const searchEmpty = document.getElementById('committeeMembersEmpty');

    const escapeHtml = function (value) {
        const element = document.createElement('div');
        element.textContent = value;
        return element.innerHTML;
    };

    const renderSelected = function () {
        const selected = checkboxes.filter(function (checkbox) { return checkbox.checked; });
        count.textContent = selected.length + (selected.length === 1 ? ' vinculado' : ' vinculados');
        selectedEmpty.classList.toggle('d-none', selected.length > 0);
        selectedList.innerHTML = selected.map(function (checkbox) {
            const name = escapeHtml(checkbox.dataset.name || '');
            const email = escapeHtml(checkbox.dataset.email || 'Sem e-mail cadastrado');
            return '<div class="committee-selected-item d-flex align-items-center justify-content-between gap-2 rounded-3 p-2 mb-2">'
                + '<span class="min-width-0"><strong class="d-block text-truncate">' + name + '</strong>'
                + '<small class="text-muted d-block text-truncate">' + email + '</small></span>'
                + '<button type="button" class="btn btn-sm btn-outline-danger flex-shrink-0 committee-remove-member"'
                + ' data-member-id="' + checkbox.value + '" title="Remover do comitê" aria-label="Remover ' + name + '">'
                + '<i class="fas fa-times"></i></button></div>';
        }).join('');
    };

    const filterMembers = function () {
        const query = (search.value || '').trim().toLocaleLowerCase('pt-BR');
        let visible = 0;
        options.forEach(function (option) {
            const matches = query === '' || (option.dataset.search || '').includes(query);
            option.classList.toggle('d-none', !matches);
            if (matches) visible++;
        });
        result.textContent = visible + (visible === 1 ? ' usuário encontrado' : ' usuários encontrados');
        searchEmpty.classList.toggle('d-none', visible > 0);
    };

    checkboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', renderSelected);
    });
    search?.addEventListener('input', filterMembers);
    document.getElementById('committeeClearMembers')?.addEventListener('click', function () {
        checkboxes.forEach(function (checkbox) { checkbox.checked = false; });
        renderSelected();
    });
    selectedList?.addEventListener('click', function (event) {
        const button = event.target.closest('.committee-remove-member');
        if (!button) return;
        const checkbox = checkboxes.find(function (item) { return item.value === button.dataset.memberId; });
        if (checkbox) checkbox.checked = false;
        renderSelected();
    });

    filterMembers();
    renderSelected();
});
</script>
