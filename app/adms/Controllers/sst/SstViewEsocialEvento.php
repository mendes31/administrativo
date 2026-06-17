<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\SstEsocialEventosRepository;
use App\adms\Views\Services\LoadViewService;

class SstViewEsocialEvento
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-esocial-eventos');
            exit;
        }

        $repo = new SstEsocialEventosRepository();
        $this->data['item'] = $repo->getById((int) $id);
        if (!$this->data['item']) {
            $_SESSION['msg'] = 'Evento não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-esocial-eventos');
            exit;
        }

        $pageElements = [
            'title_head' => 'Evento eSocial - SST',
            'menu' => 'sst-list-esocial-eventos',
            'buttonPermission' => ['SstViewEsocialEvento', 'SstGenerateEsocialEvento', 'SstMarkEsocialEnviado'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/esocial_eventos/view', $this->data))->loadView();
    }
}
