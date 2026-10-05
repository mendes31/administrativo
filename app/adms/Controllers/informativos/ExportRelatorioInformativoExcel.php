<?php

namespace App\adms\Controllers\informativos;

use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Repository\InformativosRepository;
use App\adms\Models\Services\InformativoRelatorioService;
use App\adms\Models\Services\InformativosPermissionService;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportRelatorioInformativoExcel
{
    public function index(): void
    {
        $informativoId = (int) ($_GET['informativo_id'] ?? 0);
        if ($informativoId <= 0) {
            http_response_code(422);
            echo 'ID inválido';
            return;
        }

        $usuarioFilter = trim((string) ($_GET['usuario_filter'] ?? ''));

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

        $relatorio = (new InformativoRelatorioService())->build($informativoId, $informativo, $usuarioFilter);
        $kpis = $relatorio['kpis'];
        $requiresAck = $relatorio['requires_ack'];

        $spreadsheet = new Spreadsheet();

        $resumo = $spreadsheet->getActiveSheet();
        $resumo->setTitle('Resumo');
        $resumoRows = [
            ['Indicador', 'Valor'],
            ['Total de ativos', $kpis['total']],
            ['Visualizaram', $kpis['visualizaram']],
            ['% Visualização (ativos)', InformativoRelatorioService::formatPct($kpis['pct_visualizacao'])],
            ['Pendentes (ativos)', $kpis['pendentes']],
            ['Cientes', $requiresAck ? $kpis['cientes'] : 'N/A'],
            ['% Ciência (ativos)', $requiresAck ? InformativoRelatorioService::formatPct($kpis['pct_ciencia']) : 'N/A'],
            ['Inativos com histórico', count($relatorio['inativos_historico'])],
            ['Inativos omitidos (sem visualização/ciência)', $relatorio['excluidos_sem_historico']],
        ];
        $resumo->fromArray($resumoRows, null, 'A1');
        $resumo->getStyle('A1:B1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF2C3E50');
        $resumo->getStyle('A1:B1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        foreach (range('A', 'B') as $c) {
            $resumo->getColumnDimension($c)->setAutoSize(true);
        }

        $this->writeSheet($spreadsheet->createSheet(), 'Ativos', $relatorio['ativos']);
        $this->writeSheet($spreadsheet->createSheet(), 'Inativos histórico', $relatorio['inativos_historico']);

        $writer = new Xlsx($spreadsheet);
        $filename = 'relatorio_informativo_' . $informativoId . '_' . date('Y-m-d_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function writeSheet(Worksheet $sheet, string $title, array $rows): void
    {
        $sheet->setTitle(mb_substr($title, 0, 31));

        $headers = [
            'Usuário',
            'Visualizou',
            'Data Visualização',
            'Está Ciente?',
            'Data da Ciência',
            'Status',
        ];

        $col = 1;
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
        foreach ($rows as $dado) {
            $sheet->setCellValueExplicit(
                'A' . $row,
                (string) ($dado['usuario_nome'] ?? '') . "\n" . (string) ($dado['usuario_email'] ?? ''),
                DataType::TYPE_STRING
            );
            $sheet->setCellValue('B' . $row, (string) ($dado['visualizou'] ?? ''));
            $sheet->setCellValue('C' . $row, (string) ($dado['data_visualizacao'] ?? '-'));
            $sheet->setCellValue('D' . $row, (string) ($dado['esta_ciente'] ?? ''));
            $sheet->setCellValue('E' . $row, (string) ($dado['data_ciencia'] ?? '-'));
            $sheet->setCellValue('F' . $row, (string) ($dado['status'] ?? ''));
            $sheet->getStyle('A' . $row)->getAlignment()->setWrapText(true);
            $row++;
        }

        foreach (range('A', 'F') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }
    }
}
