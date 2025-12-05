<?php
use App\adms\Helpers\FormatHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Criar Chamado</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-employee-tickets" class="text-decoration-none">Chamados</a>
            </li>
            <li class="breadcrumb-item">Criar</li>
        </ol>
    </div>
    
    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <span><i class="fas fa-ticket-alt me-2"></i>Novo Chamado</span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form action="" method="POST" class="row g-3">
                <div class="col-md-6">
                    <label for="ticket_type" class="form-label">Tipo de Chamado <span class="text-danger">*</span></label>
                    <select name="ticket_type" id="ticket_type" class="form-select" required>
                        <option value="">Selecione...</option>
                        <option value="technical">Técnico</option>
                        <option value="hr">RH</option>
                        <option value="it">TI</option>
                        <option value="other">Outro</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="priority" class="form-label">Prioridade <span class="text-danger">*</span></label>
                    <select name="priority" id="priority" class="form-select" required>
                        <option value="low" selected>Baixa</option>
                        <option value="medium">Média</option>
                        <option value="high">Alta</option>
                        <option value="urgent">Urgente</option>
                    </select>
                </div>
                
                <div class="col-md-12">
                    <label for="title" class="form-label">Título <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="title" class="form-control" required 
                           placeholder="Ex: Problema com acesso ao sistema">
                </div>
                
                <div class="col-md-12">
                    <label for="description" class="form-label">Descrição <span class="text-danger">*</span></label>
                    <textarea name="description" id="description" class="form-control" rows="5" required 
                              placeholder="Descreva o problema ou solicitação..."></textarea>
                </div>
                
                <div class="col-12">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Abrir Chamado
                        </button>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>list-employee-tickets" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

