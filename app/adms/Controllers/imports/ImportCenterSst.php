<?php

declare(strict_types=1);

namespace App\adms\Controllers\imports;

/** Página ACL dos tipos SST — redireciona para o hub. */
class ImportCenterSst
{
    public function index(): void
    {
        header('Location: ' . $_ENV['URL_ADM'] . 'import-center');
        exit;
    }
}
