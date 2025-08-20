<?php
use App\adms\Helpers\CSRFHelper;
$csrf_token = CSRFHelper::generateCSRFToken('form_create_categoria_titular');
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Cadastrar Categoria de Titular LGPD</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-categorias-titulares" class="text-decoration-none">Categorias de Titulares</a>
            </li>
            <li class="breadcrumb-item">Cadastrar</li>
        </ol>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span><i class="fas fa-users me-2"></i>Cadastrar Categoria de Titular</span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form action="" method="POST" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="col-md-6">
                    <label for="titular" class="form-label">Titular <span class="text-danger">*</span></label>
                    <input type="text" name="titular" class="form-control" id="titular" value="<?php echo $this->data['form']['titular'] ?? ''; ?>" required>
                    <?php if (isset($this->data['errors']['titular'])): ?>
                        <div class="text-danger small"><?php echo $this->data['errors']['titular']; ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label for="exemplo" class="form-label">Exemplo</label>
                    <input type="text" name="exemplo" class="form-control" id="exemplo" value="<?php echo $this->data['form']['exemplo'] ?? ''; ?>">
                    <?php if (isset($this->data['errors']['exemplo'])): ?>
                        <div class="text-danger small"><?php echo $this->data['errors']['exemplo']; ?></div>
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
                        <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-categorias-titulares" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Voltar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div> 