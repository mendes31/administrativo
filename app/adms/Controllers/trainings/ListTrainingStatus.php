<?php

namespace App\adms\Controllers\trainings;

use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Repository\TrainingsRepository;
use App\adms\Models\Repository\TrainingUsersRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\PositionDisplayHelper;
use App\adms\Helpers\ScreenResolutionHelper;
use App\adms\Models\Services\TrainingStatusUpdaterService;

class ListTrainingStatus
{
    private array $data = [];

    public function index(): void
    {
        // Atualizar status dinâmicos automaticamente (se necessário)
        // Executa apenas se passou mais de 15 minutos desde a última atualização
        TrainingStatusUpdaterService::ensureUpdated();
        
        // Obter configurações responsivas
        $resolution = ScreenResolutionHelper::getScreenResolution();
        $responsiveClasses = ScreenResolutionHelper::getResponsiveClasses($resolution['category']);
        $paginationSettings = ScreenResolutionHelper::getPaginationSettings($resolution['category']);
        
        $trainingUsersRepo = new TrainingUsersRepository();
        $usersRepo = new UsersRepository();
        $departmentsRepo = new DepartmentsRepository();
        $positionsRepo = new PositionsRepository();
        $trainingsRepo = new TrainingsRepository();

        // Verificar se o usuário clicou em "Limpar"
        if (isset($_GET['limpar'])) {
            unset($_SESSION['training_status_filters']);
            // Redirecionar para a página sem parâmetros
            header('Location: ' . $_ENV['URL_ADM'] . 'list-training-status');
            exit;
        }

        // Verificar se há filtros na URL
        $hasUrlFilters = !empty($_GET['colaborador']) || !empty($_GET['departamento']) || 
                         !empty($_GET['cargo']) || !empty($_GET['treinamento']) || 
                         !empty($_GET['status']) || !empty($_GET['codigo']) ||
                         !empty($_GET['area_responsavel_id']) || !empty($_GET['area_elaborador_id']);

        // Se há filtros na URL, salvá-los na sessão
        if ($hasUrlFilters) {
            $filters = [
                'colaborador' => $_GET['colaborador'] ?? null,
                'departamento' => $_GET['departamento'] ?? null,
                'cargo' => $_GET['cargo'] ?? null,
                'treinamento' => $_GET['treinamento'] ?? null,
                'status' => $_GET['status'] ?? '',
                'codigo' => $_GET['codigo'] ?? null,
                'area_responsavel_id' => $_GET['area_responsavel_id'] ?? null,
                'area_elaborador_id' => $_GET['area_elaborador_id'] ?? null,
            ];
            $_SESSION['training_status_filters'] = $filters;
        } 
        // Se não há filtros na URL, usar os da sessão (se existirem)
        elseif (isset($_SESSION['training_status_filters'])) {
            $filters = $_SESSION['training_status_filters'];
        } 
        // Se não há filtros em nenhum lugar, usar valores vazios
        else {
            $filters = [
                'colaborador' => null,
                'departamento' => null,
                'cargo' => null,
                'treinamento' => null,
                'status' => '',
                'codigo' => null,
                'area_responsavel_id' => null,
                'area_elaborador_id' => null,
            ];
        }

        $statusFiltro = $filters['status'] ?? '';

        // Garante materialização dos vínculos obrigatórios por cargo na tabela adms_training_users
        // antes da listagem de status.
        $syncOk = $trainingUsersRepo->syncMandatoryCargoLinksForAllActiveUsers(
            !empty($filters['treinamento']) ? (int)$filters['treinamento'] : null
        );
        if (!$syncOk) {
            \App\adms\Helpers\GenerateLog::generateLog(
                "error",
                "Falha ao sincronizar vínculos obrigatórios por cargo antes da listagem de status.",
                ['filters' => $filters]
            );
        }
        
        // Paginação
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        // Usar configuração responsiva + parâmetro per_page (10, 20, 50, 100)
        if (isset($_GET['per_page']) && in_array((int)$_GET['per_page'], $paginationSettings['options'] ?? [10, 20, 50, 100])) {
            $perPage = (int) $_GET['per_page'];
        } else {
            $perPage = $paginationSettings['per_page'] ?? 50;
        }
        
        // Exportação: todos os registros do conjunto filtrado (ignora paginação da tela)
        if (isset($_GET['export']) && $_GET['export'] === 'excel') {
            $exportRows = $this->fetchExportMatrix($trainingUsersRepo, $filters);
            $this->exportExcel($exportRows);
            return;
        }
        if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
            $exportRows = $this->fetchExportMatrix($trainingUsersRepo, $filters);
            $this->exportPdf($exportRows);
            return;
        }

