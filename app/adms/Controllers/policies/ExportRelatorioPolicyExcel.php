<?php

namespace App\adms\Controllers\policies;

use App\adms\Helpers\InstitutionalSystemUserHelper;
use App\adms\Models\Repository\PoliciesRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class ExportRelatorioPolicyExcel
{
    public function index(): void
    {
        $policyId = (int)($_GET['policy_id'] ?? 0);
        if ($policyId <= 0) {
            http_response_code(422);
            echo 'ID inválido';
            return;
        }

        $usuarioFilter = trim((string)($_GET['usuario_filter'] ?? ''));
        $usuarioFilterLower = mb_strtolower($usuarioFilter);

        $repo = new PoliciesRepository();
        $policy = $repo->getPolicyById($policyId);
        if (!$policy) {
            http_response_code(404);
            echo 'Política não encontrada';
            return;
        }

        // Interpretar requires_ack
        $requiresAck = false;
        $val = $policy['requires_ack'] ?? null;
        if ($val === 1 || $val === '1' || $val === true || $val === 'true' || $val === 'Sim' || $val === 'sim') {
            $requiresAck = true;
        }

        // Mesma base de usuários usada no RelatorioPolicy.
        $usuarios = InstitutionalSystemUserHelper::filterReportUsers($repo->getUsersForPolicyReport($policyId));

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
            $cell = Coordinate::stringFromColumnIndex($col) . '1';
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

            $read = $repo->getReadByUser($policyId, $userId);

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

            $sheet->setCellValueExplicit(
                'A' . $row,
                (string)($usuario['name'] ?? '') . "\n" . (string)($usuario['email'] ?? ''),
                DataType::TYPE_STRING
            );
            $sheet->setCellValue('B' . $row, $visualizou);
            $sheet->setCellValue('C' . $row, $dataVisualizacao);
            $sheet->setCellValue('D' . $row, $estaCiente);
            $sheet->setCellValue('E' . $row, $dataCiencia);
            $sheet->setCellValue('F' . $row, $status);

            $sheet->getStyle('A' . $row)->getAlignment()->setWrapText(true);

            $row++;
        }

        foreach (range('A', 'F') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'relatorio_politica_' . $policyId . '_' . date('Y-m-d_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}

