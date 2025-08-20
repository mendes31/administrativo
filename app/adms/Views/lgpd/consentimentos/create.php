<?php
use App\adms\Helpers\CSRFHelper;
?>
<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">
            <i class="fas fa-plus text-success"></i>
            Cadastrar Consentimento
        </h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-dashboard" class="text-decoration-none">LGPD</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-consentimentos" class="text-decoration-none">Consentimentos</a>
            </li>
            <li class="breadcrumb-item">Cadastrar</li>
        </ol>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-handshake"></i>
                Novo Consentimento LGPD
            </h5>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <form action="" method="POST" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_consentimento'); ?>">

                <!-- Informações do Titular -->
                <div class="col-12">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">Informações do Titular</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="titular_nome" class="form-label">
                                        Nome do Titular <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="titular_nome" 
                                           name="titular_nome" 
                                           value="<?php echo htmlspecialchars($this->data['form']['titular_nome'] ?? ''); ?>"
                                           required>
                                </div>
                                <div class="col-md-6">
                                    <label for="titular_email" class="form-label">
                                        E-mail do Titular
                                    </label>
                                    <input type="email" 
                                           class="form-control" 
                                           id="titular_email" 
                                           name="titular_email" 
                                           value="<?php echo htmlspecialchars($this->data['form']['titular_email'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detalhes do Consentimento -->
                <div class="col-12">
                    <div class="card border-info">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">Detalhes do Consentimento</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <label for="finalidade" class="form-label">
                                        Finalidade do Consentimento <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control" 
                                              id="finalidade" 
                                              name="finalidade" 
                                              rows="3" 
                                              required><?php echo htmlspecialchars($this->data['form']['finalidade'] ?? ''); ?></textarea>
                                    <div class="form-text">
                                        Descreva claramente para que finalidade o consentimento está sendo solicitado.
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="canal" class="form-label">
                                        Canal de Coleta
                                    </label>
                                    <select class="form-select" id="canal" name="canal">
                                        <option value="">Selecione</option>
                                        <option value="Site" <?php echo (isset($this->data['form']['canal']) && $this->data['form']['canal'] === 'Site') ? 'selected' : ''; ?>>Site</option>
                                        <option value="Aplicativo" <?php echo (isset($this->data['form']['canal']) && $this->data['form']['canal'] === 'Aplicativo') ? 'selected' : ''; ?>>Aplicativo</option>
                                        <option value="Formulário" <?php echo (isset($this->data['form']['canal']) && $this->data['form']['canal'] === 'Formulário') ? 'selected' : ''; ?>>Formulário</option>
                                        <option value="Telefone" <?php echo (isset($this->data['form']['canal']) && $this->data['form']['canal'] === 'Telefone') ? 'selected' : ''; ?>>Telefone</option>
                                        <option value="Presencial" <?php echo (isset($this->data['form']['canal']) && $this->data['form']['canal'] === 'Presencial') ? 'selected' : ''; ?>>Presencial</option>
                                        <option value="E-mail" <?php echo (isset($this->data['form']['canal']) && $this->data['form']['canal'] === 'E-mail') ? 'selected' : ''; ?>>E-mail</option>
                                        <option value="Outro" <?php echo (isset($this->data['form']['canal']) && $this->data['form']['canal'] === 'Outro') ? 'selected' : ''; ?>>Outro</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data e Status -->
                <div class="col-12">
                    <div class="card border-warning">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="mb-0">Data e Status</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="data_consentimento" class="form-label">
                                        Data do Consentimento <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" 
                                           class="form-control" 
                                           id="data_consentimento" 
                                           name="data_consentimento" 
                                           value="<?php echo $this->data['form']['data_consentimento'] ?? date('Y-m-d'); ?>"
                                           required>
                                </div>
                                <div class="col-md-6">
                                    <label for="status" class="form-label">
                                        Status <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="">Selecione</option>
                                        <option value="Ativo" <?php echo (isset($this->data['form']['status']) && $this->data['form']['status'] === 'Ativo') ? 'selected' : ''; ?>>Ativo</option>
                                        <option value="Revogado" <?php echo (isset($this->data['form']['status']) && $this->data['form']['status'] === 'Revogado') ? 'selected' : ''; ?>>Revogado</option>
                                        <option value="Expirado" <?php echo (isset($this->data['form']['status']) && $this->data['form']['status'] === 'Expirado') ? 'selected' : ''; ?>>Expirado</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botões -->
                <div class="col-12">
                    <div class="d-flex justify-content-between">
                        <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-consentimentos" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Voltar
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Salvar Consentimento
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Validação do formulário
    $('form').on('submit', function(e) {
        var titularNome = $('#titular_nome').val().trim();
        var finalidade = $('#finalidade').val().trim();
        var dataConsentimento = $('#data_consentimento').val();
        var status = $('#status').val();

        if (!titularNome) {
            alert('Por favor, informe o nome do titular.');
            $('#titular_nome').focus();
            e.preventDefault();
            return false;
        }

        if (!finalidade) {
            alert('Por favor, informe a finalidade do consentimento.');
            $('#finalidade').focus();
            e.preventDefault();
            return false;
        }

        if (!dataConsentimento) {
            alert('Por favor, informe a data do consentimento.');
            $('#data_consentimento').focus();
            e.preventDefault();
            return false;
        }

        if (!status) {
            alert('Por favor, selecione o status.');
            $('#status').focus();
            e.preventDefault();
            return false;
        }

        return true;
    });
});
</script>
