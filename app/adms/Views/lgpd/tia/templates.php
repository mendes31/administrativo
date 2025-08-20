<?php
use App\adms\Helpers\CSRFHelper;
?>
<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">
            <i class="fas fa-copy text-info"></i>
            Templates de TIA por Setor
        </h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-dashboard" class="text-decoration-none">LGPD</a>
            </li>
            <li class="breadcrumb-item">Templates TIA</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <!-- INTRODUÇÃO -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Como usar os Templates
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-3">
                        Os templates de TIA são modelos pré-definidos que facilitam a criação de testes de impacto à privacidade 
                        para diferentes setores da organização. Cada template inclui:
                    </p>
                    <ul class="mb-0">
                        <li><strong>Checklist específico</strong> do setor para avaliação</li>
                        <li><strong>Riscos identificados</strong> comuns na área</li>
                        <li><strong>Medidas de proteção</strong> recomendadas</li>
                        <li><strong>Recomendações</strong> para implementação</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- TEMPLATES DISPONÍVEIS -->
    <div class="row mb-4">
        <!-- Template RH -->
        <div class="col-xl-6 col-lg-6 mb-4">
            <div class="card border-primary h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>Recursos Humanos
                    </h5>
                </div>
                <div class="card-body d-flex flex-column">
                    <p class="card-text">
                        Template específico para operações de RH, incluindo recrutamento, gestão de funcionários, 
                        folha de pagamento e benefícios.
                    </p>
                    <div class="mt-auto">
                        <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-tia-template-rh" class="btn btn-primary w-100">
                            <i class="fas fa-eye me-2"></i>Visualizar Template
                        </a>
                    </div>
                </div>
                <div class="card-footer text-muted">
                    <small>
                        <i class="fas fa-tags me-1"></i>
                        Recrutamento, Folha de Pagamento, Benefícios, Avaliações
                    </small>
                </div>
            </div>
        </div>

        <!-- Template Marketing -->
        <div class="col-xl-6 col-lg-6 mb-4">
            <div class="card border-success h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-bullhorn me-2"></i>Marketing
                    </h5>
                </div>
                <div class="card-body d-flex flex-column">
                    <p class="card-text">
                        Template para operações de marketing, incluindo campanhas publicitárias, análise de comportamento 
                        e relacionamento com clientes.
                    </p>
                    <div class="mt-auto">
                        <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-tia-template-marketing" class="btn btn-success w-100">
                            <i class="fas fa-eye me-2"></i>Visualizar Template
                        </a>
                    </div>
                </div>
                <div class="card-footer text-muted">
                    <small>
                        <i class="fas fa-tags me-1"></i>
                        Campanhas, CRM, Cookies, Redes Sociais, Analytics
                    </small>
                </div>
            </div>
        </div>

        <!-- Template Financeiro -->
        <div class="col-xl-6 col-lg-6 mb-4">
            <div class="card border-warning h-100">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>Financeiro
                    </h5>
                </div>
                <div class="card-body d-flex flex-column">
                    <p class="card-text">
                        Template para operações financeiras, incluindo processamento de pagamentos, análise de crédito 
                        e conformidade regulatória.
                    </p>
                    <div class="mt-auto">
                        <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-tia-template-financeiro" class="btn btn-warning w-100">
                            <i class="fas fa-eye me-2"></i>Visualizar Template
                        </a>
                    </div>
                </div>
                <div class="card-footer text-muted">
                    <small>
                        <i class="fas fa-tags me-1"></i>
                        Pagamentos, Crédito, Compliance, Auditoria, Riscos
                    </small>
                </div>
            </div>
        </div>

        <!-- Template TI -->
        <div class="col-xl-6 col-lg-6 mb-4">
            <div class="card border-danger h-100">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-laptop-code me-2"></i>Tecnologia da Informação
                    </h5>
                </div>
                <div class="card-body d-flex flex-column">
                    <p class="card-text">
                        Template para operações de TI, incluindo infraestrutura, desenvolvimento de sistemas 
                        e suporte técnico.
                    </p>
                    <div class="mt-auto">
                        <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-tia-template-ti" class="btn btn-danger w-100">
                            <i class="fas fa-eye me-2"></i>Visualizar Template
                        </a>
                    </div>
                </div>
                <div class="card-footer text-muted">
                    <small>
                        <i class="fas fa-tags me-1"></i>
                        Infraestrutura, Desenvolvimento, Segurança, Suporte
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- CRIAR TIA PERSONALIZADO -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-secondary">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-edit me-2"></i>Criar TIA Personalizado
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-3">
                        Se nenhum dos templates acima atender às suas necessidades, você pode criar um teste TIA 
                        completamente personalizado do zero.
                    </p>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-tia-create" class="btn btn-secondary">
                        <i class="fas fa-plus me-2"></i>Criar TIA Personalizado
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- DICAS DE USO -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-dark">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-lightbulb me-2"></i>Dicas para Uso Eficiente
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary">
                                <i class="fas fa-check-circle me-2"></i>Antes de usar um template:
                            </h6>
                            <ul class="mb-3">
                                <li>Identifique o setor mais próximo da sua atividade</li>
                                <li>Adapte o checklist às necessidades específicas</li>
                                <li>Considere riscos adicionais não cobertos</li>
                                <li>Personalize as medidas de proteção</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-success">
                                <i class="fas fa-rocket me-2"></i>Durante a aplicação:
                            </h6>
                            <ul class="mb-3">
                                <li>Preencha todos os campos obrigatórios</li>
                                <li>Documente justificativas claras</li>
                                <li>Defina responsáveis e prazos</li>
                                <li>Revise antes de finalizar</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- LINKS ÚTEIS -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-link me-2"></i>Links Úteis
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-tia" class="btn btn-outline-primary w-100">
                                <i class="fas fa-list me-2"></i>Listar Testes TIA
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-tia-dashboard" class="btn btn-outline-info w-100">
                                <i class="fas fa-chart-line me-2"></i>Dashboard TIA
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-workflow-report" class="btn btn-outline-success w-100">
                                <i class="fas fa-chart-bar me-2"></i>Relatório Workflow
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
