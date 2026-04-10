<?php

declare(strict_types=1);

namespace App\adms\Controllers\portal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\PayrollDocumentTypesRepository;
use App\adms\Views\Services\LoadViewService;

class ListPayrollDocumentTypes
{
    private array $data = [];

    public function index(): void
    {
        $repo = new PayrollDocumentTypesRepository();
        $this->data['types'] = $repo->listAll();

        $pageElements = [
            'title_head' => 'Tipos de documento (RH)',
            'menu' => 'list-payroll-document-types',
            'buttonPermission' => [
                'CreatePayrollDocumentType',
                'UpdatePayrollDocumentType',
                'DeletePayrollDocumentType',
            ],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/portal/list_payroll_document_types', $this->data);
        $loadView->loadView();
    }
}
