<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdRipdRepository;
use App\adms\Views\Services\LoadViewService;
use Exception;

/**
 * Controller responsável pela visualização de Relatórios de Impacto à Proteção de Dados (RIPD).
 *
 * @package App\adms\Controllers\lgpd
 */
class LgpdRipdView
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
     * Método principal para exibir um relatório RIPD específico.
     *
     * @param int $id ID do RIPD
     * @return void
     */
    public function index(int $id): void
    {
        try {
            // Buscar dados do RIPD
            $ripd = $this->ripdRepo->getRipdById($id);
            
            if (!$ripd) {
                $_SESSION['error'] = "RIPD não encontrado!";
                header("Location: " . $_ENV['URL_ADM'] . "lgpd-ripd");
                exit;
            }
            
            $this->data['ripd'] = $ripd;
            
            // Configurar elementos da página
            $pageElements = [
                'title_head' => 'Visualizar RIPD - ' . $ripd['codigo'],
                'menu' => 'lgpd-ripd',
                'buttonPermission' => ['LgpdRipd', 'LgpdRipdCreate', 'LgpdRipdEdit', 'LgpdRipdView', 'LgpdRipdDelete'],
            ];
            
            $pageLayoutService = new PageLayoutService();
            $pageLayoutData = $pageLayoutService->configurePageElements($pageElements);
            
            // Mesclar dados de forma segura
            foreach ($pageLayoutData as $key => $value) {
                $this->data[$key] = $value;
            }

            // Carregar a VIEW
            $loadView = new LoadViewService("adms/Views/lgpd/ripd/view", $this->data);
            $loadView->loadView();
        } catch (Exception $e) {
            error_log("Erro no controller LgpdRipdView: " . $e->getMessage());
            $_SESSION['error'] = "Erro ao carregar RIPD!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-ripd");
            exit;
        }
    }
}
