<?php

use App\adms\Helpers\CSRFHelper;

?>
<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Editar Fonte de Coleta</h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-fontes-coleta" class="text-decoration-none">Fontes de Coleta</a>
            </li>
            <li class="breadcrumb-item">Editar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <h5 class="mb-0"><i class="fa-solid fa-edit"></i> Editar Fonte de Coleta</h5>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <form method="POST" action="<?php echo $_ENV['URL_ADM']; ?>lgpd-fontes-coleta-update/<?php echo $this->data['fonte']['id']; ?>">
                <?php CSRFHelper::generateCSRFToken('form_edit_fonte_coleta'); ?>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nome" class="form-label">Nome da Fonte <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nome" name="nome" required 
                               value="<?php echo htmlspecialchars($this->data['fonte']['nome']); ?>"
                               placeholder="Ex: Formulário Online">
                        <div class="form-text">Nome descritivo da fonte de coleta</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="ativo" class="form-label">Status</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="ativo" name="ativo" 
                                   <?php echo $this->data['fonte']['ativo'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="ativo">
                                Ativo
                            </label>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 mb-3">
                        <label for="descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3" 
                                  placeholder="Descreva detalhes sobre esta fonte de coleta..."><?php echo htmlspecialchars($this->data['fonte']['descricao']); ?></textarea>
                        <div class="form-text">Informações adicionais sobre como os dados são coletados</div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-warning">
                        <i class="fa-solid fa-save"></i> Atualizar
                    </button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-fontes-coleta" class="btn btn-secondary">
                        <i class="fa-solid fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div> 