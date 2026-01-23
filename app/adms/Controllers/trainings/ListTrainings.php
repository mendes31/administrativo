<?php

namespace App\adms\Controllers\trainings;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\TrainingsRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Helpers\ScreenResolutionHelper;

class ListTrainings
{
    private array|string|null $data = null;
    private int $limitResult = 10;

    public function index(string|int $page = 1)
    {
        // Capturar o parâmetro page da URL, se existir
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int)$_GET['page'];
        }
        // Obter configurações responsivas
        $resolution = ScreenResolutionHelper::getScreenResolution();
        $responsiveClasses = ScreenResolutionHelper::getResponsiveClasses($resolution['category']);
        $paginationSettings = ScreenResolutionHelper::getPaginationSettings($resolution['category']);
        
        // Verificar se o usuário clicou em "Limpar"
        if (isset($_GET['limpar'])) {
            unset($_SESSION['list_trainings_filters']);
            // Redirecionar para a página sem parâmetros
            header('Location: ' . $_ENV['URL_ADM'] . 'list-trainings');
            exit;
        }

        // Tratar per_page com base na resolução
        if (isset($_GET['per_page']) && in_array((int)$_GET['per_page'], $paginationSettings['options'])) {
            $this->limitResult = (int)$_GET['per_page'];
        } else {
            $this->limitResult = $paginationSettings['per_page'];
        }

        // Verificar se há filtros na URL
        $hasUrlFilters = !empty($_GET['nome']) || !empty($_GET['codigo']) || 
                         !empty($_GET['ativo']) || !empty($_GET['instrutor']) ||
                         !empty($_GET['tipo']) || !empty($_GET['reciclagem']) ||
                         !empty($_GET['area_responsavel_id']) || !empty($_GET['area_elaborador_id']) ||
                         !empty($_GET['tipo_obrigatoriedade']);

        // Se há filtros na URL, salvá-los na sessão
        if ($hasUrlFilters) {
            $filters = [
                'nome' => $_GET['nome'] ?? '',
                'codigo' => $_GET['codigo'] ?? '',
                'ativo' => $_GET['ativo'] ?? '',
                'instrutor' => $_GET['instrutor'] ?? '',
                'tipo' => $_GET['tipo'] ?? '',
                'reciclagem' => $_GET['reciclagem'] ?? '',
                'area_responsavel_id' => $_GET['area_responsavel_id'] ?? '',
                'area_elaborador_id' => $_GET['area_elaborador_id'] ?? '',
                'tipo_obrigatoriedade' => $_GET['tipo_obrigatoriedade'] ?? '',
            ];
            $_SESSION['list_trainings_filters'] = $filters;
        } 
        // Se não há filtros na URL, usar os da sessão (se existirem)
        elseif (isset($_SESSION['list_trainings_filters'])) {
            $filters = $_SESSION['list_trainings_filters'];
        } 
        // Se não há filtros em nenhum lugar, usar valores vazios
        else {
            $filters = [
                'nome' => '',
                'codigo' => '',
                'ativo' => '',
                'instrutor' => '',
                'tipo' => '',
                'reciclagem' => '',
                'area_responsavel_id' => '',
                'area_elaborador_id' => '',
                'tipo_obrigatoriedade' => '',
            ];
        }

        // Verificar se é exportação (antes de processar dados paginados)
        if (isset($_GET['export']) && $_GET['export'] === 'excel') {
            $this->exportExcel($filters);
            return;
        }
        if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
            $this->exportPdf($filters);
            return;
        }

        $repo = new TrainingsRepository();
        $this->data['trainings'] = $repo->getAllTrainings((int)$page, (int)$this->limitResult, $filters);
        // Adicionar total de colaboradores vinculados em cada treinamento
        foreach ($this->data['trainings'] as &$training) {
            $training['colaboradores_vinculados'] = $repo->getTotalColaboradoresVinculados($training['id']);
            // Log temporário para depuração
            error_log('Treinamento ID ' . $training['id'] . ' - Colaboradores vinculados: ' . $training['colaboradores_vinculados']);
            $training['cargos_vinculados'] = $repo->getLinkedPositionsCount($training['id']);
        }
        unset($training);
        $totalTrainings = $repo->getTotalTrainings($filters);
        $pagination = PaginationService::generatePagination(
            (int) $totalTrainings,
            (int) $this->limitResult,
            (int) $page,
            'list-trainings',
            array_merge($filters, ['per_page' => $this->limitResult])
        );
        $this->data['pagination'] = $pagination;
        $this->data['per_page'] = $this->limitResult;
        $this->data['filters'] = $filters;
        
        // Carregar departamentos para os filtros
        $departmentsRepo = new DepartmentsRepository();
        $this->data['listDepartments'] = $departmentsRepo->getAllDepartmentsSelect();
        
        $pageElements = [
            'title_head' => 'Listar Treinamentos',
            'menu' => 'list-trainings',
            // Incluir todos os botões/atalhos que podem aparecer na listagem
            'buttonPermission' => [
                'CreateTraining',
                'ViewTraining',
                'UpdateTraining',
                'DeleteTraining',
                'TrainingPositions',
                'LinkTrainingUsers',
                'TrainingKpiDashboard',
                'TrainingMatrixManager',
                'ListTrainingStatus'
            ],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        // Adicionar dados responsivos
        $this->data['responsiveClasses'] = $responsiveClasses;
        $this->data['paginationSettings'] = $paginationSettings;
        $this->data['screenResolution'] = $resolution;
        
        $loadView = new LoadViewService('adms/Views/trainings/list', $this->data);
        $loadView->loadView();
    }

    private function exportExcel(array $filters): void
    {
        $repo = new TrainingsRepository();
        // Buscar todos os treinamentos sem paginação para exportação
        $trainings = $repo->getAllTrainings(1, 999999, $filters);
        
        // Adicionar dados adicionais
        foreach ($trainings as &$training) {
            $training['colaboradores_vinculados'] = $repo->getTotalColaboradoresVinculados($training['id']);
            $training['cargos_vinculados'] = $repo->getLinkedPositionsCount($training['id']);
        }
        unset($training);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Definir margens e alinhamento
        $sheet->getPageMargins()->setLeft(1.5);
        $sheet->getPageMargins()->setRight(1.5);
        $sheet->getPageMargins()->setTop(1.5);
        $sheet->getPageMargins()->setBottom(1.5);
        
        // Cabeçalho
        $headers = ['Código', 'Nome', 'Versão', 'Reciclagem', 'Área Responsável', 'Área Elaborador', 'Obrigatoriedade', 'Categoria', 'Instrutor', 'Carga Horária', 'Cargos Vinculados', 'Colaboradores Vinculados', 'Status'];
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
        $sheet->getStyle('A1:M1')->applyFromArray($headerStyle);
        
        // Dados
        $row = 2;
        foreach ($trainings as $training) {
            $sheet->setCellValue('A' . $row, $training['codigo'] ?? '-');
            $sheet->setCellValue('B' . $row, $training['nome'] ?? '-');
            $sheet->setCellValue('C' . $row, !empty($training['versao']) ? 'v' . $training['versao'] : '-');
            
            // Reciclagem
            $reciclagem = '-';
            if (!empty($training['reciclagem']) && !empty($training['reciclagem_periodo'])) {
                $periodo = (int)$training['reciclagem_periodo'];
                if ($periodo > 0) {
                    $reciclagem = $periodo . ' meses';
                }
            }
            $sheet->setCellValue('D' . $row, $reciclagem);
            
            $sheet->setCellValue('E' . $row, $training['area_responsavel_nome'] ?? '-');
            $sheet->setCellValue('F' . $row, $training['area_elaborador_nome'] ?? '-');
            $sheet->setCellValue('G' . $row, $training['tipo_obrigatoriedade'] ?? '-');
            $sheet->setCellValue('H' . $row, $training['tipo'] ?? '-');
            
            // Instrutor
            $instrutor = '-';
            if (!empty($training['user_name'])) {
                $instrutor = $training['user_name'];
            } elseif (!empty($training['instrutor'])) {
                $instrutor = $training['instrutor'];
            }
            $sheet->setCellValue('I' . $row, $instrutor);
            
            $sheet->setCellValue('J' . $row, !empty($training['carga_horaria']) ? substr($training['carga_horaria'], 0, 5) : '-');
            $sheet->setCellValue('K' . $row, $training['cargos_vinculados'] ?? 0);
            $sheet->setCellValue('L' . $row, $training['colaboradores_vinculados'] ?? 0);
            $sheet->setCellValue('M' . $row, !empty($training['ativo']) ? 'Ativo' : 'Inativo');
            $row++;
        }
        
        // Aplicar alinhamento à esquerda para todos os dados
        $dataStyle = [
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT]
        ];
        if ($row > 2) {
            $sheet->getStyle('A2:M' . ($row - 1))->applyFromArray($dataStyle);
        }
        
        // Ajustar largura das colunas
        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="listagem_treinamentos.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function exportPdf(array $filters): void
    {
        $repo = new TrainingsRepository();
        // Buscar todos os treinamentos sem paginação para exportação
        $trainings = $repo->getAllTrainings(1, 999999, $filters);
        
        // Adicionar dados adicionais
        foreach ($trainings as &$training) {
            $training['colaboradores_vinculados'] = $repo->getTotalColaboradoresVinculados($training['id']);
            $training['cargos_vinculados'] = $repo->getLinkedPositionsCount($training['id']);
        }
        unset($training);

        // Montar HTML da tabela
        $html = '<div style="margin: 20px; font-family: Arial, sans-serif;">';
        $html .= '<h2 style="text-align:center; margin-bottom: 20px;">Listagem de Treinamentos</h2>';
        
        // Informações dos filtros aplicados
        $filtrosAplicados = [];
        if (!empty($filters['nome'])) $filtrosAplicados[] = 'Nome: ' . htmlspecialchars($filters['nome']);
        if (!empty($filters['codigo'])) $filtrosAplicados[] = 'Código: ' . htmlspecialchars($filters['codigo']);
        if (!empty($filters['instrutor'])) $filtrosAplicados[] = 'Instrutor: ' . htmlspecialchars($filters['instrutor']);
        if (isset($filters['ativo']) && $filters['ativo'] !== '') $filtrosAplicados[] = 'Status: ' . ($filters['ativo'] ? 'Ativo' : 'Inativo');
        if (!empty($filters['tipo'])) $filtrosAplicados[] = 'Tipo: ' . htmlspecialchars($filters['tipo']);
        
        if (!empty($filtrosAplicados)) {
            $html .= '<p style="margin-bottom: 15px; font-size: 11px;"><strong>Filtros aplicados:</strong> ' . implode(' | ', $filtrosAplicados) . '</p>';
        }
        
        $html .= '<table border="1" cellpadding="6" cellspacing="0" width="100%" style="font-size:9px; border-collapse:collapse;">';
        $html .= '<thead><tr style="background:#f0f0f0;">';
        $html .= '<th style="text-align:left; padding-left: 8px;">Código</th>';
        $html .= '<th style="text-align:left; padding-left: 8px;">Nome</th>';
        $html .= '<th style="text-align:left; padding-left: 8px;">Versão</th>';
        $html .= '<th style="text-align:left; padding-left: 8px;">Reciclagem</th>';
        $html .= '<th style="text-align:left; padding-left: 8px;">Área Resp.</th>';
        $html .= '<th style="text-align:left; padding-left: 8px;">Área Elab.</th>';
        $html .= '<th style="text-align:left; padding-left: 8px;">Obrigatoriedade</th>';
        $html .= '<th style="text-align:left; padding-left: 8px;">Categoria</th>';
        $html .= '<th style="text-align:left; padding-left: 8px;">Instrutor</th>';
        $html .= '<th style="text-align:left; padding-left: 8px;">Carga Horária</th>';
        $html .= '<th style="text-align:left; padding-left: 8px;">Cargos</th>';
        $html .= '<th style="text-align:left; padding-left: 8px;">Colaboradores</th>';
        $html .= '<th style="text-align:left; padding-left: 8px;">Status</th>';
        $html .= '</tr></thead><tbody>';
        
        foreach ($trainings as $training) {
            $html .= '<tr>';
            $html .= '<td style="text-align:left; padding-left: 8px;">' . htmlspecialchars($training['codigo'] ?? '-') . '</td>';
            $html .= '<td style="text-align:left; padding-left: 8px;">' . htmlspecialchars($training['nome'] ?? '-') . '</td>';
            $html .= '<td style="text-align:left; padding-left: 8px;">' . (!empty($training['versao']) ? 'v' . htmlspecialchars($training['versao']) : '-') . '</td>';
            
            // Reciclagem
            $reciclagem = '-';
            if (!empty($training['reciclagem']) && !empty($training['reciclagem_periodo'])) {
                $periodo = (int)$training['reciclagem_periodo'];
                if ($periodo > 0) {
                    $reciclagem = $periodo . ' meses';
                }
            }
            $html .= '<td style="text-align:left; padding-left: 8px;">' . $reciclagem . '</td>';
            
            $html .= '<td style="text-align:left; padding-left: 8px;">' . htmlspecialchars($training['area_responsavel_nome'] ?? '-') . '</td>';
            $html .= '<td style="text-align:left; padding-left: 8px;">' . htmlspecialchars($training['area_elaborador_nome'] ?? '-') . '</td>';
            $html .= '<td style="text-align:left; padding-left: 8px;">' . htmlspecialchars($training['tipo_obrigatoriedade'] ?? '-') . '</td>';
            $html .= '<td style="text-align:left; padding-left: 8px;">' . htmlspecialchars($training['tipo'] ?? '-') . '</td>';
            
            // Instrutor
            $instrutor = '-';
            if (!empty($training['user_name'])) {
                $instrutor = htmlspecialchars($training['user_name']);
            } elseif (!empty($training['instrutor'])) {
                $instrutor = htmlspecialchars($training['instrutor']);
            }
            $html .= '<td style="text-align:left; padding-left: 8px;">' . $instrutor . '</td>';
            
            $html .= '<td style="text-align:left; padding-left: 8px;">' . (!empty($training['carga_horaria']) ? htmlspecialchars(substr($training['carga_horaria'], 0, 5)) : '-') . '</td>';
            $html .= '<td style="text-align:left; padding-left: 8px;">' . ($training['cargos_vinculados'] ?? 0) . '</td>';
            $html .= '<td style="text-align:left; padding-left: 8px;">' . ($training['colaboradores_vinculados'] ?? 0) . '</td>';
            $html .= '<td style="text-align:left; padding-left: 8px;">' . (!empty($training['ativo']) ? 'Ativo' : 'Inativo') . '</td>';
            $html .= '</tr>';
        }
        
        if (empty($trainings)) {
            $html .= '<tr><td colspan="13" style="text-align:center; color:#888; padding: 20px;">Nenhum treinamento encontrado.</td></tr>';
        }
        
        $html .= '</tbody></table>';
        $html .= '</div>';

        // Gerar PDF
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('listagem_treinamentos.pdf', ['Attachment' => true]);
        exit;
    }
} 