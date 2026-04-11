<?php

declare(strict_types=1);

namespace App\adms\Controllers\portal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\UrlAdmHelper;
use App\adms\Models\Repository\EmployeePayrollDocumentsRepository;
use App\adms\Models\Repository\NotificationsRepository;
use App\adms\Models\Repository\PayrollDocumentEventsRepository;
use App\adms\Models\Repository\PayrollDocumentTypesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Relatório por lote de importação: disponibilização, notificação interna, visualização, download, ciência.
 */
class PayrollImportBatchReport
{
    private array $data = [];

    public function index(string|null $param = null): void
    {
        $batchId = (int)($param ?? '');
        if ($batchId <= 0) {
            header('Location: ' . UrlAdmHelper::to('import-payroll-documents'));
            exit;
        }

        $repo = new EmployeePayrollDocumentsRepository();
        $batch = $repo->getImportBatchById($batchId);
        if ($batch === null) {
            $_SESSION['msg'] = '<div class="alert alert-warning">Lote de importação não encontrado.</div>';
            header('Location: ' . UrlAdmHelper::to('import-payroll-documents'));
            exit;
        }

        $docs = $repo->listDocumentsWithOwnerForBatch($batchId);
        $docIds = array_map(static fn (array $d): int => (int)($d['id'] ?? 0), $docs);
        $docIds = array_values(array_filter($docIds, static fn (int $i) => $i > 0));

        $eventAgg = (new PayrollDocumentEventsRepository())->aggregateFirstEventTimesByDocument($docIds);
        $notifFirst = (new NotificationsRepository())->findEarliestPayrollPublicationNotificationByDocumentIds($docIds);

        $reportRows = [];
        foreach ($docs as $d) {
            $id = (int)($d['id'] ?? 0);
            $ev = $eventAgg[$id] ?? [];
            $reqSig = (int)($d['requires_signature_snapshot'] ?? 0) === 1;
            $sigSt = (string)($d['signature_status'] ?? 'not_required');
            $publishedAt = (string)($d['published_at'] ?? '');
            if ($publishedAt === '' && isset($ev['document_published'])) {
                $publishedAt = $ev['document_published'];
            }
            $reportRows[] = [
                'document_id' => $id,
                'owner_name' => (string)($d['owner_name'] ?? ''),
                'owner_email' => (string)($d['owner_email'] ?? ''),
                'title' => (string)($d['title'] ?? ''),
                'status_version' => (string)($d['status_version'] ?? ''),
                'published_at' => $publishedAt,
                'notified' => isset($notifFirst[$id]),
                'notified_at' => $notifFirst[$id] ?? null,
                'viewed_at' => $ev['document_viewed'] ?? null,
                'downloaded_at' => $ev['document_downloaded'] ?? null,
                'requires_signature' => $reqSig,
                'signature_status' => $sigSt,
                'signed_at' => isset($d['signed_at']) && $d['signed_at'] !== null && (string)$d['signed_at'] !== '' ? (string)$d['signed_at'] : null,
            ];
        }

        if (($_GET['export'] ?? '') === 'csv') {
            $this->sendCsv($batchId, $reportRows);
            exit;
        }

        $kpi = $this->computeKpi($reportRows);

        $this->data['batch'] = $batch;
        $this->data['batch_id'] = $batchId;
        $this->data['report_rows'] = $reportRows;
        $this->data['kpi'] = $kpi;
        $this->data['type_labels'] = self::resolveTypeLabels();
        $this->data['audit_url'] = UrlAdmHelper::to('payroll-import-batch-audit/' . $batchId);

        $pageElements = [
            'title_head' => 'Relatório do lote de importação (RH)',
            'menu' => 'import-payroll-documents',
            'buttonPermission' => [
                'PayrollImportBatchReport',
                'PayrollImportBatchAudit',
                'ViewPayrollSignedBundle',
            ],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));

        (new LoadViewService('adms/Views/portal/payroll_import_batch_report', $this->data))->loadView();
    }

    /**
     * @param list<array<string, mixed>> $reportRows
     * @return array{total: int, notified: int, viewed: int, downloaded: int, signed: int, pending: int, na_ciencia: int}
     */
    private function computeKpi(array $reportRows): array
    {
        $k = [
            'total' => count($reportRows),
            'notified' => 0,
            'viewed' => 0,
            'downloaded' => 0,
            'signed' => 0,
            'pending' => 0,
            'na_ciencia' => 0,
        ];
        foreach ($reportRows as $r) {
            if (!empty($r['notified'])) {
                $k['notified']++;
            }
            if (!empty($r['viewed_at'])) {
                $k['viewed']++;
            }
            if (!empty($r['downloaded_at'])) {
                $k['downloaded']++;
            }
            if (!empty($r['requires_signature'])) {
                if (($r['signature_status'] ?? '') === 'signed') {
                    $k['signed']++;
                } elseif (($r['signature_status'] ?? '') === 'pending') {
                    $k['pending']++;
                }
            } else {
                $k['na_ciencia']++;
            }
        }

        return $k;
    }

    /**
     * @param list<array<string, mixed>> $reportRows
     */
    private function sendCsv(int $batchId, array $reportRows): void
    {
        $filename = 'payroll-lote-' . $batchId . '-relatorio.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        if ($out === false) {
            return;
        }
        fputcsv($out, [
            'documento_id',
            'nome',
            'email',
            'titulo',
            'versao_status',
            'disponibilizado_em',
            'notificado_interno',
            'notificado_em',
            'visualizado_em',
            'baixado_em',
            'exige_ciencia',
            'estado_ciencia',
            'ciencia_em',
        ], ';');
        foreach ($reportRows as $r) {
            fputcsv($out, [
                $r['document_id'],
                $r['owner_name'],
                $r['owner_email'],
                $r['title'],
                $r['status_version'],
                $r['published_at'],
                !empty($r['notified']) ? 'SIM' : 'NÃO',
                $r['notified_at'] ?? '',
                $r['viewed_at'] ?? '',
                $r['downloaded_at'] ?? '',
                !empty($r['requires_signature']) ? 'SIM' : 'NÃO',
                $r['signature_status'],
                $r['signed_at'] ?? '',
            ], ';');
        }
        fclose($out);
    }

    /** @return array<string, string> */
    private static function resolveTypeLabels(): array
    {
        try {
            $map = (new PayrollDocumentTypesRepository())->getLabelsMapActive();
            if ($map !== []) {
                return $map;
            }
        } catch (\Throwable) {
        }

        return [
            'payroll' => 'Folha de pagamento',
            'vacation_receipt' => 'Recibo de férias',
            'ir_statement' => 'Informe de IR',
            'time_bank' => 'Banco de horas',
            'other' => 'Outros',
        ];
    }
}
