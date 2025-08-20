<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdTiaRepository;
use App\adms\Models\Repository\LgpdRopaRepository;
use App\adms\Models\Repository\LgpdDataGroupsRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Helpers\CSRFHelper;
use Exception;

/**
 * Controller responsável pela gestão de Testes de Impacto às Atividades (TIA).
 *
 * Esta classe gerencia a listagem, criação, edição e exclusão de testes TIA,
 * trabalhando com o repositório de TIA para operações de CRUD.
 *
 * @package App\adms\Controllers\lgpd
 */
class LgpdTia
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
     * Método principal para listar testes TIA.
     *
     * @return void
     */
    public function index(): void
    {
        try {
            // Carregar dados dos testes TIA
            $this->data['tias'] = $this->tiaRepo->getAllTia();
            $this->data['total_tias'] = $this->tiaRepo->getAmountTia();
            
            // Configurar elementos da página
            $pageElements = [
                'title_head' => 'Testes de Impacto às Atividades (TIA)',
                'menu' => 'lgpd-tia',
                'buttonPermission' => ['LgpdTia', 'LgpdTiaCreate', 'LgpdTiaEdit', 'LgpdTiaView', 'LgpdTiaDelete'],
            ];
            
            $pageLayoutService = new PageLayoutService();
            $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

            // Carregar a VIEW
            $loadView = new LoadViewService("adms/Views/lgpd/tia/list", $this->data);
            $loadView->loadView();
        } catch (Exception $e) {
            error_log("Erro no controller LgpdTia: " . $e->getMessage());
            $_SESSION['error'] = "Erro ao carregar testes TIA!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-dashboard");
            exit;
        }
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
            $tias = $this->tiaRepo->getAllTia();
            echo json_encode([
                'success' => true,
                'data' => $tias
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Erro ao carregar dados dos testes TIA'
            ]);
        }
    }

    /**
     * Método para obter estatísticas dos testes TIA.
     *
     * @return void
     */
    public function getEstatisticas(): void
    {
        header('Content-Type: application/json');
        
        try {
            $estatisticas = $this->tiaRepo->getEstatisticas();
            echo json_encode([
                'success' => true,
                'data' => $estatisticas
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Erro ao carregar estatísticas dos testes TIA'
            ]);
        }
    }
}
