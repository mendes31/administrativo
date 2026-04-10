<?php

declare(strict_types=1);

namespace App\adms\Controllers\portal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\EmployeePayrollDocumentsRepository;
use App\adms\Models\Repository\PayrollDocumentTypesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Painel RH: documentos ativos com ciência/assinatura pendente.
 */
class ListPayrollSigningPendencies
{
    private array $data = [];

    public function index(string|null $param = null): void
    {
        $repo = new EmployeePayrollDocumentsRepository();
        $this->data['rows'] = $repo->listPendingSignaturesForRh();
        $this->data['type_labels'] = self::resolvePayrollTypeLabels();

        $pageElements = [
            'title_head' => 'Pendências de ciência (folha RH)',
            'menu' => 'list-payroll-signing-pendencies',
            'buttonPermission' => ['ListPayrollSigningPendencies'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));

        (new LoadViewService('adms/Views/portal/list_payroll_signing_pendencies', $this->data))->loadView();
    }

    /**
     * @return array<string, string>
     */
    private static function resolvePayrollTypeLabels(): array
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
