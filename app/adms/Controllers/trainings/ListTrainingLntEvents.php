<?php

namespace App\adms\Controllers\trainings;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\TrainingLntEventsRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Helpers\ScreenResolutionHelper;

class ListTrainingLntEvents
{
    private array $data = [];

    public function index(): void
    {
        $repo = new TrainingLntEventsRepository();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 30;
        $offset = ($page - 1) * $limit;

        $filters = [
            'event_type' => trim((string)($_GET['event_type'] ?? '')),
            'date_from' => trim((string)($_GET['date_from'] ?? '')),
            'date_to' => trim((string)($_GET['date_to'] ?? '')),
            'q' => trim((string)($_GET['q'] ?? '')),
        ];

        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $this->exportCsv($repo, $filters);
            return;
        }

        $total = $repo->count($filters);
        $this->data['items'] = $repo->list($filters, $limit, $offset);
        $this->data['filters'] = $filters;
        $this->data['pagination'] = [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => max(1, (int)ceil($total / $limit)),
        ];

        $resolution = ScreenResolutionHelper::getScreenResolution();
        $this->data['responsiveClasses'] = ScreenResolutionHelper::getResponsiveClasses($resolution['category']);

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements([
            'title_head' => 'Eventos LNT (RH)',
            'menu' => 'list-training-lnt-events',
            'buttonPermission' => ['ListTrainingLntEvents', 'ListTrainings'],
        ]));

        $loadView = new LoadViewService('adms/Views/trainings/listTrainingLntEvents', $this->data);
        $loadView->loadView();
    }

  /**
     * @param array<string, string> $filters
     */
    private function exportCsv(TrainingLntEventsRepository $repo, array $filters): void
    {
        $rows = $repo->list($filters, 5000, 0);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="eventos_lnt_' . date('Y-m-d') . '.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Data/Hora', 'Ação', 'Nome', 'CPF', 'Setor', 'Cargo', 'Admissão', 'Desligamento'], ';');
        foreach ($rows as $row) {
            fputcsv($out, [
                $row['created_at'] ?? '',
                $row['action_label'] ?? '',
                $row['collaborator_name'] ?? '',
                $row['collaborator_cpf'] ?? '',
                $row['department_name'] ?? '',
                $row['position_name'] ?? '',
                $row['data_admissao'] ?? '',
                $row['data_desligamento'] ?? '',
            ], ';');
        }
        fclose($out);
        exit;
    }
}
