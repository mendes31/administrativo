<?php

use App\adms\Helpers\CSRFHelper;

$isEdit = !empty($this->data['client']);
$client = $this->data['client'] ?? [];

$csrfToken = CSRFHelper::generateCSRFToken('sac_client_form');

?>

<div class="container-fluid px-4">

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">
            <i class="fas fa-<?php echo $isEdit ? 'edit' : 'user-plus'; ?> me-2"></i><?php echo $isEdit ? 'Editar' : 'Novo'; ?> Cliente SAC
        </h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item">SAC</li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>sac-list-clients">Clientes</a></li>
            <li class="breadcrumb-item active"><?php echo $isEdit ? 'Editar' : 'Novo'; ?></li>
        </ol>
    </div>

    <form method="POST" action="<?php echo $_ENV['URL_ADM']; ?><?php echo $isEdit ? 'sac-update-client/' . $client['id'] : 'sac-create-client'; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?php echo $client['id']; ?>">
        <?php endif; ?>

        <div class="row">
            <!-- Coluna Esquerda -->
            <div class="col-md-8">

                <!-- Card Dados do Cliente -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header"><h5 class="mb-0"><i class="fas fa-user me-2"></i>Dados do Cliente</h5></div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="type_person" class="form-label">Tipo Pessoa *</label>
                                <select name="type_person" id="type_person" class="form-select" required>
                                    <option value="PJ" <?php echo ($client['type_person'] ?? 'PJ') == 'PJ' ? 'selected' : ''; ?>>Pessoa Jurídica</option>
                                    <option value="PF" <?php echo ($client['type_person'] ?? '') == 'PF' ? 'selected' : ''; ?>>Pessoa Física</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label for="razao_social" id="label_razao_social" class="form-label">Razão Social *</label>
                                <input type="text" name="razao_social" id="razao_social" class="form-control" required
                                       value="<?php echo htmlspecialchars($client['razao_social'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6" id="wrapper_nome_fantasia">
                                <label for="nome_fantasia" class="form-label">Nome Fantasia</label>
                                <input type="text" name="nome_fantasia" id="nome_fantasia" class="form-control"
                                       value="<?php echo htmlspecialchars($client['nome_fantasia'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6" id="wrapper_document">
                                <label for="document" id="label_document" class="form-label">CPF/CNPJ</label>
                                <input type="text" name="document" id="document" class="form-control"
                                       value="<?php echo htmlspecialchars($client['document'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="contact_name" class="form-label">Nome do Contato</label>
                                <input type="text" name="contact_name" id="contact_name" class="form-control"
                                       value="<?php echo htmlspecialchars($client['contact_name'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Endereco -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header"><h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Endereco</h5></div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="zip_code" class="form-label">CEP</label>
                                <div class="input-group">
                                    <input type="text" name="zip_code" id="zip_code" class="form-control"
                                           placeholder="00000-000"
                                           value="<?php echo htmlspecialchars($client['zip_code'] ?? ''); ?>">
                                    <button type="button" class="btn btn-success" id="btn-search-cep" title="Buscar CEP">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                                <div class="form-text" id="cep-hint">
                                    <i class="fas fa-lightbulb text-warning"></i>
                                    Digite o CEP para auto-completar
                                </div>
                                <div id="cep-loading" class="text-primary mt-1" style="display: none;">
                                    <i class="fas fa-spinner fa-spin"></i> Buscando CEP...
                                </div>
                                <div id="cep-success" class="text-success mt-1" style="display: none;">
                                    <i class="fas fa-check-circle"></i> CEP encontrado!
                                </div>
                                <div id="cep-error" class="text-danger mt-1" style="display: none;"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="address" class="form-label">Endereco</label>
                                <input type="text" name="address" id="address" class="form-control"
                                       value="<?php echo htmlspecialchars($client['address'] ?? ''); ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="number" class="form-label">Numero</label>
                                <input type="text" name="number" id="number" class="form-control"
                                       value="<?php echo htmlspecialchars($client['number'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="complement" class="form-label">Complemento</label>
                                <input type="text" name="complement" id="complement" class="form-control"
                                       value="<?php echo htmlspecialchars($client['complement'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="neighborhood" class="form-label">Bairro</label>
                                <input type="text" name="neighborhood" id="neighborhood" class="form-control"
                                       value="<?php echo htmlspecialchars($client['neighborhood'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="city" class="form-label">Cidade</label>
                                <input type="text" name="city" id="city" class="form-control"
                                       value="<?php echo htmlspecialchars($client['city'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="state" class="form-label">Estado</label>
                                <select name="state" id="state" class="form-select">
                                    <option value="">Selecione...</option>
                                    <?php
                                    $ufs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
                                    foreach ($ufs as $uf):
                                    ?>
                                        <option value="<?php echo $uf; ?>" <?php echo ($client['state'] ?? '') == $uf ? 'selected' : ''; ?>>
                                            <?php echo $uf; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Coluna Direita -->
            <div class="col-md-4">

                <!-- Card Contato -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header"><h5 class="mb-0"><i class="fas fa-phone me-2"></i>Contato</h5></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="phone" class="form-label">Telefone</label>
                            <input type="text" name="phone" id="phone" class="form-control"
                                   placeholder="(00) 0000-0000"
                                   value="<?php echo htmlspecialchars($client['phone'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="mobile" class="form-label">Celular</label>
                            <input type="text" name="mobile" id="mobile" class="form-control"
                                   placeholder="(00) 00000-0000"
                                   value="<?php echo htmlspecialchars($client['mobile'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">E-mail</label>
                            <input type="email" name="email" id="email" class="form-control"
                                   value="<?php echo htmlspecialchars($client['email'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <!-- Card Classificacao -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header"><h5 class="mb-0"><i class="fas fa-tags me-2"></i>Classificacao</h5></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="segment" class="form-label">Segmento</label>
                            <input type="text" name="segment" id="segment" class="form-control"
                                   value="<?php echo htmlspecialchars($client['segment'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="Ativo" <?php echo ($client['status'] ?? 'Ativo') == 'Ativo' ? 'selected' : ''; ?>>Ativo</option>
                                <option value="Inativo" <?php echo ($client['status'] ?? '') == 'Inativo' ? 'selected' : ''; ?>>Inativo</option>
                                <option value="Bloqueado" <?php echo ($client['status'] ?? '') == 'Bloqueado' ? 'selected' : ''; ?>>Bloqueado</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Card Observacoes -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header"><h5 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Observacoes</h5></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notas</label>
                            <textarea name="notes" id="notes" class="form-control" rows="4"><?php echo htmlspecialchars($client['notes'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Botoes -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-save me-2"></i>Salvar
                    </button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>sac-list-clients" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typePerson = document.getElementById('type_person');
    const documentInput = document.getElementById('document');
    const phoneInput = document.getElementById('phone');
    const mobileInput = document.getElementById('mobile');
    const zipCodeInput = document.getElementById('zip_code');

    function maskCPF(value) {
        value = value.replace(/\D/g, '');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        return value.substring(0, 14);
    }

    function maskCNPJ(value) {
        value = value.replace(/\D/g, '');
        value = value.replace(/(\d{2})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1/$2');
        value = value.replace(/(\d{4})(\d{1,2})$/, '$1-$2');
        return value.substring(0, 18);
    }

    function maskPhone(value) {
        value = value.replace(/\D/g, '');
        if (value.length <= 10) {
            value = value.replace(/(\d{2})(\d)/, '($1) $2');
            value = value.replace(/(\d{4})(\d{1,4})$/, '$1-$2');
        } else {
            value = value.replace(/(\d{2})(\d)/, '($1) $2');
            value = value.replace(/(\d{5})(\d{1,4})$/, '$1-$2');
        }
        return value.substring(0, 15);
    }

    function maskCEP(value) {
        value = value.replace(/\D/g, '');
        if (value.length > 5) {
            value = value.replace(/(\d{5})(\d)/, '$1-$2');
        }
        return value.substring(0, 9);
    }

    const labelRazao = document.getElementById('label_razao_social');
    const labelDoc = document.getElementById('label_document');
    const wrapperFantasia = document.getElementById('wrapper_nome_fantasia');
    const wrapperDoc = document.getElementById('wrapper_document');

    function applyTypePersonLayout() {
        const type = typePerson ? typePerson.value : 'PJ';
        if (type === 'PF') {
            if (labelRazao) labelRazao.textContent = 'Nome Completo *';
            if (labelDoc) labelDoc.textContent = 'CPF';
            if (wrapperFantasia) wrapperFantasia.style.display = 'none';
            if (wrapperDoc) wrapperDoc.className = 'col-md-12';
            if (documentInput) {
                documentInput.value = maskCPF(documentInput.value);
                documentInput.placeholder = '000.000.000-00';
                documentInput.maxLength = 14;
            }
        } else {
            if (labelRazao) labelRazao.textContent = 'Razão Social *';
            if (labelDoc) labelDoc.textContent = 'CNPJ';
            if (wrapperFantasia) wrapperFantasia.style.display = '';
            if (wrapperDoc) wrapperDoc.className = 'col-md-6';
            if (documentInput) {
                documentInput.value = maskCNPJ(documentInput.value);
                documentInput.placeholder = '00.000.000/0000-00';
                documentInput.maxLength = 18;
            }
        }
    }

    if (typePerson) {
        applyTypePersonLayout();
        typePerson.addEventListener('change', function() {
            if (documentInput) documentInput.value = '';
            applyTypePersonLayout();
        });
    }
    if (documentInput) {
        documentInput.addEventListener('input', function() {
            applyTypePersonLayout();
        });
    }

    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            e.target.value = maskPhone(e.target.value);
        });
    }

    if (mobileInput) {
        mobileInput.addEventListener('input', function(e) {
            e.target.value = maskPhone(e.target.value);
        });
    }

    if (zipCodeInput) {
        zipCodeInput.addEventListener('input', function(e) {
            e.target.value = maskCEP(e.target.value);
        });
    }

    // Busca CEP via ViaCEP
    document.getElementById('zip_code')?.addEventListener('blur', function() {
        const cep = this.value.replace(/\D/g, '');
        if (cep.length === 8) {
            fetch('https://viacep.com.br/ws/' + cep + '/json/')
                .then(r => r.json())
                .then(data => {
                    if (!data.erro) {
                        document.getElementById('address').value = data.logradouro || '';
                        document.getElementById('neighborhood').value = data.bairro || '';
                        document.getElementById('city').value = data.localidade || '';
                        document.getElementById('state').value = data.uf || '';
                    }
                });
        }
    });

    // Botao buscar CEP
    document.getElementById('btn-search-cep')?.addEventListener('click', function() {
        const cepInput = document.getElementById('zip_code');
        const cep = cepInput.value.replace(/\D/g, '');
        const cepLoading = document.getElementById('cep-loading');
        const cepSuccess = document.getElementById('cep-success');
        const cepError = document.getElementById('cep-error');

        cepError.style.display = 'none';
        cepSuccess.style.display = 'none';

        if (cep.length !== 8) {
            cepError.style.display = 'block';
            cepError.innerHTML = '<i class="fas fa-exclamation-circle"></i> CEP deve ter 8 digitos.';
            return;
        }

        cepLoading.style.display = 'block';

        fetch('https://viacep.com.br/ws/' + cep + '/json/')
            .then(r => r.json())
            .then(data => {
                cepLoading.style.display = 'none';
                if (data.erro) {
                    cepError.style.display = 'block';
                    cepError.innerHTML = '<i class="fas fa-exclamation-circle"></i> CEP nao encontrado.';
                    return;
                }
                document.getElementById('address').value = data.logradouro || '';
                document.getElementById('neighborhood').value = data.bairro || '';
                document.getElementById('city').value = data.localidade || '';
                document.getElementById('state').value = data.uf || '';
                document.getElementById('number')?.focus();
                cepSuccess.style.display = 'block';
                setTimeout(() => { cepSuccess.style.display = 'none'; }, 3000);
            })
            .catch(() => {
                cepLoading.style.display = 'none';
                cepError.style.display = 'block';
                cepError.innerHTML = '<i class="fas fa-exclamation-circle"></i> Erro ao buscar CEP. Verifique sua conexao.';
            });
    });
});
</script>
