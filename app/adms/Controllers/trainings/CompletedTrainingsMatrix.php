<?php

namespace App\adms\Controllers\trainings;

use App\adms\Models\Repository\TrainingUsersRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\TrainingsRepository;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Views\Services\LoadViewService;
use App\adms\Helpers\ScreenResolutionHelper;

class CompletedTrainingsMatrix
{
    private TrainingUsersRepository $trainingUsersRepo;
    private UsersRepository $usersRepo;
    private TrainingsRepository $trainingsRepo;

    public function __construct()
    {
        $this->trainingUsersRepo = new TrainingUsersRepository();
        $this->usersRepo = new UsersRepository();
        $this->trainingsRepo = new TrainingsRepository();
    }

    public function index(): void
    {
        // Obter configurações responsivas
        $resolution = ScreenResolutionHelper::getScreenResolution();
        $responsiveClasses = ScreenResolutionHelper::getResponsiveClasses($resolution['category']);
        $paginationSettings = ScreenResolutionHelper::getPaginationSettings($resolution['category']);
        
        $filters = [
            'colaborador' => $_GET['colaborador'] ?? null,
            'treinamento' => $_GET['treinamento'] ?? null,
            'mes' => $_GET['mes'] ?? null,
            'ano' => $_GET['ano'] ?? null,
            'sort' => $_GET['sort'] ?? null,
            'order' => $_GET['order'] ?? null,
        ];
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        
        // Usar configuração responsiva para per_page
        if (isset($_GET['per_page']) && in_array((int)$_GET['per_page'], $paginationSettings['options'])) {
            $perPage = (int)$_GET['per_page'];
        } else {
            $perPage = $paginationSettings['per_page'];
        }
        $matrixData = $this->trainingUsersRepo->getCompletedTrainingsMatrixPaginated($filters, $page, $perPage);
        $matrix = $matrixData['data'];
        $total = $matrixData['total'];
        $summary = $this->trainingUsersRepo->getCompletedTrainingsSummary($filters);
        // Exportação
        if (isset($_GET['export']) && $_GET['export'] === 'excel') {
            $this->exportExcel($matrix);
            return;
        }
        if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
            $this->exportPdf($matrix);
            return;
        }
        $data = [
            'title_head' => 'Matriz de Treinamentos Realizados',
            'menu' => 'completed-trainings-matrix',
            'buttonPermission' => [],
            'filters' => $filters,
            'matrix' => $matrix,
            'summary' => $summary,
            'listUsers' => $this->usersRepo->getAllUsersSelect(),
            'listTrainings' => $this->trainingsRepo->getAllTrainingsSelect(),
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
        ];
        $pageLayout = new PageLayoutService();
        $data = $pageLayout->configurePageElements($data);
        
