<?php
use App\adms\Helpers\CSRFHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Criar Tipo de Solicitação</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-request-types" class="text-decoration-none">Tipos de Solicitação</a>
            </li>
            <li class="breadcrumb-item">Criar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <span><i class="fas fa-plus-circle me-2"></i>Novo Tipo de Solicitação</span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            
            <form action="" method="POST" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_create_request_type'); ?>">
                
                <div class="col-md-6">
                    <label for="code" class="form-label">Código <span class="text-danger">*</span></label>
                    <input type="text" name="code" id="code" class="form-control" required 
                           pattern="[a-z0-9_]+" 
                           placeholder="ex: vacation, time_off, document"
                           title="Apenas letras minúsculas, números e underscore">
                    <small class="form-text text-muted">Código único (ex: vacation, time_off). Apenas letras minúsculas, números e underscore.</small>
                </div>
                
                <div class="col-md-6">
                    <label for="name" class="form-label">Nome <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control" required 
                           placeholder="ex: Férias, Afastamento">
                </div>
                
                <div class="col-12">
                    <label for="description" class="form-label">Descrição</label>
                    <textarea name="description" id="description" class="form-control" rows="2" 
                              placeholder="Descrição do tipo de solicitação..."></textarea>
                </div>
                
                <div class="col-md-3">
                    <label for="icon" class="form-label">Ícone FontAwesome</label>
                    <input type="text" name="icon" id="icon" class="form-control" 
                           placeholder="ex: fa-calendar-alt">
                    <small class="form-text text-muted">Nome do ícone (sem o prefixo "fa-")</small>
                </div>
                
                <div class="col-md-3">
                    <label for="color" class="form-label">Cor do Badge</label>
                    <select name="color" id="color" class="form-select">
                        <option value="primary" selected>Primary (Azul)</option>
                        <option value="success">Success (Verde)</option>
                        <option value="warning">Warning (Amarelo)</option>
                        <option value="danger">Danger (Vermelho)</option>
                        <option value="info">Info (Ciano)</option>
                        <option value="secondary">Secondary (Cinza)</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="sort_order" class="form-label">Ordem de Exibição</label>
                    <input type="number" name="sort_order" id="sort_order" class="form-control" 
                           value="0" min="0">
                    <small class="form-text text-muted">Menor número aparece primeiro</small>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="status" id="status" value="1" checked>
                        <label class="form-check-label" for="status">Ativo</label>
                    </div>
                </div>
                
                <div class="col-12">
                    <hr>
                    <h6 class="mb-3">Configurações de Campos</h6>
                </div>
                
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="requires_manager_approval" id="requires_manager_approval" value="1">
                        <label class="form-check-label" for="requires_manager_approval">
                            <strong>Requer Aprovação do Gestor</strong>
                        </label>
                        <small class="form-text text-muted d-block">Se marcado, a solicitação precisará ser aprovada pelo gestor antes de ir para o RH</small>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="requires_dates" id="requires_dates" value="1">
                        <label class="form-check-label" for="requires_dates">
                            <strong>Requer Datas</strong>
                        </label>
                        <small class="form-text text-muted d-block">Se marcado, exibirá campos de data de início e término</small>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="requires_days" id="requires_days" value="1">
                        <label class="form-check-label" for="requires_days">
                            <strong>Requer Quantidade de Dias</strong>
                        </label>
                        <small class="form-text text-muted d-block">Se marcado, exibirá campo de dias solicitados (calculado automaticamente se datas estiverem marcadas)</small>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="requires_amount" id="requires_amount" value="1">
                        <label class="form-check-label" for="requires_amount">
                            <strong>Requer Valor</strong>
                        </label>
                        <small class="form-text text-muted d-block">Se marcado, exibirá campo de valor monetário</small>
                    </div>
                </div>
                
                <div class="col-12">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Salvar
                        </button>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>list-request-types" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

