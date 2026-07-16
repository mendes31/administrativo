<?php
/**
 * Roteador administrativo (LoadPageAdm)
 *
 * - Este roteador só utiliza os parâmetros 'url' ou 'controller' para decidir qual controller carregar.
 * - Todos os outros parâmetros da URL (ex: page, per_page, nome, status, publica, etc.) são repassados intactos para os controllers via $_GET.
 * - Controllers de listagem devem SEMPRE ler filtros e paginação diretamente de $_GET.
 * - O roteador NUNCA deve filtrar, modificar ou remover parâmetros extras da URL.
 * - Isso garante robustez e flexibilidade para qualquer tela de listagem, mesmo com múltiplos filtros e paginação.
 *
 * Exemplo de URL suportada:
 *   /administrativo/?url=list-pages&page=2&per_page=10&nome=teste&status=Ativo
 *
 * Se 'url' e 'controller' estiverem ausentes ou inválidos, o usuário é redirecionado para o dashboard.
 */

namespace Routes;

use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\SlugController;

/**
 * Classe LoadPageAdm
 * 
 * Esta classe é responsável por carregar a página de administração solicitada, verificando se a página e a controller existem, 
 * e se o método necessário está presente na controller. Ela também registra logs de erros ou acessos bem-sucedidos.
 * 
 * @package App\adms\Controllers\Services
 * @author Rafael Mendes
 */
class LoadPageAdm
{
    /** @var string $urlController Recebe da URL o nome da controller */
    private string $urlController;

    /** @var string $urlParameter Recebe da URL o parametro */
    private string $urlParameter;

    /** @var string $classLoad Controller que deve ser carregada */
    private string $classLoad;

    /**
     * Lista legada de controllers “públicos” para o fluxo antigo desta classe.
     * Em produção o `PageController` usa {@see LoadPageAdmAccessLevel}, que lê `adms_pages.public_page`
     * na base — não confundir estas listas com o cadastro de páginas.
     */
    private array $listPgPublic = [
        "Login", "Error403", "NewUser", "ForgotPassword", "ResetPassword", "LgpdConsentimentoLogin",
        "MeetingBookingRsvp",
        // Servidor de arquivos foi tornado público para evitar problemas
        // de permissão com avatares e imagens em dashboards, mantendo ainda
        // toda a validação de caminho dentro do próprio FileServer.
        "ServeFile"
    ];

