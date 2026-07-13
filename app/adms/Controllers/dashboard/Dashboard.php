<?php

namespace App\adms\Controllers\dashboard;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\InformativosRepository;
use App\adms\Models\Repository\PoliciesRepository;
use App\adms\Models\Repository\NotificationsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\EmployeePayrollDocumentsRepository;
use App\adms\Models\Repository\GamificationQuizRepository;
use App\adms\Models\Repository\MeetingRoomsRepository;
use App\adms\Models\Repository\CompanyEventsDashboardSupport;
use App\adms\Models\Repository\CompanyEventsRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Models\Services\CandidateRetentionService;
use App\adms\Models\Services\InformativosStatusUpdaterService;
use App\adms\Models\Services\PayrollDocumentRemindersService;
use App\adms\Models\Services\TrainingLntDigestService;

class Dashboard
{
    /** @var array<string, mixed> $data Recebe os dados que devem ser enviados para a VIEW */
    private array $data = [];

    private function applyDashboardDefaults(): void
    {
        $this->data['informativos'] = [];
        $this->data['informativos_ativos'] = 0;
        $this->data['informativos_nao_lidos'] = 0;
        $this->data['categorias_informativos'] = [];

        $this->data['policies_dashboard'] = [];
        $this->data['policies_urgentes'] = 0;
        $this->data['policies_ativas'] = 0;
        $this->data['policies_nao_lidas'] = 0;

        $this->data['timeline_notificacoes_nao_lidas'] = 0;

        $this->data['aniversariantes_dia'] = [];
        $this->data['qtd_aniversariantes_dia'] = 0;
        $this->data['aniversariantes_mes'] = [];
        $this->data['qtd_aniversariantes_mes'] = 0;

        $this->data['aniversariantes_empresa_mes'] = [];
        $this->data['qtd_aniversariantes_empresa_mes'] = 0;

        $this->data['company_events_dashboard'] = [];
        $this->data['company_events_dashboard_year'] = (int) date('Y');
        $this->data['company_events_dashboard_month'] = (int) date('n');
        $this->data['company_events_month_count'] = 0;
        $this->data['company_events_year_count'] = 0;
        $this->data['company_events_unread_count'] = 0;

        $this->data['gamification_quizzes_catalog_count'] = 0;
        $this->data['meeting_rooms_active_count'] = 0;
        $this->data['payroll_documents_total'] = 0;
        $this->data['payroll_documents_latest'] = [];
        $this->data['payroll_pending_signatures'] = 0;
        $this->data['my_calendar_month_count'] = 0;
        $this->data['my_calendar_modal_events_json'] = '[]';
    }

    private function loadInformativosDashboardData(int $userId): void
    {
        $repo = new InformativosRepository();
        $informativos = $repo->getInformativosDashboard(12);
        $this->data['informativos'] = $informativos;
        $this->data['informativos_ativos'] = count(array_filter(
            $informativos,
            static fn (array $i): bool => !empty($i['ativo'])
        ));
        $this->data['informativos_nao_lidos'] = $userId > 0 ? $repo->countNaoLidos($userId) : 0;

        $categorias = [];
        foreach ($informativos as $info) {
            $cat = $info['categoria'] ?? 'Geral';
            if (!isset($categorias[$cat])) {
                $categorias[$cat] = [
                    'count' => 0,
                    'imagem' => null,
                    'has_anexo' => false,
                ];
            }
            $categorias[$cat]['count']++;
            if (!$categorias[$cat]['imagem'] && !empty($info['imagem'])) {
                $categorias[$cat]['imagem'] = $info['imagem'];
            }
            if (!$categorias[$cat]['has_anexo'] && !empty($info['anexo'])) {
                $categorias[$cat]['has_anexo'] = true;
            }
        }
        $this->data['categorias_informativos'] = $categorias;
    }

    private function loadPoliciesDashboardData(int $userId): void
    {
        $repo = new PoliciesRepository();
        $policiesDashboard = $repo->getPoliciesDashboard(12);
        $this->data['policies_dashboard'] = $policiesDashboard;
        $this->data['policies_urgentes'] = $repo->countPoliciesUrgentes();
        $this->data['policies_ativas'] = count(array_filter(
            $policiesDashboard,
            static fn (array $p): bool => !empty($p['ativo'])
        ));
        $this->data['policies_nao_lidas'] = $userId > 0 ? $repo->countNaoLidos($userId) : 0;
    }

    private function loadBirthdaysDashboardData(): void
    {
        $usersRepo = new UsersRepository();
        $mesAtual = (int) date('m');

        $this->data['aniversariantes_dia'] = $usersRepo->listActiveUsersBirthdaysTodayForDashboard();
        $this->data['qtd_aniversariantes_dia'] = count($this->data['aniversariantes_dia']);
        $this->data['aniversariantes_mes'] = $usersRepo->listActiveUsersBirthdaysInMonth($mesAtual);
        $this->data['qtd_aniversariantes_mes'] = $usersRepo->countActiveUsersBirthdaysInMonth($mesAtual);
    }

