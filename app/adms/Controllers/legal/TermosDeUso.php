<?php

namespace App\adms\Controllers\legal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Services\SiteLegalTermService;
use App\adms\Views\Services\LoadViewService;

class TermosDeUso
{
    private array $data = [];

    public function index(): void
    {
        $termo = (new SiteLegalTermService())->getTermosDeUso();

        $this->data['termo'] = $termo;
        $this->data['has_termo'] = !empty($termo['conteudo']);
        $this->data['titulo_busca'] = trim((string) ($_ENV['LGPD_TERMO_USO_TITULO'] ?? 'Termos de Uso')) ?: 'Termos de Uso';
        $this->data['empty_hint'] = 'Cadastre um termo LGPD com tipo <strong>Site/Portal</strong> e título contendo '
            . htmlspecialchars($this->data['titulo_busca'], ENT_QUOTES, 'UTF-8')
            . ' em LGPD → Termos LGPD.';

        $pageElements = [
            'title_head' => 'Termos de Uso',
            'menu' => '',
            'buttonPermission' => [],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        (new LoadViewService('adms/Views/legal/site_term', $this->data))->loadView();
    }
}
