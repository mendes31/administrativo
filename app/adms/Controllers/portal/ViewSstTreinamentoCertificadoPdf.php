<?php

declare(strict_types=1);

namespace App\adms\Controllers\portal;

use App\adms\Models\Repository\SstTreinamentoVinculosRepository;
use App\adms\Models\Services\SstTreinamentoCertificadoPdfService;

/**
 * PDF do certificado SST — colaborador vê apenas os próprios vínculos.
 */
class ViewSstTreinamentoCertificadoPdf
{
    public function index(string|int|null $id = null): void
    {
        $uid = (int) ($_SESSION['user_id'] ?? 0);
        if ($uid <= 0) {
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }

        $vinculoId = (int) $id;
        $repo = new SstTreinamentoVinculosRepository();
        $vinculo = $repo->getByIdForUser($vinculoId, $uid);
        if (!$vinculo) {
            $_SESSION['msg'] = 'Certificado não encontrado.';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $_ENV['URL_ADM'] . 'my-sst-treinamentos');
            exit;
        }

        $path = (string) ($vinculo['certificado'] ?? '');
        if ($path === '' || !is_readable(SstTreinamentoCertificadoPdfService::absoluteStoragePath($path))) {
            try {
                $path = (new SstTreinamentoCertificadoPdfService())->generateForVinculo($vinculoId) ?? '';
                $vinculo = $repo->getByIdForUser($vinculoId, $uid) ?? $vinculo;
            } catch (\Throwable) {
                $path = '';
            }
        }

        $abs = $path !== '' ? SstTreinamentoCertificadoPdfService::absoluteStoragePath($path) : '';
        if ($path === '' || !is_readable($abs)) {
            $_SESSION['msg'] = 'Certificado PDF não disponível. Conclua o treinamento com data de realização.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'my-sst-treinamentos');
            exit;
        }

        if (ob_get_length()) {
            ob_end_clean();
        }
        $nome = preg_replace('/\W+/', '_', (string) ($vinculo['colaborador_nome'] ?? 'colaborador'));
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="Certificado_SST_' . $nome . '_' . $vinculoId . '.pdf"');
        header('Content-Length: ' . (string) filesize($abs));
        readfile($abs);
        exit;
    }
}
