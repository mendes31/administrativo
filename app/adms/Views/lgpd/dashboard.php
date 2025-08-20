<?php
// View do Dashboard LGPD
$indicadores = $this->data['indicadores'] ?? [];
$compliance = $indicadores['compliance'] ?? [];
$inventario = $indicadores['inventario'] ?? [];
$ropa = $indicadores['ropa'] ?? [];
$data_mapping = $indicadores['data_mapping'] ?? [];
$consentimentos = $indicadores['consentimentos'] ?? [];
$medidas_seguranca = $indicadores['medidas_seguranca'] ?? [];
$incidentes = $indicadores['incidentes'] ?? [];
$treinamentos = $indicadores['treinamentos'] ?? [];
$riscos = $indicadores['riscos'] ?? [];
$riscos_inventario = $indicadores['riscos_inventario'] ?? [];
$riscos_ropa = $indicadores['riscos_ropa'] ?? [];
$recentes = $indicadores['recentes'] ?? [];

// Função para obter cor baseada no score
function getScoreColor($score) {
    if ($score >= 80) return 'success';
    if ($score >= 60) return 'warning';
    return 'danger';
}

// Função para obter cor baseada no risco
function getRiskColor($risco) {
    switch ($risco) {
        case 'Alto': return 'danger';
        case 'Médio': return 'warning';
        case 'Baixo': return 'success';
        default: return 'secondary';
    }
}
?>

