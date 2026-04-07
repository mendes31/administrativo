<?php

declare(strict_types=1);

namespace App\adms\Controllers\portal;

use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\EmployeePayrollDocumentsRepository;

/**
 * Entrega o PDF por stream (nunca URL pública ao ficheiro em disco).
 */
class ViewPayrollDocument
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

        $ownerId = (int)($doc['user_id'] ?? 0);
        if ($ownerId !== $sessionUid && !UserAccessHelper::hasFullSystemAccess()) {
            header('HTTP/1.0 403 Forbidden');
            exit;
        }

        $rel = (string)($doc['storage_path'] ?? '');
        $abs = $repo->absoluteStoragePath($rel);
        if ($rel === '' || !is_readable($abs)) {
            header('HTTP/1.0 404 Not Found');
            exit;
        }

        $inline = ($_GET['inline'] ?? '1') !== '0';
        $filename = 'documento.pdf';

        header('Content-Type: application/pdf');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        if ($inline) {
            header('Content-Disposition: inline; filename="' . $filename . '"');
        } else {
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        }
        header('Content-Length: ' . (string)filesize($abs));

        readfile($abs);
        exit;
    }
}