        // Buscar dados com paginação (OTIMIZADO - resolve N+1 e adiciona paginação)
        $matrixResult = $trainingUsersRepo->getTrainingStatusByUser($filters, $page, $perPage);
        
        // Dados para a view
        $this->data = [
            'filters' => $filters,
            'matrix' => $matrixResult['data'],
            'pagination' => [
                'total' => $matrixResult['total'],
                'total_pages' => $matrixResult['total_pages'],
                'current_page' => $matrixResult['current_page'],
                'per_page' => $matrixResult['per_page'],
            ],
            'summary' => $trainingUsersRepo->getSummaryAll(),
            'expiring' => $trainingUsersRepo->getExpiringTrainings(30),
            'listDepartments' => $departmentsRepo->getAllDepartmentsSelect(),
            'listPositions' => $positionsRepo->getAllPositionsSelect(),
            'listTrainings' => $trainingsRepo->getAllTrainingsSelect(),
            'listUsers' => $usersRepo->getAllUsersSelect(),
        ];
        // Contagem dinâmica dos status para os cards (usar summary otimizado)
        $summary = $trainingUsersRepo->getSummaryAll();
        $statusCounts = [
            'dentro_do_prazo' => $summary['dentro_do_prazo'] ?? 0,
            'proximo_vencimento' => $summary['proximo_vencimento'] ?? 0,
            'vencido' => $summary['vencido'] ?? 0,
            'agendado' => $summary['agendado'] ?? 0,
            'concluido' => $summary['concluido'] ?? 0,
            'todos' => $summary['todos'] ?? 0,
        ];
        $this->data['statusCounts'] = $statusCounts;

