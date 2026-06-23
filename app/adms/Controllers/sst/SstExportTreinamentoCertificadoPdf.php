<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\PagesRoutesRepository;
use App\adms\Models\Repository\SstTreinamentoVinculosRepository;
use App\adms\Models\Services\SstTreinamentoCertificadoPdfService;

class SstExportTreinamentoCertificadoPdf
{
    public function index(string|int|null $id = null): void
    {
        try {
            if (ob_get_length()) {
                ob_end_clean();
            }
            @set_time_limit(60);

            $vinculoId = (int) $id;
            if ($vinculoId <= 0) {
                header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-treinamento-vinculos');
                exit;
            }

            $repo = new SstTreinamentoVinculosRepository();
            $vinculo = $repo->getById($vinculoId);
            if (!$vinculo) {
                header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-treinamento-vinculos');
                exit;
            }

            if (!$this->canAccessPdf($vinculo)) {
                $_SESSION['msg'] = 'Sem permissão para visualizar este certificado.';
                $_SESSION['msg_type'] = 'danger';
                header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-treinamento-vinculo/' . $vinculoId);
                exit;
            }

            $path = (string) ($vinculo['certificado'] ?? '');
            if ($path === '' || !is_readable(SstTreinamentoCertificadoPdfService::absoluteStoragePath($path))) {
                $path = (new SstTreinamentoCertificadoPdfService())->generateForVinculo($vinculoId) ?? '';
                $vinculo = $repo->getById($vinculoId) ?? $vinculo;
            }

            $abs = $path !== '' ? SstTreinamentoCertificadoPdfService::absoluteStoragePath($path) : '';
            if ($path === '' || !is_readable($abs)) {
                $_SESSION['msg'] = 'Certificado não disponível. Registre a conclusão do treinamento.';
                $_SESSION['msg_type'] = 'danger';
                header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-treinamento-vinculo/' . $vinculoId);
                exit;
            }

            $nome = preg_replace('/\W+/', '_', (string) ($vinculo['colaborador_nome'] ?? 'colaborador'));
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="Certificado_SST_' . $nome . '_' . $vinculoId . '.pdf"');
            header('Content-Length: ' . (string) filesize($abs));
            readfile($abs);
            exit;
        } catch (\Throwable $e) {
            $_SESSION['msg'] = 'Erro ao abrir certificado: ' . $e->getMessage();
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-treinamento-vinculo/' . (int) ($id ?? 0));
            exit;
        }
    }

    /** @param array<string, mixed> $vinculo */
    private function canAccessPdf(array $vinculo): bool
    {
        $uid = (int) ($_SESSION['user_id'] ?? 0);
        if ($uid <= 0) {
            return false;
        }
        if (UserAccessHelper::hasFullSystemAccess()) {
            return true;
        }
        if ((int) ($vinculo['adms_user_id'] ?? 0) === $uid) {
            return true;
        }

        return (new PagesRoutesRepository())->checkUserAnyPagePermissionForControllers([
            'SstViewTreinamentoVinculo',
            'SstExportTreinamentoCertificadoPdf',
            'SstListTreinamentoVinculos',
        ]);
    }
}
