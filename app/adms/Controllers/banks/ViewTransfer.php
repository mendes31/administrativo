<?php

namespace App\adms\Controllers\banks;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\LogsRepository;
use App\adms\Models\Repository\MovBetweenAccountsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar um transferência entre contas
 *
 * Esta classe é responsável por exibir as informações detalhadas de uma transferência entre contas específica. Ela recupera os dados
 * da transferência entre contas a partir do repositório, valida se a transferência entre contas existe e carrega a visualização apropriada. Se a transferência entre contas
 * não for encontrada, uma mensagem de erro é exibida e a transferência entre contas é redirecionada para a página de lista.
 *
 * @package App\adms\Controllers\banks
 * @author Rafael Mendes de Oliveira
 */
class ViewTransfer
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes da transferência entre contas.
     *
     * Este método gerencia a recuperação e exibição dos detalhes de uma transferência entre contas específica. Ele valida o ID fornecido,
     * recupera os dados da transferência entre contas do repositório e carrega a visualização. Se a transferência entre contas não for encontrada, registra
     * um erro, exibe uma mensagem e redireciona para a página de lista de transferência entre contas.
     *
     * @param int|string $id ID da transferência entre contas a ser visualizado.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Validar se o ID é um valor inteiro
        if (!(int) $id) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Transferência entre contas não encontrada", ['id' => (int) $id]);
            $_SESSION['error'] = "Transferência entre contas não encontrada!";
            header("Location: {$_ENV['URL_ADM']}list-mov-between-accounts");
            return;
        }

        // Instanciar o Repository para recuperar o registro do banco de dados
        $viewTransfer = new MovBetweenAccountsRepository();
        $this->data['transfer'] = $viewTransfer->getMovBetweenAccounts((int) $id);
        // var_dump($this->data['transfer']);
        // exit;


        // Verificar se encontrou o registro no banco de dados
        if (!$this->data['transfer']) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Transferência entre contas não encontrada", ['id' => (int) $id]);
            $_SESSION['error'] = "Transferência entre contas não encontrada!";
            header("Location: {$_ENV['URL_ADM']}list-mov-between-accounts");
            return;
        }

        // Registrar a visualização do Banco
        GenerateLog::generateLog("info", "Visualizado a transferência entre contas.", ['id' => (int) $id]);

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Visualizar Transferência entre Contas',
            'menu' => 'list-mov-between-accounts',
            'buttonPermission' => ['ListMovBetweenAccounts', 'UpdateMovBetweenAccounts', 'DeleteMovBetweenAccounts', 'MovBetweenAccounts'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        //var_dump($this->data);
        //exit;
        // gravar logs na tabela adms-logs
        if ($_ENV['APP_LOGS'] == 'Sim') {
            $dataLogs = [
                'table_name' => 'adms_mov_between_accounts',
                'action' => 'visualização',
                'record_id' => $this->data['transfer']['id'],
                'description' => $this->data['transfer']['description'],
            ];
            // Instanciar a classe validar  o usuário
            $insertLogs = new LogsRepository();
            $insertLogs->insertLogs($dataLogs);
        }

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/banks/viewTransfer", $this->data);
        $loadView->loadView();
    }
}
