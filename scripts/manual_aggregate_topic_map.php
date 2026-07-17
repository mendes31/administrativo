<?php

declare(strict_types=1);

/**
 * Retorna mapa agregado slug da tela → id do tópico visão geral / agrupado.
 * @return array<string, string>
 */
function buildManualAggregateTopicMap(): array
{
    $map = [];

    $add = static function (array $slugs, string $topic) use (&$map): void {
        foreach ($slugs as $slug) {
            $map[$slug] = $topic;
        }
    };

    $add(['dashboard'], 'dashboard-visao-geral');

    $add([
        'email-config', 'notification-settings', 'calendar-config', 'sap-api-config', 'mcp-api-config',
        'push-config', 'whats-app-config', 'password-policy', 'update-password-policy', 'list-branches',
        'create-branch', 'update-branch', 'view-branch',
    ], 'adm-configuracoes');
    $add([
        'list-log-acessos', 'list-log-alteracoes', 'list-connected-users', 'list-users-last-access', 'log-settings',
    ], 'adm-logs');
    $add([
        'list-groups-pages', 'create-group-page', 'update-group-page', 'view-group-page',
        'list-packages', 'create-package', 'update-package', 'view-package',
        'list-pages', 'create-page', 'update-page', 'view-page',
        'list-permission', 'update-permission',
    ], 'adm-permissoes');
    $add(['list-mandatory-trainings', 'create-mandatory-training', 'update-mandatory-training'], 'adm-treinamentos-obrigatorios');

    $add([
        'list-positions', 'create-position', 'update-position', 'view-position',
        'list-departments', 'create-department', 'update-department', 'view-department',
        'list-cost-centers', 'create-cost-center', 'update-cost-center', 'view-cost-center',
        'list-work-shifts', 'create-work-shift', 'update-work-shift', 'view-work-shift',
    ], 'cad-estrutura');
    $add([
        'list-users', 'create-user', 'update-user', 'view-user', 'import-users',
        'update-user-access-levels',
    ], 'cad-usuarios');
    $add(['organization-chart'], 'cad-organograma');
    $add([
        'list-access-levels', 'create-access-level', 'update-access-level', 'view-access-level',
        'list-access-levels-permissions', 'update-access-levels-permissions',
    ], 'cad-niveis-acesso');

    $add(['list-informativos', 'create-informativo', 'update-informativo', 'view-informativo'], 'com-informativos');
    $add(['timeline', 'timeline-moderate'], 'com-timeline');
    $add(['list-company-events', 'create-company-event', 'update-company-event', 'view-company-event'], 'com-eventos');
    $add([
        'list-gamification-timeline-rules', 'create-gamification-timeline-rule', 'update-gamification-timeline-rule',
        'list-gamification-quizzes', 'create-gamification-quiz', 'update-gamification-quiz',
        'list-gamification-point-ledger', 'gamification-quiz-catalog', 'gamification-leaderboard',
        'gamification-engagement-dashboard',
    ], 'com-gamificacao');

    $add(['crm-dashboard', 'crm-manager-dashboard'], 'crm-dashboards');
    $add(['crm-kanban-pipeline'], 'crm-pipeline');
    $add([
        'crm-list-partners', 'crm-create-partner', 'crm-update-partner', 'crm-view-partner',
    ], 'crm-parceiros');
    $add([
        'crm-list-opportunities', 'crm-create-opportunity', 'crm-update-opportunity', 'crm-view-opportunity',
    ], 'crm-oportunidades');
    $add([
        'crm-list-activities', 'crm-create-activity', 'crm-update-activity', 'crm-view-activity',
    ], 'crm-atividades');
    $add([
        'crm-list-tags', 'crm-create-tag', 'crm-update-tag',
        'crm-list-custom-fields', 'crm-create-custom-field', 'crm-update-custom-field',
        'crm-list-automations', 'crm-create-automation', 'crm-update-automation', 'crm-view-automation',
    ], 'crm-configuracoes');

    $add([
        'list-inventory-items', 'create-inventory-item', 'update-inventory-item', 'view-inventory-item',
        'list-inventory-units', 'list-inventory-categories', 'list-inventory-stocks',
        'list-inventory-positions', 'list-inventory-operations', 'list-inventory-production-resources',
        'list-inventory-labor-roles',
    ], 'est-cadastros');
    $add([
        'create-inventory-adjust', 'create-inventory-entry', 'create-inventory-exit', 'create-inventory-transfer',
        'list-inventory-movements', 'view-inventory-movement',
    ], 'est-movimentacoes');
    $add([
        'list-inventory-cost-production-batches', 'list-inventory-cost-periods', 'simulate-inventory-cost',
    ], 'est-custeio');
    $add(['report-inventory-balance', 'report-inventory-history'], 'est-relatorios');

    $add([
        'list-banks', 'create-bank', 'update-bank',
        'list-frequencies', 'create-frequency', 'update-frequency',
        'list-payment-methods', 'create-payment-method', 'update-payment-method',
        'list-accounts-plan', 'create-account-plan', 'update-account-plan',
        'list-mov-between-accounts', 'create-mov-between-accounts',
    ], 'fin-cadastros');
    $add([
        'list-payments', 'create-payment', 'update-payment', 'view-payment',
        'list-receipts', 'create-receipt', 'update-receipt', 'view-receipt',
    ], 'fin-pagar-receber');
    $add([
        'cost-center-summary', 'movements', 'cash-flow', 'flow-cash-competence',
    ], 'fin-relatorios');

    $add([
        'list-customers', 'create-customer', 'update-customer', 'view-customer',
        'list-suppliers', 'create-supplier', 'update-supplier', 'view-supplier',
    ], 'parceiros-negocio');
    $add(['list-documents', 'create-document', 'update-document', 'view-document'], 'qualidade-documentos');

    $add(['list-trainings', 'create-training', 'update-training', 'view-training'], 'rh-trein-catalogo');
    $add(['training-kpi-dashboard', 'training-compliance-dashboard'], 'rh-trein-dashboards');
    $add(['matrix-by-user', 'completed-trainings-matrix', 'list-training-status', 'apply-training', 'schedule-training', 'training-matrix-manager'], 'rh-trein-matrizes');
    $add(['new-training-version', 'training-version-audit', 'training-history'], 'rh-trein-catalogo');
    $add([
        'my-evaluations', 'create-evaluation-model-with-questions', 'list-evaluation-models',
        'assign-evaluation', 'list-evaluation-assignments', 'view-evaluation-assignment',
    ], 'rh-trein-avaliacoes');
    $add(['test-notification'], 'rh-trein-notificacoes');

    $add([
        'list-projects', 'create-project', 'update-project', 'view-project',
        'list-project-stages', 'create-project-stage', 'update-project-stage',
        'list-stage-groups', 'create-stage-group', 'update-stage-group',
    ], 'proj-projetos');

    $add([
        'list-policies', 'create-policy', 'update-policy', 'view-policy',
        'list-policy-categories', 'create-policy-category', 'update-policy-category',
    ], 'gp-politicas');
    $add(['employee-portal', 'my-payroll-documents', 'my-epi-deliveries', 'my-sst-treinamentos'], 'gp-portal');
    $add([
        'import-payroll-documents', 'list-payroll-signing-pendencies', 'payroll-cron-config',
        'list-payroll-document-types', 'create-payroll-document-type', 'update-payroll-document-type',
    ], 'gp-folha');
    $add([
        'list-performance-reviews', 'create-performance-review', 'update-performance-review',
        'list-performance-goals', 'create-performance-goal', 'update-performance-goal',
        'list-performance-feedbacks', 'create-performance-feedback',
        'list-competencies', 'create-competency', 'update-competency',
        'competency-matrix', 'nine-box-matrix', 'performance-dashboard',
    ], 'gp-desempenho');
    $add([
        'list-employee-requests', 'create-employee-request', 'view-employee-request',
        'pending-approvals', 'list-request-types', 'create-request-type', 'update-request-type',
        'list-employee-tickets', 'create-employee-ticket', 'view-employee-ticket',
    ], 'gp-solicitacoes');
    $add(['people-analytics', 'people-reports'], 'gp-analytics');
    $add([
        'rh-kpi-dashboard', 'rh-candidatos', 'rh-vagas', 'rh-entrevistas',
        'create-rh-vaga', 'update-rh-vaga', 'view-rh-candidato',
    ], 'gp-recrutamento');

    $add([
        'room-calendar', 'list-meeting-rooms', 'create-meeting-room', 'update-meeting-room', 'view-meeting-room',
        'list-bookings', 'create-booking', 'update-booking', 'view-booking', 'cancel-booking', 'book-room',
        'booking-waitlist',
    ], 'salas-reservas');
    $add([
        'rooms-list-service-requests', 'rooms-list-request-types', 'rooms-list-request-groups',
        'admin-booking-dashboard', 'rooms-calendar-integration-settings', 'booking-reports',
    ], 'salas-administracao');

    $add([
        'sac-dashboard', 'sac-list-tickets', 'sac-create-ticket', 'sac-view-ticket', 'sac-update-ticket',
        'sac-list-clients', 'sac-create-client', 'sac-update-client',
        'sac-list-categories', 'sac-create-category', 'sac-update-category',
        'sac-list-sla-rules', 'sac-create-sla-rule', 'sac-update-sla-rule',
    ], 'sac-atendimento');

    $add(['lgpd-dashboard'], 'lgpd-dashboard');
    $add(['lgpd-consentimentos', 'lgpd-termos', 'create-lgpd-termo', 'update-lgpd-termo'], 'lgpd-consentimentos');
    $add([
        'lgpd-inventory', 'lgpd-ropa', 'lgpd-data-mapping', 'lgpd-workflow-report',
        'lgpd-categorias-titulares', 'lgpd-finalidades', 'lgpd-bases-legais',
        'lgpd-tipos-dados', 'lgpd-classificacoes-dados',
    ], 'lgpd-inventario');
    $add([
        'lgpd-aipd', 'lgpd-aipd-suggest', 'lgpd-ripd', 'lgpd-tia',
        'lgpd-aipd-template-ecommerce', 'lgpd-aipd-template-educacao', 'lgpd-aipd-template-financeiro',
        'lgpd-aipd-template-juridico', 'lgpd-aipd-template-logistica', 'lgpd-aipd-template-marketing',
        'lgpd-aipd-template-rh', 'lgpd-aipd-template-saude', 'lgpd-aipd-template-telecom',
    ], 'lgpd-aipd');

    $add([
        'strategic-dashboard', 'list-strategic-plans', 'create-strategic-plan', 'update-strategic-plan',
        'strategic-indicators-list', 'create-strategic-indicator', 'update-strategic-indicator',
    ], 'pe-estrategico');
    $add([
        'list-dynamic-reports', 'create-dynamic-report', 'update-dynamic-report', 'view-dynamic-report',
        'list-dynamic-reports-sap', 'list-dashboards', 'create-dashboard', 'update-dashboard',
        'sales-dashboard', 'sales-dashboard-data',
    ], 'rel-dinamicos');

    $add(['cadastro'], 'cad-visao-geral');

    ksort($map);

    return $map;
}

