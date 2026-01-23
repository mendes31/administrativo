<?php

namespace App\adms\Controllers\trainings;

use App\adms\Models\Repository\TrainingUsersRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\TrainingsRepository;
use App\adms\Models\Repository\TrainingApplicationsRepository;
use App\adms\Models\Repository\LogAlteracoesRepository;
use App\adms\Models\Repository\LogJustificativasRepository;
use App\adms\Models\Repository\LoginRepository;
use App\adms\Models\Repository\PagesRoutesRepository;
use App\adms\Models\Services\LogAlteracaoService;
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
        
        // Verificar se o usuário clicou em "Limpar"
        if (isset($_GET['limpar'])) {
            unset($_SESSION['completed_trainings_filters']);
            // Redirecionar para a página sem parâmetros
            header('Location: ' . $_ENV['URL_ADM'] . 'completed-trainings-matrix');
            exit;
        }

        // Verificar se há filtros na URL
        $hasUrlFilters = !empty($_GET['colaborador']) || !empty($_GET['treinamento']) || 
                         !empty($_GET['mes']) || !empty($_GET['ano']) ||
                         !empty($_GET['sort']) || !empty($_GET['order']) ||
                         !empty($_GET['codigo']);

        // Se há filtros na URL, salvá-los na sessão
        if ($hasUrlFilters) {
            $filters = [
                'colaborador' => $_GET['colaborador'] ?? null,
                'treinamento' => $_GET['treinamento'] ?? null,
                'mes' => $_GET['mes'] ?? null,
                'ano' => $_GET['ano'] ?? null,
                'sort' => $_GET['sort'] ?? null,
                'order' => $_GET['order'] ?? null,
                'codigo' => $_GET['codigo'] ?? null,
            ];
            $_SESSION['completed_trainings_filters'] = $filters;
        } 
        // Se não há filtros na URL, usar os da sessão (se existirem)
        elseif (isset($_SESSION['completed_trainings_filters'])) {
            $filters = $_SESSION['completed_trainings_filters'];
        } 
        // Se não há filtros em nenhum lugar, usar valores vazios
        else {
            $filters = [
                'colaborador' => null,
                'treinamento' => null,
                'mes' => null,
                'ano' => null,
                'sort' => null,
                'order' => null,
                'codigo' => null,
            ];
        }

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
        // Filtro por código (parcial) em nível de aplicação caso o repositório não trate
        if (!empty($filters['codigo'])) {
            $codigoFiltro = trim((string)$filters['codigo']);
            $matrix = array_values(array_filter($matrix, function($row) use ($codigoFiltro) {
                $codigo = $row['training_code'] ?? $row['codigo'] ?? '';
                return stripos((string)$codigo, $codigoFiltro) !== false;
            }));
            $total = count($matrix);
        }
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
            'buttonPermission' => ['EditCompletedTraining'],
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

    /**
     * Retorna dados de uma aplicação de treinamento para edição (AJAX)
     */
    public function getApplication(): void
    {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
            exit;
        }

        // Verificar permissão para editar treinamentos realizados
        $pagesRepo = new PagesRoutesRepository();
        $editPage = $pagesRepo->getPage('EditCompletedTraining');
        if (!$editPage || !$pagesRepo->checkUserPagePermission($editPage['id_ap'])) {
            echo json_encode(['success' => false, 'message' => 'Você não tem permissão para editar treinamentos realizados']);
            exit;
        }

        $applicationId = $_GET['id'] ?? null;
        if (!$applicationId || !is_numeric($applicationId)) {
            echo json_encode(['success' => false, 'message' => 'ID da aplicação não informado']);
            exit;
        }

        $applicationsRepo = new TrainingApplicationsRepository();
        $application = $applicationsRepo->getById((int)$applicationId);

        if (!$application) {
            echo json_encode(['success' => false, 'message' => 'Aplicação não encontrada']);
            exit;
        }

        // Verificar se o status é concluído
        if ($application['status'] !== 'concluido') {
            echo json_encode(['success' => false, 'message' => 'Apenas treinamentos concluídos podem ser editados']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'application' => [
                'id' => $application['id'],
                'data_realizacao' => $application['data_realizacao'] ?? '',
                'data_avaliacao' => $application['data_avaliacao'] ?? '',
                'nota' => $application['nota'] ?? '',
                'instrutor_nome' => $application['instrutor_nome'] ?? '',
                'observacoes' => $application['observacoes'] ?? '',
            ]
        ]);
        exit;
    }

    /**
     * Atualiza uma aplicação de treinamento com validação de senha e justificativa
     */
    public function updateApplication(): void
    {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
            exit;
        }

        // Verificar permissão para editar treinamentos realizados
        $pagesRepo = new PagesRoutesRepository();
        $editPage = $pagesRepo->getPage('EditCompletedTraining');
        if (!$editPage || !$pagesRepo->checkUserPagePermission($editPage['id_ap'])) {
            echo json_encode(['success' => false, 'message' => 'Você não tem permissão para editar treinamentos realizados']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }

        $applicationId = $_POST['application_id'] ?? null;
        $justificativa = trim($_POST['justificativa'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validações
        if (!$applicationId || !is_numeric($applicationId)) {
            echo json_encode(['success' => false, 'message' => 'ID da aplicação não informado']);
            exit;
        }

        if (empty($justificativa)) {
            echo json_encode(['success' => false, 'message' => 'Justificativa é obrigatória']);
            exit;
        }

        if (empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Senha é obrigatória']);
            exit;
        }

        // Validar senha do usuário
        $loginRepo = new LoginRepository();
        $user = $loginRepo->getUser($_SESSION['user_username'] ?? '');
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
            exit;
        }

        if (!password_verify($password, $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'Senha incorreta']);
            exit;
        }

        // Buscar dados atuais da aplicação
        $applicationsRepo = new TrainingApplicationsRepository();
        $applicationAtual = $applicationsRepo->getById((int)$applicationId);

        if (!$applicationAtual) {
            echo json_encode(['success' => false, 'message' => 'Aplicação não encontrada']);
            exit;
        }

        // Verificar se o status é concluído
        if ($applicationAtual['status'] !== 'concluido') {
            echo json_encode(['success' => false, 'message' => 'Apenas treinamentos concluídos podem ser editados']);
            exit;
        }

        // Preparar dados para atualização
        $dadosAtualizacao = [
            'data_realizacao' => !empty($_POST['data_realizacao']) ? $_POST['data_realizacao'] : null,
            'data_avaliacao' => !empty($_POST['data_avaliacao']) ? $_POST['data_avaliacao'] : null,
            'nota' => !empty($_POST['nota']) ? $_POST['nota'] : null,
            'instrutor_nome' => !empty($_POST['instrutor_nome']) ? trim($_POST['instrutor_nome']) : null,
            'observacoes' => !empty($_POST['observacoes']) ? trim($_POST['observacoes']) : null,
            'status' => 'concluido', // Manter status como concluído
        ];

        // Atualizar aplicação
        $result = $applicationsRepo->update((int)$applicationId, $dadosAtualizacao);

        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar aplicação']);
            exit;
        }

        // Buscar dados atualizados para o log
        $applicationNova = $applicationsRepo->getById((int)$applicationId);

        // Registrar log de alteração
        LogAlteracaoService::registrarAlteracao(
            'adms_training_applications',
            (int)$applicationId,
            $_SESSION['user_id'],
            'update',
            $applicationAtual,
            $applicationNova
        );

        // Buscar o último log de alteração criado para vincular a justificativa
        // O LogAlteracaoService já cria o log, então precisamos buscar o último criado
        $logAlteracoesRepo = new LogAlteracoesRepository();
        $sql = 'SELECT id FROM adms_log_alteracoes 
                WHERE tabela = :tabela 
                AND objeto_id = :objeto_id 
                AND usuario_id = :usuario_id 
                ORDER BY id DESC 
                LIMIT 1';
        $conn = $logAlteracoesRepo->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':tabela', 'adms_training_applications', \PDO::PARAM_STR);
        $stmt->bindValue(':objeto_id', (int)$applicationId, \PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', $_SESSION['user_id'], \PDO::PARAM_INT);
        $stmt->execute();
        $ultimoLog = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($ultimoLog && isset($ultimoLog['id'])) {
            // Salvar justificativa
            $logJustificativasRepo = new LogJustificativasRepository();
            $logJustificativasRepo->insert([
                'log_alteracao_id' => $ultimoLog['id'],
                'justificativa' => $justificativa,
                'assinatura' => $_SESSION['user_name'] ?? 'Usuário não identificado',
                'data_justificativa' => date('Y-m-d H:i:s'),
            ]);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Treinamento atualizado com sucesso!'
        ]);
        exit;
    }
} 