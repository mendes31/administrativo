<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Models\Repository\SstEsocialEventosRepository;

class SstExportEsocialJson
{
    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-esocial-eventos');
            exit;
        }

        $item = (new SstEsocialEventosRepository())->getById((int) $id);
        if (!$item || empty($item['payload_json'])) {
            $_SESSION['msg'] = 'Payload não disponível.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-esocial-evento/' . (int) $id);
            exit;
        }

        $filename = 'esocial_' . str_replace('-', '', (string) ($item['tipo_evento'] ?? 'evt')) . '_' . (int) $id . '.json';
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $item['payload_json'];
        exit;
    }
}
