<?php
use App\adms\Helpers\CSRFHelper;
$csrf_token = CSRFHelper::generateCSRFToken('form_create_classificacao_dados');
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Cadastrar Classificação de Dados LGPD</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-classificacoes-dados" class="text-decoration-none">Classificações de Dados</a>
            </li>
            <li class="breadcrumb-item">Cadastrar</li>
        </ol>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span><i class="fas fa-tags me-2"></i>Cadastrar Classificação de Dados</span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form action="" method="POST" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="col-md-6">
                    <label for="classificacao" class="form-label">Classificação <span class="text-danger">*</span></label>
                    <input type="text" name="classificacao" class="form-control" id="classificacao" value="<?php echo $this->data['form']['classificacao'] ?? ''; ?>" required>
                    <?php if (isset($this->data['errors']['classificacao'])): ?>
                        <div class="text-danger small"><?php echo $this->data['errors']['classificacao']; ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label for="base_legal_id" class="form-label">Base Legal <span class="text-danger">*</span></label>
                    <select name="base_legal_id" class="form-select" id="base_legal_id" required>
                        <option value="">Selecione...</option>
                        <?php foreach (($this->data['listBasesLegais'] ?? []) as $baseLegal): ?>
                            <option value="<?php echo $baseLegal['id']; ?>" <?php echo (isset($this->data['form']['base_legal_id']) && $this->data['form']['base_legal_id'] == $baseLegal['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($baseLegal['base_legal']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($this->data['errors']['base_legal_id'])): ?>
                        <div class="text-danger small"><?php echo $this->data['errors']['base_legal_id']; ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label for="exemplos" class="form-label">Exemplos</label>
                    <textarea name="exemplos" class="form-control" id="exemplos" rows="3"><?php echo $this->data['form']['exemplos'] ?? ''; ?></textarea>
                    <?php if (isset($this->data['errors']['exemplos'])): ?>
                        <div class="text-danger small"><?php echo $this->data['errors']['exemplos']; ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" id="status" required>
                        <option value="">Selecione...</option>
                        <option value="Ativo" <?php echo (isset($this->data['form']['status']) && $this->data['form']['status'] === 'Ativo') ? 'selected' : ''; ?>>Ativo</option>
                        <option value="Inativo" <?php echo (isset($this->data['form']['status']) && $this->data['form']['status'] === 'Inativo') ? 'selected' : ''; ?>>Inativo</option>
                    </select>
                    <?php if (isset($this->data['errors']['status'])): ?>
                        <div class="text-danger small"><?php echo $this->data['errors']['status']; ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-12">
                    <hr>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-1"></i>Cadastrar
                        </button>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-classificacoes-dados" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Voltar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div> 