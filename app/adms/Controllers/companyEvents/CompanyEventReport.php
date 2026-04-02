<?php

namespace App\adms\Controllers\companyEvents;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CompanyEventRsvpAccessHelper;
use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Repository\CompanyEventsRepository;
use App\adms\Views\Services\LoadViewService;

class CompanyEventReport
{
    private array $data = [];

    public function index(string|null $id = null): void
    {
        $eventId = (int)($id ?? 0);
        if ($eventId <= 0) {
            header('Location: ' . $_ENV['URL_ADM'] . 'list-company-events');
            exit;
        }
        $repo = new CompanyEventsRepository();
        $event = $repo->getById($eventId);
        if (!$event) {
            $_SESSION['msg'] = '<div class="alert alert-danger">Evento não encontrado.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-company-events');
            exit;
        }
        $uid = (int)($_SESSION['user_id'] ?? 0);
        $permRepo = new ButtonPermissionUserRepository();
        $raw = $permRepo->buttonPermission([
            'UpdateCompanyEvent',
            'DeleteCompanyEvent',
            'CreateCompanyEvent',
            'CompanyEventReport',
        ]);
        $btnPerms = is_array($raw) ? $raw : [];
        if (!CompanyEventRsvpAccessHelper::canAdminRsvpForOthers($event, $uid, $btnPerms)) {
            $_SESSION['msg'] = '<div class="alert alert-danger">Apenas o criador do evento ou a equipe autorizada pode ver o relatório.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-company-events');
            exit;
        }

        $this->data['event'] = $event;
        // Após o prazo: garante linhas "declined" para quem não respondeu (incl. quem nunca acessou o sistema).
        $repo->syncAutoDeclineForAllEligibleUsersAfterDeadline($eventId);
        $this->data['rows'] = $repo->getReportRowsForEvent($eventId);
        $this->data['can_edit_rsvp_responses'] = !empty($event['requires_rsvp'])
            && CompanyEventRsvpAccessHelper::canAdminRsvpForOthers($event, $uid, $btnPerms);

        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $this->exportCsv($event['title'] ?? 'evento', $this->data['rows']);
            return;
        }

        $pageElements = [
            'title_head' => 'Relatório de confirmações',
            'menu' => 'company-event-report',
            'buttonPermission' => [],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/companyEvents/report', $this->data);
        $loadView->loadView();
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function exportCsv(string $title, array $rows): void
    {
        $safe = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $title);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="relatorio_evento_' . $safe . '.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($out, ['Colaborador', 'E-mail', 'Departamento', 'Status', 'Respondido em', 'Cancelado em', 'Convidados'], ';');
        foreach ($rows as $r) {
            $guests = array_map(static fn ($g) => ($g['full_name'] ?? '') . ' (' . ($g['relationship'] ?? '') . ')', $r['guests'] ?? []);
            fputcsv($out, [
                $r['user_name'] ?? '',
                $r['user_email'] ?? '',
                $r['department_name'] ?? '',
                $r['status'] ?? '',
                $r['responded_at'] ?? '',
                $r['cancelled_at'] ?? '',
                implode(' | ', $guests),
            ], ';');
        }
        fclose($out);
        exit;
    }
}
