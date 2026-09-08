<?php

declare(strict_types=1);

namespace App\adms\Controllers\imports;

/** Página ACL do tipo cargos — redireciona para o envio. */
class ImportCenterPositions
{
    public function index(): void
    {
        header('Location: ' . $_ENV['URL_ADM'] . 'import-center-create?profile=positions');
        exit;
    }
}
