<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdConsentimentosRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller responsável pela gestão de Consentimentos LGPD.
 *
 * Esta classe gerencia a listagem, criação, edição e exclusão de consentimentos,
 * trabalhando com o repositório de consentimentos para operações de CRUD.
 *
 * @package App\adms\Controllers\lgpd
 */
class LgpdConsentimentos
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
     * Método principal para listar consentimentos.
     *
     * @return void
     */
    public function index(): void
    {
        // Carregar dados dos consentimentos
        $this->data['consentimentos'] = $this->consentimentosRepo->getAllConsentimentos();
        
        // Configurar elementos da página
        $pageElements = [
            'title_head' => 'Consentimentos LGPD',
            'menu' => 'lgpd-consentimentos',
            'buttonPermission' => ['ListLgpdConsentimentos', 'CreateLgpdConsentimentos', 'EditLgpdConsentimentos', 'ViewLgpdConsentimentos', 'DeleteLgpdConsentimentos'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/lgpd/consentimentos/list", $this->data);
        $loadView->loadView();
    }





    /**
     * Método para revogar consentimento.
     *
     * @param int $id ID do consentimento
     * @return void
     */
    public function revogar(int $id): void
    {
        $consentimento = $this->consentimentosRepo->getConsentimentoById($id);
        
        if (!$consentimento) {
            $_SESSION['error'] = "Consentimento não encontrado!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-consentimentos");
            exit;
        }

        $result = $this->consentimentosRepo->revogarConsentimento($id);
        
        if ($result) {
            $_SESSION['success'] = "Consentimento revogado com sucesso!";
        } else {
            $_SESSION['error'] = "Erro ao revogar consentimento!";
        }

        header("Location: " . $_ENV['URL_ADM'] . "lgpd-consentimentos");
        exit;
    }

    /**
     * Método para obter dados via AJAX.
     *
     * @return void
     */
    public function getData(): void
    {
        header('Content-Type: application/json');
        
        try {
            $consentimentos = $this->consentimentosRepo->getAllConsentimentos();
            echo json_encode([
                'success' => true,
                'data' => $consentimentos
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Erro ao carregar dados dos consentimentos'
            ]);
        }
    }
}
