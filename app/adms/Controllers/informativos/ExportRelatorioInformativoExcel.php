<?php

namespace App\adms\Controllers\informativos;

use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Helpers\InstitutionalSystemUserHelper;
use App\adms\Models\Repository\InformativosRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\InformativosPermissionService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ExportRelatorioInformativoExcel
{
    public function index(): void
    {
        $informativoId = (int)($_GET['informativo_id'] ?? 0);
        if ($informativoId <= 0) {
            http_response_code(422);
            echo 'ID inválido';
            return;
        }

        $usuarioFilter = trim((string)($_GET['usuario_filter'] ?? ''));
        $usuarioFilterLower = mb_strtolower($usuarioFilter);

        $repo = new InformativosRepository();
        $informativo = $repo->getInformativoById($informativoId);
        if (!$informativo) {
            http_response_code(404);
            echo 'Informativo não encontrado';
            return;
        }

        $perm = new ButtonPermissionUserRepository();
        $relBtn = $perm->buttonPermission(['RelatorioInformativo']);
        if (!is_array($relBtn) || count($relBtn) === 0) {
            http_response_code(403);
            echo 'Sem permissão';
            return;
        }
        $userId = InformativosPermissionService::sessionUserId();
        $userDept = InformativosPermissionService::sessionUserDepartmentId();
        if (!InformativosPermissionService::canManageRecord($informativo, $userId, $userDept)) {
            http_response_code(403);
            echo 'Sem permissão';
            return;
        }

        // Interpretar requires_ack
        $requiresAck = false;
        $val = $informativo['requires_ack'] ?? null;
        if ($val === 1 || $val === '1' || $val === true || $val === 'true' || $val === 'Sim' || $val === 'sim') {
            $requiresAck = true;
        }

        // Mesma base de usuários usada no RelatorioInformativo.
        $usersRepo = new UsersRepository();
        $usuarios = InstitutionalSystemUserHelper::filterReportUsers($usersRepo->getAllUsers(1, 1000, []));

        // Filtrar por usuário (opcional) usando a mesma lógica do filtro da tela.
        if ($usuarioFilterLower !== '') {
            $usuarios = array_values(array_filter($usuarios, function (array $u) use ($usuarioFilterLower) {
                $haystack = mb_strtolower((string)($u['name'] ?? '') . ' ' . (string)($u['email'] ?? ''));
                return mb_strpos($haystack, $usuarioFilterLower) !== false;
            }));
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Relatório');

        $headers = [
            'Usuário',
            'Visualizou',
            'Data Visualização',
            'Está Ciente?',
            'Data da Ciência',
            'Status',
        ];

        $col = 1; // A=1
        foreach ($headers as $header) {
            $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '1';
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF2C3E50');
            $sheet->getStyle($cell)->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $col++;
        }

        $row = 2;
        foreach ($usuarios as $usuario) {
            $userId = (int)($usuario['id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }

            $read = $repo->getReadByUser($informativoId, $userId);

            $visualizou = $read ? 'SIM' : 'NÃO';
            $dataVisualizacao = $read && !empty($read['read_at'])
                ? date('d/m/Y H:i:s', strtotime((string)$read['read_at']))
                : '-';

            $estaCiente = $requiresAck
                ? (($read && !empty($read['acknowledged'])) ? 'SIM' : 'NÃO')
                : 'N/A';

            $dataCiencia = $requiresAck && $read && !empty($read['ack_at'])
                ? date('d/m/Y H:i:s', strtotime((string)$read['ack_at']))
                : '-';

            $status = !$read
                ? 'PENDENTE'
                : ($requiresAck
                    ? (!empty($read['acknowledged']) ? 'CIENTE' : 'VISUALIZOU MAS NÃO CIENTE')
                    : 'VISUALIZOU');

            // Coluna A: Nome + Email (quebra de linha)
            $sheet->setCellValueExplicit('A' . $row, (string)($usuario['name'] ?? '') . "\n" . (string)($usuario['email'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('B' . $row, $visualizou);
            $sheet->setCellValue('C' . $row, $dataVisualizacao);
            $sheet->setCellValue('D' . $row, $estaCiente);
            $sheet->setCellValue('E' . $row, $dataCiencia);
            $sheet->setCellValue('F' . $row, $status);

            $sheet->getStyle('A' . $row)->getAlignment()->setWrapText(true);

            $row++;
        }

        // Auto-ajustar largura (A-F)
        foreach (range('A', 'F') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'relatorio_informativo_' . $informativoId . '_' . date('Y-m-d_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}

