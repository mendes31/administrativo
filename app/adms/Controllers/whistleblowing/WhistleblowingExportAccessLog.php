<?php

declare(strict_types=1);

namespace App\adms\Controllers\whistleblowing;

use App\adms\Models\Repository\WhistleblowingAccessLogRepository;
use App\adms\Models\Repository\WhistleblowingReportsRepository;
use App\adms\Models\Services\WhistleblowingPermissionService;
use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Exporta auditoria de acesso e linha do tempo de status de uma denúncia.
 */
final class WhistleblowingExportAccessLog
{
    public function index(string|int|null $reportId = null): void
    {
        $id = (int) ($reportId ?? $_GET['report_id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            exit;
        }

        $repo = new WhistleblowingReportsRepository();
        $report = $repo->getReportById($id);
        if (!$report || !WhistleblowingPermissionService::canAccessReport($report)) {
            http_response_code(403);
            exit;
        }

        $format = strtolower((string) ($_GET['format'] ?? 'excel'));

        $accessLogRepo = new WhistleblowingAccessLogRepository();
        $userId = WhistleblowingPermissionService::sessionUserId();
        if ($userId > 0) {
            $accessLogRepo->log($id, $userId, $format === 'pdf' ? 'export_audit_pdf' : 'export_audit_excel');
        }

        $accessLog = $accessLogRepo->getByReportId($id);
        $statusLog = $repo->getStatusLog($id);
        $protocol = (string) ($report['protocol'] ?? 'denuncia');

        if ($format === 'pdf') {
            $this->exportPdf($protocol, $accessLog, $statusLog);
            return;
        }

        $this->exportExcel($protocol, $accessLog, $statusLog);
    }

    /**
     * @param list<array<string, mixed>> $accessLog
     * @param list<array<string, mixed>> $statusLog
     */
    private function exportExcel(string $protocol, array $accessLog, array $statusLog): void
    {
        $spreadsheet = new Spreadsheet();

        $sheetAccess = $spreadsheet->getActiveSheet();
        $sheetAccess->setTitle('Acesso');
        $sheetAccess->fromArray(['Data/Hora', 'Usuário', 'Ação', 'IP', 'User-Agent'], null, 'A1');
        $row = 2;
        foreach ($accessLog as $entry) {
            $sheetAccess->setCellValue('A' . $row, date('d/m/Y H:i:s', strtotime((string) ($entry['created_at'] ?? 'now'))));
            $sheetAccess->setCellValue('B' . $row, (string) ($entry['user_name'] ?? ''));
            $sheetAccess->setCellValue('C' . $row, (string) ($entry['action'] ?? ''));
            $sheetAccess->setCellValue('D' . $row, (string) ($entry['ip_address'] ?? ''));
            $sheetAccess->setCellValue('E' . $row, (string) ($entry['user_agent'] ?? ''));
            $row++;
        }

        $sheetStatus = $spreadsheet->createSheet();
        $sheetStatus->setTitle('Status');
        $sheetStatus->fromArray(['Data/Hora', 'De', 'Para', 'Usuário', 'Observação'], null, 'A1');
        $row = 2;
        foreach ($statusLog as $entry) {
            $sheetStatus->setCellValue('A' . $row, date('d/m/Y H:i:s', strtotime((string) ($entry['created_at'] ?? 'now'))));
            $sheetStatus->setCellValue('B' . $row, (string) ($entry['from_status'] ?? ''));
            $sheetStatus->setCellValue('C' . $row, (string) ($entry['to_status'] ?? ''));
            $sheetStatus->setCellValue('D' . $row, (string) ($entry['user_name'] ?? ''));
            $sheetStatus->setCellValue('E' . $row, (string) ($entry['notes'] ?? ''));
            $row++;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="auditoria_' . $protocol . '.xlsx"');
        (new Xlsx($spreadsheet))->save('php://output');
        exit;
    }

    /**
     * @param list<array<string, mixed>> $accessLog
     * @param list<array<string, mixed>> $statusLog
     */
    private function exportPdf(string $protocol, array $accessLog, array $statusLog): void
    {
        $html = '<h2>Auditoria — ' . htmlspecialchars($protocol) . '</h2>';
        $html .= '<h3>Acesso interno</h3><table border="1" cellpadding="4" cellspacing="0" width="100%"><tr><th>Data</th><th>Usuário</th><th>Ação</th><th>IP</th><th>User-Agent</th></tr>';
        foreach ($accessLog as $entry) {
            $html .= '<tr><td>' . htmlspecialchars(date('d/m/Y H:i', strtotime((string) ($entry['created_at'] ?? 'now')))) . '</td>'
                . '<td>' . htmlspecialchars((string) ($entry['user_name'] ?? '')) . '</td>'
                . '<td>' . htmlspecialchars((string) ($entry['action'] ?? '')) . '</td>'
                . '<td>' . htmlspecialchars((string) ($entry['ip_address'] ?? '')) . '</td>'
                . '<td>' . htmlspecialchars(mb_substr((string) ($entry['user_agent'] ?? ''), 0, 80)) . '</td></tr>';
        }
        $html .= '</table>';

        $html .= '<h3>Linha do tempo de status</h3><table border="1" cellpadding="4" cellspacing="0" width="100%"><tr><th>Data</th><th>Status</th><th>Usuário</th><th>Obs.</th></tr>';
        foreach ($statusLog as $entry) {
            $html .= '<tr><td>' . htmlspecialchars(date('d/m/Y H:i', strtotime((string) ($entry['created_at'] ?? 'now')))) . '</td>'
                . '<td>' . htmlspecialchars((string) ($entry['to_status'] ?? '')) . '</td>'
                . '<td>' . htmlspecialchars((string) ($entry['user_name'] ?? '')) . '</td>'
                . '<td>' . htmlspecialchars((string) ($entry['notes'] ?? '')) . '</td></tr>';
        }
        $html .= '</table>';

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('auditoria_' . $protocol . '.pdf', ['Attachment' => true]);
        exit;
    }
}
