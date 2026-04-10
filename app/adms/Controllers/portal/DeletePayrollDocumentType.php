<?php

declare(strict_types=1);

namespace App\adms\Controllers\portal;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\UrlAdmHelper;
use App\adms\Models\Repository\PayrollDocumentTypesRepository;

class DeletePayrollDocumentType
{
    public function index(int|string $id): void
    {
        if ((int)$id <= 0) {
            $_SESSION['error'] = 'Registo não encontrado.';
            header('Location: ' . UrlAdmHelper::to('list-payroll-document-types'));
            exit;
        }

        if (!CSRFHelper::validateCSRFToken('form_delete_payroll_document_type', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            header('Location: ' . UrlAdmHelper::to('list-payroll-document-types'));
            exit;
        }

        $repo = new PayrollDocumentTypesRepository();
        $row = $repo->findById((int)$id);
        if ($row === null) {
            $_SESSION['error'] = 'Registo não encontrado.';
            header('Location: ' . UrlAdmHelper::to('list-payroll-document-types'));
            exit;
        }

        if ($repo->countUsageByCode((string)$row['code']) > 0) {
            $_SESSION['error'] = 'Não é possível excluir: existem documentos ou importações usando este tipo. Desative-o em vez de excluir.';
            header('Location: ' . UrlAdmHelper::to('list-payroll-document-types'));
            exit;
        }

        if ($repo->delete((int)$id)) {
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Tipo de documento excluído.</div>';
            GenerateLog::generateLog('info', 'PayrollDocumentType excluído.', ['id' => (int)$id, 'code' => $row['code']]);
        } else {
            $_SESSION['error'] = 'Não foi possível excluir. Tente novamente.';
        }

        header('Location: ' . UrlAdmHelper::to('list-payroll-document-types'));
        exit;
    }
}
