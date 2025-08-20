<?php
use App\adms\Helpers\CSRFHelper;
$csrf_token = CSRFHelper::generateCSRFToken('form_update_finalidade');
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Editar Finalidade LGPD</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-finalidades" class="text-decoration-none">Finalidades</a>
            </li>
            <li class="breadcrumb-item">Editar</li>
        </ol>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span><i class="fas fa-bullseye me-2"></i>Editar Finalidade</span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form action="" method="POST" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="id" value="<?php echo $this->data['form']['id']; ?>">
                <div class="col-md-6">
                    <label for="finalidade" class="form-label">Finalidade <span class="text-danger">*</span></label>
                    <input type="text" name="finalidade" class="form-control" id="finalidade" value="<?php echo $this->data['form']['finalidade'] ?? ''; ?>" required>
                    <?php if (isset($this->data['errors']['finalidade'])): ?>
                        <div class="text-danger small"><?php echo $this->data['errors']['finalidade']; ?></div>
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
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-1"></i>Atualizar
                        </button>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-finalidades" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Voltar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div> 