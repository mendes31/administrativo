<?php

namespace App\adms\Controllers\logs;

use App\adms\Models\Repository\BookingAdditionalRequestsRepository;
use App\adms\Models\Repository\BookingParticipantsRepository;
use App\adms\Models\Repository\BookingWaitlistRepository;
use App\adms\Models\Repository\AccessLevelsPagesRepository;
use App\adms\Models\Repository\DocumentPositionsRepository;
use App\adms\Models\Repository\UsersAccessLevelsRepository;
use App\adms\Models\Repository\PerformanceCompetenciesRepository;
use App\adms\Models\Repository\RhEntrevistasRepository;
use App\adms\Models\Repository\CrmNotesRepository;
use App\adms\Models\Repository\CrmCustomFieldsRepository;
use App\adms\Models\Repository\CrmDocumentsRepository;
use App\adms\Models\Repository\CompetencyMatrixRepository;
use App\adms\Models\Repository\LogAlteracoesRepository;
use App\adms\Models\Repository\RoomBookingSlotHoldRepository;
use App\adms\Models\Repository\LogAlteracoesDetalhesRepository;
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
        ];
        
        // Parâmetros de ordenação
        $orderBy = $_GET['order_by'] ?? null;
        $orderDirection = $_GET['order_direction'] ?? 'DESC';

        // Se veio filtrando por tabela + objeto_id e não foi especificada ordenação,
        // ordenar por data_alteracao (do mais recente para o mais antigo)
        if ($orderBy === null) {
            if (!empty($filtros['tabela']) && !empty($filtros['objeto_id'])) {
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
        if (!empty($filtros['tabela']) && !empty($filtros['objeto_id']) && !empty($this->data['logs'])) {
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
                $ent = (new RhEntrevistasRepository())->getById($objetoId);
                if ($ent && !empty($ent['rh_candidato_id'])) {
                    return $_ENV['URL_ADM'] . 'rh-candidatos-view/' . (int) $ent['rh_candidato_id'];
                }

                return null;
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
            default:
                return null; // Tabela não mapeada
        }
    }
} 