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
        $fields = $profile->fields();
        fputcsv($out, array_values($fields), ';');
        fputcsv($out, array_keys($fields), ';');
        $samples = [];
        if (method_exists($profile, 'sampleRows')) {
            $rows = $profile->sampleRows();
            $samples = is_array($rows) ? $rows : [];
        } elseif (method_exists($profile, 'sampleRow')) {
            $sample = $profile->sampleRow();
            $samples = is_array($sample) && $sample !== [] ? [$sample] : [];
        } elseif ($profile->key() === 'users') {
            $samples = [[
                '', '000.000.000-00', 'fulano.silva', 'Fulano Silva', 'fulano@empresa.com',
                '', '', 'Suprimentos', 'Gerente de Suprimentos', 'Ativo', 'Não',
                '01/01/1990', '15/03/2020', '', '', 'MAT001',
            ]];
        } elseif ($profile->key() === 'departments') {
            $samples = [['', 'Suprimentos']];
        } elseif ($profile->key() === 'positions') {
            $samples = [['', 'Gerente de Suprimentos']];
        }
        foreach ($samples as $sample) {
            if (is_array($sample) && $sample !== []) {
                fputcsv($out, $sample, ';');
            }
        }
        fclose($out);
        exit;
    }
}
