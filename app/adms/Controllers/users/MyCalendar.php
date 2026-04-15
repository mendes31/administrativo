<?php

declare(strict_types=1);

namespace App\adms\Controllers\users;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\UserCalendarRepository;
use App\adms\Views\Services\LoadViewService;
use DateTimeImmutable;

/**
 * Calendário pessoal: reservas, convites, eventos corporativos e compromissos próprios.
 */
final class MyCalendar
{
    private array|string|null $data = null;

    public function index(): void
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }

        $uid = (int) $_SESSION['user_id'];
        $repo = new UserCalendarRepository();
        $adm = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/';

        $monthParam = trim((string) ($_GET['month'] ?? ''));
        if ($monthParam === '' || !preg_match('/^\d{4}-\d{2}$/', $monthParam)) {
            $monthParam = date('Y-m');
        }
        $firstDay = DateTimeImmutable::createFromFormat('Y-m-d', $monthParam . '-01');
        if ($firstDay === false) {
            $firstDay = new DateTimeImmutable(date('Y-m-01'));
            $monthParam = $firstDay->format('Y-m');
        }
        $lastDay = $firstDay->modify('last day of this month');
        $prevMonth = $firstDay->modify('-1 month')->format('Y-m');
        $nextMonth = $firstDay->modify('+1 month')->format('Y-m');
        $rangeStart = $firstDay->format('Y-m-d') . ' 00:00:00';
        $rangeEnd = $lastDay->format('Y-m-d') . ' 23:59:59';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $returnMonth = trim((string) ($_POST['return_month'] ?? ''));
            if ($returnMonth !== '' && preg_match('/^\d{4}-\d{2}$/', $returnMonth)) {
                $monthParam = $returnMonth;
            }
            $this->handlePost($uid, $repo);
            header('Location: ' . $adm . 'my-calendar?month=' . rawurlencode($monthParam));
            exit;
        }

        $agenda = $repo->listUnifiedAgenda($uid, $rangeStart, $rangeEnd);
        $agendaByDate = $this->groupAgendaByStartDate($agenda);

        $editId = (int) ($_GET['edit'] ?? 0);
        $editingPersonal = null;
        if ($editId > 0) {
            $editingPersonal = $repo->getPersonalById($uid, $editId);
        }

        $mesesPt = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
        ];
        $monthNamePt = $mesesPt[(int) $firstDay->format('n')] . ' de ' . $firstDay->format('Y');

        $this->data = [];
        $this->data['agenda'] = $agenda;
        $this->data['agenda_by_date'] = $agendaByDate;
        $this->data['selected_month'] = $monthParam;
        $this->data['prev_month'] = $prevMonth;
        $this->data['next_month'] = $nextMonth;
        $this->data['month_name_pt'] = $monthNamePt;
        $this->data['first_day'] = $firstDay;
        $this->data['last_day'] = $lastDay;
        $this->data['tomorrow_items'] = $repo->listTomorrowAndConflicts($uid);
        $this->data['overlap_warnings'] = $this->computeOverlapWarnings($agenda);
        $this->data['editing_personal'] = $editingPersonal;

        $pageElements = [
            'title_head' => 'Meu calendário',
            'menu' => 'my-calendar',
            'buttonPermission' => ['MyCalendar', 'ViewBooking', 'UpdateBooking', 'ViewCompanyEvent'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        $this->data['csrf_personal'] = CSRFHelper::generateCSRFToken('form_my_calendar_personal');

        $loadView = new LoadViewService('adms/Views/users/my_calendar', $this->data);
        $loadView->loadView();
    }

    /**
     * @param list<array<string, mixed>> $agenda
     * @return array<string, list<array<string, mixed>>>
     */
    private function groupAgendaByStartDate(array $agenda): array
    {
        $by = [];
        foreach ($agenda as $row) {
            $st = strtotime((string) ($row['start_datetime'] ?? ''));
            if ($st === false) {
                continue;
            }
            $d = date('Y-m-d', $st);
            if (!isset($by[$d])) {
                $by[$d] = [];
            }
            $by[$d][] = $row;
        }
        foreach ($by as $k => $list) {
            usort($list, static function (array $a, array $b): int {
                return strcmp((string) ($a['start_datetime'] ?? ''), (string) ($b['start_datetime'] ?? ''));
            });
            $by[$k] = $list;
        }

        return $by;
    }

    /**
     * @param list<array<string, mixed>> $agenda
     * @return list<string>
     */
    private function computeOverlapWarnings(array $agenda): array
    {
        $n = count($agenda);
        $warnings = [];
        for ($i = 0; $i < $n; $i++) {
            $a = $agenda[$i];
            $as = strtotime((string) ($a['start_datetime'] ?? ''));
            $ae = strtotime((string) ($a['end_datetime'] ?? ''));
            if ($as === false || $ae === false) {
                continue;
            }
            for ($j = $i + 1; $j < $n; $j++) {
                $b = $agenda[$j];
                $bs = strtotime((string) ($b['start_datetime'] ?? ''));
                $be = strtotime((string) ($b['end_datetime'] ?? ''));
                if ($bs === false || $be === false) {
                    continue;
                }
                if ($as < $be && $ae > $bs) {
                    $la = (string) ($a['title'] ?? 'Item');
                    $lb = (string) ($b['title'] ?? 'Item');
                    $warnings[] = 'Sobreposição: «' . $la . '» com «' . $lb . '».';
                }
            }
        }

        return array_values(array_unique($warnings));
    }

    private function handlePost(int $uid, UserCalendarRepository $repo): void
    {
        if (!CSRFHelper::validateCSRFToken('form_my_calendar_personal', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = '<div class="alert alert-danger">Token inválido.</div>';

            return;
        }
        $action = trim((string) ($_POST['calendar_action'] ?? ''));
        if ($action === 'add_personal') {
            $title = trim((string) ($_POST['personal_title'] ?? ''));
            $start = trim((string) ($_POST['personal_start'] ?? ''));
            $end = trim((string) ($_POST['personal_end'] ?? ''));
            $desc = trim((string) ($_POST['personal_description'] ?? ''));
            if ($title === '' || $start === '' || $end === '') {
                $_SESSION['msg'] = '<div class="alert alert-warning">Preencha título, início e fim do compromisso.</div>';

                return;
            }
            $ts = strtotime($start);
            $te = strtotime($end);
            if ($ts === false || $te === false || $te <= $ts) {
                $_SESSION['msg'] = '<div class="alert alert-warning">Datas inválidas.</div>';

                return;
            }
            $repo->createPersonal(
                $uid,
                $title,
                $desc !== '' ? $desc : null,
                date('Y-m-d H:i:s', $ts),
                date('Y-m-d H:i:s', $te)
            );
            $_SESSION['msg'] = '<div class="alert alert-success">Compromisso pessoal adicionado.</div>';
        } elseif ($action === 'update_personal') {
            $eid = (int) ($_POST['entry_id'] ?? 0);
            $title = trim((string) ($_POST['personal_title'] ?? ''));
            $start = trim((string) ($_POST['personal_start'] ?? ''));
            $end = trim((string) ($_POST['personal_end'] ?? ''));
            $desc = trim((string) ($_POST['personal_description'] ?? ''));
            if ($eid <= 0 || $title === '' || $start === '' || $end === '') {
                $_SESSION['msg'] = '<div class="alert alert-warning">Dados em falta para atualizar.</div>';

                return;
            }
            $ts = strtotime($start);
            $te = strtotime($end);
            if ($ts === false || $te === false || $te <= $ts) {
                $_SESSION['msg'] = '<div class="alert alert-warning">Datas inválidas.</div>';

                return;
            }
            if ($repo->updatePersonal($uid, $eid, $title, $desc !== '' ? $desc : null, date('Y-m-d H:i:s', $ts), date('Y-m-d H:i:s', $te))) {
                $_SESSION['msg'] = '<div class="alert alert-success">Compromisso atualizado.</div>';
            } else {
                $_SESSION['msg'] = '<div class="alert alert-warning">Não foi possível atualizar.</div>';
            }
        } elseif ($action === 'delete_personal') {
            $eid = (int) ($_POST['entry_id'] ?? 0);
            if ($eid > 0 && $repo->deletePersonal($uid, $eid)) {
                $_SESSION['msg'] = '<div class="alert alert-success">Compromisso removido.</div>';
            } else {
                $_SESSION['msg'] = '<div class="alert alert-warning">Não foi possível remover.</div>';
            }
        }
    }
}
