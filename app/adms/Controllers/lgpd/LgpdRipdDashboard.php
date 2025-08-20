<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdRipdRepository;
use App\adms\Views\Services\LoadViewService;
use Exception;

/**
 * Controller responsável pelo dashboard de Relatórios de Impacto à Proteção de Dados (RIPD).
 *
 * @package App\adms\Controllers\lgpd
 */
class LgpdRipdDashboard
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
     * Método principal para exibir o dashboard do RIPD.
     *
     * @return void
     */
    public function index(): void
    {
        try {
            // Carregar estatísticas
            $this->data['estatisticas'] = $this->ripdRepo->getEstatisticas();
            
            // Configurar elementos da página
            $pageElements = [
                'title_head' => 'Dashboard RIPD - Relatórios de Impacto à Proteção de Dados',
                'menu' => 'LgpdRipdDashboard',
                'buttonPermission' => ['LgpdRipd', 'LgpdRipdCreate', 'LgpdRipdEdit', 'LgpdRipdView', 'LgpdRipdDelete'],
            ];
            
            $pageLayoutService = new PageLayoutService();
            $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

            // Carregar a VIEW
            $loadView = new LoadViewService("adms/Views/lgpd/ripd/dashboard", $this->data);
            $loadView->loadView();
        } catch (Exception $e) {
            error_log("Erro no controller LgpdRipdDashboard: " . $e->getMessage());
            $_SESSION['error'] = "Erro ao carregar dashboard do RIPD!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-dashboard");
            exit;
        }
    }
}
