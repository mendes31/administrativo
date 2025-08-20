<?php

use App\adms\Helpers\CSRFHelper;

?>
<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Relatório Integrado - Fluxo LGPD</h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">LGPD</li>
            <li class="breadcrumb-item">Relatório Integrado</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <!-- RESUMO EXECUTIVO -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fa-solid fa-chart-line"></i> RESUMO EXECUTIVO</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <div class="text-center">
                                <h3 class="text-primary"><?php echo $this->data['report']['summary']['total_inventory_items']; ?></h3>
                                <p class="text-muted mb-0">Itens no Inventário</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="text-center">
                                <h3 class="text-success"><?php echo $this->data['report']['summary']['total_ropa_operations']; ?></h3>
                                <p class="text-muted mb-0">Operações ROPA</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="text-center">
                                <h3 class="text-info"><?php echo $this->data['report']['summary']['total_data_mappings']; ?></h3>
                                <p class="text-muted mb-0">Data Mappings</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="text-center">
                                <h3 class="text-warning"><?php echo $this->data['report']['summary']['sensitive_data_count']; ?></h3>
                                <p class="text-muted mb-0">Dados Sensíveis</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- STATUS DE COMPLIANCE -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-<?php echo $this->data['report']['compliance_status']['overall_status'] === 'Compliant' ? 'success' : ($this->data['report']['compliance_status']['overall_status'] === 'Needs Attention' ? 'warning' : 'danger'); ?>">
                <div class="card-header bg-<?php echo $this->data['report']['compliance_status']['overall_status'] === 'Compliant' ? 'success' : ($this->data['report']['compliance_status']['overall_status'] === 'Needs Attention' ? 'warning' : 'danger'); ?> text-white">
                    <h5 class="mb-0">
                        <i class="fa-solid fa-shield-alt"></i> 
                        STATUS DE COMPLIANCE: <?php echo strtoupper($this->data['report']['compliance_status']['overall_status']); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($this->data['report']['compliance_status']['issues'])): ?>
                        <div class="alert alert-warning">
                            <h6><i class="fa-solid fa-exclamation-triangle"></i> Problemas Identificados:</h6>
                            <ul class="mb-0">
                                <?php foreach ($this->data['report']['compliance_status']['issues'] as $issue): ?>
                                    <li><?php echo htmlspecialchars($issue); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($this->data['report']['compliance_status']['warnings'])): ?>
                        <div class="alert alert-info">
                            <h6><i class="fa-solid fa-info-circle"></i> Avisos:</h6>
                            <ul class="mb-0">
                                <?php foreach ($this->data['report']['compliance_status']['warnings'] as $warning): ?>
                                    <li><?php echo htmlspecialchars($warning); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($this->data['report']['compliance_status']['issues']) && empty($this->data['report']['compliance_status']['warnings'])): ?>
                        <div class="alert alert-success">
                            <i class="fa-solid fa-check-circle"></i>
                            <strong>Parabéns!</strong> O sistema está em conformidade com a LGPD.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- RECOMENDAÇÕES -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fa-solid fa-lightbulb"></i> RECOMENDAÇÕES</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($this->data['report']['recommendations'])): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($this->data['report']['recommendations'] as $recommendation): ?>
                                <li class="list-group-item">
                                    <i class="fa-solid fa-arrow-right text-info me-2"></i>
                                    <?php echo htmlspecialchars($recommendation); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted mb-0">Nenhuma recomendação específica no momento.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- DETALHES POR MÓDULO -->
    <div class="row">
        <!-- INVENTÁRIO -->
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-primary">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fa-solid fa-database"></i> INVENTÁRIO</h6>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        <strong>Total:</strong> <?php echo $this->data['report']['summary']['total_inventory_items']; ?> itens<br>
                        <strong>Sem ROPA:</strong> <?php echo $this->data['report']['summary']['missing_ropa_count']; ?> itens<br>
                        <strong>Dados Sensíveis:</strong> <?php echo $this->data['report']['summary']['sensitive_data_count']; ?> grupos
                    </p>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-inventory" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-eye"></i> Ver Inventário
                    </a>
                </div>
            </div>
        </div>

        <!-- ROPA -->
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-success">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="fa-solid fa-clipboard-list"></i> ROPA</h6>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        <strong>Total:</strong> <?php echo $this->data['report']['summary']['total_ropa_operations']; ?> operações<br>
                        <strong>Sem Data Mapping:</strong> <?php echo $this->data['report']['summary']['missing_mapping_count']; ?> operações<br>
                        <strong>Status:</strong> Ativas
                    </p>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-ropa" class="btn btn-success btn-sm">
                        <i class="fa-solid fa-eye"></i> Ver ROPA
                    </a>
                </div>
            </div>
        </div>

        <!-- DATA MAPPING -->
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fa-solid fa-project-diagram"></i> DATA MAPPING</h6>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        <strong>Total:</strong> <?php echo $this->data['report']['summary']['total_data_mappings']; ?> mapeamentos<br>
                        <strong>Fluxos Técnicos:</strong> Implementados<br>
                        <strong>Cobertura:</strong> Completa
                    </p>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-data-mapping" class="btn btn-info btn-sm">
                        <i class="fa-solid fa-eye"></i> Ver Data Mapping
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- AÇÕES RÁPIDAS -->
    <div class="row">
        <div class="col-12">
            <div class="card border-secondary">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fa-solid fa-bolt"></i> AÇÕES RÁPIDAS</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-inventory-create" class="btn btn-outline-primary">
                            <i class="fa-solid fa-plus"></i> Novo Inventário
                        </a>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-ropa-create" class="btn btn-outline-success">
                            <i class="fa-solid fa-plus"></i> Nova ROPA
                        </a>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-data-mapping-create" class="btn btn-outline-info">
                            <i class="fa-solid fa-plus"></i> Novo Data Mapping
                        </a>
                        <button type="button" class="btn btn-outline-warning" onclick="window.print()">
                            <i class="fa-solid fa-print"></i> Imprimir Relatório
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 