/** @return array<string, string> tópico agregado → pasta em content/ */
function manualTopicToContentDir(): array
{
    return [
        'dashboard-visao-geral' => 'dashboard',
        'adm-visao-geral' => 'administracao', 'adm-configuracoes' => 'administracao', 'adm-logs' => 'administracao',
        'adm-permissoes' => 'administracao', 'adm-treinamentos-obrigatorios' => 'administracao',
        'cad-visao-geral' => 'cadastro', 'cad-estrutura' => 'cadastro', 'cad-usuarios' => 'cadastro',
        'cad-organograma' => 'cadastro', 'cad-niveis-acesso' => 'cadastro',
        'com-visao-geral' => 'comunicacao', 'com-informativos' => 'comunicacao', 'com-timeline' => 'comunicacao',
        'com-eventos' => 'comunicacao', 'com-gamificacao' => 'comunicacao',
        'crm-visao-geral' => 'crm', 'crm-dashboards' => 'crm', 'crm-pipeline' => 'crm', 'crm-parceiros' => 'crm',
        'crm-oportunidades' => 'crm', 'crm-atividades' => 'crm', 'crm-configuracoes' => 'crm',
        'est-visao-geral' => 'estoque', 'est-cadastros' => 'estoque', 'est-movimentacoes' => 'estoque',
        'est-custeio' => 'estoque', 'est-relatorios' => 'estoque',
        'fin-visao-geral' => 'financeiro', 'fin-cadastros' => 'financeiro', 'fin-pagar-receber' => 'financeiro',
        'fin-relatorios' => 'financeiro',
        'parceiros-negocio' => 'parceiros', 'qualidade-documentos' => 'qualidade',
        'rh-trein-visao-geral' => 'rh_treinamentos', 'rh-trein-catalogo' => 'rh_treinamentos',
        'rh-trein-dashboards' => 'rh_treinamentos', 'rh-trein-matrizes' => 'rh_treinamentos',
        'rh-trein-avaliacoes' => 'rh_treinamentos', 'rh-trein-notificacoes' => 'rh_treinamentos',
        'proj-projetos' => 'projetos',
        'gp-visao-geral' => 'gestao_pessoas', 'gp-politicas' => 'gestao_pessoas', 'gp-portal' => 'gestao_pessoas',
        'gp-folha' => 'gestao_pessoas', 'gp-desempenho' => 'gestao_pessoas', 'gp-solicitacoes' => 'gestao_pessoas',
        'gp-analytics' => 'gestao_pessoas', 'gp-recrutamento' => 'gestao_pessoas',
        'salas-visao-geral' => 'salas', 'salas-reservas' => 'salas', 'salas-administracao' => 'salas',
        'sac-visao-geral' => 'sac', 'sac-atendimento' => 'sac',
        'lgpd-visao-geral' => 'lgpd', 'lgpd-dashboard' => 'lgpd', 'lgpd-consentimentos' => 'lgpd',
        'lgpd-inventario' => 'lgpd', 'lgpd-aipd' => 'lgpd',
        'pe-estrategico' => 'planejamento', 'rel-visao-geral' => 'relatorios', 'rel-dinamicos' => 'relatorios',
    ];
}

