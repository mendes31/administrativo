<?php

namespace App\adms\Controllers\trainings;

use App\adms\Models\Repository\TrainingUsersRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Repository\TrainingsRepository;
use App\adms\Helpers\ScreenResolutionHelper;

class MatrixByUser
{
    private TrainingUsersRepository $trainingUsersRepo;
    private UsersRepository $usersRepo;
    private DepartmentsRepository $departmentsRepo;
    private PositionsRepository $positionsRepo;
    private TrainingsRepository $trainingsRepo;

    public function __construct()
    {
        $this->trainingUsersRepo = new TrainingUsersRepository();
        $this->usersRepo = new UsersRepository();
        $this->departmentsRepo = new DepartmentsRepository();
        $this->positionsRepo = new PositionsRepository();
        $this->trainingsRepo = new TrainingsRepository();
    }

    public function index(): void
    {
        // Obter configurações responsivas
        $resolution = ScreenResolutionHelper::getScreenResolution();
        $responsiveClasses = ScreenResolutionHelper::getResponsiveClasses($resolution['category']);
        $paginationSettings = ScreenResolutionHelper::getPaginationSettings($resolution['category']);
        
        // Verificar se o usuário clicou em "Limpar"
        if (isset($_GET['limpar'])) {
            unset($_SESSION['matrix_by_user_filters']);
            // Redirecionar para a página sem parâmetros
            header('Location: ' . $_ENV['URL_ADM'] . 'matrix-by-user');
            exit;
        }

        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        
        // Usar configuração responsiva para per_page
        if (isset($_GET['per_page']) && in_array((int)$_GET['per_page'], $paginationSettings['options'])) {
            $perPage = (int)$_GET['per_page'];
        } else {
            $perPage = $paginationSettings['per_page'];
        }
        $offset = ($page - 1) * $perPage;

        // Verificar se há filtros na URL
        $hasUrlFilters = !empty($_GET['colaborador']) || !empty($_GET['departamento']) || 
                         !empty($_GET['cargo']) || !empty($_GET['treinamento']) ||
                         !empty($_GET['tipo_vinculo']) || !empty($_GET['codigo']);

        // Se há filtros na URL, salvá-los na sessão
        if ($hasUrlFilters) {
            $filters = [
                'colaborador' => $_GET['colaborador'] ?? null,
                'departamento' => $_GET['departamento'] ?? null,
                'cargo' => $_GET['cargo'] ?? null,
                'treinamento' => $_GET['treinamento'] ?? null,
                'tipo_vinculo' => $_GET['tipo_vinculo'] ?? null,
                'codigo' => $_GET['codigo'] ?? null,
            ];
            $_SESSION['matrix_by_user_filters'] = $filters;
        } 
        // Se não há filtros na URL, usar os da sessão (se existirem)
        elseif (isset($_SESSION['matrix_by_user_filters'])) {
            $filters = $_SESSION['matrix_by_user_filters'];
        } 
        // Se não há filtros em nenhum lugar, usar valores vazios
        else {
            $filters = [
                'colaborador' => null,
                'departamento' => null,
                'cargo' => null,
                'treinamento' => null,
                'tipo_vinculo' => null,
                'codigo' => null,
            ];
        }
        
        $matrixByUser = [];
        $total = 0;

        // Garante materialização dos vínculos obrigatórios por cargo na tabela adms_training_users
        // antes de montar a listagem da matriz por colaborador.
        $syncOk = $this->trainingUsersRepo->syncMandatoryCargoLinksForAllActiveUsers(
            !empty($filters['treinamento']) ? (int)$filters['treinamento'] : null
        );
        if (!$syncOk) {
            \App\adms\Helpers\GenerateLog::generateLog(
                "error",
                "Falha ao sincronizar vínculos obrigatórios por cargo antes da matriz por colaborador.",
                ['filters' => $filters]
            );
        }

        if (!empty($filters['treinamento'])) {
            // Se filtrou por um treinamento específico, traz todos os vinculados (cargo e individual)
            $matrixByUser = $this->trainingUsersRepo->getAllVinculadosPorTreinamento($filters['treinamento']);
            $total = count($matrixByUser);
            // Paginação manual
            $matrixByUser = array_slice($matrixByUser, $offset, $perPage);
        } else {
            // Comportamento padrão (apenas obrigatórios por cargo)
            // OTIMIZADO: Usa contagem eficiente (COUNT no SQL) em vez de buscar 1.000.000 registros
            $result = $this->trainingUsersRepo->getMandatoryMatrixByUser($filters, $perPage, $offset, true);
            if (is_array($result) && isset($result['data']) && isset($result['total'])) {
                $matrixByUser = $result['data'];
                $total = $result['total'];
            } else {
                $matrixByUser = [];
                $total = 0;
            }
        }
        
        // NOTA: Filtro de código já é aplicado no SQL (não precisa filtrar em PHP novamente)

        // Exportação - Buscar todos os dados sem paginação
        if (isset($_GET['export']) && in_array($_GET['export'], ['excel', 'pdf'])) {
            $exportData = [];
            if (!empty($filters['treinamento'])) {
                $exportData = $this->trainingUsersRepo->getAllVinculadosPorTreinamento($filters['treinamento']);
            } else {
                // OTIMIZADO: Buscar em lotes para exportação (mais eficiente que 1.000.000)
                // Buscar em lotes de 10.000 até obter todos
                $exportData = [];
                $batchSize = 10000;
                $batchOffset = 0;
                do {
                    $batch = $this->trainingUsersRepo->getMandatoryMatrixByUser($filters, $batchSize, $batchOffset, false);
                    if (!empty($batch)) {
                        $exportData = array_merge($exportData, $batch);
                        $batchOffset += $batchSize;
                    } else {
                        break;
                    }
                } while (count($batch) === $batchSize);
            }
            
            if ($_GET['export'] === 'excel') {
                $this->exportExcel($exportData);
            } else {
                $this->exportPdf($exportData);
            }
            return;
        }
        $pagination = \App\adms\Controllers\Services\PaginationService::generatePagination(
            $total,
            $perPage,
            $page,
            'matrix-by-user',
            array_merge($filters, ['per_page' => $perPage])
        );
        $data = [
            'title_head' => 'Matriz de Treinamentos por Colaborador',
            'matrixByUser' => $matrixByUser,
            'menu' => 'gestao_treinamentos',
            'buttonPermission' => [],
            'filters' => $filters,
            'listUsers' => $this->usersRepo->getAllUsersSelect(),
            'listDepartments' => $this->departmentsRepo->getAllDepartmentsSelect(),
            'listPositions' => $this->positionsRepo->getAllPositionsSelect(),
            'listTrainings' => $this->trainingsRepo->getAllTrainingsSelect(),
            'pagination' => $pagination,
            'per_page' => $perPage,
        ];
        
        $pageLayout = new \App\adms\Controllers\Services\PageLayoutService();
        $data = $pageLayout->configurePageElements($data);
        
        // Adicionar configurações responsivas
        $data['responsiveClasses'] = $responsiveClasses;
        $data['paginationSettings'] = $paginationSettings;
        
        $loadView = new \App\adms\Views\Services\LoadViewService('adms/Views/trainings/matrixByUser', $data);
        $loadView->loadView();
    }

