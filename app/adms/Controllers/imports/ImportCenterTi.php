<?php

declare(strict_types=1);

namespace App\adms\Controllers\imports;

/** Página ACL do tipo RustDesk — atalho para o envio do perfil. */
class ImportCenterTi
{
    public function index(): void
    {
        header('Location: ' . $_ENV['URL_ADM'] . 'import-center-create?profile=ti_rustdesk');
        exit;
    }
}
