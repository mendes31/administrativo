<?php

declare(strict_types=1);

namespace App\adms\Controllers\timeline;

/**
 * Permissão "Repostar" é verificada em CreateTimelinePost; esta rota evita Erro 004
 * se alguém acessar o controller_url cadastrado em adms_pages.
 */
class TimelineShare
{
    public function index(): void
    {
        header('Location: ' . rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/timeline', true, 302);
        exit;
    }
}
