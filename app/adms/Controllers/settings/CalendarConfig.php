<?php

namespace App\adms\Controllers\settings;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\CalendarRepository;
use App\adms\Models\Services\BrazilHolidaysService;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

class CalendarConfig
{
    public function index(): void
    {
        $form = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        $repo = new CalendarRepository();

        // Se POST com token válido, salva configurações e feriados
        if (!empty($form['csrf_token'])
            && CSRFHelper::validateCSRFToken('form_calendar_config', $form['csrf_token'])) {
            $this->save($form, $repo);
            return;
        }

        $settings = $repo->getSettings();
        $year = (int)($form['year'] ?? date('Y'));
        $holidays = $repo->listHolidays($year);

        // Se ainda não existem feriados cadastrados para o ano,
        // pré-carrega automaticamente os feriados nacionais.
        if (empty($holidays)) {
            $nationalService = new BrazilHolidaysService();
            $national = $nationalService->getNationalHolidays($year);
            foreach ($national as $h) {
                $repo->saveHoliday([
                    'id' => 0,
                    'name' => $h['name'],
                    'start_date' => $h['start_date'],
                    'end_date' => $h['end_date'],
                    'observations' => $h['observations'],
                    'year' => $year,
                ]);
            }
            $holidays = $repo->listHolidays($year);
        }

        $data = [
            'title_head' => 'Calendário e Feriados',
            'menu' => 'calendar-config',
            'buttonPermission' => ['CalendarConfig'],
            'settings' => $settings,
            'year' => $year,
            'holidays' => $holidays,
        ];
        $settingsId = (int) ($settings['id'] ?? 0);
        if ($settingsId > 0) {
            $returnUrl = $_ENV['URL_ADM'] . 'calendar-config';
            $data['log_resumo'] = LogResumoService::getResumo('calendar_settings', $settingsId, $returnUrl);
        }

        $pageLayout = new PageLayoutService();
        $data = array_merge($data, $pageLayout->configurePageElements($data));

        $loadView = new LoadViewService('adms/Views/settings/calendarConfig', $data);
        $loadView->loadView();
    }

    private function save(array $form, CalendarRepository $repo): void
    {
        // Salvar parâmetros gerais de calendário (semana/fim de semana)
        $repo->saveSettings([
            'week_start_day' => (int)($form['week_start_day'] ?? 1),
            'weekend_start_day' => (int)($form['weekend_start_day'] ?? 6),
            'weekend_end_day' => (int)($form['weekend_end_day'] ?? 7),
            'valid_for_one_year' => !empty($form['valid_for_one_year']) ? 1 : 0,
        ]);

        $year = (int)($form['year'] ?? date('Y'));

        // Salvar feriados da grade
        $names = $form['holiday_name'] ?? [];
        $starts = $form['holiday_start_date'] ?? [];
        $ends = $form['holiday_end_date'] ?? [];
        $obs = $form['holiday_observations'] ?? [];
        $ids = $form['holiday_id'] ?? [];

        foreach ($names as $idx => $name) {
            $name = trim((string)$name);
            $start = $starts[$idx] ?? '';

            if ($name === '' || $start === '') {
                continue;
            }

            $repo->saveHoliday([
                'id' => isset($ids[$idx]) && $ids[$idx] !== '' ? (int)$ids[$idx] : 0,
                'name' => $name,
                'start_date' => $start,
                'end_date' => $ends[$idx] ?? null,
                'observations' => $obs[$idx] ?? null,
                'year' => $year,
            ]);
        }

        $_SESSION['msg'] = 'Configurações de calendário salvas com sucesso.';
        $_SESSION['msg_type'] = 'success';

        header('Location: ' . $_ENV['URL_ADM'] . 'calendar-config');
        exit;
    }
}