    private function loadCompanyTenureDashboardData(): void
    {
        $usersRepo = new UsersRepository();
        $mesAtual = (int) date('m');

        $this->data['aniversariantes_empresa_mes'] = $usersRepo->listActiveUsersCompanyAnniversariesInMonth($mesAtual);
        $this->data['qtd_aniversariantes_empresa_mes'] = $usersRepo->countActiveUsersCompanyAnniversariesInMonth($mesAtual);
    }

    private function loadCompanyEventsDashboardData(int $userId): void
    {
        try {
            $eventsRepo = new CompanyEventsRepository();
            /** @var CompanyEventsDashboardSupport $eventsDashboard */
            $eventsDashboard = $eventsRepo;
            $y = (int) date('Y');
            $m = (int) date('n');
            $displayPeriod = $eventsRepo->resolveDashboardDisplayMonth($y);
            $displayYear = (int) $displayPeriod['year'];
            $displayMonth = (int) $displayPeriod['month'];
            $companyEvents = $eventsRepo->getEventsIntersectingMonth($displayYear, $displayMonth);
            $rsvpByEvent = $userId > 0
                ? $eventsDashboard->buildDashboardRsvpMapForUser($companyEvents, $userId)
                : [];
            foreach ($companyEvents as &$ce) {
                $ceId = (int) ($ce['id'] ?? 0);
                $ce['rsvp'] = $userId > 0 ? ($rsvpByEvent[$ceId] ?? null) : null;
            }
            unset($ce);

            $this->data['company_events_dashboard'] = $companyEvents;
            $this->data['company_events_dashboard_year'] = $displayYear;
            $this->data['company_events_dashboard_month'] = $displayMonth;
            $this->data['company_events_month_count'] = ($displayYear === $y && $displayMonth === $m)
                ? count($companyEvents)
                : $eventsRepo->countEventsIntersectingMonth($y, $m);
            $this->data['company_events_year_count'] = $eventsRepo->countEventsIntersectingYear($y);
            $this->data['company_events_unread_count'] = $eventsRepo->countUnreadIntersectingYear($y, $userId);
        } catch (\Throwable) {
            // defaults já aplicados em applyDashboardDefaults()
        }
    }

    /**
     * Itens da agenda para o modal do dashboard, com URL principal já resolvida.
     *
     * @param array<int, string> $menuPermission
     * @return array<int, array<string, mixed>>
     */
    private function buildMyCalendarDashboardEventsPayload(
        int $userId,
        array $menuPermission,
        \App\adms\Models\Repository\UserCalendarRepository $calRepo
    ): array {
        $base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/';
        $y = (int) date('Y');
        $rangeStart = sprintf('%04d-01-01 00:00:00', $y);
        $rangeEnd = sprintf('%04d-12-31 23:59:59', $y);
        $items = $calRepo->listUnifiedAgenda($userId, $rangeStart, $rangeEnd);
        $permViewBooking = in_array('ViewBooking', $menuPermission, true);
        $permViewCompanyEvent = in_array('ViewCompanyEvent', $menuPermission, true);

        $out = [];
        foreach ($items as $row) {
            $src = (string) ($row['source'] ?? '');
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $st = (string) ($row['start_datetime'] ?? '');
            $en = (string) ($row['end_datetime'] ?? '');
            $title = (string) ($row['title'] ?? '');
            $href = '#';
            $label = 'Detalhe';
            if ($src === 'personal') {
                $mon = strlen($st) >= 7 ? substr($st, 0, 7) : date('Y-m');
                $href = $base . 'my-calendar?month=' . rawurlencode($mon) . '&edit=' . $id;
                $label = 'Editar compromisso';
            } elseif ($src === 'room_booking' && $permViewBooking) {
                $href = $base . 'view-booking/' . $id;
                $label = 'Ver reserva';
            } elseif ($src === 'room_invite') {
                $tok = trim((string) ($row['rsvp_token'] ?? ''));
                if ($tok !== '') {
                    $href = $base . 'meeting-booking-rsvp/' . rawurlencode($tok);
                    $label = 'RSVP convite';
                }
            } elseif ($src === 'company_event' && $permViewCompanyEvent) {
                $href = $base . 'view-company-event/' . $id;
                $label = 'Evento / RSVP';
            }
            $out[] = [
                'start' => $st,
                'end' => $en,
                'title' => $title,
                'href' => $href,
                'label' => $label,
                'source' => $src,
            ];
        }

        return $out;
    }

