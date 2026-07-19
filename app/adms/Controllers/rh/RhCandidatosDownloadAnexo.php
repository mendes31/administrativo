<?php

declare(strict_types=1);

namespace App\adms\Controllers\rh;

use App\adms\Models\Repository\RhCandidatoAnexoAccessLogRepository;
use App\adms\Models\Repository\RhCandidatosRepository;
use App\adms\Models\Services\RhCandidatoAnexoService;
use App\adms\Models\Services\RhCandidatoPermissionService;

/**
 * Download autorizado de anexo/currículo (storage privado ou legado).
 */
class RhCandidatosDownloadAnexo
{
    public function index(int|string $id = null): void
    {
        if (!$id) {
            $id = $_GET['id'] ?? null;
        }

        if (!$id || !is_numeric($id)) {
            $this->deny('Anexo inválido.', 400);
        }

        $anexoId = (int) $id;
        $repo = new RhCandidatosRepository();
        $anexo = $repo->getAnexoById($anexoId);

        if ($anexo === null) {
            $this->deny('Anexo não encontrado.', 404);
        }

        $candidatoId = (int) ($anexo['rh_candidato_id'] ?? 0);
        if (!RhCandidatoPermissionService::canDownloadAnexo($candidatoId)) {
            $this->deny('Acesso não autorizado a este currículo.', 403);
        }

        $path = (string) ($anexo['arquivo_caminho'] ?? '');
        $fullPath = RhCandidatoAnexoService::resolvePhysicalPath($path);
        if ($fullPath === null || !is_readable($fullPath)) {
            $this->deny('Arquivo não encontrado.', 404);
        }

        $original = (string) ($anexo['nome_original'] ?? basename($fullPath));
        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            default => 'application/octet-stream',
        };

        (new RhCandidatoAnexoAccessLogRepository())->logDownload([
            'rh_candidato_anexo_id' => $anexoId,
            'rh_candidato_id' => $candidatoId,
            'actor_user_id' => (int) ($_SESSION['user_id'] ?? 0),
            'action' => 'download',
            'delivery_mode' => 'inline',
            'source' => 'authorized_controller',
            'anexo_tipo' => $anexo['tipo'] ?? 'curriculo',
            'path' => $path,
        ]);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($fullPath));
        header('Content-Disposition: inline; filename="' . str_replace(['"', "\r", "\n"], '', $original) . '"');
        header('Cache-Control: private, no-store');
        header('X-Content-Type-Options: nosniff');

        readfile($fullPath);
        exit;
    }

    private function deny(string $message, int $status): never
    {
        http_response_code($status);
        if (!empty($_SESSION['user_id'])) {
            $_SESSION['error'] = $message;
            header('Location: ' . ($_ENV['URL_ADM'] ?? '/') . 'rh-candidatos');
            exit;
        }

        echo $message;
        exit;
    }
}
