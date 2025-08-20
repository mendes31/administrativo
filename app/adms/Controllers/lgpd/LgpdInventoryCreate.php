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

class LgpdInventoryCreate
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Verificar se é uma requisição POST e se tem dados
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
            // Verificar CSRF token se existir
            if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_create_inventory', $this->data['form']['csrf_token'])) {
                $this->addInventory();
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
        $this->data['departamentos'] = $departmentsRepo->getAllDepartmentsSelect();

        // Carregar grupos de dados
        $dataGroupsRepo = new LgpdDataGroupsRepository();
        $this->data['grupos_dados'] = $dataGroupsRepo->getAllForSelect();

        // Carregar categorias de titulares
        $categoriasTitularesRepo = new LgpdCategoriasTitularesRepository();
        $this->data['categorias_titulares'] = $categoriasTitularesRepo->getActiveCategoriasTitulares();

        $pageElements = [
            'title_head' => 'Cadastrar Inventário',
            'menu' => 'ListLgpdInventory',
            'buttonPermission' => ['ListLgpdInventory'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/inventory/create", $this->data);
        $loadView->loadView();
    }

    public function addInventory(): void
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
            $result = $inventoryRepo->create($this->data['form']);
            
            if ($result) {
                $inventoryId = $result;
                
                // Associar grupos de dados selecionados com configurações individuais
                if (isset($this->data['form']['data_groups']) && is_array($this->data['form']['data_groups'])) {
                    $groupsWithConfig = [];
                    
                    foreach ($this->data['form']['data_groups'] as $groupId) {
                        $groupConfig = [
                            'id' => $groupId,
                            'risk_level' => $this->data['form']["group_risk_{$groupId}"] ?? 'Médio',
                            'data_category' => $this->data['form']["group_category_{$groupId}"] ?? 'Pessoal',
                            'notes' => $this->data['form']["group_notes_{$groupId}"] ?? ''
                        ];
                        $groupsWithConfig[] = $groupConfig;
                    }
                    
                    $inventoryRepo->associateDataGroups($inventoryId, $groupsWithConfig);
                }
                
                $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>Inventário cadastrado com sucesso!</div>";
                $urlRedirect = $_ENV['URL_ADM'] . "lgpd-inventory";
                header("Location: $urlRedirect");
                exit();
            } else {
                $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Erro: Inventário não cadastrado! Verifique os dados.</div>";
                $this->viewCreate();
            }
        } catch (Exception $e) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Erro: " . $e->getMessage() . "</div>";
            $this->viewCreate();
        }
    }
}