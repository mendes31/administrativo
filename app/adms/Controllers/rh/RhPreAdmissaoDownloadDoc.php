<?php

declare(strict_types=1);

namespace App\adms\Controllers\rh;

use App\adms\Models\Repository\RhOfertasRepository;
use App\adms\Models\Services\RhPermissionService;
use App\adms\Models\Services\RhPreAdmissaoUploadService;

/**
 * Download autenticado de arquivo de pré-admissão.
 * URL: rh-pre-admissao-download-doc/{documentoId}?oferta_id=
 */
final class RhPreAdmissaoDownloadDoc
{
    public function index(int|string $id = 0): void
    {
        $documentoId = (int) $id;
        $ofertaId = (int) ($_GET['oferta_id'] ?? 0);
        if ($documentoId <= 0 || $ofertaId <= 0) {
            http_response_code(404);
            echo 'Documento não encontrado.';
            exit;
        }

        $repo = new RhOfertasRepository();
        $oferta = $repo->getById($ofertaId);
        $doc = $repo->getDocumentoById($documentoId, $ofertaId);
        if ($oferta === null || $doc === null || empty($doc['arquivo_caminho'])) {
            http_response_code(404);
            echo 'Arquivo não encontrado.';
            exit;
        }

        if (!RhPermissionService::canManagePipelineByVagaId((int) $oferta['rh_vaga_id'])) {
            http_response_code(403);
            echo 'Acesso negado.';
            exit;
        }

        $path = RhPreAdmissaoUploadService::resolvePhysicalPath((string) $doc['arquivo_caminho']);
        if ($path === null) {
            http_response_code(404);
            echo 'Arquivo físico não encontrado.';
            exit;
        }

        $name = (string) ($doc['arquivo_nome_original'] ?: basename($path));
        $mime = (string) ($doc['arquivo_mime'] ?: 'application/octet-stream');
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . rawurlencode($name) . '"');
        header('Content-Length: ' . (string) filesize($path));
        readfile($path);
        exit;
    }
}
