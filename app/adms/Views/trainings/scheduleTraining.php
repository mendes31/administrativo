<?php
use App\adms\Helpers\FormatHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Agendar Treinamento</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-training-status" class="text-decoration-none">Status de Treinamentos</a>
            </li>
            <li class="breadcrumb-item">Agendar Treinamento</li>
        </ol>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4 border-light shadow">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-plus me-2"></i>Agendar Treinamento
                    </h5>
                </div>
                <div class="card-body">
                    <?php include './app/adms/Views/partials/alerts.php'; ?>
                    <form method="POST" action="<?php echo $_ENV['URL_ADM']; ?>schedule-training">
                        <input type="hidden" name="training_id" value="<?php echo $this->data['training_id']; ?>">
                        <input type="hidden" name="user_id" value="<?php echo $this->data['user_id']; ?>">
<?php if (!empty($this->data['edit_id'])): ?>
                        <input type="hidden" name="edit_id" value="<?php echo $this->data['edit_id']; ?>">
<?php endif; ?>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="data_agendada" class="form-label">
                                    <strong>Data Agendada *</strong>
                                </label>
                                <input type="date" 
                                       name="data_agendada" 
                                       class="form-control" 
                                       id="data_agendada" 
                                       value="<?php echo $this->data['form_data_agendada'] ?? ''; ?>"
                                       min="<?php echo date('Y-m-d'); ?>">
                                <div class="form-text">Data em que o treinamento está agendado para ocorrer</div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="observacoes" class="form-label">
                                    <strong>Observações</strong>
                                </label>
                                <textarea name="observacoes" 
                                          class="form-control" 
                                          id="observacoes" 
                                          rows="4" 
                                          placeholder="Observações sobre o agendamento do treinamento..."><?php echo $this->data['form_observacoes'] ?? ''; ?></textarea>
                                <div class="form-text">Observações adicionais sobre o treinamento</div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="instrutor_nome" class="form-label">
                                    <strong>Nome do Instrutor</strong>
                                </label>
                                <input type="text" name="instrutor_nome" class="form-control" id="instrutor_nome" value="<?php echo $this->data['form_instrutor_nome'] ?? ''; ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="instrutor_email" class="form-label">
                                    <strong>Email do Instrutor</strong>
                                </label>
                                <input type="email" name="instrutor_email" class="form-control" id="instrutor_email" value="<?php echo $this->data['form_instrutor_email'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="d-flex justify-content-between">
                                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-training-status" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Voltar
                                    </a>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save me-2"></i>Salvar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-4 border-light shadow">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Informações do Treinamento
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Treinamento:</strong><br>
                        <span class="text-primary"><?php echo htmlspecialchars($this->data['training']['nome']); ?></span>
                    </div>
                    <div class="mb-3">
                        <strong>Código:</strong><br>
                        <span class="badge bg-secondary"><?php echo htmlspecialchars($this->data['training']['codigo']); ?></span>
                    </div>
                    <?php if (!empty($this->data['training']['versao'])): ?>
                    <div class="mb-3">
                        <strong>Versão:</strong><br>
                        <span class="badge bg-info"><?php echo htmlspecialchars($this->data['training']['versao']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div> 