    private array $listPgPrivate = [
        "Dashboard",
        "ListUsers", "ViewUser", "CreateUser", "UpdateUser", "UpdateUserImage", "DeleteUserImage", "DeleteUser", "UpdatePasswordUser", "UpdatePassword", "Profile", "ImportUsers", "ImportPayrollDocuments", "PayrollImportBatchReport", "PayrollImportBatchAudit", "ListPayrollDocumentTypes", "CreatePayrollDocumentType", "UpdatePayrollDocumentType", "DeletePayrollDocumentType", "MyPayrollDocuments", "ViewPayrollDocument", "ViewPayrollSignedBundle", "ConfirmPayrollDocumentDownload", "SignPayrollDocument", "PayrollSignatureReceipt", "ListPayrollSigningPendencies", "PayrollRemindersCron", "PayrollCronConfig", "OrganizationChart", "ExportUsersExcel", "ExportUsersPdf", "MyEpiDeliveries", "SignEpiFicha", "ViewEpiFichaPdf", "MySstTreinamentos", "ViewSstTreinamentoCertificadoPdf",
        "Logout", 
        "ListAccessLevels", "CreateAccessLevel", "ViewAccessLevel", "UpdateAccessLevel", "DeleteAccessLevel", "ImportAccessLevels",
        "ExportAccessLevelsPermissionsPdf", "ExportAccessLevelsPermissionsExcel",
        "ListDepartments",  "CreateDepartment",  "ViewDepartment", "UpdateDepartments", "DeleteDepartment", "ImportDepartments",
        "ListWorkShifts", "CreateWorkShift", "ViewWorkShift", "UpdateWorkShift", "DeleteWorkShift",
        "UpdateUserAccessLevels",
        "AccessLevelPageSync", "ListPackages", "CreatePackage", "ViewPackage", "UpdatePackage", "DeletePackage",
        "ListGroupsPages", "ViewGroupPage", "CreateGroupPage", "UpdateGroupPage", "DeleteGroupPage",
        "ListPages", "ViewPage", "CreatePage", "UpdatePage", "DeletePage",
        "ListPositions", "CreatePosition", "ViewPosition", "UpdatePosition", "DeletePosition", "ImportPositions",
        "ListAccessLevelsPermissions",
        "ListBanks", "CreateBank", "ViewBank", "UpdateBank", "DeleteBank",
        "ListMovBetweenAccounts", "CreateMovBetweenAccounts", "ViewMovBetweenAccounts", "UpdateMovBetweenAccounts", "DeleteMovBetweenAccounts",
        "ListCostCenters", "CreateCostCenter", "ViewCostCenter", "UpdateCostCenter", "DeleteCostCenter", "ImportCostCenters",
        "ListAccountsPlan", "CreateAccountPlan", "ViewAccountPlan", "UpdateAccountPlan", "DeleteAccountPlan",
        "ListFrequncies", "CreateFrequncy", "ViewFrequncy", "UpdateFrequncy", "DeleteFrequncy",
        "ListCustomers", "CreateCustomer", "ViewCustomer", "UpdateCustomer", "DeleteCustomer",
        "ListSuppliers", "CreateSupplier", "ViewSupplier", "UpdateSupplier", "DeleteSupplier", 'ProcessFile',
        "ListPaymentMethods", "CreatePaymentMethod", "ViewPaymentMethod", "UpdatePaymentMethod", "DeletePaymentMethod",
        "ListPayments", "CreatePay", "ViewPay", "UpdatePay", "DeletePay", "Payment", "Installments", "ListPartialValues", "ClearBusyPay", "CheckBusy", "GetPaymentsStatus",
        "ListReceipts", "CreateReceive", "ViewReceive", "UpdateReceive", "DeleteReceive", "Receive", "Installments", "ListPartialValues", "ClearBusyReceive", "CheckBusy", "GetReceiptsStatus",
        "Movements","CashFlow","ExportPdfCashFlow",
        "EditMovement", "DeleteMovement",
        "TrainingKpiDashboard",
        "TrainingComplianceDashboard",
        "ListTrainings", "CreateTraining", "UpdateTraining", "DeleteTraining", "ViewTraining", "TrainingPositions", "TrainingMatrixManager", "ListTrainingStatus", "ApplyTraining", "NewTrainingVersion", "TrainingVersionAudit",
        "UpdateTrainingMatrix",
        "ListEvaluationModels", "CreateEvaluationModel", "UpdateEvaluationModel", "DeleteEvaluationModel", "ViewEvaluationModel",
        "CreateEvaluationModelWithQuestions",
        "ListEvaluationQuestions", "CreateEvaluationQuestion", "UpdateEvaluationQuestion", "DeleteEvaluationQuestion", "ViewEvaluationQuestion",
        "ListEvaluationAnswers", "CreateEvaluationAnswer", "UpdateEvaluationAnswer", "DeleteEvaluationAnswer", "ViewEvaluationAnswer",
        "AssignEvaluation", "CancelEvaluationAssignment", "ListEvaluationAssignments",
        "AnswerEvaluation", "ViewEvaluationResult", "EvaluationHistory",
        "MyEvaluations", "PrintEvaluationResult", "PrintEvaluationBlank",
        "GetQuestionsByModel",
        "ScheduleTraining", "ApplyTraining", "ListTrainingStatus", "TrainingPositions", "TrainingMatrixManager", "ListTrainings", "CreateTraining", "UpdateTraining", "DeleteTraining", "ViewTraining", "UpdateTrainingMatrix", "NewTrainingVersion", "TrainingVersionAudit",
        "MatrixByUser", "TestNotification", "SyncTrainingLinks", "CreateTestData", "TrainingDashboard",
        "SendNotification",
        "CreateTrainingUser", "DeleteTrainingUserLink",
        "EmailConfig",
        "AjaxSimplePasswordValidate",
        "AjaxPasswordPolicy",
        "ListLogAlteracoes", "ViewLogAlteracao", "ExportLogCsv", "ExportLogExcel", "ExportLogPdf", "ListLogAcessos", "ExportLogAcessosExcel", "ExportLogAcessosPdf",
        "ListConnectedUsers", "ListUsersLastAccess",
        "ExportUsersLastAccessPdf", "ExportUsersLastAccessExcel",
        "ForcePasswordChange",
        "ListStrategicPlans", "CreateStrategicPlan", "EditStrategicPlan", "UpdateStrategicPlan", "DeleteStrategicPlan", "ViewStrategicPlan",
        "ViewStrategicPlanObservations", "AddStrategicPlanObservation",
        "ListStrategicIndicators", "CreateStrategicIndicator", "EditStrategicIndicator", "UpdateStrategicIndicator", "DeleteStrategicIndicator", "ViewStrategicIndicator",
        "ListBranches", "CreateBranch", "ViewBranch", "UpdateBranch", "DeleteBranch",
        "ListInformativos", "CreateInformativo", "ViewInformativo", "UpdateInformativo", "DeleteInformativo", "RemoveInformativoImagem", "RemoveInformativoAnexo", "ResendInformativoPush", "InformativoPushStatus", "InformativosPublishCron",
        // Políticas Internas
        "ListPolicies", "CreatePolicy", "ViewPolicy", "UpdatePolicy", "DeletePolicy", "AcknowledgePolicy", "ReadPolicy", "ResendPolicyPush",
        "RelatorioPolicy", "ExportRelatorioPolicyPdf",
        "ListPolicyCategories", "CreatePolicyCategory", "UpdatePolicyCategory", "DeletePolicyCategory",
        "ListNotifications",
        "LgpdDashboard",
        "LgpdRopa", "LgpdRopaCreate", "LgpdRopaEdit", "LgpdRopaView", "LgpdRopaDelete",
        "LgpdCategoriasTitulares", "LgpdCategoriasTitularesCreate", "LgpdCategoriasTitularesEdit", "LgpdCategoriasTitularesView", "LgpdCategoriasTitularesDelete",
        "LgpdFinalidades", "LgpdFinalidadesCreate", "LgpdFinalidadesEdit", "LgpdFinalidadesView", "LgpdFinalidadesDelete",
        "LgpdBasesLegais", "LgpdBasesLegaisCreate", "LgpdBasesLegaisEdit", "LgpdBasesLegaisView", "LgpdBasesLegaisDelete",
        "LgpdTermos", "LgpdTermosCreate", "LgpdTermosEdit", "LgpdTermosView", "LgpdTermosDelete", "LgpdTermosExportPdf",
        "LgpdTiposDados", "LgpdTiposDadosCreate", "LgpdTiposDadosEdit", "LgpdTiposDadosView", "LgpdTiposDadosDelete",
        "LgpdClassificacoesDados", "LgpdClassificacoesDadosCreate", "LgpdClassificacoesDadosEdit", "LgpdClassificacoesDadosView", "LgpdClassificacoesDadosDelete",
        "LgpdInventory", "LgpdInventoryCreate", "LgpdInventoryEdit", "LgpdInventoryView", "LgpdInventoryDelete",
        "LgpdDataMapping", "LgpdDataMappingCreate", "LgpdDataMappingEdit", "LgpdDataMappingView", "LgpdDataMappingDelete",
        "LgpdRopaCreateFromInventory", "LgpdDataMappingCreateFromRopa", "LgpdWorkflowReport",
        "LgpdAipd", "LgpdAipdCreate", "LgpdAipdEdit", "LgpdAipdView", "LgpdAipdDelete", "LgpdAipdSuggest",
        "LgpdRipd", "LgpdRipdCreate", "LgpdRipdEdit", "LgpdRipdView", "LgpdRipdDelete", "LgpdRipdDashboard", "LgpdRipdExportPdf", "LgpdRipdExportPdfList", "LgpdRipdExportPdfView",
        "LgpdAipdTemplateSaude", "LgpdAipdTemplateFinanceiro", "LgpdAipdTemplateEcommerce", "LgpdAipdTemplateEducacao", "LgpdAipdTemplateRh", "LgpdAipdTemplateMarketing", "LgpdAipdTemplateTelecom", "LgpdAipdTemplateLogistica", "LgpdAipdTemplateJuridico",
        "LgpdConsentimentos", "LgpdConsentimentosCreate", "LgpdConsentimentosEdit", "LgpdConsentimentosView", "LgpdConsentimentosDelete", "LgpdConsentimentosRevogar",
        "LgpdConsentimentoColeta", "LgpdConsentimentoColetaProcessar", "LgpdConsentimentoEmail", "LgpdConsentimentoEmailProcessar",
        "LgpdTia", "LgpdTiaCreate", "LgpdTiaEdit", "LgpdTiaView", "LgpdTiaDelete", "LgpdTiaDashboard", "LgpdTiaTemplateFinanceiro", "LgpdTiaTemplateMarketing", "LgpdTiaTemplateRh", "LgpdTiaTemplateTi", "LgpdTiaTemplates", "LgpdTiaExportPdf", "LgpdTiaExportPdfList", "LgpdTiaExportPdfView",
        // CRM - Módulo de Gestão de Relacionamento com Clientes
        "CrmDashboard", "CrmKanbanPipeline", "CrmMoveOpportunity",
        "CrmListPartners", "CrmCreatePartner", "CrmViewPartner", "CrmUpdatePartner", "CrmDeletePartner",
        "CrmListOpportunities", "CrmCreateOpportunity", "CrmViewOpportunity", "CrmUpdateOpportunity", "CrmDeleteOpportunity",
        "CrmListActivities", "CrmCreateActivity", "CrmCompleteActivity", "CrmDeleteActivity",
        "CrmCreateNote", "CrmDeleteNote",
        "CrmUploadDocument", "CrmDownloadDocument", "CrmDeleteDocument",
        "CrmListTags", "CrmCreateTag", "CrmUpdateTag", "CrmDeleteTag",
        "CrmReportPipeline", "CrmReportPerformance", "CrmReportConversion",
        "CrmImportPartners", "CrmExportPartners", "CrmDownloadTemplatePartners",
        "CrmImportOpportunities", "CrmExportOpportunities", "CrmDownloadTemplateOpportunities",
        "WhatsAppConfig", "CrmSendWhatsApp",
        "SapApiConfig", "SaveSapApiConfig", "TestSapApiConfig",
        "McpChatApi",
        "CreateEmailConfig", "ListEmailConfig", "TestEmailConfig",
        "McpApiConfig", 
        "SaveMcpApiConfig",
        // Relatórios Dinâmicos
        "ListDynamicReports", "ListDynamicReportsSap",
        "DynamicReportBuilder", "DynamicReportBuilderLocal", "DynamicReportBuilderSap",
        "ViewDynamicReport", "SaveDynamicReport", "ExecuteDynamicReport",
        "DeleteDynamicReport", "ExportDynamicReportExcel", "ExportDynamicReportPdf", "ExportDynamicReportCsv",
        // Dashboards - fontes de dados
        "DashboardDataSources",
        // Dashboards de KPI
        "ListKpiDashboards", "ViewKpiDashboard", "CreateKpiDashboard", "UpdateKpiDashboard", "DeleteKpiDashboard", "GetKpiWidgetData",
        // Dashboards Dinâmicos (Power BI-like)
        "ListDashboards", "CreateDashboard", "ViewDashboard", "EditDashboard", "DeleteDashboard", "DuplicateDashboard",
        "ExecuteDashboard", "GetFilterOptions", "UploadSpreadsheet", "GetSpreadsheetFields",
        // Dashboard de Vendas SAP B1
        "SalesDashboard", "SalesDashboardData",
        // RH - Currículos / Candidatos
        "RhCandidatos", "RhCandidatosView", "RhCandidatosCreate", "RhCandidatosEdit", "RhCandidatosDelete", "RhCandidatosVagas",
        "RhVagas", "RhVagasView", "RhVagasCreate", "RhVagasEdit", "RhVagasDelete", "RhVagasPipeline",
        "RhVagasCandidatos", "RhVincularCandidatoVaga", "RhAtualizarStatusCandidatura",
        "RhKpiDashboard",
        "RhEntrevistas", "RhEntrevistasCreate", "RhEntrevistasView", "RhEntrevistasEdit", "RhEntrevistasDelete",
        "CalendarConfig",
        // Reserva de Salas - Tipos de Solicitação (Salas)
        "RoomsListRequestTypes", "RoomsCreateRequestType", "RoomsUpdateRequestType", "RoomsDeleteRequestType",
        // Reserva de Salas - Equipes/Grupos responsáveis
        "RoomsListRequestGroups", "RoomsCreateRequestGroup", "RoomsUpdateRequestGroup", "RoomsDeleteRequestGroup",
        // Reserva de Salas - Solicitações avulsas
        "RoomsListServiceRequests", "RoomsCreateServiceRequest", "RoomsViewServiceRequest", "RoomsUpdateServiceRequest", "RoomsDeleteServiceRequest",
        "RoomsCalendarIntegrationSettings",
        "RoomBookingSlotHold",
        // Gestão de Projetos
        "ListProjects", "CreateProject", "UpdateProject", "DeleteProject",
        // Gestão de Projetos - Grupos de Etapas
        "ListStageGroups", "CreateStageGroup", "UpdateStageGroup", "DeleteStageGroup",
        // Permissões - utilitários
        "CopyAccessLevelPermissions",
        // Gamificação
        "ListGamificationTimelineRules", "UpdateGamificationTimelineRule",
        "ListGamificationQuizzes", "CreateGamificationQuiz", "UpdateGamificationQuiz", "DeleteGamificationQuiz",
        "ListGamificationQuizQuestions", "CreateGamificationQuizQuestion", "UpdateGamificationQuizQuestion", "DeleteGamificationQuizQuestion",
        "ListGamificationPointLedger", "GamificationQuizCatalog", "TakeGamificationQuiz", "SubmitGamificationQuizAttempt",
        "GamificationLeaderboard", "GamificationEngagementDashboard",
        "TermosDeUso", "PoliticaPrivacidade",
        // SAC — Módulo de Atendimento ao Cliente (SmartSAC)
        "SacDashboard",
        "SacListCategories", "SacCreateCategory", "SacUpdateCategory", "SacDeleteCategory",
        "SacListSlaRules", "SacCreateSlaRule", "SacUpdateSlaRule", "SacDeleteSlaRule",
        "SacListClients", "SacCreateClient", "SacViewClient", "SacUpdateClient", "SacDeleteClient",
        "SacListTickets", "SacCreateTicket", "SacViewTicket", "SacUpdateTicket", "SacDeleteTicket", "SacReplyTicket", "SacTransferTicket", "SacRateTicket",
        // SST — Segurança e Medicina do Trabalho
        "SstDashboard", "SstEmployeeProfile", "SstReportPendencias", "SstReportExames", "SstReportEpis",
        "SstListExames", "SstCreateExame", "SstViewExame", "SstUpdateExame", "SstDeleteExame",
        "SstListAsos", "SstCreateAso", "SstViewAso", "SstUpdateAso", "SstDeleteAso",
        "SstAbrirAsoPendencia", "SstRegistrarResultadosAso",
        "SstListAfastamentos", "SstCreateAfastamento", "SstViewAfastamento", "SstUpdateAfastamento", "SstDeleteAfastamento",
        "SstListEpis", "SstCreateEpi", "SstViewEpi", "SstUpdateEpi", "SstDeleteEpi",
        "SstListEpiEntregas", "SstCreateEpiEntrega", "SstViewEpiEntrega", "SstUpdateEpiEntrega", "SstDeleteEpiEntrega",
        "SstListEpiFichas", "SstCreateEpiFicha", "SstViewEpiFicha", "SstExportEpiFichaPdf",
        "SstListEpiEstoque", "SstListEpiMovimentos", "SstCreateEpiMovimento",
        "SstListRiscos", "SstCreateRisco", "SstViewRisco", "SstUpdateRisco", "SstDeleteRisco",
        "SstListAcidentes", "SstCreateAcidente", "SstViewAcidente", "SstUpdateAcidente", "SstDeleteAcidente",
        "SstListCids", "SstCreateCid", "SstUpdateCid", "SstDeleteCid",
        "SstListMedicos", "SstCreateMedico", "SstUpdateMedico", "SstDeleteMedico",
        "SstListEpiNecessidade", "SstCreateEpiNecessidade", "SstUpdateEpiNecessidade", "SstDeleteEpiNecessidade",
        "SstListExameNecessidade", "SstCreateExameNecessidade", "SstUpdateExameNecessidade", "SstDeleteExameNecessidade",
        "SstListTreinamentos", "SstCreateTreinamento", "SstViewTreinamento", "SstUpdateTreinamento", "SstDeleteTreinamento",
        "SstListTreinamentoNecessidade", "SstCreateTreinamentoNecessidade", "SstUpdateTreinamentoNecessidade", "SstDeleteTreinamentoNecessidade",
        "SstMatrizTreinamentoCargo", "SstSaveMatrizTreinamentoCargo",
        "SstListRiscoTreinamento", "SstCreateRiscoTreinamento", "SstUpdateRiscoTreinamento", "SstDeleteRiscoTreinamento", "SstSaveRiscoTreinamentos",
        "SstListTreinamentoVinculos", "SstViewTreinamentoVinculo", "SstApplyTreinamento", "SstSyncTreinamentoVinculos", "SstReportTreinamentos", "SstExportTreinamentoCertificadoPdf",
        "SstListGhe", "SstCreateGhe", "SstViewGhe", "SstUpdateGhe", "SstDeleteGhe", "SstSaveGheRelacionamentos",
        "SstListRiscoCargo", "SstCreateRiscoCargo", "SstUpdateRiscoCargo", "SstDeleteRiscoCargo",
        "SstListRiscoExame", "SstCreateRiscoExame", "SstUpdateRiscoExame", "SstDeleteRiscoExame",
        "SstListRiscoEpi", "SstCreateRiscoEpi", "SstUpdateRiscoEpi", "SstDeleteRiscoEpi",
        "SstSaveRiscoRelacionamentos",
        "SstPacoteExamesAso",
        "SstEncaminhamentoAso",
        "SstExportEncaminhamentoAsoPdf",
        "SstSearchCids",
        "SstListEquipamentoTipos", "SstCreateEquipamentoTipo", "SstViewEquipamentoTipo", "SstUpdateEquipamentoTipo", "SstDeleteEquipamentoTipo",
        "SstManageEquipamentoChecklistItem",
        "SstListEquipamentos", "SstCreateEquipamento", "SstViewEquipamento", "SstUpdateEquipamento", "SstDeleteEquipamento",
        "SstListEquipamentoVistorias", "SstMinhasEquipamentoVistorias", "SstExecuteEquipamentoVistoria",
        "SstGenerateEquipamentoVistoria", "SstScanEquipamento", "SstExportEquipamentoQr",
        "SstRegisterEquipamentoRecarga",
    ];

