<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\SstAnexosRepository;
use App\adms\Models\Repository\SstProgramasRepository;
use App\adms\Views\Services\LoadViewService;

class SstViewPrograma
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-programas');
            exit;
        }

        $repo = new SstProgramasRepository();
        $this->data['item'] = $repo->getById((int) $id);
        if (!$this->data['item']) {
            $_SESSION['msg'] = 'Programa não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-programas');
            exit;
        }

        $itemId = (int) $this->data['item']['id'];
        $this->data['anexos'] = (new SstAnexosRepository())->getByEntity('programas', $itemId);

        $pageElements = [
            'title_head' => 'Visualizar programa SST - SST',
            'menu' => 'sst-list-programas',
            'buttonPermission' => ['SstViewPrograma', 'SstUpdatePrograma', 'SstDeletePrograma'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/programas/view', $this->data))->loadView();
    }
}
