<?php

namespace App\adms\Controllers\logs;

use App\adms\Models\Repository\BookingAdditionalRequestsRepository;
use App\adms\Models\Repository\BookingParticipantsRepository;
use App\adms\Models\Repository\BookingWaitlistRepository;
use App\adms\Models\Repository\AccessLevelsPagesRepository;
use App\adms\Models\Repository\DocumentPositionsRepository;
use App\adms\Models\Repository\UsersAccessLevelsRepository;
use App\adms\Models\Repository\PerformanceCompetenciesRepository;
use App\adms\Models\Repository\projects\ProjCommentsRepository;
use App\adms\Models\Repository\StrategicPlanObservationsRepository;
use App\adms\Models\Repository\CrmNotesRepository;
use App\adms\Models\Repository\CrmCustomFieldsRepository;
use App\adms\Models\Repository\CrmDocumentsRepository;
use App\adms\Models\Repository\CompetencyMatrixRepository;
use App\adms\Models\Repository\LogAlteracoesRepository;
use App\adms\Models\Repository\RoomBookingSlotHoldRepository;
use App\adms\Models\Repository\LogAlteracoesDetalhesRepository;
use App\adms\Models\Repository\TimelineRepository;
use App\adms\Models\Repository\EmploymentHistoryRepository;
use App\adms\Models\Repository\PayrollDocumentEventsRepository;
use App\adms\Models\Repository\PayrollDocumentOtpRepository;
use App\adms\Models\Repository\KpiDashboardRepository;
use App\adms\Models\Repository\GamificationQuizRepository;
use App\adms\Models\Repository\EvaluationAttemptsRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\PageLayoutService;

class ListLogAlteracoes
{
    private array|string|null $data = null;
    private int $limitResult = 10;

