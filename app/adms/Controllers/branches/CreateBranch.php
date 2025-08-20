<?php

namespace App\adms\Controllers\branches;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationBranchService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\BranchesRepository;
use App\adms\Models\Repository\LogsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para criação de filial
 *
 * Esta classe é responsável pelo processo de criação de novas Filiais. Ela lida com a recepção dos dados do
 * formulário, validação dos mesmos, e criação da Filial no sistema. Além disso, é responsável por carregar
 * a visualização apropriada com mensagens de sucesso ou erro.
 */
class CreateBranch
{
    /** @var array|string|null $data Dados que serão enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Método principal que gerencia a criação da Filial.
     *
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Verificar se o token CSRF é válido
        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_create_branch', $this->data['form']['csrf_token'])) {
            $this->addBranch();
        } else {
            $this->viewBranch();
        }
    }

    /**
     * Carregar a visualização de criação da Filial.
     *
     * @return void
     */
    private function viewBranch(): void
    {
        $pageElements = [
            'title_head' => 'Cadastrar Filial',
            'menu' => 'list-branches',
            'buttonPermission' => ['ListBranches'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/branches/create", $this->data);
        $loadView->loadView();
    }

    /**
     * Adicionar uma nova Filial ao sistema.
     *
     * @return void
     */
    private function addBranch(): void
    {
        $validationBranch = new ValidationBranchService();
        $this->data['errors'] = $validationBranch->validate($this->data['form']);

        if (!empty($this->data['errors'])) {
            $this->viewBranch();
            return;
        }

        $branchCreate = new BranchesRepository();
        $result = $branchCreate->createBranch($this->data['form']);

        if ($result) {
            if ($_ENV['APP_LOGS'] == 'Sim') {
                $dataLogs = [
                    'table_name' => 'adms_branches',
                    'action' => 'inserção',
                    'record_id' => $result,
                    'description' => $this->data['form']['name'],
                ];
                $insertLogs = new LogsRepository();
                $insertLogs->insertLogs($dataLogs);
            }
            $_SESSION['success'] = "Filial cadastrada com sucesso!";
            header("Location: {$_ENV['URL_ADM']}view-branch/$result");
            return;
        } else {
            $this->data['errors'][] = "Filial não cadastrada!";
            $this->viewBranch();
        }
    }
} 