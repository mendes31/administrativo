<?php

declare(strict_types=1);

namespace App\adms\Controllers\imports;

use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Services\Imports\ImportProfileCatalog;

class ImportCenterTemplate
{
    public function index(): void
    {
        $profile = ImportProfileCatalog::get((string) ($_GET['profile'] ?? ''));
        if ($profile === null) {
            header('Location: ' . $_ENV['URL_ADM'] . 'import-center');
            exit;
        }
        if (!UserAccessHelper::hasFullSystemAccess()) {
            $allowed = (new ButtonPermissionUserRepository())->buttonPermission([$profile->permission()]);
            if (!is_array($allowed) || !in_array($profile->permission(), $allowed, true)) {
                $_SESSION['msg'] = 'Sem permissão para este tipo de importação.';
                $_SESSION['msg_type'] = 'danger';
                header('Location: ' . $_ENV['URL_ADM'] . 'import-center');
                exit;
            }
        }

        $filename = 'template_importacao_' . $profile->key() . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        $fields = array_keys($profile->fields());
        fputcsv($out, $fields, ';');
        if (method_exists($profile, 'sampleRow')) {
            $sample = $profile->sampleRow();
            if (is_array($sample) && $sample !== []) {
                fputcsv($out, $sample, ';');
            }
        } elseif ($profile->key() === 'users') {
            fputcsv($out, [
                '', '000.000.000-00', 'fulano.silva', 'Fulano Silva', 'fulano@empresa.com',
                '', '', 'Suprimentos', 'Gerente de Suprimentos', 'Ativo', 'Não',
                '01/01/1990', '15/03/2020', '', '', 'MAT001',
            ], ';');
        } elseif ($profile->key() === 'departments') {
            fputcsv($out, ['', 'Suprimentos'], ';');
        } elseif ($profile->key() === 'positions') {
            fputcsv($out, ['', 'Gerente de Suprimentos'], ';');
        }
        fclose($out);
        exit;
    }
}