        // Elementos de página
        $pageElements = [
            'title_head' => 'Matriz de Treinamentos',
            'menu' => 'list-training-status',
            'buttonPermission' => ['ListTrainingStatus'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        // Adicionar configurações responsivas
        $this->data['responsiveClasses'] = $responsiveClasses;
        $this->data['paginationSettings'] = $paginationSettings;

        $loadView = new LoadViewService('adms/Views/trainings/listTrainingStatus', $this->data);
        $loadView->loadView();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchExportMatrix(TrainingUsersRepository $trainingUsersRepo, array $filters): array
    {
        $preview = $trainingUsersRepo->getTrainingStatusByUser($filters, 1, 1);
        $total = max(1, (int)($preview['total'] ?? 0));

        return $trainingUsersRepo->getTrainingStatusByUser($filters, 1, $total)['data'];
    }

    /**
     * @param array<int, array<string, mixed>> $matrix
     */
    private function exportExcel(array $matrix): void
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'Código',
            'Treinamento',
            'Versão',
            'Colaborador',
            'Departamento',
            'Cargo',
            'Status',
            'Prazo Treinamento',
            'Agendamento',
            'Tipo do Treinamento',
        ];
        $sheet->fromArray($headers, null, 'A1');

        $headerStyle = [
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F0F0F0'],
            ],
        ];
        $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);

        $row = 2;
        foreach ($matrix as $item) {
            $status = (string)($item['status_dinamico'] ?? $item['status'] ?? '');
            $sheet->setCellValue('A' . $row, $item['codigo'] ?? '');
            $sheet->setCellValue('B' . $row, $item['training_name'] ?? '');
            $sheet->setCellValue('C' . $row, !empty($item['training_version']) ? 'v' . $item['training_version'] : '-');
            $sheet->setCellValue('D' . $row, $item['user_name'] ?? '');
            $sheet->setCellValue('E' . $row, $item['department'] ?? '');
            $sheet->setCellValue('F' . $row, PositionDisplayHelper::formatForDisplay((string)($item['position'] ?? '')));
            $sheet->setCellValue('G' . $row, $this->formatStatusLabel($status));
            $sheet->setCellValue('H' . $row, $this->formatPrazoTreinamento($item, $status));
            $sheet->setCellValue('I' . $row, $this->formatAgendamento($item));
            $sheet->setCellValue('J' . $row, $item['tipo_treinamento'] ?? '-');
            $row++;
        }

        $dataStyle = [
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT],
        ];
        if ($row > 2) {
            $sheet->getStyle('A2:J' . ($row - 1))->applyFromArray($dataStyle);
        }

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="status_treinamentos_colaborador.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * @param array<int, array<string, mixed>> $matrix
     */
    private function exportPdf(array $matrix): void
    {
        $html = '<div style="margin: 20px; font-family: Arial, sans-serif;">';
        $html .= '<h2 style="text-align:center; margin-bottom: 20px;">Status de Treinamentos por Colaborador</h2>';
        $html .= '<table border="1" cellpadding="8" cellspacing="0" width="100%" style="font-size:9px; border-collapse:collapse;">';
        $html .= '<thead><tr style="background:#f0f0f0;">';
        foreach ([
            'Código',
            'Treinamento',
            'Versão',
            'Colaborador',
            'Departamento',
            'Cargo',
            'Status',
            'Prazo Treinamento',
            'Agendamento',
            'Tipo',
        ] as $header) {
            $html .= '<th style="text-align:left;">' . htmlspecialchars($header) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($matrix as $item) {
            $status = (string)($item['status_dinamico'] ?? $item['status'] ?? '');
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars((string)($item['codigo'] ?? '')) . '</td>';
            $html .= '<td>' . htmlspecialchars((string)($item['training_name'] ?? '')) . '</td>';
            $html .= '<td>' . htmlspecialchars(!empty($item['training_version']) ? 'v' . $item['training_version'] : '-') . '</td>';
            $html .= '<td>' . htmlspecialchars((string)($item['user_name'] ?? '')) . '</td>';
            $html .= '<td>' . htmlspecialchars((string)($item['department'] ?? '')) . '</td>';
            $html .= '<td>' . htmlspecialchars(PositionDisplayHelper::formatForDisplay((string)($item['position'] ?? ''))) . '</td>';
            $html .= '<td>' . htmlspecialchars($this->formatStatusLabel($status)) . '</td>';
            $html .= '<td>' . htmlspecialchars($this->formatPrazoTreinamento($item, $status)) . '</td>';
            $html .= '<td>' . htmlspecialchars($this->formatAgendamento($item)) . '</td>';
            $html .= '<td>' . htmlspecialchars((string)($item['tipo_treinamento'] ?? '-')) . '</td>';
            $html .= '</tr>';
        }

        if ($matrix === []) {
            $html .= '<tr><td colspan="10" style="text-align:center; color:#888; padding: 20px;">Nenhum registro encontrado.</td></tr>';
        }

        $html .= '</tbody></table></div>';

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('status_treinamentos_colaborador.pdf', ['Attachment' => true]);
        exit;
    }

    private function formatStatusLabel(string $status): string
    {
        return match ($status) {
            'em_dia' => 'Em Dia',
            'pendente' => 'Pendente',
            'vencido' => 'Vencido',
            'proximo_vencimento' => 'Próximo do Vencimento',
            'agendado' => 'Agendado',
            'dentro_do_prazo' => 'Dentro do Prazo',
            'concluido' => 'Concluído',
            default => $status !== '' ? ucfirst($status) : '-',
        };
    }

    /**
     * @param array<string, mixed> $item
     */
    private function formatPrazoTreinamento(array $item, string $status): string
    {
        if ($status === 'concluido') {
            return '-';
        }

        $prazo = $item['data_limite_primeiro_treinamento'] ?? null;
        if (empty($prazo)) {
            return '-';
        }

        return date('d/m/Y', strtotime((string)$prazo));
    }

    /**
     * @param array<string, mixed> $item
     */
    private function formatAgendamento(array $item): string
    {
        $dataAgendada = $item['data_agendada'] ?? null;
        if (empty($dataAgendada) || $dataAgendada === '0000-00-00') {
            return '-';
        }

        return date('d/m/Y', strtotime((string)$dataAgendada));
    }
} 