<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\SstPppRepository;
use App\adms\Views\Services\LoadViewService;

class SstViewPpp
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-ppp');
            exit;
        }
        $repo = new SstPppRepository();
        $this->data['item'] = $repo->getById((int) $id);
        if (!$this->data['item']) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-ppp');
            exit;
        }
        $this->data['payload'] = json_decode($this->data['item']['payload_json'] ?? '{}', true) ?: [];
        $pageElements = ['title_head' => 'PPP', 'menu' => 'sst-list-ppp', 'buttonPermission' => ['SstViewPpp', 'SstExportPppPdf', 'SstGeneratePpp']];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/ppp/view', $this->data))->loadView();
    }
}