    /** @var array $listDirectory Recebe a lista de diretórios com as controllers */
    private array $listDirectory = [
        "login",
        "dashboard",
        "users",
        "errors",
        "accessLevels",
        "departments",
        "packages",
        "groupsPages",
        "pages",
        "positions",
        "banks",
        "pay",
        "receive",
        "costCenter",
        "accountsPlan",
        "frequency",
        "customer",
        "supplier",
        "paymentMethod",
        "financialReports",
        "movement",
        "qualityAssurance",
        "trainings",
        "evaluations",  
        "settings",
        "strategicPlans",
        "strategicIndicators",
        "branches",
        "informativos",
        // Diretório das controllers de Políticas Internas
        "policies",
        "serveFile",
        "lgpd",
        "crm",
        "reports",
        "dashboards",
        "rh",
        // Portal do Colaborador / folha de pagamento (PSR-4: app/adms/Controllers/portal)
        "portal",
        // Diretório para controllers de permissões (ListAccessLevelsPermissions, CopyAccessLevelPermissions, etc.)
        "permission",
        "notifications",
        "gamification",
        "legal",
        "companyEvents",
        "sac",
        "sst"
    ];

    /** @var array $listPackages Recebe a lista de pacotes com as controllers */
    private array $listPackages = ["adms"];