    private function exportExcel(array $matrix): void
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Configurar margens da página
        $sheet->getPageMargins()->setLeft(1.5);
        $sheet->getPageMargins()->setRight(1.5);
        $sheet->getPageMargins()->setTop(1.5);
        $sheet->getPageMargins()->setBottom(1.5);
        
        // Cabeçalho
        $headers = ['Nome', 'Departamento', 'Cargo', 'Treinamento', 'Código', 'Versão', 'Tipo de Vínculo'];
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
        $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);
        
        // Dados
        $row = 2;
        foreach ($matrix as $item) {
            $sheet->setCellValue('A' . $row, $item['user_name'] ?? $item['name'] ?? '');
            $sheet->setCellValue('B' . $row, $item['department'] ?? $item['department_nome'] ?? '');
            $sheet->setCellValue('C' . $row, $item['position'] ?? $item['cargo_nome'] ?? '');
            $sheet->setCellValue('D' . $row, $item['training_name'] ?? $item['treinamento_nome'] ?? '');
            $sheet->setCellValue('E' . $row, $item['codigo'] ?? '');
            $sheet->setCellValue('F' . $row, $item['training_version'] ?? '-');
            $sheet->setCellValue('G' . $row, ($item['tipo_vinculo'] ?? '') === 'individual' ? 'Individual' : 'Obrigatório por Cargo');
            $row++;
        }
        
        // Aplicar estilo aos dados (alinhamento à esquerda)
        $dataStyle = [
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT]
        ];
        $sheet->getStyle('A2:G' . ($row - 1))->applyFromArray($dataStyle);
        
        // Ajustar largura das colunas
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="matriz_treinamentos_por_colaborador.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function exportPdf(array $matrix): void
    {
        // Montar HTML da tabela com margens e alinhamento à esquerda
        $html = '<div style="margin: 20px; font-family: Arial, sans-serif;">';
        $html .= '<h2 style="text-align:center; margin-bottom: 20px;">Matriz de Treinamentos por Colaborador</h2>';
        $html .= '<table border="1" cellpadding="8" cellspacing="0" width="100%" style="font-size:10px; border-collapse:collapse; margin-left: 10px;">';
        $html .= '<thead><tr style="background:#f0f0f0;">';
        $html .= '<th style="text-align:left; padding-left: 10px;">Nome</th>';
        $html .= '<th style="text-align:left; padding-left: 10px;">Departamento</th>';
        $html .= '<th style="text-align:left; padding-left: 10px;">Cargo</th>';
        $html .= '<th style="text-align:left; padding-left: 10px;">Treinamento</th>';
        $html .= '<th style="text-align:left; padding-left: 10px;">Código</th>';
        $html .= '<th style="text-align:left; padding-left: 10px;">Versão</th>';
        $html .= '<th style="text-align:left; padding-left: 10px;">Tipo de Vínculo</th>';
        $html .= '</tr></thead><tbody>';
        
        foreach ($matrix as $item) {
            $html .= '<tr>';
            $html .= '<td style="text-align:left; padding-left: 10px;">' . htmlspecialchars($item['user_name'] ?? $item['name'] ?? '') . '</td>';
            $html .= '<td style="text-align:left; padding-left: 10px;">' . htmlspecialchars($item['department'] ?? $item['department_nome'] ?? '') . '</td>';
            $html .= '<td style="text-align:left; padding-left: 10px;">' . htmlspecialchars($item['position'] ?? $item['cargo_nome'] ?? '') . '</td>';
            $html .= '<td style="text-align:left; padding-left: 10px;">' . htmlspecialchars($item['training_name'] ?? $item['treinamento_nome'] ?? '') . '</td>';
            $html .= '<td style="text-align:left; padding-left: 10px;">' . htmlspecialchars($item['codigo'] ?? '') . '</td>';
            $html .= '<td style="text-align:left; padding-left: 10px;">' . htmlspecialchars($item['training_version'] ?? '-') . '</td>';
            $html .= '<td style="text-align:left; padding-left: 10px;">' . (($item['tipo_vinculo'] ?? '') === 'individual' ? 'Individual' : 'Obrigatório por Cargo') . '</td>';
            $html .= '</tr>';
        }
        
        if (empty($matrix)) {
            $html .= '<tr><td colspan="7" style="text-align:center; color:#888; padding-left: 10px;">Nenhum vínculo encontrado.</td></tr>';
        }
        
        $html .= '</tbody></table></div>';

        // Gerar PDF
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('matriz_treinamentos_por_colaborador.pdf', ['Attachment' => true]);
        exit;
    }
} 