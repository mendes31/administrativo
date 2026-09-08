<?php

declare(strict_types=1);

namespace App\adms\Controllers\imports;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\ImportJobsRepository;
use App\adms\Models\Services\Imports\ImportProfileCatalog;
use App\adms\Views\Services\LoadViewService;

class ImportCenter
{
    private array $data = [];

    public function index(): void
    {
        $pageElements = [
            'title_head' => 'Central de Importações',
            'menu' => 'import-center',
            'buttonPermission' => [
                'ImportCenter',
                'ImportCenterUsers',
                'ImportCenterDepartments',
                'ImportCenterPositions',
                'ImportCenterSst',
                'ImportCenterView',
            ],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        $perms = $this->data['buttonPermission'] ?? [];

        $profiles = [];
        foreach (ImportProfileCatalog::all() as $profile) {
            if (UserAccessHelper::hasFullSystemAccess() || in_array($profile->permission(), $perms, true)) {
                $profiles[] = $profile;
            }
        }

        $uid = (int) ($_SESSION['user_id'] ?? 0);
        $this->data['profiles'] = $profiles;
        $this->data['jobs'] = (new ImportJobsRepository())->listRecent(
            40,
            UserAccessHelper::hasFullSystemAccess() ? null : $uid
        );

        (new LoadViewService('adms/Views/imports/list', $this->data))->loadView();
    }
}
