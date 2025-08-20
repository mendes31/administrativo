<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdConsentimentosRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller responsável pela criação de Consentimentos LGPD.
 *
 * @package App\adms\Controllers\lgpd
 */
class LgpdConsentimentosCreate
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
     * Método para criar novo consentimento.
     *
     * @return void
     */
    public function index(): void
    {
        $this->data['form'] = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->data['form'] = $_POST;
            
            $result = $this->consentimentosRepo->create($this->data['form']);
            
            if ($result) {
                $_SESSION['success'] = "Consentimento cadastrado com sucesso!";
                header("Location: " . $_ENV['URL_ADM'] . "lgpd-consentimentos");
                exit;
            } else {
                $this->data['errors'][] = "Consentimento não cadastrado!";
            }
        }

        // Configurar elementos da página
        $pageElements = [
            'title_head' => 'Cadastrar Consentimento',
            'menu' => 'lgpd-consentimentos',
            'buttonPermission' => ['CreateLgpdConsentimentos'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/lgpd/consentimentos/create", $this->data);
        $loadView->loadView();
    }
}
