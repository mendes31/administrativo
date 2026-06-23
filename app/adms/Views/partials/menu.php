<?php
// var_dump($this->data['menuPermission']); // DEBUG: Exibe as permissões do menu do usuário
// deploy-sync: 2026-06-22 — menu completo (SST equipamentos + treinamentos)
use App\adms\Models\Repository\AdmsPasswordPolicyRepository;

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
                'permission' => 'ListPositions'
            ],
            [
                'label' => 'Centros de Custo',
                'url' => $_ENV['URL_ADM'] . 'list-cost-centers',
                'permission' => 'ListCostCenters'
            ],
            [
                'label' => 'Departamentos',
                'url' => $_ENV['URL_ADM'] . 'list-departments',
                'permission' => 'ListDepartments'
            ],
            [
                'label' => 'Turnos de trabalho',
                'url' => $_ENV['URL_ADM'] . 'list-work-shifts',
                'permission' => 'ListWorkShifts'
            ],
            [
                'label' => 'Níveis de Acesso',
                'url' => $_ENV['URL_ADM'] . 'list-access-levels',
                'permission' => 'ListAccessLevels'
            ],
            [
                'label' => 'Usuários',
                'icon' => 'fa-solid fa-users',
                'submenu' => [
                    [
                        'label' => 'Listar Usuários',
                        'url' => $_ENV['URL_ADM'] . 'list-users',
                        'permission' => 'ListUsers'
                    ],
                    [
                        'label' => 'Organograma',
                        'url' => $_ENV['URL_ADM'] . 'organization-chart',
                        'permission' => 'OrganizationChart'
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
                'permission' => 'ListInventoryItems'
            ],
            [
                'label' => 'Lotes Produzidos',
                'url' => $_ENV['URL_ADM'] . 'list-inventory-cost-production-batches',
                'any_of' => ['ListInvCostProductionBatches', 'SimulateInventoryCost', 'ListInventoryItems'],
            ],
            [
                'label' => 'Períodos de Custeio',
                'url' => $_ENV['URL_ADM'] . 'list-inventory-cost-periods',
                'any_of' => ['ListInvCostPeriods', 'SimulateInventoryCost', 'ListInventoryItems'],
            ],
            [
                'label' => 'Cadastros Bases',
                'icon' => 'fa-solid fa-database',
                'submenu' => [
                    [
                        'label' => 'Unidades de Medida',
                        'url' => $_ENV['URL_ADM'] . 'list-inventory-units',
                        'permission' => 'ListInventoryUnits'
                    ],
                    [
                        'label' => 'Categorias de Item',
                        'url' => $_ENV['URL_ADM'] . 'list-inventory-categories',
                        'permission' => 'ListInventoryCategories'
                    ],
                    [
                        'label' => 'Estoques',
                        'url' => $_ENV['URL_ADM'] . 'list-inventory-stocks',
                        'permission' => 'ListInventoryStocks'
                    ],
                    [
                        'label' => 'Posições Internas',
                        'url' => $_ENV['URL_ADM'] . 'list-inventory-positions',
                        'permission' => 'ListInventoryPositions'
                    ],
                    [
                        'label' => 'Operações de Produção',
                        'url' => $_ENV['URL_ADM'] . 'list-inventory-operations',
                        'permission' => 'ListInventoryOperations'
                    ],
                    [
                        'label' => 'Recursos de Produção',
                        'url' => $_ENV['URL_ADM'] . 'list-inventory-production-resources',
                        'permission' => 'ListInventoryProductionResources'
                    ],
                    [
                        'label' => 'Papéis de MO',
                        'url' => $_ENV['URL_ADM'] . 'list-inventory-labor-roles',
                        'permission' => 'ListInventoryLaborRoles'
                    ],
                ]
            ],
            [
                'label' => 'Transações de Estoque',
                'submenu' => [
                    [
                        'label' => 'Ajuste',
                        'url' => $_ENV['URL_ADM'] . 'create-inventory-adjust',
                        'permission' => 'CreateInventoryAdjust'
                    ],
                    [
                        'label' => 'Entrada',
                        'url' => $_ENV['URL_ADM'] . 'create-inventory-entry',
                        'permission' => 'CreateInventoryEntry'
                    ],
                    [
                        'label' => 'Saída',
                        'url' => $_ENV['URL_ADM'] . 'create-inventory-exit',
                        'permission' => 'CreateInventoryExit'
                    ],
                    [
                        'label' => 'Transferência',
                        'url' => $_ENV['URL_ADM'] . 'create-inventory-transfer',
                        'permission' => 'CreateInventoryTransfer'
                    ],
                    
                ]
            ],
            [
                'label' => 'Relatórios',
                'submenu' => [
                    [
                        'label' => 'Saldos de Estoque',
                        'url' => $_ENV['URL_ADM'] . 'report-inventory-balance',
                        'permission' => 'ReportInventoryBalance'
                    ],
                    [
                        'label' => 'Histórico de Movimentações',
                        'url' => $_ENV['URL_ADM'] . 'report-inventory-history',
                        'permission' => 'ReportInventoryHistory'
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
            ],
            [
                'label' => 'Categorias de Políticas',
                'icon'  => 'fa-solid fa-tags',
                'url'   => $_ENV['URL_ADM'] . 'list-policy-categories',
                'permission' => 'ListPolicyCategories',
            ],
            [
                'label' => 'Portal do Colaborador',
                'url' => $_ENV['URL_ADM'] . 'employee-portal',
                'permission' => 'EmployeePortal',
                'icon' => 'fas fa-user-circle'
            ],
            [
                'label' => 'Meus documentos (folha)',
                'url' => $_ENV['URL_ADM'] . 'my-payroll-documents',
                'permission' => 'MyPayrollDocuments',
                'icon' => 'fas fa-file-invoice-dollar'
            ],
            [
                'label' => 'Meus EPIs',
                'url' => $_ENV['URL_ADM'] . 'my-epi-deliveries',
                'permission' => 'MyEpiDeliveries',
                'icon' => 'fas fa-hard-hat'
            ],
            [
                'label' => 'Meus treinamentos SST',
                'url' => $_ENV['URL_ADM'] . 'my-sst-treinamentos',
                'permission' => 'MySstTreinamentos',
                'icon' => 'fas fa-graduation-cap'
            ],
            [
                'label' => 'Importar documentos RH (PDF)',
                'url' => $_ENV['URL_ADM'] . 'import-payroll-documents',
                'permission' => 'ImportPayrollDocuments',
                'icon' => 'fas fa-file-pdf'
            ],
            [
                'label' => 'Pendências de ciência (folha)',
                'url' => $_ENV['URL_ADM'] . 'list-payroll-signing-pendencies',
                'permission' => 'ListPayrollSigningPendencies',
                'icon' => 'fas fa-user-clock'
            ],
            [
                'label' => 'Cron lembretes folha (token)',
                'url' => $_ENV['URL_ADM'] . 'payroll-cron-config',
                'permission' => 'PayrollCronConfig',
                'icon' => 'fas fa-clock'
            ],
            [
                'label' => 'Tipos de documento (RH)',
                'url' => $_ENV['URL_ADM'] . 'list-payroll-document-types',
                'permission' => 'ListPayrollDocumentTypes',
                'icon' => 'fas fa-tags'
            ],
            [
                'label' => 'Desempenho',
                'icon' => 'fa-solid fa-chart-line',
                'submenu' => [
                    [
                        'label' => 'Avaliações de Desempenho',
                        'url' => $_ENV['URL_ADM'] . 'list-performance-reviews',
                        'permission' => 'ListPerformanceReviews'
                    ],
                    [
                        'label' => 'Metas (OKRs)',
                        'url' => $_ENV['URL_ADM'] . 'list-performance-goals',
                        'permission' => 'ListPerformanceGoals'
                    ],
                    [
                        'label' => 'Feedbacks',
                        'url' => $_ENV['URL_ADM'] . 'list-performance-feedbacks',
                        'permission' => 'ListPerformanceFeedbacks'
                    ],
                    [
                        'label' => 'Competências',
                        'url' => $_ENV['URL_ADM'] . 'list-competencies',
                        'permission' => 'ListCompetencies'
                    ],
                    [
                        'label' => 'Matriz de Competências',
                        'url' => $_ENV['URL_ADM'] . 'competency-matrix',
                        'permission' => 'CompetencyMatrix'
                    ],
                    [
                        'label' => 'Matriz 9BOX',
                        'url' => $_ENV['URL_ADM'] . 'nine-box-matrix',
                        'permission' => 'NineBoxMatrix'
                    ],
                    [
                        'label' => 'Dashboard de Desempenho',
                        'url' => $_ENV['URL_ADM'] . 'performance-dashboard',
                        'permission' => 'PerformanceDashboard'
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
                        'permission' => 'ListEmployeeRequests'
                    ],
                    [
                        'label' => 'Aprovações Pendentes',
                        'url' => $_ENV['URL_ADM'] . 'pending-approvals',
                        'permission' => 'PendingApprovals',
                        'badge' => true // Mostrar badge com contagem
                    ],
                    [
                        'label' => 'Tipos de Solicitação',
                        'url' => $_ENV['URL_ADM'] . 'list-request-types',
                        'permission' => 'ListRequestTypes'
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
                        'permission' => 'ListEmployeeTickets'
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
                        'permission' => 'PeopleAnalytics'
                    ],
                    [
                        'label' => 'Relatórios de RH',
                        'url' => $_ENV['URL_ADM'] . 'people-reports',
                        'permission' => 'PeopleReports'
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
                        'icon' => 'fas fa-chart-pie'
                    ],
                    [
                        'label' => 'Currículos / Candidatos',
                        'url' => $_ENV['URL_ADM'] . 'rh-candidatos',
                        'permission' => 'RhCandidatos',
                        'icon' => 'fas fa-user-tie'
                    ],
                    [
                        'label' => 'Vagas de Emprego',
                        'url' => $_ENV['URL_ADM'] . 'rh-vagas',
                        'permission' => 'RhVagas',
                        'icon' => 'fas fa-briefcase'
                    ],
                    [
                        'label' => 'Entrevistas',
                        'url' => $_ENV['URL_ADM'] . 'rh-entrevistas',
                        'permission' => 'RhEntrevistas',
                        'icon' => 'fas fa-calendar-alt'
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
        'id' => 'sst',
        'icon' => 'fa-solid fa-heart-pulse',
        'label' => 'Segurança e Medicina',
        'submenu' => [
            [
                'label' => 'Dashboard',
                'url' => $_ENV['URL_ADM'] . 'sst-dashboard',
                'permission' => 'SstDashboard',
                'icon' => 'fas fa-chart-line'
            ],
            [
                'label' => 'Cadastros e vínculos',
                'icon' => 'fa-solid fa-database',
                'submenu' => [
                    [
                        'label' => 'CIDs',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-cids',
                        'permission' => 'SstListCids',
                        'icon' => 'fas fa-notes-medical'
                    ],
                    [
                        'label' => 'EPIs',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-epis',
                        'permission' => 'SstListEpis',
                        'icon' => 'fas fa-hard-hat'
                    ],
                    [
                        'label' => 'Exames',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-exames',
                        'permission' => 'SstListExames',
                        'icon' => 'fas fa-stethoscope'
                    ],
                    [
                        'label' => 'Médicos',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-medicos',
                        'permission' => 'SstListMedicos',
                        'icon' => 'fas fa-user-md'
                    ],
                    [
                        'label' => 'Necessidades de EPI',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-epi-necessidade',
                        'permission' => 'SstListEpiNecessidade',
                        'icon' => 'fas fa-list-check'
                    ],
                    [
                        'label' => 'Necessidades de exame',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-exame-necessidade',
                        'permission' => 'SstListExameNecessidade',
                        'icon' => 'fas fa-clipboard-list'
                    ],
                    [
                        'label' => 'Necessidades de treinamento',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-treinamento-necessidade',
                        'permission' => 'SstListTreinamentoNecessidade',
                        'icon' => 'fas fa-clipboard-list'
                    ],
                    [
                        'label' => 'Matriz trein. × cargo',
                        'url' => $_ENV['URL_ADM'] . 'sst-matriz-treinamento-cargo',
                        'permission' => 'SstMatrizTreinamentoCargo',
                        'icon' => 'fas fa-th'
                    ],
                    [
                        'label' => 'Riscos',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-riscos',
                        'permission' => 'SstListRiscos',
                        'icon' => 'fas fa-exclamation-triangle'
                    ],
                    [
                        'label' => 'Treinamentos SST',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-treinamentos',
                        'permission' => 'SstListTreinamentos',
                        'icon' => 'fas fa-graduation-cap'
                    ],
                    [
                        'label' => 'GHE (ambientes)',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-ghe',
                        'permission' => 'SstListGhe',
                        'icon' => 'fas fa-industry'
                    ],
                    // SST equipamentos de segurança e vistorias periódicas
                    [
                        'label' => 'Tipos de equipamento',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-equipamento-tipos',
                        'permission' => 'SstListEquipamentoTipos',
                        'icon' => 'fas fa-layer-group'
                    ],
                    [
                        'label' => 'Equipamentos de segurança',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-equipamentos',
                        'permission' => 'SstListEquipamentos',
                        'icon' => 'fas fa-fire-extinguisher'
                    ],
                    [
                        'label' => 'Config. vistorias equipamentos',
                        'url' => $_ENV['URL_ADM'] . 'sst-equipamento-settings',
                        'permission' => 'SstEquipamentoSettings',
                        'icon' => 'fas fa-cog'
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
                        'icon' => 'fas fa-ambulance'
                    ],
                    [
                        'label' => 'Afastamentos',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-afastamentos',
                        'permission' => 'SstListAfastamentos',
                        'icon' => 'fas fa-procedures'
                    ],
                    [
                        'label' => 'ASOs',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-asos',
                        'permission' => 'SstListAsos',
                        'icon' => 'fas fa-file-medical'
                    ],
                    [
                        'label' => 'Status treinamentos SST',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-treinamento-vinculos',
                        'permission' => 'SstListTreinamentoVinculos',
                        'icon' => 'fas fa-user-graduate'
                    ],
                    [
                        'label' => 'Aplicar treinamento SST',
                        'url' => $_ENV['URL_ADM'] . 'sst-apply-treinamento',
                        'permission' => 'SstApplyTreinamento',
                        'icon' => 'fas fa-check-circle'
                    ],
                    [
                        'label' => 'Fichas de entrega EPI',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-epi-fichas',
                        'permission' => 'SstListEpiFichas',
                        'icon' => 'fas fa-file-signature'
                    ],
                    [
                        'label' => 'Movimentações EPI',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-epi-movimentos',
                        'permission' => 'SstListEpiMovimentos',
                        'icon' => 'fas fa-dolly'
                    ],
                    [
                        'label' => 'Inspeções',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-inspecoes',
                        'permission' => 'SstListInspecoes',
                        'icon' => 'fas fa-search'
                    ],
                    [
                        'label' => 'Vistorias de equipamentos',
                        'url' => $_ENV['URL_ADM'] . 'sst-minhas-equipamento-vistorias',
                        'permission' => 'SstMinhasEquipamentoVistorias',
                        'icon' => 'fas fa-clipboard-check'
                    ],
                    [
                        'label' => 'Ler QR equipamento',
                        'url' => $_ENV['URL_ADM'] . 'sst-scan-equipamento',
                        'permission' => 'SstScanEquipamento',
                        'icon' => 'fas fa-qrcode'
                    ],
                    [
                        'label' => 'CIPA',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-cipa-mandatos',
                        'permission' => 'SstListCipaMandatos',
                        'icon' => 'fas fa-users-cog'
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
                        'icon' => 'fas fa-balance-scale'
                    ],
                    [
                        'label' => 'Programas PGR/PCMSO',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-programas',
                        'permission' => 'SstListProgramas',
                        'icon' => 'fas fa-file-contract'
                    ],
                    [
                        'label' => 'Fila eSocial',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-esocial-eventos',
                        'permission' => 'SstListEsocialEventos',
                        'icon' => 'fas fa-cloud-upload-alt'
                    ],
                    [
                        'label' => 'PPP',
                        'url' => $_ENV['URL_ADM'] . 'sst-list-ppp',
                        'permission' => 'SstListPpp',
                        'icon' => 'fas fa-file-alt'
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
                        'icon' => 'fas fa-exclamation-circle'
                    ],
                    [
                        'label' => 'Relatório de EPIs',
                        'url' => $_ENV['URL_ADM'] . 'sst-report-epis',
                        'permission' => 'SstReportEpis',
                        'icon' => 'fas fa-chart-bar'
                    ],
                    [
                        'label' => 'Relatório de exames',
                        'url' => $_ENV['URL_ADM'] . 'sst-report-exames',
                        'permission' => 'SstReportExames',
                        'icon' => 'fas fa-chart-bar'
                    ],
                    [
                        'label' => 'Relatório treinamentos SST',
                        'url' => $_ENV['URL_ADM'] . 'sst-report-treinamentos',
                        'permission' => 'SstReportTreinamentos',
                        'icon' => 'fas fa-graduation-cap'
                    ],
                    [
                        'label' => 'Relatório de afastamentos',
                        'url' => $_ENV['URL_ADM'] . 'sst-report-afastamentos',
                        'permission' => 'SstReportAfastamentos',
                        'icon' => 'fas fa-procedures'
                    ],
                    [
                        'label' => 'Relatório por CID',
                        'url' => $_ENV['URL_ADM'] . 'sst-report-cids',
                        'permission' => 'SstReportCids',
                        'icon' => 'fas fa-notes-medical'
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

        return $token === $override || $token === 'relatorios';
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

/** menuAtivo / override coincide com permission ou com algum any_of */
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
                                    $submenuActive = false;
                                    
                                    // Verifica se algum submenu está ativo
                                    // Captura URL atual para permitir abertura mesmo sem depender de $menuAtivo
                                    $currentUrlPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
                                    $currentSegments = explode('/', trim((string)$currentUrlPath, '/'));
                                    $basePath = trim((string)(parse_url($_ENV['URL_ADM'] ?? '', PHP_URL_PATH) ?? ''), '/');
                                    if (!empty($basePath) && !empty($currentSegments) && $currentSegments[0] === $basePath) {
                                        array_shift($currentSegments);
                                    }
                                    $currentFirstSeg = $currentSegments[0] ?? '';
                                    $currentBaseName = basename((string)$currentUrlPath);
                                    $currentPathNoBase = implode('/', $currentSegments);
                                    foreach ($menu['submenu'] as $submenu) {
                                        if (menuEntryAllowed($submenu, $menuPermission)) {
                                            // Determinar primeiro segmento do caminho da URL (ex.: password-policy/1 -> password-policy)
                                            $firstSegment = '';
                                            if (isset($submenu['url'])) {
                                                $path = parse_url($submenu['url'], PHP_URL_PATH);
                                                $parts = explode('/', trim((string)$path, '/'));
                                                if (!empty($basePath) && !empty($parts) && $parts[0] === $basePath) {
                                                    array_shift($parts);
                                                }
                                                $firstSegment = $parts[0] ?? '';
                                                $submenuPathNoBase = implode('/', $parts);
                                            }
                                            // Prioriza override explícito (somente na seção correspondente)
                                            $override = $_SESSION['menu_override'] ?? null;
                                            if (
                                                admsMenuSessionOverrideApplies($menuAtivo)
                                                && $override
                                                && (
                                                    $override === ($submenu['permission'] ?? null)
                                                    || (!empty($submenu['any_of']) && is_array($submenu['any_of']) && in_array($override, $submenu['any_of'], true))
                                                    || $override === basename($submenu['url'] ?? '')
                                                )
                                            ) {
                                                $submenuActive = true;
                                                break;
                                            }
                                            // Match por controller/permission informado pelo controller
                                            if ((isset($menu['id']) && $menuAtivo == $menu['id'])
                                                || (isset($submenu['url']) && $menuAtivo == basename($submenu['url']))
                                                || ($firstSegment !== '' && $menuAtivo == $firstSegment)
                                                || menuEntryMatchesAtivo($submenu, $menuAtivo)) {
                                                $submenuActive = true;
                                                break;
                                            }
                                            // Match por URL atual (EVITAR basename numérico para não colidir com password-policy/{id})
                                            $submenuBaseName = '';
                                            if (isset($submenu['url'])) {
                                                $sbPath = parse_url($submenu['url'], PHP_URL_PATH);
                                                $submenuBaseName = is_string($sbPath) && $sbPath !== '' ? basename($sbPath) : '';
                                            }
                                            $isNumericBaseCurrent = ctype_digit($currentBaseName ?? '');
                                            $useBaseNameMatch = (!$isNumericBaseCurrent) && ($currentBaseName !== '') && ($submenuBaseName !== '') && ($currentBaseName === $submenuBaseName);
                                            if ($useBaseNameMatch
                                                || ($currentFirstSeg !== '' && $currentFirstSeg === ($firstSegment ?? ''))
                                                || (($currentPathNoBase !== '' && !empty($submenuPathNoBase)) && rtrim($currentPathNoBase, '/') === rtrim($submenuPathNoBase, '/')))
                                            {
                                                $submenuActive = true;
                                                break;
                                            }
                                        }
                                        // Verifica submenus aninhados
                                        if (isset($submenu['submenu'])) {
                                            foreach ($submenu['submenu'] as $nestedSubmenu) {
                                                if (menuEntryAllowed($nestedSubmenu, $menuPermission)) {
                                                    $nestedFirst = '';
                                                    if (isset($nestedSubmenu['url'])) {
                                                        $npath = parse_url($nestedSubmenu['url'], PHP_URL_PATH);
                                                        $nparts = explode('/', trim((string)$npath, '/'));
                                                        if (!empty($basePath) && !empty($nparts) && $nparts[0] === $basePath) {
                                                            array_shift($nparts);
                                                        }
                                                        $nestedFirst = $nparts[0] ?? '';
                                                        $nestedPathNoBase = implode('/', $nparts);
                                                    }
                                                    // Match por controller/permission
                                                    if ((isset($nestedSubmenu['url']) && $menuAtivo == basename($nestedSubmenu['url']))
                                                        || ($nestedFirst !== '' && $menuAtivo == $nestedFirst)
                                                        || menuEntryMatchesAtivo($nestedSubmenu, $menuAtivo)) {
                                                        $submenuActive = true;
                                                        break 2;
                                                    }
                                                    // Match por URL atual (evita basename numérico)
                                                    $nestedBaseName = '';
                                                    if (isset($nestedSubmenu['url'])) {
                                                        $nbPath = parse_url($nestedSubmenu['url'], PHP_URL_PATH);
                                                        $nestedBaseName = is_string($nbPath) && $nbPath !== '' ? basename($nbPath) : '';
                                                    }
                                                    $useNestedBase = (!ctype_digit($currentBaseName ?? '')) && ($currentBaseName !== '') && ($nestedBaseName !== '') && ($currentBaseName === $nestedBaseName);
                                                    if ($useNestedBase
                                                        || ($currentFirstSeg !== '' && $currentFirstSeg === ($nestedFirst ?? ''))
                                                        || (($currentPathNoBase !== '' && !empty($nestedPathNoBase)) && rtrim($currentPathNoBase, '/') === rtrim($nestedPathNoBase, '/')))
                                                    {
                                                        $submenuActive = true;
                                                        break 2;
                                                    }
                                                }
                                                // Suporte a terceiro nível (ex.: LGPD -> AIPD -> Templates AIPD -> Template X)
                                                if (isset($nestedSubmenu['submenu']) && is_array($nestedSubmenu['submenu'])) {
                                                    foreach ($nestedSubmenu['submenu'] as $deepSubmenu) {
                                                        if (menuEntryAllowed($deepSubmenu, $menuPermission)) {
                                                            $deepFirst = '';
                                                            if (isset($deepSubmenu['url'])) {
                                                                $dpath = parse_url($deepSubmenu['url'], PHP_URL_PATH);
                                                                $dparts = explode('/', trim((string)$dpath, '/'));
                                                                if (!empty($basePath) && !empty($dparts) && $dparts[0] === $basePath) {
                                                                    array_shift($dparts);
                                                                }
                                                                $deepFirst = $dparts[0] ?? '';
                                                                $deepPathNoBase = implode('/', $dparts);
                                                            }
                                                            if ((isset($deepSubmenu['url']) && $menuAtivo == basename($deepSubmenu['url']))
                                                                || ($deepFirst !== '' && $menuAtivo == $deepFirst)
                                                                || menuEntryMatchesAtivo($deepSubmenu, $menuAtivo)) {
                                                                $submenuActive = true;
                                                                break 3;
                                                            }
                                                            $deepBaseName = '';
                                                            if (isset($deepSubmenu['url'])) {
                                                                $dbPath = parse_url($deepSubmenu['url'], PHP_URL_PATH);
                                                                $deepBaseName = is_string($dbPath) && $dbPath !== '' ? basename($dbPath) : '';
                                                            }
                                                            $useDeepBase = (!ctype_digit($currentBaseName ?? '')) && ($currentBaseName !== '') && ($deepBaseName !== '') && ($currentBaseName === $deepBaseName);
                                                            if ($useDeepBase
                                                                || ($currentFirstSeg !== '' && $currentFirstSeg === ($deepFirst ?? ''))
                                                                || (($currentPathNoBase !== '' && !empty($deepPathNoBase)) && rtrim($currentPathNoBase, '/') === rtrim($deepPathNoBase, '/')))
                                                            {
                                                                $submenuActive = true;
                                                                break 3;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    
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
                                    $active = '';
                                    $path = parse_url($menu['url'], PHP_URL_PATH);
                                    $pathStr = is_string($path) ? $path : '';
                                    $menuSlugBase = $pathStr !== '' ? basename($pathStr) : '';
                                    // Matches com base no controller (menuAtivo) — slug do path, não basename() na URL completa
                                    $baseNameMatch = ($menuAtivo === $menuSlugBase);
                                    $firstSegmentMatch = false;
                                    $parts = explode('/', trim($pathStr, '/'));
                                    $basePath = trim((string)(parse_url($_ENV['URL_ADM'] ?? '', PHP_URL_PATH) ?? ''), '/');
                                    if (!empty($basePath) && !empty($parts) && $parts[0] === $basePath) {
                                        array_shift($parts);
                                    }
                                    $menuFirstSeg = $parts[0] ?? '';
                                    $menuPathNoBase = implode('/', $parts);
                                    if ($menuFirstSeg !== '' && $menuAtivo == $menuFirstSeg) {
                                        $firstSegmentMatch = true;
                                    }
                                    $permissionMatch = menuEntryMatchesAtivo($menu, $menuAtivo);

                                    // Matches com base na URL atual
                                    $currentUrlPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
                                    $currentSegments = explode('/', trim((string)$currentUrlPath, '/'));
                                    if (!empty($basePath) && !empty($currentSegments) && $currentSegments[0] === $basePath) {
                                        array_shift($currentSegments);
                                    }
                                    $currentFirstSeg = $currentSegments[0] ?? '';
                                    $currentBaseName = basename((string)$currentUrlPath);
                                    $currentPathNoBase = implode('/', $currentSegments);
                                    // Evita false positive quando basename atual é numérico
                                    $urlBaseNameMatch = (!ctype_digit($currentBaseName ?? '')) && ($currentBaseName === $menuSlugBase);
                                    $urlFirstSegMatch = ($currentFirstSeg !== '' && $currentFirstSeg === $menuFirstSeg);
                                    $urlFullMatch = ($currentPathNoBase !== '' && $menuPathNoBase !== '' && rtrim($currentPathNoBase, '/') === rtrim($menuPathNoBase, '/'));

                                    $urlMatch = ($urlFullMatch || $urlBaseNameMatch || $urlFirstSegMatch);
                                    $menuCoarseMatch = ($baseNameMatch || $firstSegmentMatch || $permissionMatch);

                                    // Política: priorizar match mais específico (URL completa), 
                                    // depois basename/segmento, e por fim permission/controller.
                                    if ($urlFullMatch) {
                                        $active = 'active';
                                    } elseif ($urlBaseNameMatch || $urlFirstSegMatch) {
                                        $active = 'active';
                                    } elseif ($menuCoarseMatch) {
                                        $active = 'active';
                                    }
                                    echo '<a href="' . $menu['url'] . '" class="nav-link ' . $active . '">' . ($nivel == 0 && isset($menu['icon']) ? '<div class="sb-nav-link-icon"><i class="' . $menu['icon'] . '"></i></div> ' : '') . $menu['label'] . '</a>';
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