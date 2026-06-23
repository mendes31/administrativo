<?php

declare(strict_types=1);

namespace App\adms\Controllers\portal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\InstitutionalSystemUserHelper;
use App\adms\Models\Repository\EmployeePayrollDocumentsRepository;
use App\adms\Models\Repository\PayrollDocumentTypesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Lista documentos de folha/recibos do colaborador com filtros (mês/ano e tipo).
 */
class MyPayrollDocuments
{
    private array $data = [];

    public function index(string|null $param = null): void
    {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }

        $filters = [];
        $allowedTypes = self::resolveAllowedPayrollTypeCodes();
        $type = preg_replace('/[^a-z_]/', '', strtolower((string)($_GET['document_type'] ?? '')));
        if ($type !== '' && in_array($type, $allowedTypes, true)) {
            $filters['document_type'] = $type;
        }

        if (!empty($_GET['year']) && is_numeric($_GET['year'])) {
            $y = (int)$_GET['year'];
            if ($y >= 2000 && $y <= 2100) {
                $filters['year'] = $y;
            }
        }

        if (isset($_GET['month']) && $_GET['month'] !== '') {
            $m = (int)$_GET['month'];
            if ($m >= 1 && $m <= 12) {
                $filters['month'] = $m;
            }
        }

        $repo = new EmployeePayrollDocumentsRepository();
        $this->data['documents'] = $repo->listForUser($userId, $filters);
        $this->data['is_institutional_user'] = InstitutionalSystemUserHelper::isExemptFromAcknowledgment($userId);
        $this->data['payroll_pending_signatures'] = $repo->countPendingSignaturesForUser($userId);
        $this->data['filters'] = [
            'document_type' => $type,
            'year' => $filters['year'] ?? '',
            'month' => array_key_exists('month', $filters) ? (string)$filters['month'] : '',
        ];
        $this->data['type_labels'] = self::resolvePayrollTypeLabels();

        $pageElements = [
            'title_head' => 'Meus documentos de folha',
            'menu' => 'my-payroll-documents',
            'buttonPermission' => ['MyPayrollDocuments'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/portal/my_payroll_documents', $this->data);
        $loadView->loadView();
    }

    /** @return list<string> */
    private static function resolveAllowedPayrollTypeCodes(): array
    {
        try {
            $repo = new PayrollDocumentTypesRepository();
            $codes = $repo->listActiveCodes();
            if ($codes !== []) {
                return $codes;
            }
        } catch (\Throwable) {
        }

        return ['payroll', 'vacation_receipt', 'ir_statement', 'time_bank', 'other'];
    }

    /**
     * @return array<string, string>
     */
    private static function resolvePayrollTypeLabels(): array
    {
        try {
            $repo = new PayrollDocumentTypesRepository();
            $map = $repo->getLabelsMapActive();
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
