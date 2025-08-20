<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\LgpdInventoryRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\LgpdDataGroupsRepository;
use App\adms\Models\Repository\LgpdCategoriasTitularesRepository;
use App\adms\Views\Services\LoadViewService;
use Exception;

class LgpdInventoryCreateNew
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Verificar se é uma requisição POST e se tem dados
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
            // Verificar CSRF token se existir
            if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_create_inventory_new', $this->data['form']['csrf_token'])) {
                $this->addInventoryGroup();
            } else {
                // Se não tem CSRF válido, mostrar erro mas não bloquear completamente
                $_SESSION['msg'] = "<div class='alert alert-warning' role='alert'>Aviso: Token de segurança inválido. Tente novamente.</div>";
                $this->viewCreate();
            }
        } else {
            $this->viewCreate();
        }
    }

    private function viewCreate(): void
    {
        // Carregar departamentos
        $departmentsRepo = new DepartmentsRepository();
        $this->data['departments'] = $departmentsRepo->getAllDepartmentsSelect();

        // Carregar grupos de dados
        $dataGroupsRepo = new LgpdDataGroupsRepository();
        $this->data['data_groups'] = $dataGroupsRepo->getAllForSelect();

        // Carregar categorias de titulares
        $categoriasTitularesRepo = new LgpdCategoriasTitularesRepository();
        $this->data['categorias_titulares'] = $categoriasTitularesRepo->getActiveCategoriasTitulares();

        // Carregar inventários existentes para mostrar grupos já cadastrados
        $inventoryRepo = new LgpdInventoryRepository();
        $this->data['existing_inventories'] = $inventoryRepo->getAll();

        $pageElements = [
            'title_head' => 'Cadastrar Inventário LGPD',
            'menu' => 'ListLgpdInventory',
            'buttonPermission' => ['ListLgpdInventory'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/inventory/create-new", $this->data);
        $loadView->loadView();
    }

    public function addInventoryGroup(): void
    {
        try {
            // Converter ID do titular para nome
            if (isset($this->data['form']['data_subject']) && !empty($this->data['form']['data_subject'])) {
                $categoriasTitularesRepo = new LgpdCategoriasTitularesRepository();
                $titular = $categoriasTitularesRepo->getById($this->data['form']['data_subject']);
                if ($titular) {
                    $this->data['form']['data_subject'] = $titular['titular'];
                }
            }
            
            // Buscar nome do departamento para definir a área
            if (isset($this->data['form']['department_id']) && !empty($this->data['form']['department_id'])) {
                $departmentsRepo = new DepartmentsRepository();
                $department = $departmentsRepo->getDepartment($this->data['form']['department_id']);
                if ($department) {
                    $this->data['form']['area'] = $department['name'];
                }
            }
            
            // Definir valores padrão se não existirem
            if (!isset($this->data['form']['data_type']) || empty($this->data['form']['data_type'])) {
                $this->data['form']['data_type'] = 'Dados do inventário';
            }
            
            $inventoryRepo = new LgpdInventoryRepository();
            
            // Verificar se já existe um inventário para esta combinação de departamento + titular
            $existingInventory = $this->findExistingInventory(
                $this->data['form']['department_id'],
                $this->data['form']['data_subject'],
                $this->data['form']['storage_location'],
                $this->data['form']['access_level']
            );
            
            $inventoryId = null;
            
            if ($existingInventory) {
                // Usar inventário existente
                $inventoryId = $existingInventory['id'];
            } else {
                // Criar novo inventário
                $result = $inventoryRepo->create($this->data['form']);
                if ($result) {
                    $inventoryId = $result;
                } else {
                    throw new Exception("Erro ao criar inventário");
                }
            }
            
            // Associar o grupo de dados selecionado
            if (isset($this->data['form']['data_group_id']) && !empty($this->data['form']['data_group_id'])) {
                $groupConfig = [
                    'id' => $this->data['form']['data_group_id'],
                    'risk_level' => $this->data['form']['risk_level'] ?? 'Médio',
                    'data_category' => $this->data['form']['data_category'] ?? 'Pessoal',
                    'notes' => $this->data['form']['notes'] ?? ''
                ];
                
                $inventoryRepo->associateDataGroups($inventoryId, [$groupConfig]);
            }
            
            $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>Grupo de dados adicionado com sucesso!</div>";
            $urlRedirect = $_ENV['URL_ADM'] . "lgpd-inventory-create-new";
            header("Location: $urlRedirect");
            exit();
            
        } catch (Exception $e) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Erro: " . $e->getMessage() . "</div>";
            $this->viewCreate();
        }
    }
    
    private function findExistingInventory($departmentId, $dataSubject, $storageLocation, $accessLevel): ?array
    {
        $inventoryRepo = new LgpdInventoryRepository();
        $inventories = $inventoryRepo->getAll();
        
        foreach ($inventories as $inventory) {
            if ($inventory['department_id'] == $departmentId &&
                $inventory['data_subject'] == $dataSubject &&
                $inventory['storage_location'] == $storageLocation &&
                $inventory['access_level'] == $accessLevel) {
                return $inventory;
            }
        }
        
        return null;
    }
} 