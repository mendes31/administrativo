<?php

namespace App\adms\Controllers\branches;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\BranchesRepository;
use App\adms\Models\Repository\LogsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar uma Filial
 *
 * Esta classe é responsável por exibir as informações detalhadas de uma Filial específica. Ela recupera os dados
 * da Filial a partir do repositório, valida se a Filial existe e carrega a visualização apropriada. Se a Filial
 * não for encontrada, uma mensagem de erro é exibida e o usuário é redirecionado para a página de lista.
 */
class ViewBranch
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes da Filial.
     *
     * @param int|string $id ID da Filial a ser visualizada.
     *
     * @return void
     */
    public function index(int|string $id): void
    {
        // Validar se o ID é um valor inteiro
        if (!(int) $id) {
            GenerateLog::generateLog("error", "Filial não encontrada", ['id' => (int) $id]);
            $_SESSION['error'] = "Filial não encontrada!";
            header("Location: {$_ENV['URL_ADM']}list-branches");
            return;
        }

        // Instanciar o Repository para recuperar o registro do banco de dados
        $viewBranches = new BranchesRepository();
        $this->data['branch'] = $viewBranches->getBranch((int) $id);

        // Verificar se encontrou o registro no banco de dados
        if (!$this->data['branch']) {
            GenerateLog::generateLog("error", "Filial não encontrada", ['id' => (int) $id]);
            $_SESSION['error'] = "Filial não encontrada!";
            header("Location: {$_ENV['URL_ADM']}list-branches");
            return;
        }

        // Registrar a visualização da Filial
        GenerateLog::generateLog("info", "Visualizada a Filial.", ['id' => (int) $id]);

        // Definir o título da página
        $pageElements = [
            'title_head' => 'Visualizar Filial',
            'menu' => 'list-branches',
            'buttonPermission' => ['ListBranches', 'UpdateBranch', 'DeleteBranch'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // gravar logs na tabela adms-logs
        if ($_ENV['APP_LOGS'] == 'Sim') {
            $dataLogs = [
                'table_name' => 'adms_branches',
                'action' => 'visualização',
                'record_id' => $this->data['branch']['id'],
                'description' => $this->data['branch']['name'],
            ];
            $insertLogs = new LogsRepository();
            $insertLogs->insertLogs($dataLogs);
        }

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/branches/view", $this->data);
        $loadView->loadView();
    }
} 