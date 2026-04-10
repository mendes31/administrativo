<?php

declare(strict_types=1);

namespace App\adms\Controllers\portal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\UrlAdmHelper;
use App\adms\Models\Repository\EmployeePayrollDocumentsRepository;
use App\adms\Models\Repository\PayrollDocumentEventsRepository;
use App\adms\Models\Repository\PayrollDocumentTypesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Trilha de auditoria unificada (eventos + registos de acesso ao PDF) por lote de importação.
 */
class PayrollImportBatchAudit
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
        $docIds = array_values(array_filter(array_map(static fn (array $d): int => (int)($d['id'] ?? 0), $docs), static fn (int $i) => $i > 0));
        $docMap = [];
        foreach ($docs as $d) {
            $id = (int)($d['id'] ?? 0);
            if ($id > 0) {
                $docMap[$id] = $d;
            }
        }

        $timeline = [];
        $evRepo = new PayrollDocumentEventsRepository();
        foreach ($evRepo->listEventsForDocumentIdsDetailed($docIds) as $row) {
            $did = (int)($row['employee_payroll_document_id'] ?? 0);
            $timeline[] = [
                'sort' => (string)($row['created_at'] ?? '') . '-' . (string)($row['id'] ?? '0') . '-e',
                'source' => 'evento',
                'created_at' => (string)($row['created_at'] ?? ''),
                'document_id' => $did,
                'event_type' => (string)($row['event_type'] ?? ''),
                'user_id' => $row['user_id'] !== null ? (int)$row['user_id'] : null,
                'ip' => $row['ip'] ?? null,
                'user_agent' => $row['user_agent'] ?? null,
                'meta_json' => $row['meta_json'] ?? null,
                'delivery_mode' => null,
            ];
        }

        foreach ($repo->listAccessLogsForDocumentIds($docIds) as $row) {
            $did = (int)($row['employee_payroll_document_id'] ?? 0);
            $mode = (string)($row['delivery_mode'] ?? '');
            $timeline[] = [
                'sort' => (string)($row['created_at'] ?? '') . '-' . (string)($row['id'] ?? '0') . '-a',
                'source' => 'acesso_pdf',
                'created_at' => (string)($row['created_at'] ?? ''),
                'document_id' => $did,
                'event_type' => $mode === 'attachment' ? 'access_attachment' : 'access_inline',
                'user_id' => (int)($row['viewer_user_id'] ?? 0) ?: null,
                'ip' => $row['ip'] ?? null,
                'user_agent' => $row['user_agent'] ?? null,
                'meta_json' => null,
                'delivery_mode' => $mode,
            ];
        }

        usort($timeline, static function (array $a, array $b): int {
            return strcmp($a['sort'], $b['sort']);
        });

        $this->data['batch'] = $batch;
        $this->data['batch_id'] = $batchId;
        $this->data['timeline'] = $timeline;
        $this->data['doc_map'] = $docMap;
        $this->data['type_labels'] = self::resolveTypeLabels();
        $this->data['report_url'] = UrlAdmHelper::to('payroll-import-batch-report/' . $batchId);

        $pageElements = [
            'title_head' => 'Trilha de auditoria — lote de importação (RH)',
            'menu' => 'import-payroll-documents',
            'buttonPermission' => ['PayrollImportBatchAudit'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));

        (new LoadViewService('adms/Views/portal/payroll_import_batch_audit', $this->data))->loadView();
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