/** @return array<string, string> */
function manualAggregateTopicLabels(): array
{
    return [
        'dashboard-visao-geral' => 'Dashboard — visão geral',
        'adm-visao-geral' => 'Administração — visão geral',
        'adm-configuracoes' => 'Configurações do sistema',
        'adm-logs' => 'Logs e auditoria',
        'adm-permissoes' => 'Permissões — visão geral',
        'adm-treinamentos-obrigatorios' => 'Treinamentos obrigatórios',
        'cad-visao-geral' => 'Cadastro — visão geral',
        'cad-estrutura' => 'Estrutura organizacional',
        'cad-usuarios' => 'Cadastro de usuários',
        'cad-organograma' => 'Organograma',
        'cad-niveis-acesso' => 'Níveis de acesso',
        'com-informativos' => 'Informativos',
        'com-timeline' => 'Timeline',
        'com-eventos' => 'Eventos corporativos',
        'com-gamificacao' => 'Gamificação',
        'crm-dashboards' => 'Dashboards CRM',
        'crm-pipeline' => 'Pipeline de vendas',
        'crm-parceiros' => 'Parceiros CRM',
        'crm-oportunidades' => 'Oportunidades',
        'crm-atividades' => 'Atividades comerciais',
        'crm-configuracoes' => 'Configurações CRM',
        'est-cadastros' => 'Cadastros de estoque',
        'est-movimentacoes' => 'Movimentações de estoque',
        'est-custeio' => 'Custeio',
        'est-relatorios' => 'Relatórios de estoque',
        'fin-cadastros' => 'Cadastros financeiros',
        'fin-pagar-receber' => 'Contas a pagar e receber',
        'fin-relatorios' => 'Relatórios financeiros',
        'parceiros-negocio' => 'Clientes e fornecedores',
        'qualidade-documentos' => 'Documentos (qualidade)',
        'rh-trein-catalogo' => 'Catálogo de treinamentos',
        'rh-trein-dashboards' => 'Dashboards de treinamentos',
        'rh-trein-matrizes' => 'Matrizes de treinamentos',
        'rh-trein-avaliacoes' => 'Avaliações',
        'rh-trein-notificacoes' => 'Notificações de treinamento',
        'proj-projetos' => 'Gestão de projetos',
        'gp-politicas' => 'Políticas internas',
        'gp-portal' => 'Portal do colaborador',
        'gp-folha' => 'Documentos de folha',
        'gp-desempenho' => 'Desempenho',
        'gp-solicitacoes' => 'Solicitações',
        'gp-analytics' => 'People Analytics',
        'gp-recrutamento' => 'Recrutamento',
        'salas-reservas' => 'Reservas de salas',
        'salas-administracao' => 'Administração de salas',
        'sac-atendimento' => 'SAC — atendimento',
        'lgpd-dashboard' => 'Dashboard LGPD',
        'lgpd-consentimentos' => 'Consentimentos LGPD',
        'lgpd-inventario' => 'Inventário LGPD',
        'lgpd-aipd' => 'AIPD / RIPD',
        'pe-estrategico' => 'Planejamento estratégico',
        'rel-dinamicos' => 'Relatórios dinâmicos',
    ];
}

/** @return array<string, string> pasta content → título do módulo */
function manualModuleTitlesByDir(): array
{
    return [
        'dashboard' => 'Dashboard',
        'administracao' => 'Administração',
        'cadastro' => 'Cadastro',
        'comunicacao' => 'Comunicação Interna',
        'crm' => 'CRM',
        'estoque' => 'Estoque',
        'financeiro' => 'Financeiro',
        'parceiros' => 'Parceiros de Negócio',
        'qualidade' => 'Garantia da Qualidade',
        'rh_treinamentos' => 'Gestão de Treinamentos',
        'projetos' => 'Gestão de Projetos',
        'gestao_pessoas' => 'Gestão de Pessoas',
        'salas' => 'Reserva de Salas',
        'sac' => 'SAC',
        'lgpd' => 'LGPD',
        'planejamento' => 'Planejamento Estratégico',
        'relatorios' => 'Relatórios',
    ];
}
