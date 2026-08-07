<?php

use App\adms\Helpers\CSRFHelper;

?>

<div class="container-fluid px-2 px-md-4 user-edit-page">

    <div class="mb-1 d-flex flex-column flex-sm-row gap-1 gap-sm-2">
        <h2 class="mt-2 mt-sm-3 mb-1 h3">Usuários</h2>

        <ol class="breadcrumb mb-2 mb-sm-3 mt-0 mt-sm-3 ms-sm-auto small">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>list-users" class="text-decoration-none">Usuários</a></li>
            <li class="breadcrumb-item">Cadastrar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <div class="d-flex flex-wrap align-items-center gap-2 justify-content-between">
                <span class="fw-semibold">Cadastrar</span>
                <div class="d-flex flex-wrap gap-1 justify-content-end">
                <?php
                    if (in_array('ListUsers', $this->data['buttonPermission'] ?? [], true)) {
                        echo "<a href='{$_ENV['URL_ADM']}list-users' class='btn btn-info btn-sm' title='Listar'><i class='fa-solid fa-list-ul'></i><span class='d-none d-md-inline'> Listar</span></a> ";
                    }
                ?>
                </div>
            </div>
        </div>

        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <form action="" method="POST" class="row g-3" enctype="multipart/form-data" id="formCreateUser" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_create_user'); ?>">

                <?php
                $userFormMode = 'create';
                include __DIR__ . '/partials/form_tabs.php';
                ?>

                <div class="col-12 mt-3">
                    <button type="submit" class="btn btn-success">Cadastrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('cpf')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length <= 11) {
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        e.target.value = value;
    }
});

document.getElementById('celular')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length <= 11) {
        value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
        value = value.replace(/(\d)(\d{4})$/, '$1-$2');
        e.target.value = value;
    }
});

document.getElementById('cep')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length <= 8) {
        value = value.replace(/(\d{5})(\d)/, '$1-$2');
        e.target.value = value;
    }
});

</script>
<script src="<?php echo $_ENV['URL_ADM']; ?>public/adms/js/user-form-tabs.js?v=20260807b"></script>
<script src="<?php echo $_ENV['URL_ADM']; ?>public/adms/js/address-cep-lookup.js?v=20260714"></script>
<script>
(function () {
    function syncBoolLabel(input) {
        var label = document.querySelector('label[for="' + input.id + '"]');
        if (!label) return;
        var on = input.getAttribute('data-label-on') || 'Sim';
        var off = input.getAttribute('data-label-off') || 'Não';
        label.textContent = input.checked ? on : off;
    }
    document.querySelectorAll('.js-user-bool-switch').forEach(function (el) {
        syncBoolLabel(el);
        el.addEventListener('change', function () { syncBoolLabel(el); });
    });
})();
</script>
