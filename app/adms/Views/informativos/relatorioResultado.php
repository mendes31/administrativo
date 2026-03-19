<?php
use App\adms\Helpers\CSRFHelper;
?>

<div class="container-fluid px-4">
    <h1 class="mt-4 mobile-hide-page-title">Relatório de Informativo</h1>
    
    <ol class="breadcrumb mb-4 mobile-hide-breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>list-informativos">Informativos</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>relatorio-informativo">Relatório</a></li>
        <li class="breadcrumb-item active">Resultado</li>
    </ol>

    <!-- Cabeçalho do Informativo -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">
                <i class="fas fa-file-alt me-2"></i>
                <?php echo htmlspecialchars($this->data['informativo']['titulo']); ?>
            </h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Categoria:</strong> <?php echo htmlspecialchars($this->data['informativo']['categoria_nome'] ?? $this->data['informativo']['categoria']); ?></p>
                    <p><strong>Departamento:</strong> <?php echo htmlspecialchars($this->data['informativo']['department_name'] ?? 'N/A'); ?></p>
                    <p><strong>Urgente:</strong> 
                        <?php if ($this->data['informativo']['urgente']): ?>
                            <span class="badge bg-danger">SIM</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">NÃO</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-6">
                    <p><strong>Exige Ciência:</strong> 
                        <?php if ($this->data['informativo']['requires_ack'] == 'Sim'): ?>
                            <span class="badge bg-warning text-dark">SIM</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">NÃO</span>
                        <?php endif; ?>
                    </p>
                    <p><strong>Status:</strong> 
                        <?php if ($this->data['informativo']['ativo']): ?>
                            <span class="badge bg-success">ATIVO</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">INATIVO</span>
                        <?php endif; ?>
                    </p>
                    <p><strong>Criado em:</strong> <?php echo date('d/m/Y H:i:s', strtotime($this->data['informativo']['created_at'])); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Botões de Ação -->
    <div class="row mb-3">
        <div class="col-md-6">
            <a href="<?php echo $_ENV['URL_ADM']; ?>relatorio-informativo" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>
                Voltar
            </a>
        </div>
        <div class="col-md-6 text-end">
            <a href="<?php echo $_ENV['URL_ADM']; ?>export-relatorio-informativo-pdf?informativo_id=<?php echo $this->data['informativo']['id']; ?>" class="btn btn-success">
                <i class="fas fa-file-pdf me-1"></i>
                Exportar PDF
            </a>
            <button onclick="exportarCSV()" class="btn btn-info">
                <i class="fas fa-download me-1"></i>
                Exportar CSV
            </button>
        </div>
    </div>

    <!-- Resumo Estatístico (Cards) - Agora no topo acima da listagem -->
    <?php 
        $requiresAckCard = false;
        $val = $this->data['informativo']['requires_ack'] ?? null;
        if ($val === 1 || $val === '1' || $val === true || $val === 'true' || $val === 'Sim' || $val === 'sim') {
            $requiresAckCard = true;
        }
    ?>
    <div class="row mt-0 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3><?php echo count($this->data['dados_relatorio']); ?></h3>
                    <p class="mb-0">Total de Usuários</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3><?php echo count(array_filter($this->data['dados_relatorio'], fn($d) => $d['visualizou'] === 'SIM')); ?></h3>
                    <p class="mb-0">Visualizaram</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <h3><?php echo count(array_filter($this->data['dados_relatorio'], fn($d) => $d['status'] === 'PENDENTE')); ?></h3>
                    <p class="mb-0">Pendentes</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h3>
                        <?php 
                            if ($requiresAckCard) {
                                echo count(array_filter($this->data['dados_relatorio'], fn($d) => $d['status'] === 'CIENTE'));
                            } else {
                                echo 'N/A';
                            }
                        ?>
                    </h3>
                    <p class="mb-0">Cientes</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela do Relatório -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-users me-1"></i>
                Status dos Usuários
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered" id="tabela-relatorio">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-start ps-3" style="min-width: 220px;">Usuário</th>
                            <th class="text-start ps-3" style="min-width: 120px;">Visualizou</th>
                            <th class="text-start ps-3" style="min-width: 170px;">Data Visualização</th>
                            <th class="text-start ps-3" style="min-width: 140px;">Está Ciente?</th>
                            <th class="text-start ps-3" style="min-width: 170px;">Data da Ciência</th>
                            <th class="text-start ps-3" style="min-width: 170px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($this->data['dados_relatorio'])): ?>
                            <?php foreach ($this->data['dados_relatorio'] as $dado): ?>
                                <tr>
                                    <td class="text-start align-middle ps-3">
                                        <strong><?php echo htmlspecialchars($dado['usuario_nome']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($dado['usuario_email']); ?></small>
                                    </td>
                                    <td class="text-start align-middle ps-3">
                                        <?php if ($dado['visualizou'] === 'SIM'): ?>
                                            <span class="badge bg-success">SIM</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">NÃO</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-start align-middle ps-3">
                                        <?php echo $dado['data_visualizacao']; ?>
                                    </td>
                                    <td class="text-start align-middle ps-3">
                                        <?php if ($dado['esta_ciente'] === 'N/A'): ?>
                                            <span class="badge bg-secondary">N/A</span>
                                        <?php elseif ($dado['esta_ciente'] === 'SIM'): ?>
                                            <span class="badge bg-success">SIM</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">NÃO</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-start align-middle ps-3">
                                        <?php echo $dado['data_ciencia']; ?>
                                    </td>
                                    <td class="text-start align-middle ps-3">
                                        <?php
                                        $statusClass = match($dado['status']) {
                                            'PENDENTE' => 'bg-danger',
                                            'VISUALIZOU' => 'bg-info',
                                            'VISUALIZOU MAS NÃO CIENTE' => 'bg-warning text-dark',
                                            'CIENTE' => 'bg-success',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>">
                                            <?php echo htmlspecialchars($dado['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">Nenhum usuário encontrado</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function exportarCSV() {
    const table = document.getElementById('tabela-relatorio');
    const rows = table.querySelectorAll('tr');
    
    let csv = [];
    
    // Cabeçalho
    const headers = [];
    rows[0].querySelectorAll('th').forEach(th => {
        headers.push('"' + th.textContent.trim() + '"');
    });
    csv.push(headers.join(','));
    
    // Dados
    for (let i = 1; i < rows.length; i++) {
        const row = [];
        rows[i].querySelectorAll('td').forEach(td => {
            // Remover HTML e pegar apenas texto
            const text = td.textContent.trim().replace(/\s+/g, ' ');
            row.push('"' + text + '"');
        });
        csv.push(row.join(','));
    }
    
    // Download
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', 'relatorio_informativo_<?php echo $this->data['informativo']['id']; ?>_<?php echo date('Y-m-d_H-i-s'); ?>.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>
