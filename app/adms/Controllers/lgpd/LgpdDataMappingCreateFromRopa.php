<?php
namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\LgpdRopaRepository;
use App\adms\Models\Repository\LgpdDataMappingRepository;
use App\adms\Models\Repository\LgpdInventoryRepository;
use App\adms\Views\Services\LoadViewService;

class LgpdDataMappingCreateFromRopa
{
    private array|string|null $data = null;

    public function index(): void
    {
        $urlParts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
        $ropaId = end($urlParts);
        
        if (empty($ropaId) || !is_numeric($ropaId)) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>ID da ROPA não fornecido!</div>";
            $urlRedirect = $_ENV['URL_ADM'] . "lgpd-ropa";
            header("Location: $urlRedirect");
            exit();
        }
        
        $ropaId = (int) $ropaId;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->createDataMappingFromRopa($ropaId);
        } else {
            $this->viewCreateFromRopa($ropaId);
        }
    }
    
    /**
     * Exibir formulário para criar Data Mapping a partir da ROPA
     */
    private function viewCreateFromRopa(int $ropaId): void
    {
        $ropaRepo = new LgpdRopaRepository();
        $ropa = $ropaRepo->getById($ropaId);
        
        if (!$ropa) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>ROPA não encontrada!</div>";
            $urlRedirect = $_ENV['URL_ADM'] . "lgpd-ropa";
            header("Location: $urlRedirect");
            exit();
        }
        
        // Buscar dados do inventário relacionado
        $inventoryRepo = new LgpdInventoryRepository();
        $inventory = null;
        if ($ropa['inventory_id']) {
            $inventory = $inventoryRepo->getById($ropa['inventory_id']);
        }
        
        // Sugerir fluxos técnicos baseados na ROPA
        $dataMappingRepo = new LgpdDataMappingRepository();
        $suggestedFlows = $dataMappingRepo->suggestTechnicalFlows($ropa, $inventory);
        
        $this->data['ropa'] = $ropa;
        $this->data['inventory'] = $inventory;
        $this->data['suggested_flows'] = $suggestedFlows;
        $this->data['prefilled_data'] = [
            'ropa_id' => $ropaId,
            'inventory_id' => $ropa['inventory_id'] ?? null,
            'source_system' => 'Sistema Principal',
            'source_field' => 'Dados do titular',
            'transformation_rule' => 'Validação e formatação',
            'destination_system' => 'Sistema de Processamento',
            'destination_field' => 'Dados processados',
            'observation' => 'Fluxo técnico baseado na ROPA'
        ];
        
        $pageElements = [
            'title_head' => 'Criar Data Mapping a partir da ROPA',
            'menu' => 'ListLgpdDataMapping',
            'buttonPermission' => ['ListLgpdDataMapping'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService("adms/Views/lgpd/data-mapping/create-from-ropa", $this->data);
        $loadView->loadView();
    }
    
    /**
     * Criar Data Mapping a partir da ROPA
     */
    private function createDataMappingFromRopa(int $ropaId): void
    {
        $formData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        
        if (isset($formData['SendAddDataMapping'])) {
            unset($formData['SendAddDataMapping']);
            
            // Adicionar ID da ROPA
            $formData['ropa_id'] = $ropaId;
            
            $dataMappingRepo = new LgpdDataMappingRepository();
            $mappingId = $dataMappingRepo->create($formData);
            
            if ($mappingId) {
                $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>Data Mapping criado com sucesso!</div>";
                $urlRedirect = $_ENV['URL_ADM'] . "lgpd-data-mapping-view/$mappingId";
                header("Location: $urlRedirect");
                exit();
            } else {
                $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Erro: Data Mapping não foi criado!</div>";
                $this->viewCreateFromRopa($ropaId);
            }
        } else {
            $this->viewCreateFromRopa($ropaId);
        }
    }
} 