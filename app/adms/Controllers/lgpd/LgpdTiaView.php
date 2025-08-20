<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdTiaRepository;
use App\adms\Views\Services\LoadViewService;
use Exception;

/**
 * Controller responsável pela visualização de Testes de Impacto às Atividades (TIA).
 *
 * @package App\adms\Controllers\lgpd
 */
class LgpdTiaView
{
    /** @var array $data Recebe os dados que devem ser enviados para a VIEW */
    private array $data = [];

    /** @var LgpdTiaRepository $tiaRepo */
    private LgpdTiaRepository $tiaRepo;

    public function __construct()
    {
        $this->tiaRepo = new LgpdTiaRepository();
    }

    /**
     * Método para visualizar um teste TIA específico.
     *
     * @param int $id ID do teste TIA
     * @return void
     */
    public function index(int $id): void
    {
        try {
            // Buscar dados do teste TIA
            $tia = $this->tiaRepo->getTiaById($id);
            
            if (!$tia) {
                $_SESSION['error'] = "Teste TIA não encontrado!";
                header("Location: " . $_ENV['URL_ADM'] . "lgpd-tia");
                exit;
            }
            
            $this->data['tia'] = $tia;
            
            // Buscar grupos de dados relacionados
            $this->data['data_groups'] = $this->tiaRepo->getDataGroupsByTiaId($id);
            
            // Configurar elementos da página
            $pageElements = [
                'title_head' => 'Visualizar Teste TIA: ' . $tia['codigo'],
                'menu' => 'lgpd-tia',
                'buttonPermission' => ['LgpdTiaView', 'LgpdTiaEdit', 'LgpdTiaDelete'],
            ];
            
            $pageLayoutService = new PageLayoutService();
            $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

            // Carregar a VIEW
            $loadView = new LoadViewService("adms/Views/lgpd/tia/view", $this->data);
            $loadView->loadView();
        } catch (Exception $e) {
            error_log("Erro no controller LgpdTiaView: " . $e->getMessage());
            $_SESSION['error'] = "Erro ao carregar teste TIA!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-tia");
            exit;
        }
    }
}
