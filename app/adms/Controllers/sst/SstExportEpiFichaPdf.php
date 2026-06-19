<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\PagesRoutesRepository;
use App\adms\Models\Repository\SstEpiFichasRepository;
use App\adms\Models\Services\SstEpiFichaSignedBundlePdfService;

class SstExportEpiFichaPdf
{
    public function index(string|int|null $id = null): void
    {
        try {
            if (ob_get_length()) {
                ob_end_clean();
            }
            @set_time_limit(60);

            $fichaId = (int) $id;
            if ($fichaId <= 0) {
                header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-epi-fichas');
                exit;
            }

            $repo = new SstEpiFichasRepository();
            $ficha = $repo->getById($fichaId);
            if (!$ficha) {
                header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-epi-fichas');
                exit;
            }

            if (!$this->canAccessPdf($ficha)) {
                $_SESSION['msg'] = 'Sem permissão para visualizar este PDF.';
                $_SESSION['msg_type'] = 'danger';
                header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-epi-ficha/' . $fichaId);
                exit;
            }

            SstEpiFichaSignedBundlePdfService::ensureAuditTrailIncluded($repo, $fichaId);
            $ficha = $repo->getById($fichaId) ?? $ficha;

            $path = (string) ($ficha['pdf_storage_path'] ?? '');
            $abs = $path !== '' ? $repo->absoluteStoragePath($path) : '';
            if ($path === '' || !is_readable($abs)) {
                $_SESSION['msg'] = 'PDF não encontrado. Regenere a ficha.';
                $_SESSION['msg_type'] = 'danger';
                header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-epi-ficha/' . $fichaId);
                exit;
            }

            $nome = preg_replace('/\W+/', '_', (string) ($ficha['colaborador_nome'] ?? 'colaborador'));
            $filename = 'Ficha_EPI_' . $nome . '_' . $fichaId . '.pdf';
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $filename . '"');
            header('Content-Length: ' . (string) filesize($abs));
            readfile($abs);
            exit;
        } catch (\Throwable $e) {
            $_SESSION['msg'] = 'Erro ao abrir PDF: ' . $e->getMessage();
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-epi-ficha/' . (int) ($id ?? 0));
            exit;
        }
    }

    /** @param array<string, mixed> $ficha */
    private function canAccessPdf(array $ficha): bool
    {
        $uid = (int) ($_SESSION['user_id'] ?? 0);
        if ($uid <= 0) {
            return false;
        }
        if (UserAccessHelper::hasFullSystemAccess()) {
            return true;
        }
        if ((int) ($ficha['adms_user_id'] ?? 0) === $uid) {
            return true;
        }

        return (new PagesRoutesRepository())->checkUserAnyPagePermissionForControllers([
            'SstViewEpiFicha',
            'SstExportEpiFichaPdf',
            'SstListEpiFichas',
        ]);
    }
}
