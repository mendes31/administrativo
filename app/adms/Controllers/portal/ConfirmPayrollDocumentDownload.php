<?php

declare(strict_types=1);

namespace App\adms\Controllers\portal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\PayrollDocumentDownloadUnlock;
use App\adms\Helpers\UrlAdmHelper;
use App\adms\Models\Repository\EmployeePayrollDocumentsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Reautenticação com palavra-passe antes de download (attachment) quando o tipo de documento exige.
 */
class ConfirmPayrollDocumentDownload
{
    private array $data = [];

    public function index(string|null $id = null): void
    {
        $docId = $id !== null && $id !== '' ? (int)$id : (int)($_GET['id'] ?? 0);
        $uid = (int)($_SESSION['user_id'] ?? 0);
        if ($uid <= 0) {
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }

        $repo = new EmployeePayrollDocumentsRepository();
        $doc = $docId > 0 ? $repo->getByIdForUser($docId, $uid) : null;
        if ($doc === null || (($doc['status_version'] ?? 'active') !== 'active')) {
            $_SESSION['msg'] = '<div class="alert alert-warning">Documento não encontrado.</div>';
            header('Location: ' . UrlAdmHelper::to('my-payroll-documents'));
            exit;
        }

        if (empty($doc['require_auth_download_snapshot'])) {
            header('Location: ' . UrlAdmHelper::to('view-payroll-document/' . $docId . '?inline=0'));
            exit;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            if (!CSRFHelper::validateCSRFToken('confirm_payroll_download', (string)($_POST['csrf_token'] ?? ''))) {
                $_SESSION['msg'] = '<div class="alert alert-danger">Token inválido. Atualize a página.</div>';
                header('Location: ' . UrlAdmHelper::to('confirm-payroll-document-download/' . $docId));
                exit;
            }
            $pwd = (string)($_POST['password'] ?? '');
            $hash = (new UsersRepository())->getPasswordHashById($uid);
            if ($hash === null || $pwd === '' || !password_verify($pwd, $hash)) {
                $_SESSION['msg'] = '<div class="alert alert-danger">Palavra-passe incorreta.</div>';
                header('Location: ' . UrlAdmHelper::to('confirm-payroll-document-download/' . $docId));
                exit;
            }

            PayrollDocumentDownloadUnlock::grant($docId);
            header('Location: ' . UrlAdmHelper::to('view-payroll-document/' . $docId . '?inline=0'));
            exit;
        }

        $this->data['doc'] = $doc;
        $this->data['doc_id'] = $docId;
        $this->data['csrf_token'] = CSRFHelper::generateCSRFToken('confirm_payroll_download');
        $this->data['ttl_minutes'] = (int)ceil(PayrollDocumentDownloadUnlock::TTL_SECONDS / 60);

        $pageElements = [
            'title_head' => 'Confirmar download do documento',
            'menu' => 'my-payroll-documents',
            'buttonPermission' => ['MyPayrollDocuments'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));

        (new LoadViewService('adms/Views/portal/confirm_payroll_document_download', $this->data))->loadView();
    }
}
