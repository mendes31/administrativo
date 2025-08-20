<?php
namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdConsentimentosRepository;
use App\adms\Views\Services\LoadViewService;

class LgpdConsentimentoEmail {
    private array $data = [];
    private LgpdConsentimentosRepository $consentimentosRepo;
    
    public function __construct() {
        $this->consentimentosRepo = new LgpdConsentimentosRepository();
    }
    
    /**
     * Método padrão - redireciona para enviar
     */
    public function index(): void {
        $this->enviar();
    }
    
    /**
     * Página para envio de formulário por e-mail
     */
    public function enviar(): void {
        // Configurar elementos da página
        $pageElements = [
            'title_head' => 'Enviar Formulário de Consentimento - LGPD',
            'menu' => 'LgpdConsentimentoEmail',
            'buttonPermission' => ['LgpdConsentimentoEmail'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/lgpd/consentimento-email/enviar", $this->data);
        $loadView->loadView();
    }
    

    

}
