<?php

namespace App\adms\Controllers\branches;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationBranchService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\BranchesRepository;
use App\adms\Models\Repository\LogsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para editar Filial
 *
 * Esta classe é responsável por gerenciar a edição de informações de uma Filial existente.
 */
class UpdateBranch
{
    private array|string|null $data = null;

    /**
     * Editar a Filial.
     *
     * @param int|string $id ID da Filial a ser editada.
     */
    public function index(int|string $id): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (isset($this->data['form']['csrf_token']) &&
            CSRFHelper::validateCSRFToken('form_update_branch', $this->data['form']['csrf_token']))
        {
            $this->editBranch();
        } else {
            $viewBranch = new BranchesRepository();
            $this->data['form'] = $viewBranch->getBranch((int) $id);

            if (!$this->data['form']) {
                GenerateLog::generateLog("error", "Filial não encontrada", ['id' => (int) $id]);
                $_SESSION['error'] = "Filial não encontrada!";
                header("Location: {$_ENV['URL_ADM']}list-branches");
                return;
            }

            $this->viewBranch();
        }
    }

    /**
     * Carregar a visualização para edição da Filial.
     */
    private function viewBranch(): void
    {
        $pageElements = [
            'title_head' => 'Editar Filial',
            'menu' => 'list-branches',
            'buttonPermission' => ['ListBranches', 'ViewBranch'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/branches/update", $this->data);
        $loadView->loadView();
    }

    /**
     * Editar a Filial.
     */
    private function editBranch(): void
    {
        $validationBranch = new ValidationBranchService();
        $this->data['errors'] = $validationBranch->validate($this->data['form']);

        if (!empty($this->data['errors'])) {
            $this->viewBranch();
            return;
        }

        $branchUpdate = new BranchesRepository();
        $result = $branchUpdate->updateBranch($this->data['form']);

        if ($result) {
            if ($_ENV['APP_LOGS'] == 'Sim') {
                $dataLogs = [
                    'table_name' => 'adms_branches',
                    'action' => 'edição',
                    'record_id' => $this->data['form']['id'],
                    'description' => $this->data['form']['name'],
                ];
                $insertLogs = new LogsRepository();
                $insertLogs->insertLogs($dataLogs);
            }

            $_SESSION['success'] = "Filial editada com sucesso!";
            header("Location: {$_ENV['URL_ADM']}view-branch/{$this->data['form']['id']}");
        } else {
            $this->data['errors'][] = "Filial não editada!";
            $this->viewBranch();
        }
    }
} 