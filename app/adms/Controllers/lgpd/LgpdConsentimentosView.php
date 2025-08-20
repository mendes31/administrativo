<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdConsentimentosRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller responsável pela visualização de Consentimentos LGPD.
 *
 * @package App\adms\Controllers\lgpd
 */
class LgpdConsentimentosView
{
    /** @var array $data Recebe os dados que devem ser enviados para a VIEW */
    private array $data = [];

    /** @var LgpdConsentimentosRepository $consentimentosRepo */
    private LgpdConsentimentosRepository $consentimentosRepo;

    public function __construct()
    {
        $this->consentimentosRepo = new LgpdConsentimentosRepository();
    }

    /**
     * Método para visualizar consentimento.
     *
     * @param int $id ID do consentimento
     * @return void
     */
    public function index(int $id): void
    {
        $this->data['consentimento'] = $this->consentimentosRepo->getConsentimentoById($id);
        
        if (!$this->data['consentimento']) {
            $_SESSION['error'] = "Consentimento não encontrado!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-consentimentos");
            exit;
        }

        // Configurar elementos da página
        $pageElements = [
            'title_head' => 'Visualizar Consentimento',
            'menu' => 'lgpd-consentimentos',
            'buttonPermission' => ['ViewLgpdConsentimentos'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/lgpd/consentimentos/view", $this->data);
        $loadView->loadView();
    }
}
