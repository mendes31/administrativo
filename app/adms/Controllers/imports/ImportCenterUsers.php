<?php

declare(strict_types=1);

namespace App\adms\Controllers\imports;

/** Página ACL do tipo usuários — redireciona para o envio. */
class ImportCenterUsers
{
    public function index(): void
    {
        header('Location: ' . $_ENV['URL_ADM'] . 'import-center-create?profile=users');
        exit;
    }
}