    public function index()
    {
        CandidateRetentionService::ensureUpdated();
        PayrollDocumentRemindersService::ensureUpdated();
        InformativosStatusUpdaterService::ensureUpdated();
        TrainingLntDigestService::ensureUpdated();

        $this->data['user_name'] = $_SESSION['user_name'] ?? 'Usuário';
        $userId = (int)($_SESSION['user_id'] ?? 0);

        $pageElements = [
            'title_head' => 'Dashboard',
            'menu' => 'dashboard',
            'buttonPermission' => [],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        $this->applyDashboardDefaults();

        $menuPermission = $this->data['menuPermission'] ?? [];
        // Regra estrita: cada card depende exclusivamente da permissão DashboardCard...
        $this->data['show_informativos_card'] = in_array('DashboardCardInformativos', $menuPermission, true);
        $this->data['show_policies_card'] = in_array('DashboardCardPolicies', $menuPermission, true);
        $this->data['show_timeline_card'] = in_array('DashboardCardTimeline', $menuPermission, true);
        $this->data['show_eventos_card'] = in_array('DashboardCardEventos', $menuPermission, true);
        $this->data['show_aniversariantes_card'] = in_array('DashboardCardAniversariantes', $menuPermission, true);
        $this->data['show_tempo_empresa_card'] = in_array('DashboardCardTempoEmpresa', $menuPermission, true);
        $this->data['show_payroll_documents_card'] = in_array('DashboardCardPayrollDocuments', $menuPermission, true);
        $this->data['show_my_calendar_card'] = in_array('DashboardCardMyCalendar', $menuPermission, true);
        $this->data['show_gamification_quizzes_card'] = in_array('DashboardCardGamificationQuizzes', $menuPermission, true);
        $this->data['show_room_booking_card'] = in_array('DashboardCardRoomBooking', $menuPermission, true);

        if (!empty($this->data['show_informativos_card'])) {
            $this->loadInformativosDashboardData($userId);
        }
        if (!empty($this->data['show_policies_card'])) {
            $this->loadPoliciesDashboardData($userId);
        }
        if (!empty($this->data['show_timeline_card']) && $userId > 0) {
            $this->data['timeline_notificacoes_nao_lidas'] = (new NotificationsRepository())
                ->countUnreadByTypePrefix($userId, 'timeline_');
        }
        if (!empty($this->data['show_aniversariantes_card'])) {
            $this->loadBirthdaysDashboardData();
        }
        if (!empty($this->data['show_tempo_empresa_card'])) {
            $this->loadCompanyTenureDashboardData();
        }
        if (!empty($this->data['show_eventos_card'])) {
            $this->loadCompanyEventsDashboardData($userId);
        }

        $this->data['gamification_quizzes_catalog_count'] = 0;
        if ($userId > 0 && !empty($this->data['show_gamification_quizzes_card'])) {
            try {
                $this->data['gamification_quizzes_catalog_count'] = count((new GamificationQuizRepository())->listPublishedAvailableForUser($userId));
            } catch (\Throwable) {
                $this->data['gamification_quizzes_catalog_count'] = 0;
            }
        }

        $this->data['meeting_rooms_active_count'] = 0;
        if (!empty($this->data['show_room_booking_card'])) {
            try {
                $this->data['meeting_rooms_active_count'] = (new MeetingRoomsRepository())->count(['status' => 'active']);
            } catch (\Throwable) {
                $this->data['meeting_rooms_active_count'] = 0;
            }
        }

        $this->data['payroll_documents_total'] = 0;
        $this->data['payroll_documents_latest'] = [];
        $this->data['payroll_pending_signatures'] = 0;
        if ($userId > 0 && $this->data['show_payroll_documents_card']) {
            $payrollRepo = new EmployeePayrollDocumentsRepository();
            $allDocs = $payrollRepo->listForUser($userId);
            $this->data['payroll_documents_total'] = count($allDocs);
            $this->data['payroll_documents_latest'] = array_slice($allDocs, 0, 3);
            $this->data['payroll_pending_signatures'] = $payrollRepo->countPendingSignaturesForUser($userId);
        }

        $this->data['my_calendar_month_count'] = 0;
        $this->data['my_calendar_modal_events_json'] = '[]';
        if ($userId > 0 && !empty($this->data['show_my_calendar_card'])) {
            try {
                $calRepo = new \App\adms\Models\Repository\UserCalendarRepository();
                $y = (int) date('Y');
                $m = (int) date('n');
                $this->data['my_calendar_month_count'] = $calRepo->countUnifiedAgendaInMonth($userId, $y, $m);
                $payload = $this->buildMyCalendarDashboardEventsPayload($userId, $menuPermission, $calRepo);
                $this->data['my_calendar_modal_events_json'] = json_encode(
                    $payload,
                    JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
                );
            } catch (\Throwable) {
                $this->data['my_calendar_modal_events_json'] = '[]';
            }
        }

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/dashboard/dashboard", $this->data);
        $loadView->loadView();
    }
}
