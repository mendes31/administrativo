<?php
// var_dump($this->data['menuPermission']); // DEBUG: Exibe as permissões do menu do usuário
// deploy-sync: 2026-07-20 — Gestão de Pessoas related_routes (destaque de menu)
use App\adms\Models\Repository\AdmsPasswordPolicyRepository;
use App\adms\Helpers\WhistleblowingPublicUrlHelper;

static $admsMenuPasswordPolicyId = null;
if ($admsMenuPasswordPolicyId === null) {
    try {
        $repo = new AdmsPasswordPolicyRepository();
        $policy = $repo->getPolicy();
        $admsMenuPasswordPolicyId = ($policy && isset($policy->id)) ? $policy->id : null;
    } catch (\Throwable $e) {
        $admsMenuPasswordPolicyId = null;
    }
}
$policyId = $admsMenuPasswordPolicyId;

$menus = [
    [
        'id' => 'dashboard',
        'icon' => 'fas fa-tachometer-alt',
        'label' => 'Dashboard',
        'url' => $_ENV['URL_ADM'] . 'dashboard',
        'permission' => 'Dashboard',
        'submenu' => []
    ],
    
    [
        'id' => 'administracao',
        'icon' => 'fa-solid fa-gear',
        'label' => 'Administração',
        'submenu' => (function() use ($policyId) {
            $submenu = [
                [
                    'label' => 'Configurações',
                    'icon' => 'fa-solid fa-sliders',
                    'submenu' => [
                        [
                            'label' => 'Configuração de E-mail',
                            'url' => $_ENV['URL_ADM'] . 'email-config',
                            'permission' => 'EmailConfig'
                        ],
                        [
                            'label' => 'Notificações Automáticas',
                            'url' => $_ENV['URL_ADM'] . 'notification-settings',
                            'permission' => 'NotificationSettings',
                            'icon' => 'fas fa-bell'
                        ],
                        [
                            'label' => 'Calendário',
                            'url' => $_ENV['URL_ADM'] . 'calendar-config',
                            'permission' => 'CalendarConfig',
                            'icon' => 'fa-solid fa-calendar-days'
                        ],
                        [
                            'label' => 'Configuração SAP API',
                            'url' => $_ENV['URL_ADM'] . 'sap-api-config',
                            'permission' => 'SapApiConfig',
                            'icon' => 'fas fa-link'
                        ],
                        [
                            'label' => 'Configuração API MCP',
                            'url' => $_ENV['URL_ADM'] . 'mcp-api-config',
                            'permission' => 'McpApiConfig',
                            'icon' => 'fas fa-robot'
                        ],
                        [
                            'label' => 'Configuração Push (PWA)',
                            'url' => $_ENV['URL_ADM'] . 'push-config',
                            'permission' => 'PushConfig',
                            'icon' => 'fas fa-bell'
                        ],
                        [
                            'label' => 'Configuração de WhatsApp',
                            'url' => $_ENV['URL_ADM'] . 'whats-app-config',
                            'permission' => 'WhatsAppConfig',
                            'icon' => 'fab fa-whatsapp'
                        ],
                        [
                            'label' => 'Política de Senha',
                            'url' => $_ENV['URL_ADM'] . 'password-policy' . ($policyId ? '/' . $policyId : ''),
                            'permission' => 'PasswordPolicy'
                        ],
                        [
                            'label' => 'Filiais',
                            'url' => $_ENV['URL_ADM'] . 'list-branches',
                            'permission' => 'ListBranches'
                        ],
                    ]
                ],             
               
                [
                    'label' => 'Logs',
                    'icon' => 'fa-solid fa-file-alt',
                    'submenu' => [
                        [
                            'label' => 'Log de Acessos',
                            'url' => $_ENV['URL_ADM'] . 'list-log-acessos',
                            'permission' => 'ListLogAcessos'
                        ],
                        [
                            'label' => 'Log de Alterações',
                            'url' => $_ENV['URL_ADM'] . 'list-log-alteracoes',
                            'permission' => 'ListLogAlteracoes'
                        ],
                        [
                            'label' => 'Usuários conectados',
                            'url' => $_ENV['URL_ADM'] . 'list-connected-users',
                            'permission' => 'ListConnectedUsers',
                            'icon' => 'fas fa-user-check'
                        ],
                        [
                            'label' => 'Último acesso',
                            'url' => $_ENV['URL_ADM'] . 'list-users-last-access',
                            'permission' => 'ListUsersLastAccess',
                            'icon' => 'fas fa-clock-rotate-left'
                        ],
                        [
                            'label' => 'Configurações',
                            'url' => $_ENV['URL_ADM'] . 'log-settings',
                            'permission' => 'LogSettings',
                            'icon' => 'fas fa-sliders-h'
                        ],
                    ]
                ],
                [
                    'label' => 'Páginas',
                    'icon' => 'fa-solid fa-layer-group',
                    'submenu' => [
                        [
                            'label' => 'Grupos de Páginas',
                            'url' => $_ENV['URL_ADM'] . 'list-groups-pages',
                            'permission' => 'ListGroupsPages'
                        ],
                        [
                            'label' => 'Pacotes',
                            'url' => $_ENV['URL_ADM'] . 'list-packages',
                            'permission' => 'ListPackages'
                        ],
                        [
                            'label' => 'Páginas',
                            'url' => $_ENV['URL_ADM'] . 'list-pages',
                            'permission' => 'ListPages'
                        ],
                    ]
                ],
                [
                    'label' => 'Base de Dados',
                    'icon' => 'fa-solid fa-database',
                    'submenu' => [
                        [
                            'label' => 'Biblioteca — Sistema',
                            'url' => $_ENV['URL_ADM'] . 'list-database-tables',
                            'permission' => 'ListDatabaseTables',
                            'icon' => 'fa-solid fa-table',
                            'target' => '_blank',
                        ],
                    ]
                ],
               
                
                [
                    'label' => 'Treinamentos Obrigatórios',
                    'url' => $_ENV['URL_ADM'] . 'list-mandatory-trainings',
                    'permission' => 'ListMandatoryTrainings'
                ],
            ];
            return $submenu;
        })(),
    ],
    [
        'id' => 'cadastro',
        'icon' => 'fa-solid fa-folder-plus',
        'label' => 'Cadastro',
        'submenu' => [
            [
                'label' => 'Cargos',
                'url' => $_ENV['URL_ADM'] . 'list-positions',
                'permission' => 'ListPositions',
                'related_routes' => [
                    'list-positions', 'create-position', 'update-position', 'view-position',
                    'delete-position', 'import-positions',
                ],
            ],
            [
                'label' => 'Centros de Custo',
                'url' => $_ENV['URL_ADM'] . 'list-cost-centers',
                'permission' => 'ListCostCenters',
                'related_routes' => [
                    'list-cost-centers', 'create-cost-center', 'update-cost-center', 'view-cost-center',
                    'delete-cost-center', 'import-cost-centers',
                ],
            ],
            [
                'label' => 'Departamentos',
                'url' => $_ENV['URL_ADM'] . 'list-departments',
                'permission' => 'ListDepartments',
                'related_routes' => [
                    'list-departments', 'create-department', 'update-departments', 'view-department',
                    'delete-department', 'import-departments',
                ],
            ],
            [
                'label' => 'Turnos de trabalho',
                'url' => $_ENV['URL_ADM'] . 'list-work-shifts',
                'permission' => 'ListWorkShifts',
                'related_routes' => [
                    'list-work-shifts', 'create-work-shift', 'update-work-shift', 'view-work-shift',
                    'delete-work-shift',
                ],
            ],
            [
                'label' => 'Níveis de Acesso',
                'url' => $_ENV['URL_ADM'] . 'list-access-levels',
                'permission' => 'ListAccessLevels',
                'related_routes' => [
                    'list-access-levels', 'create-access-level', 'update-access-level', 'view-access-level',
                    'delete-access-level', 'import-access-levels', 'access-level-page-sync',
                    'export-access-levels-permissions-pdf', 'export-access-levels-permissions-excel',
                ],
            ],
            [
                'label' => 'Usuários',
                'icon' => 'fa-solid fa-users',
                'submenu' => [
                    [
                        'label' => 'Listar Usuários',
                        'url' => $_ENV['URL_ADM'] . 'list-users',
                        'permission' => 'ListUsers',
                        'related_routes' => [
                            'list-users', 'create-user', 'update-user', 'view-user', 'delete-user',
                            'update-employment-history', 'update-password-user', 'update-user-access-levels',
                            'update-user-image', 'update-user-image-only', 'delete-user-image',
                            'export-users-excel', 'export-users-pdf',
                        ],
                    ],
                    [
                        'label' => 'Importar Usuários',
                        'url' => $_ENV['URL_ADM'] . 'import-users',
                        'permission' => 'ImportUsers',
                        'related_routes' => ['import-users'],
                    ],
                    [
                        'label' => 'Organograma',
                        'url' => $_ENV['URL_ADM'] . 'organization-chart',
                        'permission' => 'OrganizationChart',
                        'related_routes' => ['organization-chart'],
                    ],
                ]
            ],
        ]
    ],
    [
        'id' => 'comunicacao_interna',
        'icon' => 'fa-solid fa-comments',
        'label' => 'Comunicação Interna',
        'submenu' => [
            [
                'label' => 'Informativos',
                'url' => $_ENV['URL_ADM'] . 'list-informativos',
                'permission' => 'ListInformativos'
            ],
            [
                'label' => 'Timeline',
                'url' => $_ENV['URL_ADM'] . 'timeline',
                'permission' => 'Timeline'
            ],
            [
                'label' => 'Eventos corporativos',
                'url' => $_ENV['URL_ADM'] . 'list-company-events',
                'permission' => 'ListCompanyEvents'
            ],
            [
                'label' => 'Moderação da timeline',
                'url' => $_ENV['URL_ADM'] . 'timeline-moderate',
                'permission' => 'TimelineModerate'
            ],
            [
                'label' => 'Gamificação',
                'icon' => 'fas fa-gamepad',
                'submenu' => [
                    [
                        'label' => 'Regras (timeline)',
                        'url' => $_ENV['URL_ADM'] . 'list-gamification-timeline-rules',
                        'permission' => 'ListGamificationTimelineRules',
                        'icon' => 'fas fa-sliders-h'
                    ],
                    [
                        'label' => 'Quizzes (gestão)',
                        'url' => $_ENV['URL_ADM'] . 'list-gamification-quizzes',
                        'permission' => 'ListGamificationQuizzes',
                        'icon' => 'fas fa-question-circle'
                    ],
                    [
                        'label' => 'Extrato de pontos',
                        'url' => $_ENV['URL_ADM'] . 'list-gamification-point-ledger',
                        'permission' => 'ListGamificationPointLedger',
                        'icon' => 'fas fa-coins'
                    ],
                    [
                        'label' => 'Quizzes disponíveis',
                        'url' => $_ENV['URL_ADM'] . 'gamification-quiz-catalog',
                        'permission' => 'GamificationQuizCatalog',
                        'icon' => 'fas fa-puzzle-piece'
                    ],
                    [
                        'label' => 'Ranking de pontos',
                        'url' => $_ENV['URL_ADM'] . 'gamification-leaderboard',
                        'permission' => 'GamificationLeaderboard',
                        'icon' => 'fas fa-trophy'
                    ],
                    [
                        'label' => 'Dashboard RH de engajamento',
                        'url' => $_ENV['URL_ADM'] . 'gamification-engagement-dashboard',
                        'permission' => 'GamificationEngagementDashboard',
                        'icon' => 'fas fa-chart-line'
                    ],
                ]
            ],
        ]
    ],
    [
        'id' => 'crm',
        'icon' => 'fa-solid fa-handshake',
        'label' => 'CRM',
        'submenu' => [
            [
                'label' => 'Dashboard CRM',
                'url' => $_ENV['URL_ADM'] . 'crm-dashboard',
                'permission' => 'CrmDashboard',
                'icon' => 'fas fa-chart-pie'
            ],
            [
                'label' => 'Dashboard Gerencial',
                'url' => $_ENV['URL_ADM'] . 'crm-manager-dashboard',
                'permission' => 'CrmManagerDashboard',
                'icon' => 'fas fa-chart-line'
            ],
            [
                'label' => 'Pipeline de Vendas',
                'url' => $_ENV['URL_ADM'] . 'crm-kanban-pipeline',
                'permission' => 'CrmKanbanPipeline',
                'icon' => 'fas fa-chart-line'
            ],
            [
                'label' => 'Parceiros CRM',
                'icon' => 'fa-solid fa-users',
                'submenu' => [
                    [
                        'label' => 'Listar Parceiros',
                        'url' => $_ENV['URL_ADM'] . 'crm-list-partners',
                        'permission' => 'CrmListPartners'
                    ],
                ]
            ],
               [
                   'label' => 'Oportunidades',
                   'icon' => 'fa-solid fa-bullseye',
                   'submenu' => [
                       [
                           'label' => 'Listar Oportunidades',
                           'url' => $_ENV['URL_ADM'] . 'crm-list-opportunities',
                           'permission' => 'CrmListOpportunities'
                       ],
                   ]
               ],
               [
                   'label' => 'Atividades',
                   'icon' => 'fa-solid fa-calendar-check',
                   'submenu' => [
                       [
                           'label' => 'Agenda de Atividades',
                           'url' => $_ENV['URL_ADM'] . 'crm-list-activities',
                           'permission' => 'CrmListActivities'
                       ],
                   ]
               ],
               [
                   'label' => 'Configurações',
                   'icon' => 'fa-solid fa-cog',
                   'submenu' => [
                       [
                           'label' => 'Gerenciar Tags',
                           'url' => $_ENV['URL_ADM'] . 'crm-list-tags',
                           'permission' => 'CrmListTags'
                       ],
                       [
                           'label' => 'Campos Customizáveis',
                           'url' => $_ENV['URL_ADM'] . 'crm-list-custom-fields',
                           'permission' => 'CrmListCustomFields',
                           'icon' => 'fa-solid fa-sliders-h'
                       ],
                       [
                           'label' => 'Automações',
                           'url' => $_ENV['URL_ADM'] . 'crm-list-automations',
                           'permission' => 'CrmListAutomations',
                           'icon' => 'fa-solid fa-robot'
                       ],
                   ]
               ],
        ]
    ],
    [
        'id' => 'estoque',
        'icon' => 'fa-solid fa-boxes-stacked',
        'label' => 'Estoque',
        'submenu' => [
            [
                'label' => 'Itens',
                'url' => $_ENV['URL_ADM'] . 'list-inventory-items',
                'permission' => 'ListInventoryItems',
                'any_of' => ['ListInventoryItems', 'ViewInventoryItem', 'UpdateInventoryItem', 'CreateInventoryItem', 'SimulateInventoryCost'],
                'related_routes' => [
                    'list-inventory-items', 'view-inventory-item', 'update-inventory-item', 'create-inventory-item',
                    'delete-inventory-item', 'simulate-inventory-cost', 'save-inventory-cost-simulation',
                    'export-inventory-cost-simulation-pdf',
                ],
            ],
            [
                'label' => 'Lotes Produzidos',
                'url' => $_ENV['URL_ADM'] . 'list-inventory-cost-production-batches',
                'permission' => 'ListInvCostProductionBatches',
                'related_routes' => ['list-inventory-cost-production-batches'],
            ],
            [
                'label' => 'Períodos de Custeio',
                'url' => $_ENV['URL_ADM'] . 'list-inventory-cost-periods',
                'permission' => 'ListInvCostPeriods',
                'any_of' => [
                    'ListInvCostPeriods', 'ViewInvCostPeriod', 'UpdateInvCostPeriod', 'CreateInvCostPeriod',
                    'ImportInvCostDre', 'SaveInvCostAllocationRules', 'SaveInvCostPeriodItems',
                ],
                'related_routes' => [
                    'list-inventory-cost-periods', 'view-inventory-cost-period', 'update-inventory-cost-period',
                    'create-inventory-cost-period', 'delete-inventory-cost-period', 'import-inventory-cost-dre',
                    'save-inventory-cost-allocation-rules', 'save-inventory-cost-scenario-production',
                    'download-inventory-cost-dre-template', 'export-inventory-cost-period-sku-results',
                ],
            ],
            [
                'label' => 'Cadastros Bases',
                'icon' => 'fa-solid fa-database',
                'submenu' => [
                    [
                        'label' => 'Unidades de Medida',
                        'url' => $_ENV['URL_ADM'] . 'list-inventory-units',
                        'permission' => 'ListInventoryUnits',
                        'related_routes' => ['list-inventory-units', 'create-inventory-unit', 'update-inventory-unit', 'delete-inventory-unit'],
                    ],
                    [
                        'label' => 'Categorias de Item',
                        'url' => $_ENV['URL_ADM'] . 'list-inventory-categories',
                        'permission' => 'ListInventoryCategories',
                        'related_routes' => ['list-inventory-categories', 'create-inventory-category', 'update-inventory-category', 'delete-inventory-category'],
                    ],
                    [
                        'label' => 'Estoques',
                        'url' => $_ENV['URL_ADM'] . 'list-inventory-stocks',
                        'permission' => 'ListInventoryStocks',
                        'related_routes' => ['list-inventory-stocks', 'create-inventory-stock', 'update-inventory-stock', 'delete-inventory-stock'],
                    ],
                    [
                        'label' => 'Posições Internas',
                        'url' => $_ENV['URL_ADM'] . 'list-inventory-positions',
                        'permission' => 'ListInventoryPositions',
                        'related_routes' => ['list-inventory-positions', 'create-inventory-position', 'update-inventory-position', 'delete-inventory-position'],
                    ],
                    [
                        'label' => 'Operações de Produção',
                        'url' => $_ENV['URL_ADM'] . 'list-inventory-operations',
                        'permission' => 'ListInventoryOperations',
                        'related_routes' => ['list-inventory-operations', 'create-inventory-operation', 'update-inventory-operation', 'delete-inventory-operation'],
                    ],
                    [
                        'label' => 'Recursos de Produção',
                        'url' => $_ENV['URL_ADM'] . 'list-inventory-production-resources',
                        'permission' => 'ListInventoryProductionResources',
                        'related_routes' => [
                            'list-inventory-production-resources', 'create-inventory-production-resource',
                            'update-inventory-production-resource', 'delete-inventory-production-resource',
                        ],
                    ],
                    [
                        'label' => 'Papéis de MO',
                        'url' => $_ENV['URL_ADM'] . 'list-inventory-labor-roles',
                        'permission' => 'ListInventoryLaborRoles',
                        'related_routes' => [
                            'list-inventory-labor-roles', 'create-inventory-labor-role',
                            'update-inventory-labor-role', 'delete-inventory-labor-role',
                        ],
                    ],
                    [
                        'label' => 'Classes HVAC (crit. 8)',
                        'url' => $_ENV['URL_ADM'] . 'list-inventory-energy-class-factors',
                        'permission' => 'ListInvEnergyClassFactors',
                        'related_routes' => ['list-inventory-energy-class-factors', 'save-inventory-energy-class-factors'],
                    ],
                    [
                        'label' => 'Complexidade (crit. 4/6)',
                        'url' => $_ENV['URL_ADM'] . 'list-inventory-complexity-level-factors',
                        'permission' => 'ListInvComplexityLevelFactors',
                        'related_routes' => ['list-inventory-complexity-level-factors', 'save-inventory-complexity-level-factors'],
                    ],
                ]
            ],
            [
                'label' => 'Transações de Estoque',
                'submenu' => [
                    [
                        'label' => 'Ajuste',
                        'url' => $_ENV['URL_ADM'] . 'create-inventory-adjust',
                        'permission' => 'CreateInventoryAdjust',
                        'related_routes' => ['create-inventory-adjust'],
                    ],
                    [
                        'label' => 'Entrada',
                        'url' => $_ENV['URL_ADM'] . 'create-inventory-entry',
                        'permission' => 'CreateInventoryEntry',
                        'related_routes' => ['create-inventory-entry'],
                    ],
                    [
                        'label' => 'Saída',
                        'url' => $_ENV['URL_ADM'] . 'create-inventory-exit',
                        'permission' => 'CreateInventoryExit',
                        'related_routes' => ['create-inventory-exit'],
                    ],
                    [
                        'label' => 'Transferência',
                        'url' => $_ENV['URL_ADM'] . 'create-inventory-transfer',
                        'permission' => 'CreateInventoryTransfer',
                        'related_routes' => ['create-inventory-transfer'],
                    ],
                    
                ]
            ],
            [
                'label' => 'Relatórios',
                'submenu' => [
                    [
                        'label' => 'Saldos de Estoque',
                        'url' => $_ENV['URL_ADM'] . 'report-inventory-balance',
                        'permission' => 'ReportInventoryBalance',
                        'related_routes' => ['report-inventory-balance', 'export-inventory-balance-csv'],
                    ],
                    [
                        'label' => 'Histórico de Movimentações',
                        'url' => $_ENV['URL_ADM'] . 'report-inventory-history',
                        'permission' => 'ReportInventoryHistory',
                        'related_routes' => ['report-inventory-history', 'export-inventory-history-csv'],
                    ],
                ]
            ],
        ]
    ],
    [
        'id' => 'financeiro',
        'icon' => 'fa-solid fa-coins',
        'label' => 'Financeiro',
        'submenu' => [
            [
                'label' => 'Bancos',
                'url' => $_ENV['URL_ADM'] . 'list-banks',
                'permission' => 'ListBanks'
            ],
            [
                'label' => 'Bancos - Transferência entre Contas',
                'url' => $_ENV['URL_ADM'] . 'list-mov-between-accounts',
                'permission' => 'ListMovBetweenAccounts'
            ],
            [
                'label' => 'Frequências',
                'url' => $_ENV['URL_ADM'] . 'list-frequencies',
                'permission' => 'ListFrequencies'
            ],
            [
                'label' => 'Formas de Pagamento',
                'url' => $_ENV['URL_ADM'] . 'list-payment-methods',
                'permission' => 'ListPaymentMethods'
            ],
            [
                'label' => 'Pagar',
                'url' => $_ENV['URL_ADM'] . 'list-payments',
                'permission' => 'ListPayments'
            ],
            [
                'label' => 'Plano de Contas',
                'url' => $_ENV['URL_ADM'] . 'list-accounts-plan',
                'permission' => 'ListAccountsPlan'
            ],
            [
                'label' => 'Receber',
                'url' => $_ENV['URL_ADM'] . 'list-receipts',
                'permission' => 'ListReceipts'
            ],
            [
                'label' => 'Rel Centro de Custo',
                'url' => $_ENV['URL_ADM'] . 'cost-center-summary',
                'permission' => 'CostCenterSummary'
            ],
            [
                'label' => 'Rel Extrato Caixa',
                'url' => $_ENV['URL_ADM'] . 'movements',
                'permission' => 'Movements'
            ],
            [
                'label' => 'Rel Fluxo de Caixa Diário',
                'url' => $_ENV['URL_ADM'] . 'cash-flow',
                'permission' => 'CashFlow'
            ],
            [
                'label' => 'Relatório Resumo Financeiro',
                'url' => $_ENV['URL_ADM'] . 'flow-cash-competence',
                'permission' => 'FlowCashCompetence'
            ],
        ]
    ],
    [
        'id' => 'parceiros',
        'icon' => 'fa-solid fa-handshake-simple',
        'label' => 'Parceiros de Negócio',
        'submenu' => [
            [
                'label' => 'Clientes',
                'url' => $_ENV['URL_ADM'] . 'list-customers',
                'permission' => 'ListCustomers'
            ],
            [
                'label' => 'Fornecedores',
                'url' => $_ENV['URL_ADM'] . 'list-suppliers',
                'permission' => 'ListSuppliers'
            ],
        ]
    ],
    [
        'id' => 'garantia',
        'icon' => 'fa-solid fa-coins',
        'label' => 'Garantia da Qualidade',
        'submenu' => [
            [
                'label' => 'Documentos',
                'url' => $_ENV['URL_ADM'] . 'list-documents',
                'permission' => 'ListDocuments'
            ],
        ]
    ],
    [
        'id' => 'gestao_treinamentos',
        'icon' => 'fa-solid fa-chalkboard-teacher',
        'label' => 'Gestão de Treinamentos',
        'submenu' => [
            [
                'label' => 'Cadastrar Treinamentos',
                'url' => $_ENV['URL_ADM'] . 'list-trainings',
                'permission' => 'ListTrainings'
            ],
            [
                'label' => 'Dashboard de KPIs',
                'url' => $_ENV['URL_ADM'] . 'training-kpi-dashboard',
                'permission' => 'TrainingKpiDashboard'
            ],
            [
                'label' => 'Dashboard de Necessidades',
                'url' => $_ENV['URL_ADM'] . 'training-compliance-dashboard',
                'permission' => 'TrainingComplianceDashboard'
            ],
            [
                'label' => 'Matriz por Colaborador',
                'url' => $_ENV['URL_ADM'] . 'matrix-by-user',
                'permission' => 'MatrixByUser'
            ],
            [
                'label' => 'Matriz de Treinamentos Realizados',
                'url' => $_ENV['URL_ADM'] . 'completed-trainings-matrix',
                'permission' => 'CompletedTrainingsMatrix'
            ],
            [
                'label' => 'Status de Treinamentos',
                'url' => $_ENV['URL_ADM'] . 'list-training-status',
                'permission' => 'ListTrainingStatus'
            ],
            [
                'label' => 'Testar Notificações',
                'url' => $_ENV['URL_ADM'] . 'test-notification',
                'permission' => 'TestNotification'
            ],
            [
                'label' => 'Avaliações e Questionários',
                'icon' => 'fa-solid fa-clipboard-question',
                'submenu' => [
                    [
                        'label' => 'Minhas Avaliações',
                        'url' => $_ENV['URL_ADM'] . 'my-evaluations',
                        'permission' => 'MyEvaluations',
                        'icon' => 'fas fa-user-check'
                    ],
                    [
                        'label' => 'Gerenciar Questionários',
                        'icon' => 'fa-solid fa-folder-open',
                        'submenu' => [
                            [
                                'label' => 'Criar Questionário Completo',
                                'url' => $_ENV['URL_ADM'] . 'create-evaluation-model-with-questions',
                                'permission' => 'CreateEvaluationModelWithQuestions'
                            ],
                            [
                                'label' => 'Listar Modelos',
                                'url' => $_ENV['URL_ADM'] . 'list-evaluation-models',
                                'permission' => 'ListEvaluationModels'
                            ]
                        ]
                    ],
                    [
                        'label' => 'Atribuições',
                        'icon' => 'fa-solid fa-user-graduate',
                        'submenu' => [
                            [
                                'label' => 'Atribuir Avaliação',
                                'url' => $_ENV['URL_ADM'] . 'assign-evaluation',
                                'permission' => 'AssignEvaluation'
                            ],
                            [
                                'label' => 'Listar Atribuições',
                                'url' => $_ENV['URL_ADM'] . 'list-evaluation-assignments',
                                'permission' => 'ListEvaluationAssignments'
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ],
    [
        'id' => 'gestao_projetos',
        'icon' => 'fa-solid fa-diagram-project',
        'label' => 'Gestão de Projetos',
        'submenu' => [
            [
                'label' => 'Projetos',
                'url' => $_ENV['URL_ADM'] . 'list-projects',
                'permission' => 'ListProjects'
            ],
            [
                'label' => 'Etapas',
                'url' => $_ENV['URL_ADM'] . 'list-project-stages',
                'permission' => 'ListProjectStages'
            ],
            [
                'label' => 'Grupos de Etapas',
                'url' => $_ENV['URL_ADM'] . 'list-stage-groups',
                'permission' => 'ListStageGroups'
            ],
        ]
    ],
    [
        'id' => 'gestao_pessoas',
        'icon' => 'fa-solid fa-users-gear',
        'label' => 'Gestão de Pessoas',
        'submenu' => [
            [
                'label' => 'Políticas Internas',
                'icon'  => 'fa-solid fa-file-contract',
                'url'   => $_ENV['URL_ADM'] . 'list-policies',
                'permission' => 'ListPolicies',
                'related_routes' => [
                    'list-policies', 'create-policy', 'view-policy', 'update-policy',
                    'delete-policy', 'relatorio-policy',
                ],
            ],
            [
                'label' => 'Categorias de Políticas',
                'icon'  => 'fa-solid fa-tags',
                'url'   => $_ENV['URL_ADM'] . 'list-policy-categories',
                'permission' => 'ListPolicyCategories',
                'related_routes' => [
                    'list-policy-categories', 'create-policy-category',
                    'update-policy-category', 'delete-policy-category',
                ],
            ],
            [
                'label' => 'Portal do Colaborador',
                'url' => $_ENV['URL_ADM'] . 'employee-portal',
                'permission' => 'EmployeePortal',
                'icon' => 'fas fa-user-circle',
                'related_routes' => ['employee-portal'],
            ],
            [
                'label' => 'Meus documentos (folha)',
                'url' => $_ENV['URL_ADM'] . 'my-payroll-documents',
                'permission' => 'MyPayrollDocuments',
                'icon' => 'fas fa-file-invoice-dollar',
                'related_routes' => [
                    'my-payroll-documents', 'sign-payroll-document',
                    'confirm-payroll-document-download',
                ],
            ],
            [
                'label' => 'Meus EPIs',
                'url' => $_ENV['URL_ADM'] . 'my-epi-deliveries',
                'permission' => 'MyEpiDeliveries',
                'icon' => 'fas fa-hard-hat',
                'related_routes' => ['my-epi-deliveries', 'sign-epi-ficha'],
            ],
            [
                'label' => 'Meus treinamentos SST',
                'url' => $_ENV['URL_ADM'] . 'my-sst-treinamentos',
                'permission' => 'MySstTreinamentos',
                'icon' => 'fas fa-graduation-cap',
                'related_routes' => ['my-sst-treinamentos'],
            ],
            [
                'label' => 'Importar documentos RH (PDF)',
                'url' => $_ENV['URL_ADM'] . 'import-payroll-documents',
                'permission' => 'ImportPayrollDocuments',
                'icon' => 'fas fa-file-pdf',
                'related_routes' => [
                    'import-payroll-documents', 'payroll-import-batch-audit',
                    'payroll-import-batch-report',
                ],
            ],
            [
                'label' => 'Pendências de ciência (folha)',
                'url' => $_ENV['URL_ADM'] . 'list-payroll-signing-pendencies',
                'permission' => 'ListPayrollSigningPendencies',
                'icon' => 'fas fa-user-clock',
                'related_routes' => ['list-payroll-signing-pendencies'],
            ],
            [
                'label' => 'Cron lembretes folha (token)',
                'url' => $_ENV['URL_ADM'] . 'payroll-cron-config',
                'permission' => 'PayrollCronConfig',
                'icon' => 'fas fa-clock',
                'related_routes' => ['payroll-cron-config'],
            ],
            [
                'label' => 'Tipos de documento (RH)',
                'url' => $_ENV['URL_ADM'] . 'list-payroll-document-types',
                'permission' => 'ListPayrollDocumentTypes',
                'icon' => 'fas fa-tags',
                'related_routes' => [
                    'list-payroll-document-types', 'create-payroll-document-type',
                    'update-payroll-document-type', 'delete-payroll-document-type',
                ],
            ],
            [
                'label' => 'Desempenho',
                'icon' => 'fa-solid fa-chart-line',
                'submenu' => [
                    [
                        'label' => 'Ciclos',
                        'url' => $_ENV['URL_ADM'] . 'list-performance-cycles',
                        'permission' => 'ListPerformanceCycles',
                        'related_routes' => [
                            'list-performance-cycles', 'create-performance-cycle',
                            'view-performance-cycle', 'update-performance-cycle',
                            'bulk-create-performance-reviews',
                        ],
                    ],
                    [
                        'label' => 'Calibração',
                        'url' => $_ENV['URL_ADM'] . 'list-performance-calibrations',
                        'permission' => 'ListPerformanceCalibrations',
                        'related_routes' => [
                            'list-performance-calibrations', 'create-performance-calibration',
                            'view-performance-calibration', 'update-performance-calibration',
                        ],
                    ],
                    [
                        'label' => 'Avaliações de Desempenho',
                        'url' => $_ENV['URL_ADM'] . 'list-performance-reviews',
                        'permission' => 'ListPerformanceReviews',
                        'related_routes' => [
                            'list-performance-reviews', 'create-performance-review',
                            'view-performance-review', 'update-performance-review',
                            'delete-performance-review', 'record-review-results',
                        ],
                    ],
                    [
                        'label' => 'Metas (OKRs)',
                        'url' => $_ENV['URL_ADM'] . 'list-performance-goals',
                        'permission' => 'ListPerformanceGoals',
                        'related_routes' => [
                            'list-performance-goals', 'create-performance-goal',
                            'view-performance-goal', 'update-performance-goal',
                            'delete-performance-goal',
                        ],
                    ],
                    [
                        'label' => 'PDI',
                        'url' => $_ENV['URL_ADM'] . 'list-pdi-plans',
                        'permission' => 'ListPdiPlans',
                        'related_routes' => [
                            'list-pdi-plans', 'create-pdi-plan',
                            'view-pdi-plan', 'update-pdi-plan',
                        ],
                    ],
                    [
                        'label' => 'Feedbacks',
                        'url' => $_ENV['URL_ADM'] . 'list-performance-feedbacks',
                        'permission' => 'ListPerformanceFeedbacks',
                        'related_routes' => [
                            'list-performance-feedbacks', 'create-performance-feedback',
                            'view-performance-feedback', 'update-performance-feedback',
                            'delete-performance-feedback',
                        ],
                    ],
                    [
                        'label' => 'Competências',
                        'url' => $_ENV['URL_ADM'] . 'list-competencies',
                        'permission' => 'ListCompetencies',
                        'related_routes' => [
                            'list-competencies', 'create-competency',
                            'view-competency', 'update-competency', 'delete-competency',
                        ],
                    ],
                    [
                        'label' => 'Matriz de Competências',
                        'url' => $_ENV['URL_ADM'] . 'competency-matrix',
                        'permission' => 'CompetencyMatrix',
                        'related_routes' => ['competency-matrix'],
                    ],
                    [
                        'label' => 'Matriz 9BOX',
                        'url' => $_ENV['URL_ADM'] . 'nine-box-matrix',
                        'permission' => 'NineBoxMatrix',
                        'related_routes' => [
                            'nine-box-matrix', 'export-nine-box-matrix-pdf',
                            'export-nine-box-matrix-excel',
                        ],
                    ],
                    [
                        'label' => 'Talent Pool',
                        'url' => $_ENV['URL_ADM'] . 'list-talent-nominations',
                        'permission' => 'ListTalentNominations',
                        'related_routes' => [
                            'list-talent-nominations', 'create-talent-nomination',
                            'view-talent-nomination', 'update-talent-nomination',
                        ],
                    ],
                    [
                        'label' => 'Sucessão',
                        'url' => $_ENV['URL_ADM'] . 'list-critical-positions',
                        'permission' => 'ListCriticalPositions',
                        'related_routes' => [
                            'list-critical-positions', 'create-critical-position',
                            'view-critical-position', 'update-critical-position',
                        ],
                    ],
                    [
                        'label' => 'Trilhas de Carreira',
                        'url' => $_ENV['URL_ADM'] . 'list-career-tracks',
                        'permission' => 'ListCareerTracks',
                        'related_routes' => [
                            'list-career-tracks', 'create-career-track',
                            'view-career-track', 'update-career-track',
                        ],
                    ],
                    [
                        'label' => 'Promoções',
                        'url' => $_ENV['URL_ADM'] . 'list-career-promotions',
                        'permission' => 'ListCareerPromotions',
                        'related_routes' => [
                            'list-career-promotions', 'create-career-promotion',
                            'view-career-promotion', 'update-career-promotion',
                        ],
                    ],
                    [
                        'label' => 'Dashboard de Desempenho',
                        'url' => $_ENV['URL_ADM'] . 'performance-dashboard',
                        'permission' => 'PerformanceDashboard',
                        'related_routes' => ['performance-dashboard'],
                    ],
                ]
            ],
            [
                'label' => 'Solicitações',
                'icon' => 'fa-solid fa-file-alt',
                'submenu' => [
                    [
                        'label' => 'Minhas Solicitações',
                        'url' => $_ENV['URL_ADM'] . 'list-employee-requests',
                        'permission' => 'ListEmployeeRequests',
                        'related_routes' => [
                            'list-employee-requests', 'create-employee-request',
                            'view-employee-request', 'update-employee-request',
                        ],
                    ],
                    [
                        'label' => 'Aprovações Pendentes',
                        'url' => $_ENV['URL_ADM'] . 'pending-approvals',
                        'permission' => 'PendingApprovals',
                        'badge' => true,
                        'related_routes' => [
                            'pending-approvals', 'list-pending-hr-approvals',
                            'list-pending-manager-approvals',
                        ],
                    ],
                    [
                        'label' => 'Tipos de Solicitação',
                        'url' => $_ENV['URL_ADM'] . 'list-request-types',
                        'permission' => 'ListRequestTypes',
                        'related_routes' => [
                            'list-request-types', 'create-request-type',
                            'update-request-type', 'delete-request-type',
                        ],
                    ],
                ]
            ],
            [
                'label' => 'Chamados',
                'icon' => 'fa-solid fa-ticket-alt',
                'submenu' => [
                    [
                        'label' => 'Meus Chamados',
                        'url' => $_ENV['URL_ADM'] . 'list-employee-tickets',
                        'permission' => 'ListEmployeeTickets',
                        'related_routes' => [
                            'list-employee-tickets', 'create-employee-ticket',
                            'view-employee-ticket', 'update-employee-ticket',
                        ],
                    ],
                ]
            ],
            [
                'label' => 'People Analytics',
                'icon' => 'fa-solid fa-chart-bar',
                'submenu' => [
                    [
                        'label' => 'Dashboard Analytics',
                        'url' => $_ENV['URL_ADM'] . 'people-analytics',
                        'permission' => 'PeopleAnalytics',
                        'related_routes' => ['people-analytics'],
                    ],
                    [
                        'label' => 'Relatórios de RH',
                        'url' => $_ENV['URL_ADM'] . 'people-reports',
                        'permission' => 'PeopleReports',
                        'related_routes' => ['people-reports'],
                    ],
                    [
                        'label' => 'Pesquisas (Pulse/eNPS)',
                        'url' => $_ENV['URL_ADM'] . 'list-pulse-campaigns',
                        'permission' => 'ListPulseCampaigns',
                        'related_routes' => [
                            'list-pulse-campaigns', 'create-pulse-campaign',
                            'view-pulse-campaign', 'update-pulse-campaign',
                            'respond-pulse-campaign',
                        ],
                    ],
                ]
            ],
            [
                'label' => 'Recrutamento / Currículos',
                'icon'  => 'fa-solid fa-address-card',
                'submenu' => [
                    [
                        'label' => 'Dashboard de Recrutamento',
                        'url' => $_ENV['URL_ADM'] . 'rh-kpi-dashboard',
                        'permission' => 'RhKpiDashboard',
                        'icon' => 'fas fa-chart-pie',
                        'related_routes' => ['rh-kpi-dashboard'],
                    ],
                    [
                        'label' => 'Currículos / Candidatos',
                        'url' => $_ENV['URL_ADM'] . 'rh-candidatos',
                        'permission' => 'RhCandidatos',
                        'icon' => 'fas fa-user-tie',
                        'related_routes' => [
                            'rh-candidatos', 'rh-candidatos-create', 'rh-candidatos-view',
                            'rh-candidatos-edit', 'rh-candidatos-vagas',
                            'rh-ofertas-create', 'rh-ofertas-view', 'rh-ofertas-convert',
                        ],
                    ],
                    [
                        'label' => 'Vagas de Emprego',
                        'url' => $_ENV['URL_ADM'] . 'rh-vagas',
                        'permission' => 'RhVagas',
                        'icon' => 'fas fa-briefcase',
                        'related_routes' => [
                            'rh-vagas', 'rh-vagas-create', 'rh-vagas-view', 'rh-vagas-edit',
                            'rh-vagas-pipeline', 'rh-vagas-candidatos',
                        ],
                    ],
                    [
                        'label' => 'Portal de Vagas (CAPTCHA)',
                        'url' => $_ENV['URL_ADM'] . 'rh-vagas-publicas-config',
                        'permission' => 'RhVagasPublicasConfig',
                        'icon' => 'fas fa-shield-alt',
                        'related_routes' => ['rh-vagas-publicas-config'],
                    ],
                    [
                        'label' => 'Requisições de Pessoal',
                        'url' => $_ENV['URL_ADM'] . 'rh-personnel-requests',
                        'permission' => 'RhPersonnelRequests',
                        'icon' => 'fas fa-user-plus',
                        'related_routes' => [
                            'rh-personnel-requests', 'rh-personnel-requests-create',
                            'rh-personnel-requests-view',
                        ],
                    ],
                    [
                        'label' => 'Entrevistas',
                        'url' => $_ENV['URL_ADM'] . 'rh-entrevistas',
                        'permission' => 'RhEntrevistas',
                        'icon' => 'fas fa-calendar-alt',
                        'related_routes' => [
                            'rh-entrevistas', 'rh-entrevistas-create',
                            'rh-entrevistas-view', 'rh-entrevistas-edit',
                        ],
                    ],
                    [
                        'label' => 'Movimentações',
                        'url' => $_ENV['URL_ADM'] . 'rh-movimentacoes',
                        'permission' => 'RhMovimentacoes',
                        'icon' => 'fas fa-people-arrows',
                        'related_routes' => [
                            'rh-movimentacoes', 'rh-movimentacoes-create', 'rh-movimentacoes-view',
                        ],
                    ],
                    [
                        'label' => 'Offboarding',
                        'url' => $_ENV['URL_ADM'] . 'rh-offboardings',
                        'permission' => 'RhOffboardings',
                        'icon' => 'fas fa-user-minus',
                        'related_routes' => [
                            'rh-offboardings', 'rh-offboardings-create', 'rh-offboardings-view',
                        ],
                    ],
                    [
                        'label' => 'Pessoas',
                        'url' => $_ENV['URL_ADM'] . 'rh-pessoas',
                        'permission' => 'RhPessoas',
                        'icon' => 'fas fa-id-card',
                        'related_routes' => [
                            'rh-pessoas', 'rh-pessoas-view',
                            'rh-onboarding-view', 'rh-experiencia-view',
                        ],
                    ],
                ]
            ],
        ]
    ],
    [
        'id' => 'reserva_salas',
        'icon' => 'fa-solid fa-door-open',
        'label' => 'Reserva de Salas',
        'submenu' => [
            [
                'label' => 'Calendário',
                'url' => $_ENV['URL_ADM'] . 'room-calendar',
                'permission' => 'RoomCalendar',
                'any_of' => ['RoomCalendar', 'ListMeetingRooms', 'BookRoom'],
                'icon' => 'fas fa-calendar'
            ],
            [
                'label' => 'Salas',
                'icon' => 'fa-solid fa-door-open',
                'submenu' => [
                    [
                        'label' => 'Listar Salas',
                        'url' => $_ENV['URL_ADM'] . 'list-meeting-rooms',
                        'permission' => 'ListMeetingRooms',
                        'any_of' => ['ListMeetingRooms', 'ViewMeetingRoom', 'UpdateMeetingRoom', 'DeleteMeetingRoom'],
                    ],
                    [
                        'label' => 'Criar Sala',
                        'url' => $_ENV['URL_ADM'] . 'create-meeting-room',
                        'permission' => 'CreateMeetingRoom'
                    ],
                ]
            ],
            [
                'label' => 'Reservas',
                'icon' => 'fa-solid fa-calendar-check',
                'submenu' => [
                    [
                        'label' => 'Todas as Reservas',
                        'url' => $_ENV['URL_ADM'] . 'list-bookings',
                        'permission' => 'ListBookings',
                        'any_of' => ['ListBookings', 'ViewBooking', 'CreateBooking', 'UpdateBooking', 'CancelBooking'],
                    ],
                    [
                        'label' => 'Lista de Espera',
                        'url' => $_ENV['URL_ADM'] . 'booking-waitlist',
                        'permission' => 'BookingWaitlist'
                    ],
                ]
            ],
            [
                'label' => 'Solicitações',
                'icon' => 'fa-solid fa-list',
                'submenu' => [
                    [
                        'label' => 'Solicitações',
                        'url' => $_ENV['URL_ADM'] . 'rooms-list-service-requests',
                        'permission' => 'RoomsListServiceRequests',
                        'icon' => 'fas fa-clipboard-list'
                    ],
                    [
                        'label' => 'Tipos (Salas)',
                        'url' => $_ENV['URL_ADM'] . 'rooms-list-request-types',
                        'permission' => 'RoomsListRequestTypes'
                    ],
                    [
                        'label' => 'Equipes / Grupos',
                        'url' => $_ENV['URL_ADM'] . 'rooms-list-request-groups',
                        'permission' => 'RoomsListRequestGroups',
                        'icon' => 'fas fa-users'
                    ],
                ]
            ],
            [
                'label' => 'Administrativo',
                'icon' => 'fa-solid fa-cog',
                'submenu' => [
                    [
                        'label' => 'Dashboard',
                        'url' => $_ENV['URL_ADM'] . 'admin-booking-dashboard',
                        'permission' => 'AdminBookingDashboard'
                    ],
                    [
                        'label' => 'Integração calendário',
                        'url' => $_ENV['URL_ADM'] . 'rooms-calendar-integration-settings',
                        'permission' => 'RoomsCalendarIntegrationSettings'
                    ],
                    [
                        'label' => 'Relatórios',
                        'url' => $_ENV['URL_ADM'] . 'booking-reports',
                        'permission' => 'BookingReports'
                    ],
                ]
            ],
        ]
    ],
    [
        'id' => 'sac',
        'icon' => 'fa-solid fa-headset',
        'label' => 'SAC',
        'submenu' => [
            [
                'label' => 'Dashboard',
                'url' => $_ENV['URL_ADM'] . 'sac-dashboard',
                'permission' => 'SacDashboard',
                'icon' => 'fas fa-chart-line'
            ],
            [
                'label' => 'Chamados',
                'url' => $_ENV['URL_ADM'] . 'sac-list-tickets',
                'permission' => 'SacListTickets',
                'icon' => 'fas fa-ticket-alt'
            ],
            [
                'label' => 'Clientes',
                'url' => $_ENV['URL_ADM'] . 'sac-list-clients',
                'permission' => 'SacListClients',
                'icon' => 'fas fa-user-tie'
            ],
            [
                'label' => 'Categorias',
                'url' => $_ENV['URL_ADM'] . 'sac-list-categories',
                'permission' => 'SacListCategories',
                'icon' => 'fas fa-tags'
            ],
            [
                'label' => 'Regras de SLA',
                'url' => $_ENV['URL_ADM'] . 'sac-list-sla-rules',
                'permission' => 'SacListSlaRules',
                'icon' => 'fas fa-stopwatch'
            ],
        ]
    ],
    [
        'id' => 'whistleblowing',
        'icon' => 'fas fa-shield-alt',
        'label' => 'Canal de Denúncias',
        'submenu' => [
            [
                'label' => 'Dashboard',
                'url' => $_ENV['URL_ADM'] . 'denuncias-dashboard',
                'permission' => 'WhistleblowingDashboard',
                'icon' => 'fas fa-chart-pie',
                'related_routes' => ['denuncias-dashboard', 'whistleblowing-export-dashboard'],
            ],
            [
                'label' => 'Denúncias',
                'url' => $_ENV['URL_ADM'] . 'denuncias',
                'permission' => 'WhistleblowingListReports',
                'icon' => 'fas fa-list',
                'related_routes' => [
                    'denuncias', 'list-denuncias', 'view-denuncia', 'reply-denuncia',
                    'update-denuncia-status', 'whistleblowing-export-access-log',
                ],
            ],
            [
                'label' => 'Comitês',
                'url' => $_ENV['URL_ADM'] . 'list-whistleblowing-committees',
                'permission' => 'WhistleblowingListCommittees',
                'icon' => 'fas fa-users-cog',
                'related_routes' => [
                    'list-whistleblowing-committees', 'create-whistleblowing-committee',
                    'update-whistleblowing-committee',
                ],
            ],
            [
                'label' => 'Classificações',
                'url' => $_ENV['URL_ADM'] . 'list-whistleblowing-categories',
                'permission' => 'WhistleblowingListCategories',
                'icon' => 'fas fa-tags',
                'related_routes' => [
                    'list-whistleblowing-categories', 'create-whistleblowing-category',
                    'update-whistleblowing-category',
                ],
            ],
            [
                'label' => 'Canal público',
                'url' => WhistleblowingPublicUrlHelper::baseUrl(),
                'target' => '_blank',
                'icon' => 'fas fa-external-link-alt',
                'any_of' => [
                    'WhistleblowingDashboard',
                    'WhistleblowingListReports',
                    'WhistleblowingViewReport',
                    'WhistleblowingListCommittees',
                    'WhistleblowingConfig',
                    'WhistleblowingGovernanceLgpd',
                ],
            ],
            [
                'label' => 'Governança LGPD',
                'url' => $_ENV['URL_ADM'] . 'whistleblowing-governance',
                'permission' => 'WhistleblowingGovernanceLgpd',
                'icon' => 'fas fa-balance-scale',
                'related_routes' => ['whistleblowing-governance'],
            ],
            [
                'label' => 'Configuração',
                'url' => $_ENV['URL_ADM'] . 'whistleblowing-config',
                'permission' => 'WhistleblowingConfig',
                'icon' => 'fas fa-cog',
                'related_routes' => ['whistleblowing-config', 'whistleblowing-audit-evidence'],
            ],
        ],
    ],
    [
        'id' => 'sst',
        'icon' => 'fa-solid fa-heart-pulse',
        'label' => 'Segurança e Medicina',
        'submenu' => [
            [
                'label' => 'Dashboard',
                'url' => $_ENV['URL_ADM'] . 'sst-dashboard',
                'permission' => 'SstDashboard',
                'icon' => 'fas fa-chart-line',
                'related_routes' => ['sst-dashboard', 'sst-employee-profile'],
            ],
            [
                'label' => 'Cadastros e vínculos',
                'icon' => 'fa-solid fa-database',
                'submenu' => [
                    [
                        'label' => 'CIDs',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-cids',
                        'permission' => 'SstListCids',
                        'icon' => 'fas fa-notes-medical',
                        'related_routes' => [
                            'sst-list-cids', 'sst-create-cid', 'sst-update-cid', 'sst-delete-cid',
                        ],
                    ],
                    [
                        'label' => 'EPIs',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-epis',
                        'permission' => 'SstListEpis',
                        'icon' => 'fas fa-hard-hat',
                        'related_routes' => [
                            'sst-list-epis', 'sst-create-epi', 'sst-view-epi', 'sst-update-epi', 'sst-delete-epi',
                            'sst-list-epi-estoque',
                        ],
                    ],
                    [
                        'label' => 'Exames',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-exames',
                        'permission' => 'SstListExames',
                        'icon' => 'fas fa-stethoscope',
                        'related_routes' => [
                            'sst-list-exames', 'sst-create-exame', 'sst-view-exame', 'sst-update-exame', 'sst-delete-exame',
                        ],
                    ],
                    [
                        'label' => 'Médicos',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-medicos',
                        'permission' => 'SstListMedicos',
                        'icon' => 'fas fa-user-md',
                        'related_routes' => [
                            'sst-list-medicos', 'sst-create-medico', 'sst-update-medico', 'sst-delete-medico',
                        ],
                    ],
                    [
                        'label' => 'Necessidades de EPI',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-epi-necessidade',
                        'permission' => 'SstListEpiNecessidade',
                        'icon' => 'fas fa-list-check',
                        'related_routes' => [
                            'sst-list-epi-necessidade', 'sst-create-epi-necessidade',
                            'sst-update-epi-necessidade', 'sst-delete-epi-necessidade',
                        ],
                    ],
                    [
                        'label' => 'Necessidades de exame',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-exame-necessidade',
                        'permission' => 'SstListExameNecessidade',
                        'icon' => 'fas fa-clipboard-list',
                        'related_routes' => [
                            'sst-list-exame-necessidade', 'sst-create-exame-necessidade',
                            'sst-update-exame-necessidade', 'sst-delete-exame-necessidade',
                        ],
                    ],
                    [
                        'label' => 'Necessidades de treinamento',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-treinamento-necessidade',
                        'permission' => 'SstListTreinamentoNecessidade',
                        'icon' => 'fas fa-clipboard-list',
                        'related_routes' => [
                            'sst-list-treinamento-necessidade', 'sst-create-treinamento-necessidade',
                            'sst-update-treinamento-necessidade', 'sst-delete-treinamento-necessidade',
                        ],
                    ],
                    [
                        'label' => 'Matriz trein. × cargo',
                        'url' => $_ENV['URL_ADM'] . 'sst-matriz-treinamento-cargo',
                        'permission' => 'SstMatrizTreinamentoCargo',
                        'icon' => 'fas fa-th',
                        'related_routes' => ['sst-matriz-treinamento-cargo'],
                    ],
                    [
                        'label' => 'Riscos',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-riscos',
                        'permission' => 'SstListRiscos',
                        'icon' => 'fas fa-exclamation-triangle',
                        'related_routes' => [
                            'sst-list-riscos', 'sst-create-risco', 'sst-view-risco', 'sst-update-risco', 'sst-delete-risco',
                            'sst-list-risco-cargo', 'sst-create-risco-cargo', 'sst-update-risco-cargo', 'sst-delete-risco-cargo',
                            'sst-list-risco-exame', 'sst-create-risco-exame', 'sst-update-risco-exame', 'sst-delete-risco-exame',
                            'sst-list-risco-epi', 'sst-create-risco-epi', 'sst-update-risco-epi', 'sst-delete-risco-epi',
                            'sst-list-risco-treinamento', 'sst-create-risco-treinamento', 'sst-update-risco-treinamento',
                            'sst-delete-risco-treinamento', 'sst-save-risco-treinamentos',
                        ],
                    ],
                    [
                        'label' => 'Treinamentos SST',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-treinamentos',
                        'permission' => 'SstListTreinamentos',
                        'icon' => 'fas fa-graduation-cap',
                        'related_routes' => [
                            'sst-list-treinamentos', 'sst-create-treinamento', 'sst-view-treinamento',
                            'sst-update-treinamento', 'sst-delete-treinamento',
                        ],
                    ],
                    [
                        'label' => 'GHE (ambientes)',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-ghe',
                        'permission' => 'SstListGhe',
                        'icon' => 'fas fa-industry',
                        'related_routes' => [
                            'sst-list-ghe', 'sst-create-ghe', 'sst-view-ghe', 'sst-update-ghe', 'sst-delete-ghe',
                            'sst-save-ghe-relacionamentos',
                        ],
                    ],
                    // SST equipamentos de segurança e vistorias periódicas
                    [
                        'label' => 'Tipos de equipamento',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-equipamento-tipos',
                        'permission' => 'SstListEquipamentoTipos',
                        'icon' => 'fas fa-layer-group',
                        'related_routes' => [
                            'sst-list-equipamento-tipos',
                            'sst-create-equipamento-tipo',
                            'sst-view-equipamento-tipo',
                            'sst-update-equipamento-tipo',
                            'sst-delete-equipamento-tipo',
                            'sst-manage-equipamento-checklist-item',
                        ],
                    ],
                    [
                        'label' => 'Equipamentos de segurança',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-equipamentos',
                        'permission' => 'SstListEquipamentos',
                        'icon' => 'fas fa-fire-extinguisher',
                        'related_routes' => [
                            'sst-list-equipamentos',
                            'sst-create-equipamento',
                            'sst-view-equipamento',
                            'sst-update-equipamento',
                            'sst-delete-equipamento',
                            'sst-register-equipamento-recarga',
                            'sst-export-equipamento-qr',
                            'sst-generate-equipamento-vistoria',
                            'sst-export-equipamento-auditoria-pdf',
                        ],
                    ],
                    [
                        'label' => 'Config. vistorias equipamentos',
                        'url' => $_ENV['URL_ADM'] . 'sst-equipamento-settings',
                        'permission' => 'SstEquipamentoSettings',
                        'icon' => 'fas fa-cog',
                        'related_routes' => ['sst-equipamento-settings'],
                    ],
                ]
            ],
            [
                'label' => 'Registros',
                'icon' => 'fa-solid fa-clipboard-list',
                'submenu' => [
                    [
                        'label' => 'Acidentes e incidentes',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-acidentes',
                        'permission' => 'SstListAcidentes',
                        'icon' => 'fas fa-ambulance',
                        'related_routes' => [
                            'sst-list-acidentes', 'sst-create-acidente', 'sst-view-acidente',
                            'sst-update-acidente', 'sst-delete-acidente',
                            'sst-create-plano-acao', 'sst-update-plano-acao',
                        ],
                    ],
                    [
                        'label' => 'Afastamentos',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-afastamentos',
                        'permission' => 'SstListAfastamentos',
                        'icon' => 'fas fa-procedures',
                        'related_routes' => [
                            'sst-list-afastamentos', 'sst-create-afastamento', 'sst-view-afastamento',
                            'sst-update-afastamento', 'sst-delete-afastamento',
                        ],
                    ],
                    [
                        'label' => 'ASOs',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-asos',
                        'permission' => 'SstListAsos',
                        'icon' => 'fas fa-file-medical',
                        'related_routes' => [
                            'sst-list-asos', 'sst-create-aso', 'sst-view-aso', 'sst-update-aso', 'sst-delete-aso',
                            'sst-abrir-aso-pendencia', 'sst-registrar-resultados-aso',
                            'sst-encaminhamento-aso', 'sst-export-encaminhamento-aso-pdf',
                        ],
                    ],
                    [
                        'label' => 'Status treinamentos SST',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-treinamento-vinculos',
                        'permission' => 'SstListTreinamentoVinculos',
                        'icon' => 'fas fa-user-graduate',
                        'related_routes' => [
                            'sst-list-treinamento-vinculos', 'sst-view-treinamento-vinculo',
                            'sst-sync-treinamento-vinculos', 'sst-export-treinamento-certificado-pdf',
                        ],
                    ],
                    [
                        'label' => 'Aplicar treinamento SST',
                        'url' => $_ENV['URL_ADM'] . 'sst-apply-treinamento',
                        'permission' => 'SstApplyTreinamento',
                        'icon' => 'fas fa-check-circle',
                        'related_routes' => ['sst-apply-treinamento'],
                    ],
                    [
                        'label' => 'Fichas de entrega EPI',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-epi-fichas',
                        'permission' => 'SstListEpiFichas',
                        'icon' => 'fas fa-file-signature',
                        'related_routes' => [
                            'sst-list-epi-fichas', 'sst-create-epi-ficha', 'sst-view-epi-ficha',
                            'sst-export-epi-ficha-pdf',
                            'sst-list-epi-entregas', 'sst-create-epi-entrega', 'sst-view-epi-entrega',
                            'sst-update-epi-entrega', 'sst-delete-epi-entrega',
                        ],
                    ],
                    [
                        'label' => 'Movimentações EPI',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-epi-movimentos',
                        'permission' => 'SstListEpiMovimentos',
                        'icon' => 'fas fa-dolly',
                        'related_routes' => [
                            'sst-list-epi-movimentos', 'sst-create-epi-movimento',
                        ],
                    ],
                    [
                        'label' => 'Inspeções',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-inspecoes',
                        'permission' => 'SstListInspecoes',
                        'icon' => 'fas fa-search',
                        'related_routes' => [
                            'sst-list-inspecoes', 'sst-create-inspecao', 'sst-view-inspecao',
                            'sst-update-inspecao', 'sst-delete-inspecao', 'sst-manage-inspecao-item',
                        ],
                    ],
                    [
                        'label' => 'Vistorias de equipamentos',
                        'url' => $_ENV['URL_ADM'] . 'sst-minhas-equipamento-vistorias',
                        'permission' => 'SstMinhasEquipamentoVistorias',
                        'icon' => 'fas fa-clipboard-check',
                        'related_routes' => [
                            'sst-minhas-equipamento-vistorias',
                            'sst-list-equipamento-vistorias',
                            'sst-execute-equipamento-vistoria',
                            'sst-export-equipamento-vistoria-pdf',
                        ],
                    ],
                    [
                        'label' => 'NCs de equipamentos',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-equipamento-nao-conformidades',
                        'permission' => 'SstListEquipamentoNaoConformidades',
                        'icon' => 'fas fa-exclamation-triangle',
                        'related_routes' => [
                            'sst-list-equipamento-nao-conformidades',
                            'sst-view-equipamento-nao-conformidade',
                            'sst-create-equipamento-acao-corretiva',
                            'sst-update-equipamento-acao-corretiva',
                            'sst-encerrar-equipamento-nao-conformidade',
                        ],
                    ],
                    [
                        'label' => 'Ler QR equipamento',
                        'url' => $_ENV['URL_ADM'] . 'sst-scan-equipamento',
                        'permission' => 'SstScanEquipamento',
                        'icon' => 'fas fa-qrcode',
                        'related_routes' => ['sst-scan-equipamento'],
                    ],
                    [
                        'label' => 'CIPA',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-cipa-mandatos',
                        'permission' => 'SstListCipaMandatos',
                        'icon' => 'fas fa-users-cog',
                        'related_routes' => [
                            'sst-list-cipa-mandatos', 'sst-create-cipa-mandato', 'sst-view-cipa-mandato',
                            'sst-update-cipa-mandato', 'sst-delete-cipa-mandato', 'sst-manage-cipa',
                        ],
                    ],
                ]
            ],
            [
                'label' => 'Conformidade',
                'icon' => 'fa-solid fa-balance-scale',
                'submenu' => [
                    [
                        'label' => 'Painel conformidade',
                        'url' => $_ENV['URL_ADM'] . 'sst-report-conformidade',
                        'permission' => 'SstReportConformidade',
                        'icon' => 'fas fa-balance-scale',
                        'related_routes' => ['sst-report-conformidade'],
                    ],
                    [
                        'label' => 'Programas PGR/PCMSO',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-programas',
                        'permission' => 'SstListProgramas',
                        'icon' => 'fas fa-file-contract',
                        'related_routes' => [
                            'sst-list-programas', 'sst-create-programa', 'sst-view-programa', 'sst-update-programa',
                        ],
                    ],
                    [
                        'label' => 'Fila eSocial',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-esocial-eventos',
                        'permission' => 'SstListEsocialEventos',
                        'icon' => 'fas fa-cloud-upload-alt',
                        'related_routes' => [
                            'sst-list-esocial-eventos', 'sst-view-esocial-evento',
                        ],
                    ],
                    [
                        'label' => 'PPP',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-ppp',
                        'permission' => 'SstListPpp',
                        'icon' => 'fas fa-file-alt',
                        'related_routes' => [
                            'sst-list-ppp', 'sst-view-ppp', 'sst-generate-ppp', 'sst-export-ppp-pdf',
                        ],
                    ],
                ]
            ],
            [
                'label' => 'Relatórios',
                'icon' => 'fa-solid fa-chart-bar',
                'submenu' => [
                    [
                        'label' => 'Pendências',
                        'url' => $_ENV['URL_ADM'] . 'sst-report-pendencias',
                        'permission' => 'SstReportPendencias',
                        'icon' => 'fas fa-exclamation-circle',
                        'related_routes' => ['sst-report-pendencias'],
                    ],
                    [
                        'label' => 'Relatório de EPIs',
                        'url' => $_ENV['URL_ADM'] . 'sst-report-epis',
                        'permission' => 'SstReportEpis',
                        'icon' => 'fas fa-chart-bar',
                        'related_routes' => ['sst-report-epis'],
                    ],
                    [
                        'label' => 'Relatório de exames',
                        'url' => $_ENV['URL_ADM'] . 'sst-report-exames',
                        'permission' => 'SstReportExames',
                        'icon' => 'fas fa-chart-bar',
                        'related_routes' => ['sst-report-exames'],
                    ],
                    [
                        'label' => 'Relatório treinamentos SST',
                        'url' => $_ENV['URL_ADM'] . 'sst-report-treinamentos',
                        'permission' => 'SstReportTreinamentos',
                        'icon' => 'fas fa-graduation-cap',
                        'related_routes' => ['sst-report-treinamentos'],
                    ],
                    [
                        'label' => 'Relatório de afastamentos',
                        'url' => $_ENV['URL_ADM'] . 'sst-report-afastamentos',
                        'permission' => 'SstReportAfastamentos',
                        'icon' => 'fas fa-procedures',
                        'related_routes' => ['sst-report-afastamentos'],
                    ],
                    [
                        'label' => 'Relatório por CID',
                        'url' => $_ENV['URL_ADM'] . 'sst-report-cids',
                        'permission' => 'SstReportCids',
                        'icon' => 'fas fa-notes-medical',
                        'related_routes' => ['sst-report-cids'],
                    ],
                ]
            ],
        ]
    ],
    [
        'id' => 'lgpd',
        'icon' => 'fa-solid fa-shield-halved',
        'label' => 'LGPD',
        'submenu' => [
            [
                'label' => 'Dashboard LGPD',
                'url' => $_ENV['URL_ADM'] . 'lgpd-dashboard',
                'permission' => 'LgpdDashboard'
            ],
            [
                'label' => 'Consentimentos',
                'url' => $_ENV['URL_ADM'] . 'lgpd-consentimentos',
                'permission' => 'LgpdConsentimentos'
            ],
            [
                'label' => 'Inventário',
                'url' => $_ENV['URL_ADM'] . 'lgpd-inventory',
                'permission' => 'LgpdInventory'
            ],
            [
                'label' => 'ROPA',
                'url' => $_ENV['URL_ADM'] . 'lgpd-ropa',
                'permission' => 'LgpdRopa'
            ],
            [
                'label' => 'Data Mapping',
                'url' => $_ENV['URL_ADM'] . 'lgpd-data-mapping',
                'permission' => 'LgpdDataMapping'
            ],
            [
                'label' => 'Relatório Integrado',
                'url' => $_ENV['URL_ADM'] . 'lgpd-workflow-report',
                'permission' => 'LgpdWorkflowReport'
            ],
            [
                'label' => 'Categorias de Titulares',
                'url' => $_ENV['URL_ADM'] . 'lgpd-categorias-titulares',
                'permission' => 'LgpdCategoriasTitulares'
            ],
            [
                'label' => 'Finalidades',
                'url' => $_ENV['URL_ADM'] . 'lgpd-finalidades',
                'permission' => 'LgpdFinalidades'
            ],
            [
                'label' => 'Bases Legais',
                'url' => $_ENV['URL_ADM'] . 'lgpd-bases-legais',
                'permission' => 'LgpdBasesLegais'
            ],
            [
                'label' => 'Termos LGPD',
                'url' => $_ENV['URL_ADM'] . 'lgpd-termos',
                'permission' => 'LgpdTermos'
            ],
            [
                'label' => 'Tipos de Dados',
                'url' => $_ENV['URL_ADM'] . 'lgpd-tipos-dados',
                'permission' => 'LgpdTiposDados'
            ],
            [
                'label' => 'Classificações de Dados',
                'url' => $_ENV['URL_ADM'] . 'lgpd-classificacoes-dados',
                'permission' => 'LgpdClassificacoesDados'
            ],
            [
                'label' => 'AIPD',
                'icon' => 'fa-solid fa-file-shield',
                'submenu' => [
                    [
                        'label' => 'AIPD',
                        'url' => $_ENV['URL_ADM'] . 'lgpd-aipd',
                        'permission' => 'LgpdAipd'
                    ],
                    [
                        'label' => 'Sugestões de AIPD',
                        'url' => $_ENV['URL_ADM'] . 'lgpd-aipd-suggest',
                        'permission' => 'LgpdAipdSuggest'
                    ],
                    [
                        'label' => 'Templates AIPD',
                        'icon' => 'fa-solid fa-file-lines',
                        'submenu' => [
                            [
                                'label' => 'Template - E-commerce',
                                'url' => $_ENV['URL_ADM'] . 'lgpd-aipd-template-ecommerce',
                                'permission' => 'LgpdAipdTemplateEcommerce'
                            ],
                            [
                                'label' => 'Template - Educação',
                                'url' => $_ENV['URL_ADM'] . 'lgpd-aipd-template-educacao',
                                'permission' => 'LgpdAipdTemplateEducacao'
                            ],
                            [
                                'label' => 'Template - Financeiro',
                                'url' => $_ENV['URL_ADM'] . 'lgpd-aipd-template-financeiro',
                                'permission' => 'LgpdAipdTemplateFinanceiro'
                            ],       
                            [
                                'label' => 'Template - Jurídico',
                                'url' => $_ENV['URL_ADM'] . 'lgpd-aipd-template-juridico',
                                'icon' => 'fas fa-balance-scale',
                                'permission' => 'LgpdAipdTemplateJuridico'
                            ],
                            [
                                'label' => 'Template - Logística',
                                'url' => $_ENV['URL_ADM'] . 'lgpd-aipd-template-logistica',
                                'icon' => 'fas fa-truck',
                                'permission' => 'LgpdAipdTemplateLogistica'
                            ],
                            [
                                'label' => 'Template - Marketing',
                                'url' => $_ENV['URL_ADM'] . 'lgpd-aipd-template-marketing',
                                'permission' => 'LgpdAipdTemplateMarketing'
                            ],
                            [
                                'label' => 'Template - RH',
                                'url' => $_ENV['URL_ADM'] . 'lgpd-aipd-template-rh',
                                'permission' => 'LgpdAipdTemplateRh'
                            ],
                            [
                                'label' => 'Template - Saúde',
                                'url' => $_ENV['URL_ADM'] . 'lgpd-aipd-template-saude',
                                'permission' => 'LgpdAipdTemplateSaude'
                            ],
                            [
                                 'label' => 'Template - Telecomunicações',
                                 'url' => $_ENV['URL_ADM'] . 'lgpd-aipd-template-telecom',
                                 'icon' => 'fas fa-broadcast-tower',
                                 'permission' => 'LgpdAipdTemplateTelecom'
                             ],
                        ]
                    ]
                ]
            ],
            [
                'label' => 'RIPD',
                'url' => $_ENV['URL_ADM'] . 'lgpd-ripd',
                'permission' => 'LgpdRipd'
            ],
            [
                'label' => 'TIA',
                'url' => $_ENV['URL_ADM'] . 'lgpd-tia',
                'permission' => 'LgpdTia'
            ],
        ]
    ],
    [
        'id' => 'planejamento-estrategico',
        'icon' => 'fa-solid fa-bullseye',
        'label' => 'Planejamento Estratégico',
        'submenu' => [
            [
                'label' => 'Dashboard',
                'url' => $_ENV['URL_ADM'] . 'strategic-dashboard',
                'permission' => 'StrategicDashboard'
            ],
            [
                'label' => 'Planos Estratégicos',
                'url' => $_ENV['URL_ADM'] . 'list-strategic-plans',
                'permission' => 'ListStrategicPlans'
            ],
            [
                'label' => 'Indicadores Estratégicos',
                'url' => $_ENV['URL_ADM'] . 'strategic-indicators-list',
                'permission' => 'StrategicIndicatorsList'
            ],
        ]
    ],
    
    [
        'id' => 'relatorios',
        'icon' => 'fa-solid fa-chart-line',
        'label' => 'Relatórios',
        'submenu' => [
            [
                'label' => 'Relatórios Locais',
                'url' => $_ENV['URL_ADM'] . 'list-dynamic-reports',
                'permission' => 'ListDynamicReports',
                'icon' => 'fa-solid fa-list'
            ],
            [
                'label' => 'Relatórios SAP (API)',
                'url' => $_ENV['URL_ADM'] . 'list-dynamic-reports-sap',
                'permission' => 'ListDynamicReportsSap',
                'icon' => 'fa-solid fa-database'
            ],
            [
                'label' => 'Dashboards',
                'url' => $_ENV['URL_ADM'] . 'list-dashboards',
                'permission' => 'ListDashboards',
                'icon' => 'fa-solid fa-chart-pie'
            ],
        ]
    ],
    
    [
        'id' => 'logout',
        'icon' => 'fa-solid fa-arrow-right-from-bracket',
        'label' => 'Sair',
        'url' => $_ENV['URL_ADM'] . 'logout',
        'submenu' => []
    ],
];


/** Ordenação alfabética do menu; entradas Dashboard primeiro; Sair sempre por último. */
if (!function_exists('admsMenuIsDashboardEntry')) {
    function admsMenuIsDashboardEntry(array $item): bool
    {
        if (($item['id'] ?? '') === 'dashboard') {
            return true;
        }
        $label = (string) ($item['label'] ?? '');
        if ($label !== '' && stripos($label, 'dashboard') !== false) {
            return true;
        }
        $permission = (string) ($item['permission'] ?? '');
        if ($permission !== '' && stripos($permission, 'Dashboard') !== false) {
            return true;
        }

        return false;
    }
}

if (!function_exists('admsMenuCompareEntries')) {
    function admsMenuCompareEntries(array $a, array $b): int
    {
        $aLogout = ($a['id'] ?? '') === 'logout';
        $bLogout = ($b['id'] ?? '') === 'logout';
        if ($aLogout !== $bLogout) {
            return $aLogout ? 1 : -1;
        }

        $aDash = admsMenuIsDashboardEntry($a);
        $bDash = admsMenuIsDashboardEntry($b);
        if ($aDash !== $bDash) {
            return $aDash ? -1 : 1;
        }

        $labelA = trim((string) ($a['label'] ?? ''));
        $labelB = trim((string) ($b['label'] ?? ''));

        return strcasecmp($labelA, $labelB);
    }
}

if (!function_exists('sortAdmsMenuTree')) {
    /**
     * @param array<int, array<string, mixed>> $menus
     * @return array<int, array<string, mixed>>
     */
    function sortAdmsMenuTree(array $menus): array
    {
        foreach ($menus as $index => $menu) {
            if (!empty($menu['submenu']) && is_array($menu['submenu'])) {
                $menus[$index]['submenu'] = sortAdmsMenuTree($menu['submenu']);
            }
        }

        usort($menus, 'admsMenuCompareEntries');

        return array_values($menus);
    }
}

/** Override de menu da sessão só vale na seção atual (evita Relatórios preso ao navegar). */
if (!function_exists('admsMenuSessionOverrideApplies')) {
    function admsMenuSessionOverrideApplies(string|bool|null $menuAtivo): bool
    {
        $override = $_SESSION['menu_override'] ?? null;
        if (!is_string($override) || $override === '') {
            return false;
        }
        if ($menuAtivo === null || $menuAtivo === false || $menuAtivo === '') {
            return true;
        }
        $token = (string) $menuAtivo;

        return $token === $override || $token === 'relatorios' || $token === 'estoque';
    }
}

$menus = sortAdmsMenuTree($menus);


/** Item de menu visível: permission OU qualquer entrada em any_of (lista de controllers). */
if (!function_exists('menuEntryAllowed')) {
    function menuEntryAllowed(array $item, array $menuPermission): bool
    {
        if (!empty($item['any_of']) && is_array($item['any_of'])) {
            foreach ($item['any_of'] as $perm) {
                if (in_array($perm, $menuPermission, true)) {
                    return true;
                }
            }
        }
        if (isset($item['permission']) && in_array($item['permission'], $menuPermission, true)) {
            return true;
        }

        return false;
    }
}

/** menuAtivo / override coincide com permission, any_of ou slug da URL / related_routes */
if (!function_exists('menuEntryMatchesAtivo')) {
    function menuEntryMatchesAtivo(array $item, string|bool|null $menuAtivo): bool
    {
        if ($menuAtivo === null || $menuAtivo === false || $menuAtivo === '') {
            return false;
        }
        $token = (string) $menuAtivo;
        if (isset($item['permission']) && $item['permission'] === $token) {
            return true;
        }
        if (!empty($item['any_of']) && is_array($item['any_of']) && in_array($token, $item['any_of'], true)) {
            return true;
        }
        // Controllers SST/Estoque costumam passar o slug (ex.: sst-list-epis), não o nome da permission
        foreach (admsMenuItemRouteSlugs($item) as $slug) {
            if ($slug === $token) {
                return true;
            }
        }

        return false;
    }
}

/** Contexto da URL atual (sem base do admin). */
if (!function_exists('admsMenuPathContext')) {
    /**
     * @return array{currentFirstSeg: string, currentPathNoBase: string, currentBaseName: string, basePath: string}
     */
    function admsMenuPathContext(): array
    {
        static $ctx = null;
        if (is_array($ctx)) {
            return $ctx;
        }

        $currentUrlPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $currentSegments = explode('/', trim((string)$currentUrlPath, '/'));
        $basePath = trim((string)(parse_url($_ENV['URL_ADM'] ?? '', PHP_URL_PATH) ?? ''), '/');
        if ($basePath !== '' && $currentSegments !== [] && ($currentSegments[0] ?? '') === $basePath) {
            array_shift($currentSegments);
        }

        $ctx = [
            'currentFirstSeg' => (string)($currentSegments[0] ?? ''),
            'currentPathNoBase' => implode('/', $currentSegments),
            'currentBaseName' => basename((string)$currentUrlPath),
            'basePath' => $basePath,
        ];

        return $ctx;
    }
}

/** Slugs de rota associados a um item (URL do menu + related_routes). */
if (!function_exists('admsMenuItemRouteSlugs')) {
    /**
     * @param array<string, mixed> $item
     * @return list<string>
     */
    function admsMenuItemRouteSlugs(array $item): array
    {
        $slugs = [];
        if (!empty($item['related_routes']) && is_array($item['related_routes'])) {
            foreach ($item['related_routes'] as $route) {
                $slug = trim((string)$route);
                if ($slug !== '') {
                    $slugs[] = $slug;
                }
            }
        }

        if (isset($item['url'])) {
            $path = parse_url((string)$item['url'], PHP_URL_PATH);
            $parts = explode('/', trim((string)$path, '/'));
            $basePath = admsMenuPathContext()['basePath'];
            if ($basePath !== '' && $parts !== [] && ($parts[0] ?? '') === $basePath) {
                array_shift($parts);
            }
            if (($parts[0] ?? '') !== '') {
                $slugs[] = (string)$parts[0];
            }
        }

        return array_values(array_unique($slugs));
    }
}

/** Item folha ativo: prioriza URL/rota; permission só com rota compatível. */
if (!function_exists('admsMenuItemMatchesCurrentRequest')) {
    /**
     * @param array<string, mixed> $item
     */
    function admsMenuItemMatchesCurrentRequest(array $item, string|bool|null $menuAtivo): bool
    {
        if (!isset($item['url'])) {
            return false;
        }

        $ctx = admsMenuPathContext();
        $slugs = admsMenuItemRouteSlugs($item);

        if ($ctx['currentFirstSeg'] !== '' && in_array($ctx['currentFirstSeg'], $slugs, true)) {
            return true;
        }

        $path = parse_url((string)$item['url'], PHP_URL_PATH);
        $parts = explode('/', trim((string)$path, '/'));
        if ($ctx['basePath'] !== '' && $parts !== [] && ($parts[0] ?? '') === $ctx['basePath']) {
            array_shift($parts);
        }
        $menuPathNoBase = implode('/', $parts);
        if (
            $menuPathNoBase !== ''
            && rtrim($ctx['currentPathNoBase'], '/') === rtrim($menuPathNoBase, '/')
        ) {
            return true;
        }

        $override = $_SESSION['menu_override'] ?? null;
        if (
            is_string($override) && $override !== ''
            && admsMenuSessionOverrideApplies($menuAtivo)
            && menuEntryMatchesAtivo($item, $override)
            && ($ctx['currentFirstSeg'] === '' || in_array($ctx['currentFirstSeg'], $slugs, true))
        ) {
            return true;
        }

        if (!menuEntryMatchesAtivo($item, $menuAtivo)) {
            return false;
        }

        return $ctx['currentFirstSeg'] === '' || in_array($ctx['currentFirstSeg'], $slugs, true);
    }
}

/** Algum item folha permitido na árvore corresponde à rota/controller atuais. */
if (!function_exists('admsMenuSubtreeIsActive')) {
    /**
     * @param array<int, array<string, mixed>> $items
     * @param array<int, string> $menuPermission
     */
    function admsMenuSubtreeIsActive(array $items, array $menuPermission, string|bool|null $menuAtivo): bool
    {
        foreach ($items as $item) {
            if (!menuEntryAllowed($item, $menuPermission)) {
                continue;
            }
            if (!empty($item['submenu']) && is_array($item['submenu'])) {
                if (admsMenuSubtreeIsActive($item['submenu'], $menuPermission, $menuAtivo)) {
                    return true;
                }
                continue;
            }
            if (isset($item['url']) && admsMenuItemMatchesCurrentRequest($item, $menuAtivo)) {
                return true;
            }
        }

        return false;
    }
}

// Função para verificar se há pelo menos um submenu permitido
if (!function_exists('hasPermittedSubmenu')) {
    /**
     * @param array<int, array<string, mixed>> $submenu
     * @param array<int, string> $menuPermission
     */
    function hasPermittedSubmenu(array $submenu, array $menuPermission): bool {
        foreach ($submenu as $item) {
            if (isset($item['submenu']) && is_array($item['submenu'])) {
                if (hasPermittedSubmenu($item['submenu'], $menuPermission)) {
                    return true;
                }
            }
            if (menuEntryAllowed($item, $menuPermission)) {
                return true;
            }
        }
        return false;
    }
}

// Função para contar submenus permitidos
if (!function_exists('countPermittedSubmenus')) {
    /**
     * @param array<int, array<string, mixed>> $submenu
     * @param array<int, string> $menuPermission
     */
    function countPermittedSubmenus(array $submenu, array $menuPermission): int {
        $count = 0;
        foreach ($submenu as $item) {
            if (menuEntryAllowed($item, $menuPermission)) {
                $count++;
            }
            if (isset($item['submenu']) && is_array($item['submenu'])) {
                $count += countPermittedSubmenus($item['submenu'], $menuPermission);
            }
        }
        return $count;
    }
}
?>

<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion sb-sidenav-five" id="sidenavAccordion">
        
        <!-- Caixa de Pesquisa do Menu -->
        <div class="menu-search-container">
            <div class="menu-search-box">
                <i class="fas fa-search menu-search-icon"></i>
                <input 
                    type="text" 
                    id="menuSearch" 
                    class="menu-search-input" 
                    placeholder="Pesquisar no menu..."
                    autocomplete="off"
                    aria-label="Pesquisar itens do menu"
                >
                <button 
                    type="button" 
                    id="clearMenuSearch" 
                    class="menu-search-clear"
                    aria-label="Limpar pesquisa"
                    style="display: none;"
                >
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="menu-search-results" id="menuSearchResults" style="display: none;">
                <span class="menu-search-count">0 resultados</span>
            </div>
            <div class="menu-search-hint">
                <kbd>Ctrl</kbd> + <kbd>K</kbd> para focar
            </div>
        </div>
        
        <div class="sb-sidenav-menu">
            <div class="nav" id="sidenavMenuNav">
                <?php

                // Função recursiva para renderizar submenus aninhados
                if (!function_exists('renderMenu')) {
                    /**
                     * @param array<int, array<string, mixed>> $menus
                     * @param array<int, string> $menuPermission
                     */
                    function renderMenu(
                        array $menus,
                        array $menuPermission,
                        ?string $menuAtivo = null,
                        int $nivel = 0,
                        string $parentId = 'sidenavMenuNav'
                    ): void {
                        foreach ($menus as $index => $menu) {
                            $hasSubmenu = !empty($menu['submenu']);
                            $hasPermitted = menuEntryAllowed($menu, $menuPermission);
                            
                            if ($hasSubmenu) {
                                // Verifica se há pelo menos um submenu permitido
                                $permittedSubmenus = array_filter($menu['submenu'], function($submenu) use ($menuPermission) {
                                    if (isset($submenu['submenu'])) {
                                        // Se o submenu tem submenus aninhados, verifica recursivamente
                                        return hasPermittedSubmenu($submenu['submenu'], $menuPermission);
                                    }
                                    return menuEntryAllowed($submenu, $menuPermission);
                                });
                                
                                // Verifica também se há submenus diretos permitidos
                                $directPermittedSubmenus = array_filter($menu['submenu'], function($submenu) use ($menuPermission) {
                                    return menuEntryAllowed($submenu, $menuPermission);
                                });
                                
                                // Se não há submenus diretos permitidos, verifica se há submenus aninhados permitidos
                                if (count($directPermittedSubmenus) == 0) {
                                    $nestedPermittedSubmenus = array_filter($menu['submenu'], function($submenu) use ($menuPermission) {
                                        return isset($submenu['submenu']) && hasPermittedSubmenu($submenu['submenu'], $menuPermission);
                                    });
                                    if (count($nestedPermittedSubmenus) > 0) {
                                        $permittedSubmenus = $nestedPermittedSubmenus;
                                    }
                                } else {
                                    $permittedSubmenus = $directPermittedSubmenus;
                                }
                                
                                // Para o menu LGPD, sempre mostrar se houver pelo menos uma permissão
                                if (isset($menu['label']) && $menu['label'] === 'LGPD') {
                                    $totalPermitted = countPermittedSubmenus($menu['submenu'], $menuPermission);
                                    if ($totalPermitted > 0) {
                                        $permittedSubmenus = $menu['submenu']; // Mostra todos os submenus
                                    }
                                }
                                
                                // Para outros menus com submenus aninhados, verifica se há pelo menos um permitido
                                if (count($permittedSubmenus) == 0) {
                                    foreach ($menu['submenu'] as $submenu) {
                                        if (isset($submenu['submenu']) && hasPermittedSubmenu($submenu['submenu'], $menuPermission)) {
                                            $permittedSubmenus = [$submenu];
                                            break;
                                        }
                                    }
                                }
                                
                                // IMPORTANTE: Se há pelo menos um submenu permitido, mostra o menu principal
                                if (count($permittedSubmenus) > 0) {
                                    // Gera um id único para cada submenu
                                    $submenuId = 'collapse' . md5(($menu['label'] ?? 'submenu') . $nivel . $index);
                                    $submenuActive = admsMenuSubtreeIsActive($menu['submenu'], $menuPermission, $menuAtivo);

                                    $isOpen = $submenuActive ? 'show' : '';
                                    $parentLinkClass = 'nav-link' . ($submenuActive ? ' active' : ' collapsed');
                                    echo '<a class="' . $parentLinkClass . '" href="#" data-bs-toggle="collapse" data-bs-target="#' . $submenuId . '" aria-expanded="' . ($isOpen ? 'true' : 'false') . '" aria-controls="' . $submenuId . '">';
                                    if ($nivel == 0 && isset($menu['icon'])) {
                                        echo '<div class="sb-nav-link-icon"><i class="' . $menu['icon'] . '"></i></div> ';
                                    }
                                    echo $menu['label'];
                                    echo '<div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>';
                                    echo '</a>';
                                    echo '<div class="collapse' . ($isOpen ? ' show' : '') . '" id="' . $submenuId . '" data-bs-parent="#' . $parentId . '">';
                                    $nestedNavId = 'nav-' . $submenuId;
                                    echo '<nav class="sb-sidenav-menu-nested nav" id="' . $nestedNavId . '">';
                                    renderMenu($menu['submenu'], $menuPermission, $menuAtivo, $nivel + 1, $nestedNavId);
                                    echo '</nav></div>';
                                }
                            } else {
                                // Para menus sem submenu, verifica se tem permissão própria
                                if ($hasPermitted) {
                                    $active = admsMenuItemMatchesCurrentRequest($menu, $menuAtivo) ? 'active' : '';
                                    $linkTarget = '';
                                    if (!empty($menu['target'])) {
                                        $linkTarget = ' target="' . htmlspecialchars((string) $menu['target'], ENT_QUOTES, 'UTF-8') . '" rel="noopener noreferrer"';
                                    }
                                    echo '<a href="' . $menu['url'] . '"' . $linkTarget . ' class="nav-link ' . $active . '">' . ($nivel == 0 && isset($menu['icon']) ? '<div class="sb-nav-link-icon"><i class="' . $menu['icon'] . '"></i></div> ' : '') . $menu['label'] . '</a>';
                                }
                            }
                        }
                    }
                }
                // Referência de item ativo: menu do controller tem prioridade sobre override antigo de outra seção
                $menuAtivo = $this->data['menu'] ?? false;
                $sessionMenuOverride = $_SESSION['menu_override'] ?? null;
                if (
                    is_string($sessionMenuOverride) && $sessionMenuOverride !== ''
                    && admsMenuSessionOverrideApplies($menuAtivo)
                    && (
                        !$menuAtivo
                        || $menuAtivo === $sessionMenuOverride
                        || $menuAtivo === 'relatorios'
                        || $menuAtivo === 'estoque'
                    )
                ) {
                    $menuAtivo = $sessionMenuOverride;
                }
                $menuPermissionList = is_array($this->data['menuPermission'] ?? null)
                    ? $this->data['menuPermission']
                    : [];
                renderMenu($menus, $menuPermissionList, $menuAtivo);
                ?>
            </div>
        </div>
        <!-- Rodapé com Informações do Usuário -->
        <div class="sb-sidenav-footer">
            <div class="small">Logado como:</div>
            <?= $_SESSION['user_name'] ?? '' ?><br>
            <?= $_SESSION['user_department'] ?? '' ?>
            <?php
            $menuPos = \App\adms\Helpers\PositionDisplayHelper::formatForDisplay((string)($_SESSION['user_position'] ?? ''));
            if ($menuPos !== '') {
                echo '<br>' . htmlspecialchars($menuPos);
            }
            ?>
        </div>
    </nav>
</div>