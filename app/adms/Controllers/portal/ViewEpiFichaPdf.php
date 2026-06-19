<?php

declare(strict_types=1);

namespace App\adms\Controllers\portal;

use App\adms\Models\Repository\SstEpiFichasRepository;

/**
 * PDF da ficha de EPI — colaborador vê apenas as próprias fichas.
 */
class ViewEpiFichaPdf
{
    public function index(string|int|null $id = null): void
    {
        $uid = (int) ($_SESSION['user_id'] ?? 0);
        if ($uid <= 0) {
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }

        $fichaId = (int) $id;
        $repo = new SstEpiFichasRepository();
        $ficha = $repo->getByIdForUser($fichaId, $uid);
        if (!$ficha) {
            $_SESSION['msg'] = 'Ficha não encontrada.';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $_ENV['URL_ADM'] . 'my-epi-deliveries');
            exit;
        }

        $path = (string) ($ficha['pdf_storage_path'] ?? '');
        $abs = $path !== '' ? $repo->absoluteStoragePath($path) : '';
        if ($path === '' || !is_readable($abs)) {
            $_SESSION['msg'] = 'PDF não disponível.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'my-epi-deliveries');
            exit;
        }

        if (ob_get_length()) {
            ob_end_clean();
        }
        $nome = preg_replace('/\W+/', '_', (string) ($ficha['colaborador_nome'] ?? 'colaborador'));
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="Ficha_EPI_' . $nome . '_' . $fichaId . '.pdf"');
        header('Content-Length: ' . (string) filesize($abs));
        readfile($abs);
        exit;
    }
}
