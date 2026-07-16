<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Models\Repository\SstAnexosRepository;
use App\adms\Models\Services\SstAnexosUploadService;

/**
 * Serve anexo SST autenticado (imagem/documento em storage/).
 */
class SstViewAnexo
{
    public function index(string|int $id = 0): void
    {
        $id = (int) $id;
        $anexo = (new SstAnexosRepository())->getById($id);
        if (!$anexo) {
            http_response_code(404);
            echo 'Anexo não encontrado.';
            exit;
        }

        $abs = (new SstAnexosUploadService())->absolutePath((string) ($anexo['file_path'] ?? ''));
        if ($abs === '' || !is_file($abs)) {
            http_response_code(404);
            echo 'Arquivo não encontrado.';
            exit;
        }

        $mime = (string) ($anexo['mime_type'] ?? '');
        if ($mime === '') {
            $mime = 'application/octet-stream';
        }
        $name = (string) ($anexo['file_name'] ?? basename($abs));
        $inline = str_starts_with(strtolower($mime), 'image/') || strtolower($mime) === 'application/pdf';

        if (ob_get_length()) {
            ob_end_clean();
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($abs));
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . rawurlencode($name) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($abs);
        exit;
    }
}
