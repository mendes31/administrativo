<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdRipdRepository;
use App\adms\Views\Services\LoadViewService;
use Exception;

/**
 * Controller responsável pela listagem de Relatórios de Impacto à Proteção de Dados (RIPD).
 *
 * @package App\adms\Controllers\lgpd
 */
class LgpdRipd
{
    /** @var array $data Recebe os dados que devem ser enviados para a VIEW */
    private array $data = [];

    /** @var LgpdRipdRepository $ripdRepo */
    private LgpdRipdRepository $ripdRepo;

    public function __construct()
    {
        $this->ripdRepo = new LgpdRipdRepository();
    }

    /**
     * Método principal para listar os relatórios RIPD.
     *
     * @return void
     */
    public function index(): void
    {
        try {
            // Carregar dados para a listagem
            $this->data['ripds'] = $this->ripdRepo->getAllRipd();
            $this->data['total_ripds'] = $this->ripdRepo->getAmountRipd();
            $this->data['estatisticas'] = $this->ripdRepo->getEstatisticas();
            
            // Configurar elementos da página
            $pageElements = [
                'title_head' => 'Relatórios de Impacto à Proteção de Dados (RIPD)',
                'menu' => 'lgpd-ripd',
                'buttonPermission' => ['LgpdRipd', 'LgpdRipdCreate', 'LgpdRipdEdit', 'LgpdRipdView', 'LgpdRipdDelete'],
            ];
            
            $pageLayoutService = new PageLayoutService();
            $pageData = $pageLayoutService->configurePageElements($pageElements);
            
            // Mesclar dados de forma mais segura
            foreach ($pageData as $key => $value) {
                $this->data[$key] = $value;
            }

            // Carregar a VIEW
            $loadView = new LoadViewService("adms/Views/lgpd/ripd/list", $this->data);
            $loadView->loadView();
        } catch (Exception $e) {
            error_log("Erro no controller LgpdRipd: " . $e->getMessage());
            $_SESSION['error'] = "Erro ao carregar listagem de RIPDs!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-dashboard");
            exit;
        }
    }
}
