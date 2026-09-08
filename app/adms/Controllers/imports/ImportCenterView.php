<?php

declare(strict_types=1);

namespace App\adms\Controllers\imports;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\ImportJobsRepository;
use App\adms\Models\Services\Imports\ImportProfileCatalog;
use App\adms\Views\Services\LoadViewService;

class ImportCenterView
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        $job = (int) $id > 0 ? (new ImportJobsRepository())->getById((int) $id) : null;
        if ($job === null) {
            $_SESSION['msg'] = 'Job não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'import-center');
            exit;
        }

        $uid = (int) ($_SESSION['user_id'] ?? 0);
        if (!UserAccessHelper::hasFullSystemAccess() && (int) ($job['created_by'] ?? 0) !== $uid) {
            $_SESSION['msg'] = 'Sem permissão para ver este job.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'import-center');
            exit;
        }

        $profile = ImportProfileCatalog::get((string) $job['profile_key']);
        $this->data['job'] = $job;
        $this->data['profile'] = $profile;
        $this->data['stats'] = json_decode((string) ($job['stats_json'] ?? '{}'), true) ?: [];
        $this->data['report'] = json_decode((string) ($job['report_json'] ?? '[]'), true) ?: [];

        $pageElements = [
            'title_head' => 'Resultado da importação',
            'menu' => 'import-center',
            'buttonPermission' => ['ImportCenterView', 'ImportCenter'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/imports/view', $this->data))->loadView();
    }
}
