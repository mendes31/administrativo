<?php

declare(strict_types=1);

namespace App\adms\Controllers\imports;

/** Página ACL do tipo departamentos — redireciona para o envio. */
class ImportCenterDepartments
{
    public function index(): void
    {
        header('Location: ' . $_ENV['URL_ADM'] . 'import-center-create?profile=departments');
        exit;
    }
}