    public function index(string|int $page = 1): void
    {
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int)$_GET['page'];
        }
        if (isset($_GET['per_page']) && in_array((int)$_GET['per_page'], [10, 20, 50, 100])) {
            $this->limitResult = (int)$_GET['per_page'];
        }
        $filtros = [
            'tabela' => $_GET['tabela'] ?? '',
            'objeto_id' => $_GET['objeto_id'] ?? '',
            'identificador' => $_GET['identificador'] ?? '',
            'usuario_nome' => $_GET['usuario_nome'] ?? '',
            'tipo' => $_GET['tipo'] ?? '',
            'data_inicio' => $_GET['data_inicio'] ?? '',
            'data_fim' => $_GET['data_fim'] ?? '',
            'strategic_plan_context' => $_GET['strategic_plan_context'] ?? '',
            'inventory_item_context' => $_GET['inventory_item_context'] ?? '',
        ];
        
        // Parâmetros de ordenação
        $orderBy = $_GET['order_by'] ?? null;
        $orderDirection = $_GET['order_direction'] ?? 'DESC';

        // Se veio filtrando por tabela + objeto_id e não foi especificada ordenação,
        // ordenar por data_alteracao (do mais recente para o mais antigo)
        if ($orderBy === null) {
            if ((!empty($filtros['tabela']) && !empty($filtros['objeto_id']))
                || !empty($filtros['strategic_plan_context'])
                || !empty($filtros['inventory_item_context'])) {
                $orderBy = 'data_alteracao';
            } else {
                $orderBy = 'id';
            }
        }
        
        // Validar parâmetros de ordenação
        $allowedOrderBy = ['id', 'tabela', 'objeto_id', 'usuario_nome', 'data_alteracao', 'tipo_operacao', 'ip', 'hostname'];
        if (!in_array($orderBy, $allowedOrderBy)) {
            $orderBy = 'id';
        }
        
        $allowedDirection = ['ASC', 'DESC'];
        if (!in_array(strtoupper($orderDirection), $allowedDirection)) {
            $orderDirection = 'DESC';
        }
        
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
        $paginaAtual = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $repo = new LogAlteracoesRepository();
        $this->data['logs'] = $repo->getAll($paginaAtual, $perPage, $filtros, $orderBy, $orderDirection);
        // Adiciona o link de registro para cada log
        foreach ($this->data['logs'] as &$log) {
            $log['link_registro'] = $this->getLinkRegistro($log['tabela'], $log['objeto_id']);
        }
        unset($log);
        $this->data['per_page'] = $perPage;
        $this->data['filtros'] = $filtros;
        $this->data['pagina_atual'] = $paginaAtual;
        $this->data['total_registros'] = $repo->countAll($filtros);
        $this->data['total_paginas'] = (int)ceil($this->data['total_registros'] / $perPage);
        $this->data['order_by'] = $orderBy;
        $this->data['order_direction'] = $orderDirection;
        $this->data['return_url'] = $_GET['return_url'] ?? '';

        // Quando filtrado por uma combinação específica de tabela + objeto_id,
        // buscar também todos os detalhes de alterações desse registro
        $this->data['detalhes_registro'] = [];
        if (!empty($this->data['logs'])
            && (
                (!empty($filtros['tabela']) && !empty($filtros['objeto_id']))
                || !empty($filtros['strategic_plan_context'])
                || !empty($filtros['inventory_item_context'])
            )) {
            $logIds = array_column($this->data['logs'], 'id');
            $detRepo = new LogAlteracoesDetalhesRepository();
            $this->data['detalhes_registro'] = $detRepo->getByLogIds($logIds);
        }
        $pageElements = [
            'title_head' => 'Log de Modificações',
            'menu' => 'list-log-alteracoes',
            'buttonPermission' => [],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        $loadView = new LoadViewService("adms/Views/logs/listLogAlteracoes", $this->data);
        $loadView->loadView();
    }
    
    /**
     * Gera link para visualizar o registro original
     */
    public function getLinkRegistro(string $tabela, int $objetoId): ?string
    {
        switch ($tabela) {
            case 'adms_users':
                return $_ENV['URL_ADM'] . 'view-user/' . $objetoId;
            case 'adms_users_access_levels':
                $ual = (new UsersAccessLevelsRepository())->getUsersAccessLevelRowById($objetoId);
                if ($ual && !empty($ual['adms_user_id'])) {
                    return $_ENV['URL_ADM'] . 'view-user/' . (int) $ual['adms_user_id'];
                }

                return null;
            case 'adms_document_positions':
                $dp = (new DocumentPositionsRepository())->getDocumentPosition($objetoId);
                if (is_array($dp) && !empty($dp['adms_document_id'])) {
                    return $_ENV['URL_ADM'] . 'view-document/' . (int) $dp['adms_document_id'];
                }

                return null;
            case 'adms_performance_competencies':
                $pc = (new PerformanceCompetenciesRepository())->getById($objetoId);
                if ($pc && !empty($pc['performance_review_id'])) {
                    return $_ENV['URL_ADM'] . 'view-performance-review/' . (int) $pc['performance_review_id'];
                }

                return null;
            case 'adms_performance_reviews':
                return $_ENV['URL_ADM'] . 'view-performance-review/' . $objetoId;
            case 'adms_performance_goals':
                return $_ENV['URL_ADM'] . 'view-performance-goal/' . $objetoId;
            case 'adms_performance_feedbacks':
                return $_ENV['URL_ADM'] . 'view-performance-feedback/' . $objetoId;
            case 'adms_customer':
                return $_ENV['URL_ADM'] . 'view-customer/' . $objetoId;
            case 'adms_departments':
                return $_ENV['URL_ADM'] . 'view-department/' . $objetoId;
            case 'adms_pay':
                return $_ENV['URL_ADM'] . 'view-pay/' . $objetoId;
            case 'adms_receive':
                return $_ENV['URL_ADM'] . 'view-receive/' . $objetoId;
            case 'adms_trainings':
                return $_ENV['URL_ADM'] . 'view-training/' . $objetoId;
            case 'adms_supplier':
                return $_ENV['URL_ADM'] . 'view-supplier/' . $objetoId;
            case 'adms_banks':
                return $_ENV['URL_ADM'] . 'view-bank/' . $objetoId;
            case 'adms_cost_centers':
                return $_ENV['URL_ADM'] . 'view-cost-center/' . $objetoId;
            case 'adms_accounts_plan':
                return $_ENV['URL_ADM'] . 'view-account-plan/' . $objetoId;
            case 'adms_frequencies':
                return $_ENV['URL_ADM'] . 'view-frequency/' . $objetoId;
            case 'adms_payment_methods':
                return $_ENV['URL_ADM'] . 'view-payment-method/' . $objetoId;
            case 'adms_positions':
                return $_ENV['URL_ADM'] . 'view-position/' . $objetoId;
            case 'adms_packages':
                return $_ENV['URL_ADM'] . 'view-package/' . $objetoId;
            case 'adms_groups_pages':
                return $_ENV['URL_ADM'] . 'view-group-page/' . $objetoId;
            case 'adms_pages':
                return $_ENV['URL_ADM'] . 'view-page/' . $objetoId;
            case 'adms_access_levels':
                return $_ENV['URL_ADM'] . 'view-access-level/' . $objetoId;
            case 'adms_access_levels_pages':
                $alpRow = (new AccessLevelsPagesRepository())->getAccessLevelPageRowById($objetoId);
                if ($alpRow && !empty($alpRow['adms_access_level_id'])) {
                    return $_ENV['URL_ADM'] . 'view-access-level/' . (int) $alpRow['adms_access_level_id'];
                }

                return null;
            case 'adms_competencies':
                return $_ENV['URL_ADM'] . 'view-competency/' . $objetoId;
            case 'adms_competency_matrix':
                $cm = (new CompetencyMatrixRepository())->getMatrixRowById($objetoId);
                if ($cm && !empty($cm['position_id'])) {
                    return $_ENV['URL_ADM'] . 'view-position/' . (int) $cm['position_id'];
                }

                return null;
            case 'lgpd_bases_legais':
                return $_ENV['URL_ADM'] . 'lgpd-bases-legais-view/' . $objetoId;
            case 'lgpd_finalidades':
                return $_ENV['URL_ADM'] . 'lgpd-finalidades-view/' . $objetoId;
            case 'lgpd_tipos_dados':
                return $_ENV['URL_ADM'] . 'lgpd-tipos-dados-view/' . $objetoId;
            case 'lgpd_classificacoes_dados':
                return $_ENV['URL_ADM'] . 'lgpd-classificacoes-dados-view/' . $objetoId;
            case 'lgpd_categorias_titulares':
                return $_ENV['URL_ADM'] . 'lgpd-categorias-titulares-view/' . $objetoId;
            case 'lgpd_termos':
                return $_ENV['URL_ADM'] . 'lgpd-termos-view/' . $objetoId;
            case 'lgpd_consentimentos':
                return $_ENV['URL_ADM'] . 'lgpd-consentimentos-view/' . $objetoId;
            case 'adms_meeting_rooms':
                return $_ENV['URL_ADM'] . 'view-meeting-room/' . $objetoId;
            case 'adms_company_events':
                return $_ENV['URL_ADM'] . 'view-company-event/' . $objetoId;
            case 'rh_vagas':
                return $_ENV['URL_ADM'] . 'rh-vagas-view/' . $objetoId;
            case 'rh_candidatos':
                return $_ENV['URL_ADM'] . 'rh-candidatos-view/' . $objetoId;
            case 'rh_entrevistas':
                return $_ENV['URL_ADM'] . 'rh-entrevistas-view/' . $objetoId;
            case 'adms_informativos':
                return $_ENV['URL_ADM'] . 'view-informativo/' . $objetoId;
            case 'adms_request_types':
                return $_ENV['URL_ADM'] . 'update-request-type/' . $objetoId;
            case 'lgpd_ripd':
                return $_ENV['URL_ADM'] . 'lgpd-ripd-view/' . $objetoId;
            case 'lgpd_ropa':
                return $_ENV['URL_ADM'] . 'lgpd-ropa-view/' . $objetoId;
            case 'lgpd_aipd':
                return $_ENV['URL_ADM'] . 'lgpd-aipd-view/' . $objetoId;
            case 'lgpd_data_mapping':
                return $_ENV['URL_ADM'] . 'lgpd-data-mapping-view/' . $objetoId;
            case 'lgpd_inventory':
                return $_ENV['URL_ADM'] . 'lgpd-inventory-view/' . $objetoId;
            case 'lgpd_tia':
                return $_ENV['URL_ADM'] . 'lgpd-tia-view/' . $objetoId;
            case 'lgpd_consentimento_arquivos':
                return $_ENV['URL_ADM'] . 'lgpd-consentimentos';
            case 'adms_room_request_types':
                return $_ENV['URL_ADM'] . 'rooms-update-request-type/' . $objetoId;
            case 'adms_room_request_groups':
                return $_ENV['URL_ADM'] . 'rooms-update-request-group/' . $objetoId;
            case 'adms_email_config':
                return $_ENV['URL_ADM'] . 'list-email-config';
            case 'adms_sap_api_config':
                return $_ENV['URL_ADM'] . 'sap-api-config';
            case 'adms_mcp_api_config':
                return $_ENV['URL_ADM'] . 'mcp-api-config';
            case 'adms_push_config':
                return $_ENV['URL_ADM'] . 'push-config';
            case 'adms_whatsapp_config':
                return $_ENV['URL_ADM'] . 'whats-app-config';
            case 'adms_log_settings':
                return $_ENV['URL_ADM'] . 'log-settings';
            case 'adms_payroll_cron_config':
                return $_ENV['URL_ADM'] . 'payroll-cron-config';
            case 'calendar_settings':
            case 'calendar_holidays':
                return $_ENV['URL_ADM'] . 'calendar-config';
            case 'adms_room_calendar_settings':
                return $_ENV['URL_ADM'] . 'rooms-calendar-integration-settings';
            case 'adms_user_calendar_entries':
                return $_ENV['URL_ADM'] . 'my-calendar';
            case 'adms_room_bookings':
                return $_ENV['URL_ADM'] . 'view-booking/' . $objetoId;
            case 'adms_policies':
                return $_ENV['URL_ADM'] . 'view-policy/' . $objetoId;
            case 'adms_policies_categorias':
                return $_ENV['URL_ADM'] . 'update-policy-category/' . $objetoId;
            case 'crm_activities':
                return $_ENV['URL_ADM'] . 'crm-view-activity/' . $objetoId;
            case 'crm_opportunities':
                return $_ENV['URL_ADM'] . 'crm-view-opportunity/' . $objetoId;
            case 'crm_partners':
                return $_ENV['URL_ADM'] . 'crm-view-partner/' . $objetoId;
            case 'crm_notes':
                $note = (new CrmNotesRepository())->getNoteById($objetoId);
                if (is_array($note)) {
                    if (!empty($note['opportunity_id'])) {
                        return $_ENV['URL_ADM'] . 'crm-view-opportunity/' . (int) $note['opportunity_id'];
                    }
                    if (!empty($note['partner_id'])) {
                        return $_ENV['URL_ADM'] . 'crm-view-partner/' . (int) $note['partner_id'];
                    }
                }

                return null;
            case 'crm_automations':
                return $_ENV['URL_ADM'] . 'crm-view-automation/' . $objetoId;
            case 'crm_custom_fields':
                return $_ENV['URL_ADM'] . 'crm-update-custom-field/' . $objetoId;
            case 'crm_custom_field_values_partners':
                $cfvRepo = new CrmCustomFieldsRepository();
                $pv = $cfvRepo->getPartnerFieldValueRowById($objetoId);
                if ($pv && !empty($pv['partner_id'])) {
                    return $_ENV['URL_ADM'] . 'crm-view-partner/' . (int) $pv['partner_id'];
                }

                return null;
            case 'crm_custom_field_values_opportunities':
                $cfvRepo2 = new CrmCustomFieldsRepository();
                $ov = $cfvRepo2->getOpportunityFieldValueRowById($objetoId);
                if ($ov && !empty($ov['opportunity_id'])) {
                    return $_ENV['URL_ADM'] . 'crm-view-opportunity/' . (int) $ov['opportunity_id'];
                }

                return null;
            case 'crm_documents':
                $doc = (new CrmDocumentsRepository())->getDocumentById($objetoId);
                if (is_array($doc)) {
                    if (!empty($doc['opportunity_id'])) {
                        return $_ENV['URL_ADM'] . 'crm-view-opportunity/' . (int) $doc['opportunity_id'];
                    }
                    if (!empty($doc['partner_id'])) {
                        return $_ENV['URL_ADM'] . 'crm-view-partner/' . (int) $doc['partner_id'];
                    }
                }

                return null;
            case 'adms_room_service_requests':
                return $_ENV['URL_ADM'] . 'rooms-view-service-request/' . $objetoId;
            case 'adms_room_booking_slot_holds':
                $hold = (new RoomBookingSlotHoldRepository())->getById($objetoId);
                if ($hold && !empty($hold['room_id'])) {
                    return $_ENV['URL_ADM'] . 'view-meeting-room/' . (int) $hold['room_id'];
                }

                return null;
            case 'adms_booking_additional_requests':
                $bar = (new BookingAdditionalRequestsRepository())->getById($objetoId);
                if ($bar && !empty($bar['booking_id'])) {
                    return $_ENV['URL_ADM'] . 'view-booking/' . (int) $bar['booking_id'];
                }

                return null;
            case 'adms_booking_participants':
                $bp = (new BookingParticipantsRepository())->getById($objetoId);
                if ($bp && !empty($bp['booking_id'])) {
                    return $_ENV['URL_ADM'] . 'view-booking/' . (int) $bp['booking_id'];
                }

                return null;
            case 'adms_booking_waitlist':
                $wl = (new BookingWaitlistRepository())->getById($objetoId);
                if ($wl && !empty($wl['booking_id'])) {
                    return $_ENV['URL_ADM'] . 'view-booking/' . (int) $wl['booking_id'];
                }

                return $_ENV['URL_ADM'] . 'booking-waitlist';
            case 'adms_payroll_document_types':
                return $_ENV['URL_ADM'] . 'update-payroll-document-type/' . $objetoId;
            case 'adms_bank_transfers':
                return $_ENV['URL_ADM'] . 'view-transfer/' . $objetoId;
            case 'adms_strategic_plans':
                return $_ENV['URL_ADM'] . 'view-strategic-plan/' . $objetoId;
            case 'adms_strategic_indicators':
                return $_ENV['URL_ADM'] . 'view-strategic-indicator/' . $objetoId;
            case 'adms_strategic_plan_observations':
                $obs = (new StrategicPlanObservationsRepository())->getById($objetoId);
                if (is_array($obs) && !empty($obs['strategic_plan_id'])) {
                    return $_ENV['URL_ADM'] . 'view-strategic-plan-observations/' . (int) $obs['strategic_plan_id'];
                }

                return null;
            case 'proj_projects':
                return $_ENV['URL_ADM'] . 'update-project/' . $objetoId;
            case 'proj_stages':
                return $_ENV['URL_ADM'] . 'update-project-stage/' . $objetoId;
            case 'proj_stage_groups':
                return $_ENV['URL_ADM'] . 'update-stage-group/' . $objetoId;
            case 'proj_comments':
                $projCommentPid = (new ProjCommentsRepository())->getProjectIdForComment($objetoId);
                if ($projCommentPid !== null) {
                    return $_ENV['URL_ADM'] . 'update-project/' . $projCommentPid;
                }

                return null;
            case 'adms_dashboards':
            case 'adms_dashboard_reports':
            case 'adms_dashboard_relationships':
                return $_ENV['URL_ADM'] . 'view-dashboard/' . $objetoId;
            case 'adms_spreadsheets':
                return $_ENV['URL_ADM'] . 'list-dashboards';
            case 'adms_kpi_dashboards':
                return $_ENV['URL_ADM'] . 'view-kpi-dashboard?id=' . $objetoId;
            case 'adms_kpi_widgets':
                $w = (new KpiDashboardRepository())->findWidgetDashboardId($objetoId);
                if ($w !== null) {
                    return $_ENV['URL_ADM'] . 'view-kpi-dashboard?id=' . $w;
                }

                return null;
            case 'adms_dynamic_reports':
            case 'adms_dynamic_report_shared_users':
                return $_ENV['URL_ADM'] . 'dynamic-report-builder?id=' . $objetoId;
            case 'adms_gamification_timeline_rules':
                return $_ENV['URL_ADM'] . 'update-gamification-timeline-rule/' . $objetoId;
            case 'adms_gamification_quizzes':
                return $_ENV['URL_ADM'] . 'update-gamification-quiz/' . $objetoId;
            case 'adms_gamification_quiz_questions':
                $gq = (new GamificationQuizRepository())->findQuestionById((int) $objetoId);
                if (is_array($gq) && !empty($gq['quiz_id'])) {
                    return $_ENV['URL_ADM'] . 'list-gamification-quiz-questions/' . (int) $gq['quiz_id'];
                }

                return $_ENV['URL_ADM'] . 'list-gamification-quizzes';
            case 'adms_gamification_quiz_options':
                $gqRepo = new GamificationQuizRepository();
                $qzId = $gqRepo->findQuizIdForOptionId((int) $objetoId);
                if ($qzId === null) {
                    $qrow = $gqRepo->findQuestionById((int) $objetoId);
                    if (is_array($qrow) && !empty($qrow['quiz_id'])) {
                        $qzId = (int) $qrow['quiz_id'];
                    }
                }
                if ($qzId !== null && $qzId > 0) {
                    return $_ENV['URL_ADM'] . 'list-gamification-quiz-questions/' . $qzId;
                }

                return $_ENV['URL_ADM'] . 'list-gamification-quizzes';
            case 'adms_gamification_levels':
            case 'adms_gamification_badges':
            case 'adms_gamification_weekly_missions':
            case 'adms_gamification_settings':
            case 'adms_gamification_user_badges':
            case 'adms_gamification_anti_fraud_events':
            case 'adms_gamification_user_mission_progress':
                return $_ENV['URL_ADM'] . 'gamification-engagement-dashboard';
            case 'adms_gamification_point_ledger':
                return $_ENV['URL_ADM'] . 'list-gamification-point-ledger';
            case 'adms_evaluation_models':
                return $_ENV['URL_ADM'] . 'update-evaluation-model/' . $objetoId;
            case 'adms_evaluation_questions':
                return $_ENV['URL_ADM'] . 'update-evaluation-question/' . $objetoId;
            case 'adms_evaluation_assignments':
                return $_ENV['URL_ADM'] . 'list-evaluation-assignments';
            case 'adms_evaluation_attempts':
                $att = (new EvaluationAttemptsRepository())->getById((int) $objetoId);
                if (is_array($att) && !empty($att['assignment_id'])) {
                    return $_ENV['URL_ADM'] . 'evaluation-history/' . (int) $att['assignment_id'];
                }

                return $_ENV['URL_ADM'] . 'list-evaluation-assignments';
            case 'adms_evaluation_answers':
                return $_ENV['URL_ADM'] . 'view-evaluation-answer/' . $objetoId;
            case 'inv_items':
                return $_ENV['URL_ADM'] . 'view-inventory-item/' . $objetoId;
            case 'inv_item_bom':
            case 'inv_item_operations':
                return $_ENV['URL_ADM'] . 'update-inventory-item/' . $objetoId;
            case 'inv_balances':
                $bRow = (new \App\adms\Models\Repository\inventory\InvBalancesRepository())->getBalanceRowById($objetoId);
                if (is_array($bRow) && !empty($bRow['inv_item_id'])) {
                    return $_ENV['URL_ADM'] . 'view-inventory-item/' . (int) $bRow['inv_item_id'];
                }

                return null;
            case 'inv_movements':
                return $_ENV['URL_ADM'] . 'report-inventory-history?' . http_build_query(['movement_id' => $objetoId]);
            case 'inv_units':
                return $_ENV['URL_ADM'] . 'update-inventory-unit/' . $objetoId;
            case 'inv_categories':
                return $_ENV['URL_ADM'] . 'update-inventory-category/' . $objetoId;
            case 'inv_positions':
                return $_ENV['URL_ADM'] . 'update-inventory-position/' . $objetoId;
            case 'inv_stocks':
                return $_ENV['URL_ADM'] . 'update-inventory-stock/' . $objetoId;
            case 'inv_operations':
                return $_ENV['URL_ADM'] . 'update-inventory-operation/' . $objetoId;
            case 'adms_notifications':
                return $_ENV['URL_ADM'] . 'notificacoes';
            case 'adms_timeline_posts':
                return $_ENV['URL_ADM'] . 'timeline?post=' . $objetoId;
            case 'adms_timeline_comments':
                return $_ENV['URL_ADM'] . 'timeline?comment=' . $objetoId;
            case 'adms_timeline_polls':
                $pollPostId = (new TimelineRepository())->findPostIdByPollId($objetoId);
                if ($pollPostId !== null && $pollPostId > 0) {
                    return $_ENV['URL_ADM'] . 'timeline?post=' . $pollPostId;
                }

                return $_ENV['URL_ADM'] . 'timeline';
            case 'adms_timeline_reports':
                $reportPostId = (new TimelineRepository())->getPostIdForTimelineReport($objetoId);
                if ($reportPostId !== null && $reportPostId > 0) {
                    return $_ENV['URL_ADM'] . 'timeline?post=' . $reportPostId;
                }

                return $_ENV['URL_ADM'] . 'timeline-moderate';
            case 'adms_employee_requests':
                return $_ENV['URL_ADM'] . 'view-employee-request/' . $objetoId;
            case 'adms_employee_tickets':
                return $_ENV['URL_ADM'] . 'view-employee-ticket/' . $objetoId;
            case 'adms_employment_history':
                $ehRow = (new EmploymentHistoryRepository())->getById($objetoId);
                if (is_array($ehRow) && !empty($ehRow['adms_user_id'])) {
                    return $_ENV['URL_ADM'] . 'view-user/' . (int) $ehRow['adms_user_id'];
                }

                return null;
            case 'adms_employee_payroll_documents':
                return $_ENV['URL_ADM'] . 'view-payroll-document/' . $objetoId;
            case 'adms_payroll_import_batches':
                return $_ENV['URL_ADM'] . 'import-payroll-documents';
            case 'adms_payroll_document_events':
                $evDocId = (new PayrollDocumentEventsRepository())->getDocumentIdForEvent($objetoId);
                if ($evDocId !== null && $evDocId > 0) {
                    return $_ENV['URL_ADM'] . 'view-payroll-document/' . $evDocId;
                }

                return $_ENV['URL_ADM'] . 'import-payroll-documents';
            case 'adms_payroll_document_otp_challenges':
                $otpDocId = (new PayrollDocumentOtpRepository())->getDocumentIdForChallenge($objetoId);
                if ($otpDocId !== null && $otpDocId > 0) {
                    return $_ENV['URL_ADM'] . 'view-payroll-document/' . $otpDocId;
                }

                return $_ENV['URL_ADM'] . 'my-payroll-documents';
            default:
                return null; // Tabela não mapeada
        }
    }
} 