<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\LgpdDataMappingRepository;
use App\adms\Models\Repository\LgpdRopaRepository;
use App\adms\Models\Repository\LgpdInventoryRepository;
use App\adms\Models\Repository\LgpdFontesColetaRepository;
use App\adms\Views\Services\LoadViewService;

class LgpdDataMappingCreate
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_create_data_mapping', $this->data['form']['csrf_token'])) {
            $this->addDataMapping();
        } else {
            $this->viewCreate();
        }
    }

    private function viewCreate(): void
    {
        $ropaRepo = new LgpdRopaRepository();
        $inventoryRepo = new LgpdInventoryRepository();
        $fontesRepo = new LgpdFontesColetaRepository();
        
        $this->data['ropas'] = $ropaRepo->getAll([], 1, 1000); // Para select
        $this->data['inventarios'] = $inventoryRepo->getAll([], 1, 1000); // Para select
        $this->data['fontes_coleta'] = $fontesRepo->listAllActive(); // Para select

        $pageElements = [
            'title_head' => 'Cadastrar Data Mapping',
            'menu' => 'lgpd-data-mapping',
            'buttonPermission' => ['ListLgpdDataMapping'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/data-mapping/create", $this->data);
        $loadView->loadView();
    }

    private function addDataMapping(): void
    {
        // Aqui você pode adicionar validação de campos se desejar
        $repo = new LgpdDataMappingRepository();
        $fontesRepo = new LgpdFontesColetaRepository();
        
        $result = $repo->create($this->data['form']);
        if ($result) {
            // Salvar fontes de coleta selecionadas
            if (!empty($this->data['form']['fontes_coleta'])) {
                $fontesIds = $this->data['form']['fontes_coleta'];
                $observacoes = $this->data['form']['observacoes_fontes'] ?? [];
                $fontesRepo->saveFontesForDataMapping($result, $fontesIds, $observacoes);
            }
            
            $_SESSION['success'] = "Data Mapping cadastrado com sucesso!";
            header("Location: {$_ENV['URL_ADM']}lgpd-data-mapping-view/$result");
            return;
        } else {
            $this->data['errors'][] = "Data Mapping não cadastrado!";
            $this->viewCreate();
        }
    }
}