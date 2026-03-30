<?php

declare(strict_types=1);

namespace App\adms\Controllers\timeline;

/**
 * Permissão lógica (ler comentários sem publicar). O feed usa ButtonPermissionUserRepository;
 * esta rota evita erro se o controller_url for acessado diretamente.
 */
class TimelineViewComments
{
    public function index(): void
    {
        header('Location: ' . rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/timeline', true, 302);
        exit;
    }
}