<div class="container-fluid px-4">
    <!-- Header do Dashboard -->
    <div class="row justify-content-center mb-4">
        <div class="col-12">
            <div class="bg-primary bg-gradient rounded-4 p-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="fw-bold text-white mb-1">
                            <i class="fas fa-shield-halved me-2"></i>
                            Dashboard LGPD
                        </h2>
                        <div class="text-white-50">
                            Visão geral do compliance e proteção de dados pessoais
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="text-white">
                            <small>Última atualização: <?php echo date('d/m/Y H:i'); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Score de Compliance Geral -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow">
                <div class="card-body p-4">
                    <h4 class="mb-3 text-center">Score de Compliance Geral</h4>
                    
                    <!-- Score Principal -->
                    <div class="row justify-content-center mb-4">
                        <div class="col-md-3 text-center">
                            <div class="display-4 fw-bold text-<?php echo getScoreColor($compliance['score_geral'] ?? 0); ?>">
                                <?php echo $compliance['score_geral'] ?? 0; ?>%
                            </div>
                            <div class="text-muted"><?php echo $compliance['nivel_compliance'] ?? 'N/A'; ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="progress mb-3" style="height: 20px;">
                                <div class="progress-bar bg-<?php echo getScoreColor($compliance['score_geral'] ?? 0); ?>" 
                                     style="width: <?php echo $compliance['score_geral'] ?? 0; ?>%">
                                    <?php echo $compliance['score_geral'] ?? 0; ?>%
                                </div>
                            </div>
                            <div class="text-muted">
                                Status: <strong><?php echo $compliance['status_geral'] ?? 'N/A'; ?></strong>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Scores Detalhados -->
                    <?php if (isset($compliance['scores_detalhados'])): ?>
                    <div class="row">
                        <div class="col-12">
                            <h6 class="mb-3">Detalhamento por Pilar (Baseado em Práticas de Mercado)</h6>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">Inventário (25%)</span>
                                        <span class="fw-bold text-primary"><?php echo round($compliance['scores_detalhados']['inventario'] / 0.25); ?>%</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-primary" style="width: <?php echo round($compliance['scores_detalhados']['inventario'] / 0.25); ?>%"></div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">ROPA (20%)</span>
                                        <span class="fw-bold text-success"><?php echo round($compliance['scores_detalhados']['ropa'] / 0.20); ?>%</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-success" style="width: <?php echo round($compliance['scores_detalhados']['ropa'] / 0.20); ?>%"></div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">Segurança (20%)</span>
                                        <span class="fw-bold text-warning"><?php echo round($compliance['scores_detalhados']['seguranca'] / 0.20); ?>%</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-warning" style="width: <?php echo round($compliance['scores_detalhados']['seguranca'] / 0.20); ?>%"></div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">Consentimentos (15%)</span>
                                        <span class="fw-bold text-info"><?php echo round($compliance['scores_detalhados']['consentimentos'] / 0.15); ?>%</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-info" style="width: <?php echo round($compliance['scores_detalhados']['consentimentos'] / 0.15); ?>%"></div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">Treinamentos (10%)</span>
                                        <span class="fw-bold text-secondary"><?php echo round($compliance['scores_detalhados']['treinamentos'] / 0.10); ?>%</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-secondary" style="width: <?php echo round($compliance['scores_detalhados']['treinamentos'] / 0.10); ?>%"></div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">Incidentes (10%)</span>
                                        <span class="fw-bold text-danger"><?php echo round($compliance['scores_detalhados']['incidentes'] / 0.10); ?>%</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-danger" style="width: <?php echo round($compliance['scores_detalhados']['incidentes'] / 0.10); ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Penalizações -->
                    <?php if (isset($compliance['penalizacoes']) && $compliance['penalizacoes'] > 0): ?>
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Penalizações aplicadas:</strong> -<?php echo $compliance['penalizacoes']; ?> pontos por pendências críticas
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Cards de Indicadores Principais -->
    <div class="row mb-4">
        <!-- Inventário -->
        <div class="col-lg-2 col-md-6 mb-3">
            <div class="card border-0 shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Inventário</h6>
                            <h3 class="mb-0"><?php echo $inventario['total'] ?? 0; ?></h3>
                            <small class="text-muted">Total de registros</small>
                        </div>
                        <div class="text-end">
                            <i class="fas fa-database fa-2x text-primary"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="d-flex justify-content-between">
                            <span class="badge bg-primary"><?php echo $inventario['colaboradores'] ?? 0; ?> RH</span>
                            <span class="badge bg-info"><?php echo $inventario['area_ti'] ?? 0; ?> TI</span>
                            <span class="badge bg-secondary"><?php echo $inventario['area_comercial'] ?? 0; ?> Com.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Mapping -->
        <div class="col-lg-2 col-md-6 mb-3">
            <div class="card border-0 shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Data Mapping</h6>
                            <h3 class="mb-0"><?php echo $data_mapping['total'] ?? 0; ?></h3>
                            <small class="text-muted">Mapeamentos</small>
                        </div>
                        <div class="text-end">
                            <i class="fas fa-exchange-alt fa-2x text-secondary"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="progress mb-2" style="height: 8px;">
                            <div class="progress-bar bg-secondary" style="width: <?php echo $data_mapping['percentual_completo'] ?? 0; ?>%"></div>
                        </div>
                        <small class="text-muted"><?php echo $data_mapping['percentual_completo'] ?? 0; ?>% completo</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- ROPA -->
        <div class="col-lg-2 col-md-6 mb-3">
            <div class="card border-0 shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">ROPA</h6>
                            <h3 class="mb-0"><?php echo $ropa['total'] ?? 0; ?></h3>
                            <small class="text-muted">Registros de operações</small>
                        </div>
                        <div class="text-end">
                            <i class="fas fa-clipboard-list fa-2x text-success"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="progress mb-2" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: <?php echo $ropa['percentual_ativos'] ?? 0; ?>%"></div>
                        </div>
                        <small class="text-muted"><?php echo $ropa['percentual_ativos'] ?? 0; ?>% ativos</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Consentimentos -->
        <div class="col-lg-2 col-md-6 mb-3">
            <div class="card border-0 shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Consentimentos</h6>
                            <h3 class="mb-0"><?php echo $consentimentos['total'] ?? 0; ?></h3>
                            <small class="text-muted">Total de registros</small>
                        </div>
                        <div class="text-end">
                            <i class="fas fa-handshake fa-2x text-info"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="progress mb-2" style="height: 8px;">
                            <div class="progress-bar bg-info" style="width: <?php echo $consentimentos['percentual_ativos'] ?? 0; ?>%"></div>
                        </div>
                        <small class="text-muted"><?php echo $consentimentos['percentual_ativos'] ?? 0; ?>% ativos</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Medidas de Segurança -->
        <div class="col-lg-2 col-md-6 mb-3">
            <div class="card border-0 shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Medidas de Segurança</h6>
                            <h3 class="mb-0"><?php echo $medidas_seguranca['com_medidas'] ?? 0; ?></h3>
                            <small class="text-muted">ROPA com medidas</small>
                        </div>
                        <div class="text-end">
                            <i class="fas fa-shield-alt fa-2x text-warning"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="progress mb-2" style="height: 8px;">
                            <div class="progress-bar bg-warning" style="width: <?php echo $medidas_seguranca['percentual_medidas'] ?? 0; ?>%"></div>
                        </div>
                        <small class="text-muted"><?php echo $medidas_seguranca['percentual_medidas'] ?? 0; ?>% implementadas</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Incidentes -->
        <div class="col-lg-2 col-md-6 mb-3">
            <div class="card border-0 shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Incidentes</h6>
                            <h3 class="mb-0"><?php echo $incidentes['total'] ?? 0; ?></h3>
                            <small class="text-muted">Total registrados</small>
                        </div>
                        <div class="text-end">
                            <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="d-flex justify-content-between">
                            <span class="badge bg-warning"><?php echo $incidentes['abertos'] ?? 0; ?> Abertos</span>
                            <span class="badge bg-success"><?php echo $incidentes['resolvidos'] ?? 0; ?> Resolvidos</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    

    <!-- Seção de Riscos, Treinamentos e Pendências -->
    <div class="row mb-4">
        <!-- Treinamentos -->
        <div class="col-lg-4 mb-3">
            <div class="card border-0 shadow h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-graduation-cap me-2"></i>
                        Treinamentos
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <h3 class="text-primary"><?php echo $treinamentos['total'] ?? 0; ?></h3>
                        <small class="text-muted">Total de treinamentos</small>
                    </div>
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="text-success">
                                <h5><?php echo $treinamentos['concluidos'] ?? 0; ?></h5>
                                <small>Concluídos</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-warning">
                                <h5><?php echo $treinamentos['em_andamento'] ?? 0; ?></h5>
                                <small>Em Andamento</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-danger">
                                <h5><?php echo $treinamentos['pendentes'] ?? 0; ?></h5>
                                <small>Pendentes</small>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="progress mb-2" style="height: 8px;">
                            <?php 
                            $total_treinamentos = $treinamentos['total'] ?? 0;
                            $concluidos = $treinamentos['concluidos'] ?? 0;
                            $percentual_concluidos = $total_treinamentos > 0 ? round(($concluidos / $total_treinamentos) * 100) : 0;
                            ?>
                            <div class="progress-bar bg-success" style="width: <?php echo $percentual_concluidos; ?>%"></div>
                        </div>
                        <small class="text-muted"><?php echo $percentual_concluidos; ?>% concluídos</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Análise de Riscos -->
        <div class="col-lg-4 mb-3">
            <div class="card border-0 shadow h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>
                        Análise de Riscos
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="text-danger">
                                <h4><?php echo $riscos['alto_risco'] ?? 0; ?></h4>
                                <small>Alto Risco</small>
                                <div class="mt-1">
                                    <small class="text-muted">
                                        <?php 
                                        $total_riscos = ($riscos['alto_risco'] ?? 0) + ($riscos['medio_risco'] ?? 0) + ($riscos['baixo_risco'] ?? 0);
                                        $percentual_alto = $total_riscos > 0 ? round((($riscos['alto_risco'] ?? 0) / $total_riscos) * 100) : 0;
                                        echo $percentual_alto . '%';
                                        ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-warning">
                                <h4><?php echo $riscos['medio_risco'] ?? 0; ?></h4>
                                <small>Médio Risco</small>
                                <div class="mt-1">
                                    <small class="text-muted">
                                        <?php 
                                        $percentual_medio = $total_riscos > 0 ? round((($riscos['medio_risco'] ?? 0) / $total_riscos) * 100) : 0;
                                        echo $percentual_medio . '%';
                                        ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-success">
                                <h4><?php echo $riscos['baixo_risco'] ?? 0; ?></h4>
                                <small>Baixo Risco</small>
                                <div class="mt-1">
                                    <small class="text-muted">
                                        <?php 
                                        $percentual_baixo = $total_riscos > 0 ? round((($riscos['baixo_risco'] ?? 0) / $total_riscos) * 100) : 0;
                                        echo $percentual_baixo . '%';
                                        ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="progress mb-3" style="height: 12px;">
                            <div class="progress-bar bg-danger" style="width: <?php echo $percentual_alto; ?>%"></div>
                            <div class="progress-bar bg-warning" style="width: <?php echo $percentual_medio; ?>%"></div>
                            <div class="progress-bar bg-success" style="width: <?php echo $percentual_baixo; ?>%"></div>
                        </div>
                        <div class="alert alert-<?php echo getRiskColor($riscos['nivel_risco_geral'] ?? 'Baixo'); ?> mb-0">
                            <strong>Nível de Risco Geral:</strong> <?php echo $riscos['nivel_risco_geral'] ?? 'Baixo'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pendências Críticas -->
        <div class="col-lg-4 mb-3">
            <div class="card border-0 shadow h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Pendências Críticas
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($compliance['pendencias_criticas'])): ?>
                        <?php foreach ($compliance['pendencias_criticas'] as $pendencia): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <strong><?php echo $pendencia['tipo']; ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo $pendencia['quantidade']; ?> item(s)</small>
                                </div>
                                <span class="badge bg-<?php echo $pendencia['prioridade'] === 'Crítica' ? 'danger' : ($pendencia['prioridade'] === 'Alta' ? 'warning' : 'info'); ?>">
                                    <?php echo $pendencia['prioridade']; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                            <p class="mb-0">Nenhuma pendência crítica encontrada</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Atividades Recentes -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-clock me-2"></i>
                        Atividades Recentes (Últimos 7 dias)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($recentes)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Identificador</th>
                                        <th>Descrição</th>
                                        <th>Data</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentes as $atividade): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-<?php echo $atividade['tipo'] === 'ROPA' ? 'success' : 'primary'; ?>">
                                                    <?php echo $atividade['tipo']; ?>
                                                </span>
                                            </td>
                                            <td><strong><?php echo $atividade['identificador']; ?></strong></td>
                                            <td><?php echo htmlspecialchars($atividade['descricao']); ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($atividade['data'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $atividade['status'] === 'Ativo' ? 'success' : 'secondary'; ?>">
                                                    <?php echo $atividade['status']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x mb-2"></i>
                            <p class="mb-0">Nenhuma atividade recente encontrada</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Links Rápidos -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-link me-2"></i>
                        Ações Rápidas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2 mb-2">
                            <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-inventory" class="btn btn-outline-primary w-100">
                                <i class="fas fa-database me-2"></i>
                                Gerenciar Inventário
                            </a>
                        </div>
                        <div class="col-md-2 mb-2">
                            <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-data-mapping" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-exchange-alt me-2"></i>
                                Data Mapping
                            </a>
                        </div>
                        <div class="col-md-2 mb-2">
                            <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-ropa" class="btn btn-outline-success w-100">
                                <i class="fas fa-clipboard-list me-2"></i>
                                Gerenciar ROPA
                            </a>
                        </div>
                        <div class="col-md-2 mb-2">
                            <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-consentimentos" class="btn btn-outline-info w-100">
                                <i class="fas fa-handshake me-2"></i>
                                Consentimentos
                            </a>
                        </div>
                        <div class="col-md-2 mb-2">
                            <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-incidentes" class="btn btn-outline-warning w-100">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Incidentes
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts para funcionalidades dinâmicas -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Atualizar dados a cada 5 minutos
    setInterval(function() {
        // Aqui você pode adicionar lógica para atualizar dados via AJAX
        console.log('Dashboard LGPD - Dados atualizados');
    }, 300000);
});
</script> 