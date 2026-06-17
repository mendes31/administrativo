<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstInspecoesRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

class SstViewInspecao
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-inspecoes');
            exit;
        }
        $repo = new SstInspecoesRepository();
        $this->data['item'] = $repo->getById((int) $id);
        if (!$this->data['item']) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-inspecoes');
            exit;
        }
        $this->data['itens'] = $repo->getItens((int) $id);
        $this->data['users'] = (new UsersRepository())->getAllUsersForSelect();
        $pageElements = ['title_head' => 'Inspeção SST', 'menu' => 'sst-list-inspecoes', 'buttonPermission' => ['SstViewInspecao', 'SstUpdateInspecao', 'SstDeleteInspecao', 'SstManageInspecaoItem']];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/inspecoes/view', $this->data))->loadView();
    }
}
