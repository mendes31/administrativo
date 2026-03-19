<?php
use App\adms\Helpers\CSRFHelper;
?>

<div class="container-fluid px-4">
    <h1 class="mt-4 mobile-hide-page-title">Relatório de Informativos</h1>
    
    <ol class="breadcrumb mb-4 mobile-hide-breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>list-informativos">Informativos</a></li>
        <li class="breadcrumb-item active">Relatório</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-chart-bar me-1"></i>
            Gerar Relatório de Visualização e Ciência
        </div>
        <div class="card-body">
            <form action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_relatorio'); ?>">
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="informativo_id" class="form-label">Selecione o Informativo:</label>
                            <select name="informativo_id" id="informativo_id" class="form-select" required>
                                <option value="">-- Selecione um informativo --</option>
                                <?php if (!empty($this->data['informativos'])): ?>
                                    <?php foreach ($this->data['informativos'] as $informativo): ?>
                                        <option value="<?php echo $informativo['id']; ?>">
                                            <?php echo htmlspecialchars($informativo['titulo']); ?>
                                            <?php if ($informativo['urgente']): ?> (URGENTE)<?php endif; ?>
                                            - <?php echo htmlspecialchars($informativo['categoria_nome'] ?? $informativo['categoria']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary d-block w-100">
                                <i class="fas fa-file-alt me-1"></i>
                                Gerar Relatório
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-info-circle me-1"></i>
            Informações sobre o Relatório
        </div>
        <div class="card-body">
            <p>Este relatório mostrará:</p>
            <ul>
                <li><strong>Usuário:</strong> Nome completo do usuário</li>
                <li><strong>Visualizou:</strong> Se o usuário visualizou o informativo (SIM/NÃO)</li>
                <li><strong>Data Visualização:</strong> Data e hora da visualização</li>
                <li><strong>Está Ciente:</strong> Se o usuário marcou "Estou ciente" (quando aplicável)</li>
                <li><strong>Data da Ciência:</strong> Data e hora quando marcou ciência</li>
                <li><strong>Status:</strong> Status geral (PENDENTE, VISUALIZOU, CIENTE, etc.)</li>
            </ul>
        </div>
    </div>
</div>
