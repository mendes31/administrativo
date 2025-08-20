<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\LgpdInventoryRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\LgpdDataGroupsRepository;
use App\adms\Models\Repository\LgpdCategoriasTitularesRepository;
use App\adms\Views\Services\LoadViewService;

class LgpdInventoryEdit
{
    private array|string|null $data = null;

    public function index($id): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_edit_inventory', $this->data['form']['csrf_token'])) {
            $this->editInventory($id);
        } else {
            $repo = new LgpdInventoryRepository();
            $this->data['inventory'] = $repo->getById($id);
            if (!$this->data['inventory']) {
                $_SESSION['error'] = "Inventário não encontrado!";
                header("Location: {$_ENV['URL_ADM']}lgpd-inventory");
                return;
            }
            $this->viewEdit($id);
        }
    }

    private function viewEdit($id): void
    {
        // Carregar departamentos
        $departmentsRepo = new DepartmentsRepository();
        $this->data['departamentos'] = $departmentsRepo->getAllDepartmentsSelect();

        // Carregar grupos de dados
        $dataGroupsRepo = new LgpdDataGroupsRepository();
        $this->data['grupos_dados'] = $dataGroupsRepo->getAllForSelect();

        // Carregar grupos de dados já associados ao inventário
        $inventoryRepo = new LgpdInventoryRepository();
        $this->data['grupos_selecionados'] = $inventoryRepo->getDataGroupsByInventoryId($id);

        // Carregar categorias de titulares
        $categoriasTitularesRepo = new LgpdCategoriasTitularesRepository();
        $this->data['categorias_titulares'] = $categoriasTitularesRepo->getActiveCategoriasTitulares();
        
        // Converter nome do titular para ID para preencher o select
        if (isset($this->data['inventory']['data_subject']) && !empty($this->data['inventory']['data_subject'])) {
            $titularEncontrado = null;
            foreach ($this->data['categorias_titulares'] as $categoria) {
                if ($categoria['titular'] === $this->data['inventory']['data_subject']) {
                    $titularEncontrado = $categoria;
                    break;
                }
            }
            if ($titularEncontrado) {
                $this->data['inventory']['data_subject_id'] = $titularEncontrado['id'];
            }
        }

        $pageElements = [
            'title_head' => 'Editar Inventário',
            'menu' => 'ListLgpdInventory',
            'buttonPermission' => ['ListLgpdInventory', 'EditLgpdInventory'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/inventory/edit", $this->data);
        $loadView->loadView();
    }

    public function editInventory(int $id): void
    {
        $formData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        
        if (isset($formData['SendEditInventory'])) {
            unset($formData['SendEditInventory']);
            
            // Converter ID do titular para nome
            if (isset($formData['data_subject']) && !empty($formData['data_subject'])) {
                $categoriasTitularesRepo = new LgpdCategoriasTitularesRepository();
                $titular = $categoriasTitularesRepo->getById($formData['data_subject']);
                if ($titular) {
                    $formData['data_subject'] = $titular['titular'];
                }
            }
            
            // Buscar nome do departamento para definir a área
            if (isset($formData['department_id']) && !empty($formData['department_id'])) {
                $departmentsRepo = new DepartmentsRepository();
                $department = $departmentsRepo->getDepartment($formData['department_id']);
                if ($department) {
                    $formData['area'] = $department['name'];
                }
            }
            
            $inventoryRepo = new LgpdInventoryRepository();
            $formData['id'] = $id; // Adicionar o ID do inventário
            $result = $inventoryRepo->update($formData);
            
            if ($result) {
                // Adicionar novos grupos de dados selecionados (preservando os existentes)
                if (isset($formData['data_groups']) && is_array($formData['data_groups'])) {
                    $inventoryRepo->addDataGroups($id, $formData['data_groups']);
                }
                
                $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>Inventário atualizado com sucesso!</div>";
                $urlRedirect = $_ENV['URL_ADM'] . "lgpd-inventory-view/$id";
                header("Location: $urlRedirect");
                exit();
            } else {
                $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Erro: Inventário não atualizado!</div>";
                // Preservar os dados do formulário para re-exibir
                $this->data['form'] = $formData;
                $this->viewEdit($id);
            }
        } else {
            $this->viewEdit($id);
        }
    }
}