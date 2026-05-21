<?php

declare(strict_types=1);

namespace App\adms\Controllers\timeline;

/**
 * Permissão verificada em CreateTimelinePost; rota evita Erro 004 se acessada diretamente.
 */
class TimelineFeaturePost
{
    public function index(): void
    {
        header('Location: ' . rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/timeline', true, 302);
        exit;
    }
}
