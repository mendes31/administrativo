<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\SstCipaRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

class SstViewCipaMandato
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-cipa-mandatos');
            exit;
        }
        $repo = new SstCipaRepository();
        $this->data['item'] = $repo->getMandatoById((int) $id);
        if (!$this->data['item']) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-cipa-mandatos');
            exit;
        }
        $this->data['membros'] = $repo->getMembros((int) $id);
        $this->data['reunioes'] = $repo->getReunioes((int) $id);
        $this->data['users'] = (new UsersRepository())->getAllUsersForSelect();
        $pageElements = ['title_head' => 'Mandato CIPA', 'menu' => 'sst-list-cipa-mandatos', 'buttonPermission' => ['SstViewCipaMandato', 'SstUpdateCipaMandato', 'SstDeleteCipaMandato', 'SstManageCipa']];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/cipa/view', $this->data))->loadView();
    }
}