    /**
     * Carregar a página de administração.
     * 
     * Este método verifica se a página existe entre as páginas públicas ou privadas. Em seguida, verifica se a controller correspondente existe,
     * e se o método necessário está presente na controller. Em caso de falha, logs são gerados e mensagens de erro são exibidas.
     * 
     * @param string|null $urlController Recebe da URL o nome da controller
     * @param string|null $urlParameter Recebe da URL o parâmetro
     * 
     * @return void
     */
    public function loadPageAdm(string|null $urlController, string|null $urlParameter): void
    {
        $this->urlController = $urlController;
        $this->urlParameter = $urlParameter;
        // Converter controller de slug para PascalCase
        $this->urlController = SlugController::slugController($this->urlController);

        // Padronizar: priorizar 'url' para listagens, senão usar 'controller'
        $routeParam = '';
        if (!empty($_GET['url']) && preg_match('/^[a-zA-Z0-9_\-]+$/', $_GET['url'])) {
            $routeParam = $_GET['url'];
        } elseif (!empty($_GET['controller']) && preg_match('/^[a-zA-Z0-9_\-]+$/', $_GET['controller'])) {
            $routeParam = $_GET['controller'];
        }
        $routeParam = trim($routeParam);
        if (empty($routeParam)) {
            // Log detalhado do erro de parâmetro
            $logPath = __DIR__ . '/../app/logs/session_debug2.log';
            @file_put_contents($logPath,
                date('Y-m-d H:i:s') . ' - [LoadPageAdm] NENHUM PARÂMETRO DE ROTA VÁLIDO (url/controller) - url: ' . ($_GET['url'] ?? 'null') . ' | controller: ' . ($_GET['controller'] ?? 'null') .
                ' | URL: ' . ($_SERVER['REQUEST_URI'] ?? 'null') .
                ' | _SESSION: ' . json_encode($_SESSION) . "\n",
                FILE_APPEND
            );
            $_SESSION['error'] = 'Nenhum parâmetro de rota válido informado. Você foi redirecionado para o início.';
            header('Location: ' . $_ENV['URL_ADM'] . 'dashboard');
            exit;
        }
        $this->urlController = SlugController::slugController($routeParam);

        // Verificar se existe a pagina
        if (!$this->checkPageExists()) {

            // Chamar o método para salvar log
            GenerateLog::generateLog("error", "Pagina não encontrada.", ['pagina' => $this->urlController, 'parametro' => $this->urlParameter]);

            // die("Erro 002: Por favor tente novamente. Caso o problema persista, entre em contato com o adminstrador {$_ENV['EMAIL_ADM']}");

            // Criar a mensagem de erro
            $_SESSION['error'] = "Necessário estar logado para acessar pagina restrita.";

            // Log detalhado do motivo do redirecionamento para login
            $logPath = __DIR__ . '/../app/logs/session_debug2.log';
            @file_put_contents($logPath,
                date('Y-m-d H:i:s') . ' - [LoadPageAdm] REDIRECIONA LOGIN - controller: ' . ($this->urlController ?? 'null') .
                ' | parametro: ' . ($this->urlParameter ?? 'null') .
                ' | session_id: ' . (session_id() ?: 'null') .
                ' | _SESSION: ' . json_encode($_SESSION) .
                ' | URL: ' . ($_SERVER['REQUEST_URI'] ?? 'null') .
                "\n",
                FILE_APPEND
            );

            // Redirecionar o usuário para a pagina de login
            header("Location: {$_ENV['URL_ADM']}login");
        }

        // Verificar se a classe/controller existe
        if (!$this->checkControllersExists()) {
            // Tratamento especial para endpoint AJAX de atualização de status de candidatura
            if ($this->urlController === 'RhAtualizarStatusCandidatura') {
                $ajaxClass = "\\App\\adms\\Controllers\\rh\\RhAtualizarStatusCandidatura";
                if (class_exists($ajaxClass)) {
                    $controller = new $ajaxClass();
                    if (method_exists($controller, 'index')) {
                        // Responde diretamente ao AJAX e encerra o fluxo normal
                        $controller->index();
                        exit;
                    }
                }
            }

            // Fallback seguro para nomes de ação
            $actionNames = ['delete', 'update', 'create', 'view'];
            $routeParamLower = strtolower($routeParam);
            if (in_array($routeParamLower, $actionNames)) {
                // Verifica se existe controller correspondente
                $controllerExists = false;
                foreach ($this->listPackages as $package) {
                    foreach ($this->listDirectory as $directory) {
                        $classTest = "\\App\\$package\\Controllers\\$directory\\" . SlugController::slugController($routeParam);
                        if (class_exists($classTest)) {
                            $controllerExists = true;
                            break 2;
                        }
                    }
                }
                if (!$controllerExists) {
                    // Log do fallback
                    $logPath = __DIR__ . '/../app/logs/session_debug2.log';
                    @file_put_contents($logPath,
                        date('Y-m-d H:i:s') . ' - [LoadPageAdm] Fallback para listagem padrão - parâmetro de ação inválido: ' . $routeParam .
                        ' | URL: ' . ($_SERVER['REQUEST_URI'] ?? 'null') .
                        ' | _SESSION: ' . json_encode($_SESSION) . "\n",
                        FILE_APPEND
                    );
                    $_SESSION['error'] = 'Ação inválida na URL. Você foi redirecionado para a listagem padrão.';
                    header('Location: ' . $_ENV['URL_ADM'] . 'list-pages');
                    exit;
                }
            }

            // Se não for nome de ação, segue fluxo normal de erro
            GenerateLog::generateLog("error", "Controller não encontrada.", [
                'pagina'    => $this->urlController,
                'parametro' => $this->urlParameter,
                'classLoad' => isset($this->classLoad) ? $this->classLoad : null,
            ]);

            die("Erro 003: Por favor tente novamente. Caso o problema persista, entre em contato com o adminstrador {$_ENV['EMAIL_ADM']}");
        }

        // Após checar se a controller existe, adicionar fallback seguro para nomes de ação
        $actionNames = ['delete', 'update', 'create', 'view'];
        $routeParamLower = strtolower($routeParam);
        if (in_array($routeParamLower, $actionNames)) {
            // Verifica se existe controller correspondente
            $controllerExists = false;
            foreach ($this->listPackages as $package) {
                foreach ($this->listDirectory as $directory) {
                    $classTest = "\\App\\$package\\Controllers\\$directory\\" . SlugController::slugController($routeParam);
                    if (class_exists($classTest)) {
                        $controllerExists = true;
                        break 2;
                    }
                }
            }
            if (!$controllerExists) {
                // Log do fallback
                $logPath = __DIR__ . '/../app/logs/session_debug2.log';
                @file_put_contents($logPath,
                    date('Y-m-d H:i:s') . ' - [LoadPageAdm] Fallback para listagem padrão - parâmetro de ação inválido: ' . $routeParam .
                    ' | URL: ' . ($_SERVER['REQUEST_URI'] ?? 'null') .
                    ' | _SESSION: ' . json_encode($_SESSION) . "\n",
                    FILE_APPEND
                );
                $_SESSION['error'] = 'Ação inválida na URL. Você foi redirecionado para a listagem padrão.';
                header('Location: ' . $_ENV['URL_ADM'] . 'list-pages');
                exit;
            }
        }
    }

