<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/** Bloqueia o CRUD manual legado de entregas EPI (substituído por Fichas de entrega). */
final class SstLegacyEpiEntregaGuard
{
    public static function denyAndRedirect(): never
    {
        $_SESSION['msg'] = 'Entregas de EPI são registradas apenas por Fichas de entrega EPI.';
        $_SESSION['msg_type'] = 'info';
        header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-epi-fichas');
        exit;
    }
}