        // Adicionar configurações responsivas
        $data['responsiveClasses'] = $responsiveClasses;
        $data['paginationSettings'] = $paginationSettings;
        $loadView = new LoadViewService('adms/Views/trainings/completedTrainingsMatrix', $data);
        $loadView->loadView();
    }

    private function exportExcel(array $matrix): void
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Definir margens e alinhamento
        $sheet->getPageMargins()->setLeft(1.5);
        $sheet->getPageMargins()->setRight(1.5);
        $sheet->getPageMargins()->setTop(1.5);
        $sheet->getPageMargins()->setBottom(1.5);
        
        // Cabeçalho
        $headers = ['Colaborador', 'Treinamento', 'Código', 'Versão', 'Data Realização', 'Data Avaliação', 'Horas', 'Instrutor', 'Nota', 'Observações'];
        $sheet->fromArray($headers, null, 'A1');
        
        // Aplicar estilo ao cabeçalho
        $headerStyle = [
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F0F0F0']
            ]
        ];
        $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);
        
        // Dados
        $row = 2;
        foreach ($matrix as $item) {
            $sheet->setCellValue('A' . $row, $item['user_name']);
            $sheet->setCellValue('B' . $row, $item['training_name']);
            $sheet->setCellValue('C' . $row, $item['training_code']);
            $sheet->setCellValue('D' . $row, $item['training_version'] ?? '-');
            $sheet->setCellValue('E' . $row, $item['data_realizacao'] ? date('d/m/Y', strtotime($item['data_realizacao'])) : '-');
            $sheet->setCellValue('F' . $row, $item['data_avaliacao'] ? date('d/m/Y', strtotime($item['data_avaliacao'])) : '-');
            $sheet->setCellValue('G' . $row, $item['carga_horaria'] ? substr($item['carga_horaria'], 0, 5) . 'h' : '-');
            $sheet->setCellValue('H' . $row, $item['instrutor_nome'] ?? '-');
            $sheet->setCellValue('I' . $row, $item['nota'] ?? '-');
            $sheet->setCellValue('J' . $row, $item['observacoes'] ?? '-');
            $row++;
        }
        
        // Aplicar alinhamento à esquerda para todos os dados
        $dataStyle = [
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT]
        ];
        $sheet->getStyle('A2:J' . ($row - 1))->applyFromArray($dataStyle);
        
        // Ajustar largura das colunas
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        // Download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="matriz_treinamentos_realizados.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function exportPdf(array $matrix): void
    {
        // Montar HTML da tabela com alinhamento à esquerda e recuo
        $html = '<div style="margin: 20px; font-family: Arial, sans-serif;">';
        $html .= '<h2 style="text-align:center; margin-bottom: 20px;">Matriz de Treinamentos Realizados</h2>';
        $html .= '<table border="1" cellpadding="8" cellspacing="0" width="100%" style="font-size:10px; border-collapse:collapse; margin-left: 10px;">';
        $html .= '<thead><tr style="background:#f0f0f0;">';
        $html .= '<th style="text-align:left; padding-left: 10px;">Colaborador</th>';
        $html .= '<th style="text-align:left; padding-left: 10px;">Treinamento</th>';
        $html .= '<th style="text-align:left; padding-left: 10px;">Código</th>';
        $html .= '<th style="text-align:left; padding-left: 10px;">Versão</th>';
        $html .= '<th style="text-align:left; padding-left: 10px;">Data Realização</th>';
        $html .= '<th style="text-align:left; padding-left: 10px;">Data Avaliação</th>';
        $html .= '<th style="text-align:left; padding-left: 10px;">Horas</th>';
        $html .= '<th style="text-align:left; padding-left: 10px;">Instrutor</th>';
        $html .= '<th style="text-align:left; padding-left: 10px;">Nota</th>';
        $html .= '<th style="text-align:left; padding-left: 10px;">Observações</th>';
        $html .= '</tr></thead><tbody>';
        foreach ($matrix as $item) {
            $html .= '<tr>';
            $html .= '<td style="text-align:left; padding-left: 10px;">' . htmlspecialchars($item['user_name']) . '</td>';
            $html .= '<td style="text-align:left; padding-left: 10px;">' . htmlspecialchars($item['training_name']) . '</td>';
            $html .= '<td style="text-align:left; padding-left: 10px;">' . htmlspecialchars($item['training_code']) . '</td>';
            $html .= '<td style="text-align:left; padding-left: 10px;">' . htmlspecialchars($item['training_version'] ?? '-') . '</td>';
            $html .= '<td style="text-align:left; padding-left: 10px;">' . ($item['data_realizacao'] ? date('d/m/Y', strtotime($item['data_realizacao'])) : '-') . '</td>';
            $html .= '<td style="text-align:left; padding-left: 10px;">' . ($item['data_avaliacao'] ? date('d/m/Y', strtotime($item['data_avaliacao'])) : '-') . '</td>';
            $html .= '<td style="text-align:left; padding-left: 10px;">' . ($item['carga_horaria'] ? substr($item['carga_horaria'], 0, 5) . 'h' : '-') . '</td>';
            $html .= '<td style="text-align:left; padding-left: 10px;">' . htmlspecialchars($item['instrutor_nome'] ?? '-') . '</td>';
            $html .= '<td style="text-align:left; padding-left: 10px;">' . htmlspecialchars($item['nota'] ?? '-') . '</td>';
            $html .= '<td style="text-align:left; padding-left: 10px;">' . htmlspecialchars($item['observacoes'] ?? '-') . '</td>';
            $html .= '</tr>';
        }
        if (empty($matrix)) {
            $html .= '<tr><td colspan="10" style="text-align:center; color:#888; padding: 20px;">Nenhum treinamento realizado encontrado.</td></tr>';
        }
        $html .= '</tbody></table>';
        $html .= '</div>';

        // Gerar PDF
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('matriz_treinamentos_realizados.pdf', ['Attachment' => true]);
        exit;
    }
} 