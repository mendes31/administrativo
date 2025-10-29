<div class="container-fluid px-4">
    
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">
            <i class="fas fa-file-excel me-2"></i>Importar Parceiros
        </h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>crm-list-partners">Parceiros</a></li>
            <li class="breadcrumb-item active">Importar</li>
        </ol>
    </div>

    <div class="row">
        <div class="col-md-8 mx-auto">
            
            <!-- Instruções -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Instruções</h5>
                </div>
                <div class="card-body">
                    <ol class="mb-3">
                        <li>Baixe o <strong>modelo de planilha</strong> clicando no botão abaixo</li>
                        <li>Preencha os dados dos parceiros na planilha</li>
                        <li>Salve o arquivo no formato <strong>.xlsx</strong></li>
                        <li>Faça o upload usando o formulário abaixo</li>
                    </ol>
                    
                    <a href="<?= $_ENV['URL_ADM'] ?>crm-download-template-partners" class="btn btn-success">
                        <i class="fas fa-download me-2"></i>Baixar Modelo de Planilha
                    </a>
                </div>
            </div>

            <!-- Formulário de Upload -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-upload me-2"></i>Upload da Planilha</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= $_ENV['URL_ADM'] ?>crm-import-partners" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Selecione a Planilha Excel *</label>
                            <input type="file" name="excel_file" class="form-control" required 
                                   accept=".xlsx,.xls">
                            <div class="form-text">
                                Formatos aceitos: .xlsx, .xls | Tamanho máximo: 5MB
                            </div>
                        </div>

                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Atenção:</strong> Parceiros duplicados (mesmo CPF/CNPJ) serão ignorados.
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-file-import me-2"></i>Importar Parceiros
                            </button>
                            <a href="<?= $_ENV['URL_ADM'] ?>crm-list-partners" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>

</div>

