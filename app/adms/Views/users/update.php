<?php

use App\adms\Helpers\CSRFHelper;

?>

<div class="container-fluid px-2 px-md-4 user-edit-page">

    <div class="mb-1 d-flex flex-column flex-sm-row gap-1 gap-sm-2">
        <h2 class="mt-2 mt-sm-3 mb-1 h3">Usuários</h2>

        <ol class="breadcrumb mb-2 mb-sm-3 mt-0 mt-sm-3 ms-sm-auto small">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>list-users" class="text-decoration-none">Usuários</a></li>
            <li class="breadcrumb-item">Editar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <div class="d-flex flex-wrap align-items-center gap-2 justify-content-between">
                <span class="fw-semibold">Editar</span>
                <div class="d-flex flex-wrap gap-1 justify-content-end">
                <?php
                    $id = ($this->data['form']['id'] ?? '');
                    if (in_array('ListUsers', $this->data['buttonPermission'] ?? [], true)) {
                        echo "<a href='{$_ENV['URL_ADM']}list-users' class='btn btn-info btn-sm' title='Listar'><i class='fa-solid fa-list-ul'></i><span class='d-none d-md-inline'> Listar</span></a> ";
                    }
                    if (in_array('ViewUser', $this->data['buttonPermission'] ?? [], true) && $id !== '') {
                        echo "<a href='{$_ENV['URL_ADM']}view-user/$id' class='btn btn-primary btn-sm' title='Visualizar'><i class='fa-regular fa-eye'></i><span class='d-none d-md-inline'> Visualizar</span></a> ";
                    }
                ?>
                </div>
            </div>
        </div>

        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <form action="" method="POST" class="row g-3" enctype="multipart/form-data" id="formUpdateUser" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_update_user'); ?>">
                <input type="hidden" name="id" id="id" value="<?php echo htmlspecialchars((string)($this->data['form']['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

                <?php
                $userFormMode = 'update';
                include __DIR__ . '/partials/form_tabs.php';
                ?>

                <div class="col-12 mt-3" id="btnSaveUserFormWrap">
                    <button type="submit" class="btn btn-warning btn-sm" id="btnSaveUserForm">Salvar</button>
                </div>
            </form>

            <?php
            // Formulários de revogação ficam fora do formulário principal (HTML não permite form aninhado).
            $tiAcessosRevoke = is_array($this->data['ti_acessos'] ?? null) ? $this->data['ti_acessos'] : [];
            $userIdRevoke = (int) ($this->data['form']['id'] ?? 0);
            if ($tiAcessosRevoke !== [] && $userIdRevoke > 0 && in_array('TiAcessosRevoke', $this->data['buttonPermission'] ?? [], true)):
                $csrfTiRevoke = CSRFHelper::generateCSRFToken('form_ti_acesso_revoke');
                $urlAdmRevoke = (string) ($_ENV['URL_ADM'] ?? '');
                foreach ($tiAcessosRevoke as $acessoRevoke):
                    if (($acessoRevoke['status'] ?? '') !== 'ativo') {
                        continue;
                    }
            ?>
                <form id="formTiAcessoRevoke<?php echo (int) $acessoRevoke['id']; ?>" method="POST" class="d-none"
                      action="<?php echo htmlspecialchars($urlAdmRevoke . 'ti-acessos-revoke/' . (int) $acessoRevoke['id'], ENT_QUOTES, 'UTF-8'); ?>"
                      onsubmit="return confirm('Confirmar que a conta foi inativada neste sistema?');">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfTiRevoke, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($urlAdmRevoke . 'update-user/' . $userIdRevoke . '?tab=acessos', ENT_QUOTES, 'UTF-8'); ?>">
                </form>
            <?php endforeach; endif; ?>

            <?php if (in_array('UpdateUserAccessLevels', $this->data['buttonPermission'] ?? [], true) && (int) ($this->data['form']['id'] ?? 0) > 0): ?>
                <form id="formUserAccessLevels" action="<?php echo htmlspecialchars((string) ($_ENV['URL_ADM'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>update-user-access-levels" method="POST" class="d-none">
                    <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_update_access_level'); ?>">
                    <input type="hidden" name="adms_user_id" value="<?php echo (int) $this->data['form']['id']; ?>">
                    <input type="hidden" name="return_to" value="<?php echo htmlspecialchars((string) (($_ENV['URL_ADM'] ?? '') . 'update-user/' . (int) $this->data['form']['id'] . '?tab=permissoes'), ENT_QUOTES, 'UTF-8'); ?>">
                </form>
            <?php endif; ?>
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

document.addEventListener('DOMContentLoaded', function() {
    const dataDesligamentoInput = document.getElementById('data_desligamento');
    const motivoContainer = document.getElementById('motivo_desligamento_container');
    const motivoInput = document.getElementById('motivo_desligamento');
    const tipoImpactoSelect = document.getElementById('tipo_impacto_desligamento');
    const dataAdmissaoInput = document.getElementById('data_admissao');
    const statusCheckbox = document.getElementById('status');
    const clearTipoImpacto = function () { if (tipoImpactoSelect) tipoImpactoSelect.value = ''; };

    if (dataDesligamentoInput && motivoContainer) {
        if (dataDesligamentoInput.value) {
            motivoContainer.style.display = 'block';
            if (statusCheckbox && statusCheckbox.checked) {
                statusCheckbox.checked = false;
            }
        }

        dataDesligamentoInput.addEventListener('change', function(e) {
            if (e.target.value) {
                motivoContainer.style.display = 'block';
                if (statusCheckbox) {
                    statusCheckbox.checked = false;
                }
            } else {
                motivoContainer.style.display = 'none';
                if (motivoInput) {
                    motivoInput.value = '';
                }
                clearTipoImpacto();
                if (statusCheckbox && !statusCheckbox.checked) {
                    if (confirm('Este colaborador está sendo recontratado? O status será alterado para Ativo.')) {
                        statusCheckbox.checked = true;
                    }
                }
            }
        });
    }

    if (dataAdmissaoInput && dataDesligamentoInput) {
        dataAdmissaoInput.addEventListener('change', function(e) {
            if (dataDesligamentoInput.value && e.target.value) {
                const dataAdmissao = new Date(e.target.value);
                const dataDesligamento = new Date(dataDesligamentoInput.value);
                if (dataAdmissao > dataDesligamento) {
                    if (confirm('A data de admissão é posterior à data de desligamento. Deseja limpar a data de desligamento (recontratação)?')) {
                        dataDesligamentoInput.value = '';
                        if (motivoContainer) motivoContainer.style.display = 'none';
                        if (motivoInput) motivoInput.value = '';
                        clearTipoImpacto();
                        if (statusCheckbox) statusCheckbox.checked = true;
                    }
                }
            }
        });
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
