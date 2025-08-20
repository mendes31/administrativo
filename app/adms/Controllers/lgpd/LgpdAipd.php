<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdAipdRepository;
use App\adms\Models\Repository\LgpdRopaRepository;
use App\adms\Models\Repository\LgpdDataGroupsRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller responsável pela gestão de AIPD (Avaliação de Impacto à Proteção de Dados).
 *
 * Esta classe gerencia as avaliações de impacto, trabalhando com grupos de dados
 * para manter consistência com a estrutura do projeto.
 *
 * @package App\adms\Controllers\lgpd
 */
class LgpdAipd
{
    private array|string|null $data = null;

    public function index(): void
    {
        $filters = [
            'departamento_id' => $_GET['departamento_id'] ?? '',
            'status' => $_GET['status'] ?? '',
            'nivel_risco' => $_GET['nivel_risco'] ?? '',
            'titulo' => $_GET['titulo'] ?? ''
        ];
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;

        $repo = new LgpdAipdRepository();
        $departmentsRepo = new DepartmentsRepository();
        
        $this->data['departamentos'] = $departmentsRepo->getAllDepartmentsSelect();
        $this->data['registros'] = $repo->getAll($filters, $page, $perPage);
        $total = $repo->getAmountAipd($filters);

        $pagination = \App\adms\Controllers\Services\PaginationService::generatePagination(
            $total,
            $perPage,
            $page,
            'lgpd-aipd',
            array_filter([
                'departamento_id' => $filters['departamento_id'],
                'status' => $filters['status'],
                'nivel_risco' => $filters['nivel_risco'],
                'titulo' => $filters['titulo'],
                'per_page' => $perPage
            ])
        );
        $this->data['paginator'] = $pagination['html'];

        $pageElements = [
            'title_head' => 'Avaliação de Impacto à Proteção de Dados (AIPD)',
            'menu' => 'lgpd-aipd',
            'buttonPermission' => ['ListLgpdAipd', 'CreateLgpdAipd', 'EditLgpdAipd', 'ViewLgpdAipd', 'DeleteLgpdAipd'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/aipd/list", $this->data);
        $loadView->loadView();
    }

    public function create(): void
    {
        $this->data['form'] = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->data['form'] = $_POST;
            
            $repo = new LgpdAipdRepository();
            $result = $repo->create($this->data['form']);
            
            if ($result) {
                $_SESSION['success'] = "AIPD cadastrada com sucesso!";
                header("Location: " . $_ENV['URL_ADM'] . "lgpd-aipd");
                exit;
            } else {
                $this->data['errors'][] = "AIPD não cadastrada!";
            }
        }

        // Carregar dados para os selects
        $departmentsRepo = new DepartmentsRepository();
        $ropaRepo = new LgpdRopaRepository();
        $dataGroupsRepo = new LgpdDataGroupsRepository();
        $usersRepo = new UsersRepository();
        
        $this->data['departamentos'] = $departmentsRepo->getAllDepartmentsSelect();
        $this->data['ropas'] = $ropaRepo->getAllRopaForSelect();
        $this->data['data_groups'] = $dataGroupsRepo->getAllDataGroupsForSelect();
        $this->data['usuarios'] = $usersRepo->getAllUsersForSelect();

        $pageElements = [
            'title_head' => 'Cadastrar AIPD',
            'menu' => 'lgpd-aipd',
            'buttonPermission' => ['CreateLgpdAipd'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/aipd/create", $this->data);
        $loadView->loadView();
    }

    public function view(string|int|null $id = null): void
    {
        if (empty($id)) {
            $_SESSION['error'] = "AIPD não encontrada!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-aipd");
            exit;
        }

        $repo = new LgpdAipdRepository();
        $aipd = $repo->getAipdById($id);

        if (!$aipd) {
            $_SESSION['error'] = "AIPD não encontrada!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-aipd");
            exit;
        }

        $this->data['aipd'] = $aipd;
        $this->data['data_groups'] = $repo->getDataGroupsByAipdId($id);

        $pageElements = [
            'title_head' => 'Visualizar AIPD',
            'menu' => 'lgpd-aipd',
            'buttonPermission' => ['ViewLgpdAipd'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/aipd/view", $this->data);
        $loadView->loadView();
    }

    public function edit(string|int|null $id = null): void
    {
        if (empty($id)) {
            $_SESSION['error'] = "AIPD não encontrada!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-aipd");
            exit;
        }

        $repo = new LgpdAipdRepository();
        $aipd = $repo->getAipdById($id);

        if (!$aipd) {
            $_SESSION['error'] = "AIPD não encontrada!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-aipd");
            exit;
        }

        $this->data['aipd'] = $aipd;
        $this->data['form'] = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->data['form'] = $_POST;
            
            $result = $repo->update($id, $this->data['form']);
            
            if ($result) {
                $_SESSION['success'] = "AIPD editada com sucesso!";
                header("Location: " . $_ENV['URL_ADM'] . "lgpd-aipd");
                exit;
            } else {
                $this->data['errors'][] = "AIPD não editada!";
            }
        }

        // Carregar dados para os selects
        $departmentsRepo = new DepartmentsRepository();
        $ropaRepo = new LgpdRopaRepository();
        $dataGroupsRepo = new LgpdDataGroupsRepository();
        $usersRepo = new UsersRepository();
        
        $this->data['departamentos'] = $departmentsRepo->getAllDepartmentsSelect();
        $this->data['ropas'] = $ropaRepo->getAllRopaForSelect();
        $this->data['data_groups'] = $dataGroupsRepo->getAllDataGroupsForSelect();
        $this->data['usuarios'] = $usersRepo->getAllUsersForSelect();
        $this->data['selected_data_groups'] = $repo->getDataGroupsByAipdId($id);

        $pageElements = [
            'title_head' => 'Editar AIPD',
            'menu' => 'lgpd-aipd',
            'buttonPermission' => ['EditLgpdAipd'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/aipd/edit", $this->data);
        $loadView->loadView();
    }

    public function delete(string|int|null $id = null): void
    {
        if (empty($id)) {
            $_SESSION['error'] = "AIPD não encontrada!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-aipd");
            exit;
        }

        $repo = new LgpdAipdRepository();
        $result = $repo->delete($id);

        if ($result) {
            $_SESSION['success'] = "AIPD excluída com sucesso!";
        } else {
            $_SESSION['error'] = "AIPD não excluída!";
        }

        header("Location: " . $_ENV['URL_ADM'] . "lgpd-aipd");
        exit;
    }
}
