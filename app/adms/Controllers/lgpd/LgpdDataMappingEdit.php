<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\LgpdDataMappingRepository;
use App\adms\Models\Repository\LgpdRopaRepository;
use App\adms\Models\Repository\LgpdInventoryRepository;
use App\adms\Models\Repository\LgpdFontesColetaRepository;
use App\adms\Views\Services\LoadViewService;

class LgpdDataMappingEdit
{
    private array|string|null $data = null;

    public function index($id): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_edit_data_mapping', $this->data['form']['csrf_token'])) {
            $this->editDataMapping($id);
        } else {
            $repo = new LgpdDataMappingRepository();
            $this->data['form'] = $repo->getById($id);
            if (!$this->data['form']) {
                $_SESSION['error'] = "Data Mapping não encontrado!";
                header("Location: {$_ENV['URL_ADM']}lgpd-data-mapping");
                return;
            }
            $this->viewEdit();
        }
    }

    private function viewEdit(): void
    {
        $ropaRepo = new LgpdRopaRepository();
        $inventoryRepo = new LgpdInventoryRepository();
        $fontesRepo = new LgpdFontesColetaRepository();
        
        $this->data['ropas'] = $ropaRepo->getAll([], 1, 1000); // Para select
        $this->data['inventories'] = $inventoryRepo->getAll([], 1, 1000); // Para select
        $this->data['fontes_coleta'] = $fontesRepo->listAllActive();
        
        // Carregar fontes já associadas a este data mapping
        $this->data['fontes_data_mapping'] = $fontesRepo->getFontesByDataMapping($this->data['form']['id']);

        $pageElements = [
            'title_head' => 'Editar Data Mapping',
            'menu' => 'lgpd-data-mapping',
            'buttonPermission' => ['ListLgpdDataMapping', 'EditLgpdDataMapping'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/data-mapping/edit", $this->data);
        $loadView->loadView();
    }

    private function editDataMapping($id): void
    {
        $repo = new LgpdDataMappingRepository();
        $fontesRepo = new LgpdFontesColetaRepository();
        
        $result = $repo->update($this->data['form']);
        if ($result) {
            // Salvar fontes de coleta selecionadas
            $fontesIds = $this->data['form']['fontes_coleta'] ?? [];
            $observacoes = $this->data['form']['observacoes_fontes'] ?? [];
            $fontesRepo->saveFontesForDataMapping($id, $fontesIds, $observacoes);
            
            $_SESSION['success'] = "Data Mapping editado com sucesso!";
            header("Location: {$_ENV['URL_ADM']}lgpd-data-mapping");
            return;
        } else {
            $this->data['errors'][] = "Data Mapping não editado!";
            $this->viewEdit();
        }
    }
}