    /**
     * Verificar se a página existe.
     * 
     * Este método verifica se o nome da controller está presente na lista de páginas públicas ou privadas.
     * 
     * @return bool Retorna verdadeiro se a página existir, falso caso contrário.
     */
    private function checkPageExists(): bool
    {

        // Verificar se existe a pagina no array de paginas publicas
        if (in_array($this->urlController, $this->listPgPublic)) {
            return true;
        }

        // Chamar o método para verificar se existe a pagina no array de paginas privadas
        if ($this->checkPagePrivateExists()) {
            return true;
        }


        return false;
    }

    private function checkPagePrivateExists(): bool
    {

        // Verificar se existe a pagina no array de paginas privadas
        if (!in_array($this->urlController, $this->listPgPrivate)) {
            return false;
        }

        // Verificar se o usuário está logado
        if ((!isset($_SESSION['user_id'])) and (!isset($_SESSION['user_name'])) and (!isset($_SESSION['user_email'])) and (!isset($_SESSION['user_email']))) {
            return false;
        }
        return true;
    }

    /**
     * Verificar se a controller existe.
     * 
     * Este método percorre os pacotes e diretórios definidos para verificar se a classe controller correspondente à página existe.
     * Se a classe for encontrada, o método `loadMetodo` é chamado para verificar a existência do método "index" e carregá-lo.
     * 
     * @return bool Retorna verdadeiro se a controller existir, falso caso contrário.
     */
    private function checkControllersExists(): bool
    {
        // Percorrer o arry de pacotes
        foreach ($this->listPackages as $package) {
            //var_dump($package);

            // Percorrer o array de diretórios
            foreach ($this->listDirectory as $directory) {
                //var_dump($directory);

                // Criar o caminho da controller/classe
                $this->classLoad = "\\App\\$package\\Controllers\\$directory\\" . $this->urlController;

                // Verificar se a classe existe
                if (class_exists($this->classLoad)) {

                    // var_dump($package, $directory);

                    // Chamar o método  para validar o método
                    $this->loadMetodo();
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Verificar se o método "index" existe na controller e carregar a página.
     * 
     * Este método instancia a controller correspondente e verifica se o método "index" está presente. 
     * Se o método existir, ele é executado com o parâmetro fornecido. Caso contrário, um log de erro é gerado e uma mensagem de erro é exibida.
     * 
     * @return void
     */
    private function loadMetodo(): void
    {
        // Rota especial para detecção de resolução de tela
        if ($this->urlController === 'ScreenResolution') {
            $controller = new \App\adms\Controllers\Services\ScreenResolutionController();
            if (!empty($this->urlParameter) && $this->urlParameter === 'set') {
                $controller->setScreenResolution();
            } else {
                $controller->getScreenResolution();
            }
            return;
        }

        // Instanciar a classe da pagina que deve ser carregada
        $classLoad = new $this->classLoad();

        // Tratamento especial: sempre usar index() para TestEmailConfig,
        // independentemente dos parâmetros recebidos na URL.
        if ($this->urlController === 'TestEmailConfig' && method_exists($classLoad, 'index')) {
            GenerateLog::generateLog("info", "Pagina acessada (TestEmailConfig).", [
                'pagina' => $this->urlController,
                'parametro' => $this->urlParameter,
            ]);
            $classLoad->index();
            return;
        }

        // Padrão: /Controller/Metodo
        $metodo = 'index';
        if (!empty($this->urlParameter)) {
            // Converter kebab-case para camelCase (ex: get-application -> getApplication)
            $metodoCamelCase = lcfirst(SlugController::slugController($this->urlParameter));
            if (method_exists($classLoad, $metodoCamelCase)) {
                $metodo = $metodoCamelCase;
            } elseif (method_exists($classLoad, $this->urlParameter)) {
                $metodo = $this->urlParameter;
            }
        }

        if (method_exists($classLoad, $metodo)) {
            GenerateLog::generateLog("info", "Pagina acessada.", ['pagina' => $this->urlController, 'parametro' => $this->urlParameter]);
            $classLoad->{$metodo}();
        } else {
            GenerateLog::generateLog("error", "Método não encontrado.", ['pagina' => $this->urlController, 'parametro' => $this->urlParameter]);
            die("Erro 004: Por favor tente novamente. Caso o problema persista, entre em contato com o adminstrador {$_ENV['EMAIL_ADM']}");
        }
    }
}

