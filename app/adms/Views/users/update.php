<?php

use App\adms\Helpers\CSRFHelper;

?>

<div class="container-fluid px-4">

    <div class="mb-1 d-flex flex-column flex-sm-row gap-2">
        <h2 class="mt-3">Usuários</h2>

        <ol class="breadcrumb  mb-3 mt-0 mt-sm-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>list-users" class="text-decoration-none">Usuários</a></li>
            <li class="breadcrumb-item">Editar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Editar</span>
            <span class="ms-auto d-sm-flex flex-row">
            <?php
                if (in_array('ListUsers', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}list-users' class='btn btn-primary btn-sm me-1 mb-1'><i class='fa-solid fa-list-ul'></i> Listar</a> ";
                }
                $id = ($this->data['form']['id'] ?? '');
                if (in_array('ViewUser', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}view-user/$id' class='btn btn-primary btn-sm me-1 mb-1'><i class='fa-regular fa-eye'></i> Visualizar</a> ";
                }
            ?>
            </span>
        </div>

        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <form action="" method="POST" class="row g-3" enctype="multipart/form-data" id="formUpdateUser">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_update_user'); ?>">
                <input type="hidden" name="id" id="id" value="<?php echo htmlspecialchars((string)($this->data['form']['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

                <?php
                $userFormMode = 'update';
                include __DIR__ . '/partials/form_tabs.php';
                ?>

                <div class="col-12 mt-3">
                    <button type="submit" class="btn btn-warning btn-sm">Salvar</button>
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

document.querySelectorAll('#userFormTabs [data-tab-key]').forEach(function (btn) {
    btn.addEventListener('shown.bs.tab', function () {
        var key = btn.getAttribute('data-tab-key');
        var hidden = document.getElementById('user_form_active_tab');
        if (hidden && key) {
            hidden.value = key;
        }
    });
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
<script src="<?php echo $_ENV['URL_ADM']; ?>public/adms/js/address-cep-lookup.js?v=20260714"></script>
