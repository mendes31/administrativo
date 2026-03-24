<?php

namespace App\adms\Controllers\trainings;

use App\adms\Models\Repository\TrainingUsersRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Repository\TrainingsRepository;
use App\adms\Helpers\ScreenResolutionHelper;
use Mpdf\Mpdf;

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

        if (isset($_GET['export']) && $_GET['export'] === 'lnt') {
            if (empty($filters['colaborador'])) {
                $_SESSION['msg'] = '<div class="alert alert-warning">Selecione um colaborador nos filtros e clique em Filtrar para gerar o LNT.</div>';
                header('Location: ' . $_ENV['URL_ADM'] . 'matrix-by-user');
                exit;
            }
            $this->exportLntPdf((int)$filters['colaborador'], $filters);
            return;
        }

        // Exportação - Buscar todos os dados sem paginação
        if (isset($_GET['export']) && in_array($_GET['export'], ['excel', 'pdf'])) {
            $exportData = $this->collectExportMatrixData($filters);
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

    /**
     * Mesma lógica de exportação Excel/PDF: todos os registros conforme filtros atuais.
     *
     * @return array<int, array<string, mixed>>
     */
    private function collectExportMatrixData(array $filters): array
    {
        if (!empty($filters['treinamento'])) {
            return $this->trainingUsersRepo->getAllVinculadosPorTreinamento($filters['treinamento']);
        }
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

        return $exportData;
    }

    /**
     * Dados da matriz para o PDF LNT (última realização concluída + colunas extras do colaborador).
     *
     * @return array<int, array<string, mixed>>
     */
    private function collectLntMatrixData(array $filtersLnt, int $userId): array
    {
        if (!empty($filtersLnt['treinamento'])) {
            $matrix = $this->trainingUsersRepo->getAllVinculadosPorTreinamento($filtersLnt['treinamento']);
            $matrix = array_values(array_filter(
                $matrix,
                static fn(array $r): bool => (int)($r['user_id'] ?? $r['id'] ?? 0) === $userId
            ));

            return $this->trainingUsersRepo->mergeUltimaRealizacaoConcluidaForUser($matrix, $userId);
        }

        $exportData = [];
        $batchSize = 10000;
        $batchOffset = 0;
        do {
            $batch = $this->trainingUsersRepo->getMandatoryMatrixByUser($filtersLnt, $batchSize, $batchOffset, false, true);
            if (!empty($batch)) {
                $exportData = array_merge($exportData, $batch);
                $batchOffset += $batchSize;
            } else {
                break;
            }
        } while (count($batch) === $batchSize);

        return $exportData;
    }

    private function formatCpfBr(?string $cpf): string
    {
        if ($cpf === null || $cpf === '') {
            return '-';
        }
        $d = preg_replace('/\D/', '', $cpf);
        if (strlen($d) !== 11) {
            return $cpf;
        }

        return substr($d, 0, 3) . '.' . substr($d, 3, 3) . '.' . substr($d, 6, 3) . '-' . substr($d, 9, 2);
    }

    private function formatDateBrFromDb(?string $ymd): string
    {
        if ($ymd === null || $ymd === '' || strpos($ymd, '0000-') === 0) {
            return '-';
        }
        $ts = strtotime($ymd);

        return $ts ? date('d/m/Y', $ts) : '-';
    }

    /**
     * Apto: realização registrada e nota ausente ou >= 7 (alinhado às regras de aprovação do módulo).
     */
    private function lntAptoLabel(?string $dataRealizacao, mixed $nota): string
    {
        if ($dataRealizacao === null || $dataRealizacao === '' || strpos((string)$dataRealizacao, '0000-') === 0) {
            return 'NÃO';
        }
        if ($nota !== null && $nota !== '' && is_numeric($nota) && (float)$nota < 7) {
            return 'NÃO';
        }

        return 'SIM';
    }

    private function resolveLogoAbsolutePath(): ?string
    {
        $root = dirname(__DIR__, 4);
        $candidates = [
            $root . '/public/adms/image/logo/Logo-Tiaraju.png',
            $root . '/public/adms/image/logo/logo.png',
        ];
        foreach ($candidates as $p) {
            if (is_readable($p)) {
                return $p;
            }
        }
        return null;
    }

    private function logoImgTagForMpdf(?string $path, int $maxW = 72): string
    {
        if ($path === null) {
            return '<span style="font-size:8pt;color:#2d5f2e;">TIARAJU</span>';
        }
        $data = @file_get_contents($path);
        if ($data === false) {
            return '<span style="font-size:8pt;color:#2d5f2e;">TIARAJU</span>';
        }
        $mime = 'image/png';
        if (stripos($path, '.jpg') !== false || stripos($path, '.jpeg') !== false) {
            $mime = 'image/jpeg';
        }
        $src = 'data:' . $mime . ';base64,' . base64_encode($data);
        return '<img src="' . $src . '" style="max-width:' . $maxW . 'px;max-height:42px;" alt="Logo" />';
    }

    /**
     * LNT — Levantamento de Necessidades de Treinamento (layout institucional, uma colaborador por documento).
     */
    private function exportLntPdf(int $userId, array $filters): void
    {
        if (ob_get_length()) {
            ob_end_clean();
        }
        $filtersLnt = $filters;
        $filtersLnt['colaborador'] = (string)$userId;

        $matrix = $this->collectLntMatrixData($filtersLnt, $userId);

        $userRow = $this->usersRepo->getUser($userId);
        $userName = is_array($userRow) ? (string)($userRow['name'] ?? '') : '';
        $dept = '';
        $cargo = '';
        $gestorNome = '';
        if (!empty($matrix[0])) {
            $dept = (string)($matrix[0]['department'] ?? $matrix[0]['department_nome'] ?? '');
            $cargo = (string)($matrix[0]['position'] ?? $matrix[0]['cargo_nome'] ?? '');
            $gestorNome = (string)($matrix[0]['gestor_nome'] ?? '');
        }
        if ($userName === '' && is_array($userRow)) {
            $userName = (string)($userRow['name'] ?? '');
        }
        if ($dept === '' && is_array($userRow)) {
            $dept = (string)($userRow['dep_name'] ?? '');
        }
        if ($cargo === '' && is_array($userRow)) {
            $cargo = (string)($userRow['pos_name'] ?? '');
        }
        if ($gestorNome === '' && is_array($userRow)) {
            $supId = (int)($userRow['immediate_supervisor_id'] ?? 0);
            if ($supId > 0) {
                $sup = $this->usersRepo->getUser($supId);
                if (is_array($sup)) {
                    $gestorNome = (string)($sup['name'] ?? '');
                }
            }
        }

        $cpfRaw = is_array($userRow) ? ($userRow['cpf'] ?? null) : null;
        if ($cpfRaw === null || $cpfRaw === '') {
            $cpfRaw = !empty($matrix[0]['cpf']) ? (string)$matrix[0]['cpf'] : null;
        }
        $emailHdr = is_array($userRow) ? (string)($userRow['email'] ?? '') : '';
        if ($emailHdr === '' && !empty($matrix[0]['user_email_lnt'])) {
            $emailHdr = (string)$matrix[0]['user_email_lnt'];
        }
        $dataAdmissaoRaw = is_array($userRow) ? ($userRow['data_admissao'] ?? null) : null;
        if (($dataAdmissaoRaw === null || $dataAdmissaoRaw === '') && !empty($matrix[0]['data_admissao'])) {
            $dataAdmissaoRaw = (string)$matrix[0]['data_admissao'];
        }

        $logoPath = $this->resolveLogoAbsolutePath();
        $logoHtml = $this->logoImgTagForMpdf($logoPath);
        $dataEmissao = date('d/m/Y');
        $safeFile = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $userName ?: 'colaborador');
        $filename = 'LNT_' . $safeFile . '_' . date('Y-m-d') . '.pdf';

        $rowsHtml = '';
        $n = 0;
        foreach ($matrix as $item) {
            $n++;
            $tipoTr = (string)($item['tipo_treinamento'] ?? '');
            if ($tipoTr === '') {
                $tipoTr = '-';
            }
            $drRaw = isset($item['data_realizacao']) && (string)$item['data_realizacao'] !== ''
                ? (string)$item['data_realizacao']
                : null;
            $dataReal = $this->formatDateBrFromDb($drRaw);
            $apto = $this->lntAptoLabel($drRaw, $item['nota'] ?? null);
            $rowsHtml .= '<tr>'
                . '<td style="text-align:center;">' . $n . '</td>'
                . '<td>' . htmlspecialchars((string)($item['codigo'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>' . htmlspecialchars((string)($item['training_name'] ?? $item['treinamento_nome'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td style="text-align:center;">' . htmlspecialchars((string)($item['training_version'] ?? '-'), ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td style="text-align:center;">' . htmlspecialchars($tipoTr, ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td style="text-align:center;">' . htmlspecialchars($dataReal, ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td style="text-align:center;">' . htmlspecialchars($apto, ENT_QUOTES, 'UTF-8') . '</td>'
                . '</tr>';
        }
        if ($rowsHtml === '') {
            $rowsHtml = '<tr><td colspan="7" style="text-align:center;color:#666;">Nenhum treinamento listado para os filtros atuais.</td></tr>';
        }

        $hName = $this->h($userName);
        $hDept = $this->h($dept);
        $hCargo = $this->h($cargo);
        $hData = $this->h($dataEmissao);
        $hCpf = $this->h($this->formatCpfBr($cpfRaw !== null ? (string)$cpfRaw : null));
        $hEmail = $this->h($emailHdr !== '' ? $emailHdr : '-');
        $hAdmissao = $this->h($this->formatDateBrFromDb($dataAdmissaoRaw !== null ? (string)$dataAdmissaoRaw : null));
        $hGestor = $this->h($gestorNome !== '' ? $gestorNome : '-');

        $html = <<<HTML
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><style>thead { display: table-header-group; }</style></head><body style="font-family: DejaVu Sans, Arial, sans-serif; font-size: 9pt; color: #000;">

<table width="100%" style="border-collapse:collapse;margin-bottom:8px;">
<tr>
<td style="width:22%;vertical-align:middle;text-align:center;border:1px solid #000;padding:6px;">{$logoHtml}</td>
<td style="width:56%;vertical-align:middle;text-align:center;border:1px solid #000;padding:8px;">
<strong style="font-size:10pt;">LEVANTAMENTO DE NECESSIDADES DE TREINAMENTO (LNT)</strong><br/>
<span style="font-size:8pt;">Matriz de treinamentos por colaborador</span>
</td>
<td style="width:22%;vertical-align:middle;text-align:center;border:1px solid #000;padding:6px;">{$logoHtml}</td>
</tr>
</table>

<table width="100%" style="border-collapse:collapse;margin-bottom:10px;font-size:9pt;">
<tr><td style="border:1px solid #000;padding:5px;width:22%;"><strong>Colaborador</strong></td><td style="border:1px solid #000;padding:5px;">{$hName}</td></tr>
<tr><td style="border:1px solid #000;padding:5px;"><strong>CPF</strong></td><td style="border:1px solid #000;padding:5px;">{$hCpf}</td></tr>
<tr><td style="border:1px solid #000;padding:5px;"><strong>E-mail</strong></td><td style="border:1px solid #000;padding:5px;">{$hEmail}</td></tr>
<tr><td style="border:1px solid #000;padding:5px;"><strong>Setor</strong></td><td style="border:1px solid #000;padding:5px;">{$hDept}</td></tr>
<tr><td style="border:1px solid #000;padding:5px;"><strong>Cargo</strong></td><td style="border:1px solid #000;padding:5px;">{$hCargo}</td></tr>
<tr><td style="border:1px solid #000;padding:5px;"><strong>Data de admissão</strong></td><td style="border:1px solid #000;padding:5px;">{$hAdmissao}</td></tr>
<tr><td style="border:1px solid #000;padding:5px;"><strong>Gestor</strong></td><td style="border:1px solid #000;padding:5px;">{$hGestor}</td></tr>
<tr><td style="border:1px solid #000;padding:5px;"><strong>Data do documento</strong></td><td style="border:1px solid #000;padding:5px;">{$hData}</td></tr>
</table>

<table width="100%" style="border-collapse:collapse;font-size:8pt;" class="lnt-grid">
<thead>
<tr style="background:#e8e8e8;">
<th style="border:1px solid #000;padding:4px;width:5%;">Nº</th>
<th style="border:1px solid #000;padding:4px;width:12%;">Código</th>
<th style="border:1px solid #000;padding:4px;width:28%;">Treinamento</th>
<th style="border:1px solid #000;padding:4px;width:8%;">Versão</th>
<th style="border:1px solid #000;padding:4px;width:14%;">Tipo do treinamento</th>
<th style="border:1px solid #000;padding:4px;width:12%;">Data da realização</th>
<th style="border:1px solid #000;padding:4px;width:8%;">Apto</th>
</tr>
</thead>
<tbody>
{$rowsHtml}
</tbody>
</table>

<div style="margin-top:14px;">
<strong>Observações</strong>
<div style="border:1px solid #000;min-height:56px;margin-top:4px;"></div>
<div style="border:1px solid #000;min-height:56px;margin-top:6px;"></div>
</div>

<table width="100%" style="margin-top:28px;border-collapse:collapse;font-size:9pt;">
<tr>
<td style="width:48%;border-top:1px solid #000;padding-top:6px;text-align:center;">Colaborador</td>
<td style="width:4%"></td>
<td style="width:48%;border-top:1px solid #000;padding-top:6px;text-align:center;">Gestor imediato / RH</td>
</tr>
</table>

</body></html>
HTML;

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 14,
            'margin_bottom' => 18,
        ]);
        $mpdf->SetTitle('LNT — ' . $userName);
        $mpdf->SetAuthor('Tiaraju — Gestão de Treinamentos');
        $mpdf->SetFooter('{PAGENO}/{nbpg}');
        $mpdf->WriteHTML($html);
        $mpdf->Output($filename, 'D');
        exit;
    }

    private function h(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }

    private function exportPdf(array $matrix): void
    {
        if (ob_get_length()) {
            ob_end_clean();
        }
        $gerado = date('d/m/Y H:i');
        $logoPath = $this->resolveLogoAbsolutePath();
        $logoHtml = $this->logoImgTagForMpdf($logoPath, 64);

        $headerBlock = '<table width="100%" style="border-collapse:collapse;margin-bottom:10px;"><tr>'
            . '<td style="width:20%;">' . $logoHtml . '</td>'
            . '<td style="width:60%;text-align:center;vertical-align:middle;">'
            . '<div style="font-size:12pt;font-weight:bold;">Matriz de Treinamentos por Colaborador</div>'
            . '<div style="font-size:8pt;color:#444;margin-top:4px;">Emitido em ' . htmlspecialchars($gerado, ENT_QUOTES, 'UTF-8') . '</div>'
            . '</td>'
            . '<td style="width:20%;text-align:right;">' . $logoHtml . '</td>'
            . '</tr></table>';

        $body = '<table width="100%" style="border-collapse:collapse;font-size:7.5pt;">'
            . '<thead><tr style="background:#2E9263;color:#fff;">'
            . '<th style="border:1px solid #1a5c3f;padding:5px;">Nome</th>'
            . '<th style="border:1px solid #1a5c3f;padding:5px;">Departamento</th>'
            . '<th style="border:1px solid #1a5c3f;padding:5px;">Cargo</th>'
            . '<th style="border:1px solid #1a5c3f;padding:5px;">Treinamento</th>'
            . '<th style="border:1px solid #1a5c3f;padding:5px;">Código</th>'
            . '<th style="border:1px solid #1a5c3f;padding:5px;">Versão</th>'
            . '<th style="border:1px solid #1a5c3f;padding:5px;">Vínculo</th>'
            . '<th style="border:1px solid #1a5c3f;padding:5px;">Tipo</th>'
            . '</tr></thead><tbody>';

        foreach ($matrix as $item) {
            $tv = (($item['tipo_vinculo'] ?? '') === 'individual') ? 'Individual' : 'Obrigatório por Cargo';
            $tipoTr = (string)($item['tipo_treinamento'] ?? '-');
            if ($tipoTr === '') {
                $tipoTr = '-';
            }
            $body .= '<tr>'
                . '<td style="border:1px solid #ccc;padding:4px;">' . $this->h((string)($item['user_name'] ?? $item['name'] ?? '')) . '</td>'
                . '<td style="border:1px solid #ccc;padding:4px;">' . $this->h((string)($item['department'] ?? $item['department_nome'] ?? '')) . '</td>'
                . '<td style="border:1px solid #ccc;padding:4px;">' . $this->h((string)($item['position'] ?? $item['cargo_nome'] ?? '')) . '</td>'
                . '<td style="border:1px solid #ccc;padding:4px;">' . $this->h((string)($item['training_name'] ?? $item['treinamento_nome'] ?? '')) . '</td>'
                . '<td style="border:1px solid #ccc;padding:4px;">' . $this->h((string)($item['codigo'] ?? '')) . '</td>'
                . '<td style="border:1px solid #ccc;padding:4px;">' . $this->h((string)($item['training_version'] ?? '-')) . '</td>'
                . '<td style="border:1px solid #ccc;padding:4px;">' . $this->h($tv) . '</td>'
                . '<td style="border:1px solid #ccc;padding:4px;">' . $this->h($tipoTr) . '</td>'
                . '</tr>';
        }
        if (empty($matrix)) {
            $body .= '<tr><td colspan="8" style="border:1px solid #ccc;padding:10px;text-align:center;color:#666;">Nenhum vínculo encontrado.</td></tr>';
        }
        $body .= '</tbody></table>';

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family: DejaVu Sans, Arial, sans-serif;">'
            . $headerBlock . $body . '</body></html>';

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 12,
            'margin_bottom' => 16,
        ]);
        $mpdf->SetTitle('Matriz de treinamentos por colaborador');
        $mpdf->SetFooter('{PAGENO}/{nbpg}');
        $mpdf->WriteHTML($html);
        $mpdf->Output('matriz_treinamentos_por_colaborador.pdf', 'D');
        exit;
    }
} 