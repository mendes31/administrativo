<?php

declare(strict_types=1);

namespace App\adms\Controllers\portal;

use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Repository\EmployeePayrollDocumentsRepository;
use App\adms\Models\Services\PayrollSignedBundlePdfService;

/**
 * PDF unificado: documento original + trilha de ciência (download controlado).
 */
class ViewPayrollSignedBundle
{
    public function index(string|null $id = null): void
    {
        $docId = $id !== null && $id !== '' ? (int)$id : (int)($_GET['id'] ?? 0);
        if ($docId <= 0) {
            header('HTTP/1.0 400 Bad Request');
            exit;
        }

        $sessionUid = (int)($_SESSION['user_id'] ?? 0);
        if ($sessionUid <= 0) {
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }

        $repo = new EmployeePayrollDocumentsRepository();
        $doc = $repo->getById($docId);
        if ($doc === null) {
            header('HTTP/1.0 404 Not Found');
            exit;
        }

        $sig = (string)($doc['signature_status'] ?? '');
        $reqSig = (int)($doc['requires_signature_snapshot'] ?? 0) === 1;
        if (!$reqSig || !in_array($sig, ['pending', 'signed'], true)) {
            header('HTTP/1.0 404 Not Found');
            exit;
        }

        $statusVer = (string)($doc['status_version'] ?? 'active');
        if ($statusVer !== 'active' && !UserAccessHelper::hasFullSystemAccess()) {
            header('HTTP/1.0 410 Gone');
            exit;
        }

        $ownerId = (int)($doc['user_id'] ?? 0);
        if (!$this->canAccessBundle($sessionUid, $ownerId)) {
            header('HTTP/1.0 403 Forbidden');
            exit;
        }

        $relBundle = (string)($doc['signed_bundle_storage_path'] ?? '');
        $absBundle = $relBundle !== '' ? $repo->absoluteStoragePath($relBundle) : '';
        $needsBundle = $relBundle === '' || !is_readable($absBundle);
        $viewerIsOwner = $sessionUid === $ownerId;
        if ($needsBundle || !$viewerIsOwner) {
            try {
                (new PayrollSignedBundlePdfService())->regenerateForDocumentId($repo, $docId);
            } catch (\Throwable) {
            }
            $doc = $repo->getById($docId);
            $relBundle = is_array($doc) ? (string)($doc['signed_bundle_storage_path'] ?? '') : '';
        }

        if ($relBundle === '') {
            header('HTTP/1.0 404 Not Found');
            exit;
        }

        $abs = $repo->absoluteStoragePath($relBundle);
        if (!is_readable($abs)) {
            header('HTTP/1.0 404 Not Found');
            exit;
        }

        $filename = 'documento_com_trilha_ciencia_' . $docId . '.pdf';
        header('Content-Type: application/pdf');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . (string)filesize($abs));
        readfile($abs);
        exit;
    }

    private function canAccessBundle(int $sessionUid, int $ownerId): bool
    {
        if ($sessionUid === $ownerId) {
            return true;
        }
        if (UserAccessHelper::hasFullSystemAccess()) {
            return true;
        }
        $perm = (new ButtonPermissionUserRepository())->buttonPermission([
            'ImportPayrollDocuments',
            'PayrollImportBatchReport',
            'ListPayrollSigningPendencies',
        ]);

        return is_array($perm) && $perm !== [];
    }
}
