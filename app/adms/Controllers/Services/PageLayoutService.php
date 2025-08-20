<?php

namespace App\adms\Controllers\Services;

use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Repository\MenuPermissionUserRepository;
use App\adms\Models\Repository\UsersAccessLevelsRepository;

class PageLayoutService
{
    public function configurePageElements(array $data): array
    {
        // Array com os itens de menu
        $menu = [
            'Dashboard',
            'ListUsers',
            'ListDepartments',
            'ListPositions',
            'ListAccessLevels',
            'ListPackages',
            'ListGroupsPages',
            'ListPages',
            'ListBanks',
            'ListMovBetweenAccounts',
            'ListPayments',
            'ListReceipts',
            'ListBalance',
            'FinancialReport',
            'ListCostCenters',
            "ListAccountsPlan",
            "ListCustomers",
            "ListSuppliers",
            "ListFrequencies",
            "ListPaymentMethods",
            "Movements",
            "CashFlow",
            "ExportPdfCashFlow",
            "CostCenterSummary",
            "FlowCashCompetence",
            "ListDocuments",
            "ListTrainings",   
            "UpdateTrainingMatrix",
            'ListTrainingStatus',
            "MatrixByUser",
            "TrainingMatrixManager",
            "ListEvaluationModels",
            "CreateEvaluationModel",
            "ViewEvaluationModel",
            "UpdateEvaluationModel",
            "DeleteEvaluationModel",
            "ListEvaluationQuestions",
            "CreateEvaluationQuestion",
            "UpdateEvaluationQuestion",
            "DeleteEvaluationQuestion",
            "ListEvaluationAnswers",
            "CreateEvaluationAnswer",
            "UpdateEvaluationAnswer",
            "DeleteEvaluationAnswer",
            "ViewEvaluationAnswer",
            "GetQuestionsByModel",
            "EmailConfig",
            'TestNotification',
            "CompletedTrainingsMatrix",
            "TrainingKpiDashboard",
            "MyEvaluations",
            "HistoricoAvaliacoes",
            "Notificacoes",
            "PasswordPolicy",
            'ListLogAlteracoes',
            'ListLogAcessos',
            'ListStrategicPlans',
            "ListBranches",
            "ListInformativos",
            'LgpdRopa', 
            'LgpdCategoriasTitulares',
            'LgpdFinalidades',
            'LgpdBasesLegais',
            'LgpdTiposDados',
            'LgpdClassificacoesDados',
            'LgpdBasesLegais',
            'UpdateLgpdBasesLegais',
            'DeleteLgpdBasesLegais',
            'LgpdInventory',
            'LgpdDataMapping',
            'LgpdDashboard',
            'LgpdAipd',
            'LgpdAipdCreate',
            'LgpdAipdEdit',
            'LgpdAipdView',
            'LgpdAipdDelete',
            'LgpdAipdSuggest',
            'LgpdRipd',
            'LgpdRipdCreate',
            'LgpdRipdEdit',
            'LgpdRipdView',
            'LgpdRipdDelete',
            'LgpdRipdDashboard',
            'LgpdAipdTemplateSaude',
            'LgpdAipdTemplateFinanceiro',
            'LgpdAipdTemplateEcommerce',
            'LgpdAipdTemplateEducacao',
            'LgpdAipdTemplateRh',
            'LgpdAipdTemplateMarketing',
            'LgpdAipdTemplateTelecom',
            'LgpdAipdTemplateLogistica',
            'LgpdAipdTemplateJuridico',
            'LgpdConsentimentos',
            'LgpdConsentimentosCreate',
            'LgpdConsentimentosEdit',
            'LgpdConsentimentosView',
            'LgpdConsentimentosDelete',
            'LgpdConsentimentoColeta',
            'LgpdConsentimentoColetaProcessar',
            'LgpdConsentimentoEmail',
            'LgpdConsentimentoEmailProcessar',
            'LgpdTia',
            'LgpdTiaCreate',
            'LgpdTiaEdit',
            'LgpdTiaView',
            'LgpdTiaDelete',
            'LgpdTiaDashboard',
            'LgpdTiaTemplate',
            'LgpdTiaTemplates',
            'LgpdTiaExportPdf',
            'LgpdTiaExportPdfView',
            'LgpdTiaExportPdfList',
            'LgpdTiaTemplateRh',
            'LgpdTiaTemplateMarketing',
            'LgpdTiaTemplateFinanceiro',
            'LgpdTiaTemplateTi',
            'LgpdWorkflowReport',
            'LgpdRopaCreateFromInventory',
            'LgpdDataMappingCreateFromRopa',
            'LgpdRipdExportPdf',
            'LgpdRipdExportPdfList',
            'LgpdRipdExportPdfView',
            'LgpdInventoryCreate',
            'LgpdInventoryEdit',
            'LgpdInventoryView',
            'LgpdInventoryDelete',
            'LgpdRopaCreate',
            'LgpdRopaEdit',
            'LgpdRopaView',
            'LgpdRopaDelete',
            'LgpdDataMappingCreate',
            'LgpdDataMappingEdit',
            'LgpdDataMappingView',
            'LgpdDataMappingDelete',
            'LgpdCategoriasTitularesCreate',
            'LgpdCategoriasTitularesEdit',
            'LgpdCategoriasTitularesView',
            'LgpdCategoriasTitularesDelete',
            'LgpdFinalidadesCreate',
            'LgpdFinalidadesEdit',
            'LgpdFinalidadesView',
            'LgpdFinalidadesDelete',
            'LgpdBasesLegaisCreate',
            'LgpdBasesLegaisEdit',
            'LgpdBasesLegaisView',
            'LgpdBasesLegaisDelete',
            'LgpdTiposDadosCreate',
            'LgpdTiposDadosEdit',
            'LgpdTiposDadosView',
            'LgpdTiposDadosDelete',
            'LgpdClassificacoesDadosCreate',
            'LgpdClassificacoesDadosEdit',
            'LgpdClassificacoesDadosView',
            'LgpdClassificacoesDadosDelete',

            // Adicione aqui outras permissões de páginas/submenus que possam existir no futuro
        ];

        // Verificar se o usuário está logado
        if (!isset($_SESSION['user_id'])) {
            // Para páginas públicas sem usuário logado, retornar apenas os dados básicos
            return array_merge($data, ['menuPermission' => []]);
        }

        // Verificar se o usuário tem o nível de acesso Super Administrador.
        // Nível de acesso Super Administrador tem acesso a todos os botões, não precisa validar as permissões no banco de dados
        $usersAccessLevels = new UsersAccessLevelsRepository();
        $userAccessLevelsArray = $usersAccessLevels->getUserAccessLevelArray($_SESSION['user_id']);
        $userAccessLevelsArray = $userAccessLevelsArray ? $userAccessLevelsArray : [];
        if (in_array(1, $userAccessLevelsArray, true)) {
            return array_merge($data, ['menuPermission' => $menu]);
        }

        // Apresentar ou ocultar botão
        $buttonPermission = new ButtonPermissionUserRepository();
        $data['buttonPermission'] = $buttonPermission->buttonPermission($data['buttonPermission'] ?? []);

        // Apresentar ou ocultar item de menu
        $menuPermission = new MenuPermissionUserRepository();
        $data['menuPermission'] = $menuPermission->menuPermission($menu);

        return $data;
    }
}
