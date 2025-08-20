<?php

namespace App\adms\Controllers\banks;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\BanksRepository;
use App\adms\Models\Repository\LogsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar um Banco
 *
 * Esta classe é responsável por exibir as informações detalhadas de um Banco específico. Ela recupera os dados
 * do Banco a partir do repositório, valida se o Banco existe e carrega a visualização apropriada. Se o Banco
 * não for encontrado, uma mensagem de erro é exibida e o Banco é redirecionado para a página de lista.
 *
 * @package App\adms\Controllers\banks
 * @author Rafael Mendes de Oliveira
 */
class ViewBank
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes do Banco.
     *
     * Este método gerencia a recuperação e exibição dos detalhes de um Banco específico. Ele valida o ID fornecido,
     * recupera os dados do Banco do repositório e carrega a visualização. Se o Banco não for encontrado, registra
     * um erro, exibe uma mensagem e redireciona para a página de lista de Banco.
     *
     * @param int|string $id ID do Banco a ser visualizado.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Validar se o ID é um valor inteiro
        if (!(int) $id) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Banco não encontrado", ['id' => (int) $id]);
            $_SESSION['error'] = "Banco não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-banks");
            return;
        }

        // Instanciar o Repository para recuperar o registro do banco de dados
        $viewBanks = new BanksRepository();
        $this->data['banks'] = $viewBanks->getBank((int) $id);
        // var_dump($this->data['banks']);
        // exit;


        // Verificar se encontrou o registro no banco de dados
        if (!$this->data['banks']) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Banco não encontrado", ['id' => (int) $id]);
            $_SESSION['error'] = "Banco não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-banks");
            return;
        }

        // Registrar a visualização do Banco
        GenerateLog::generateLog("info", "Visualizado o Banco.", ['id' => (int) $id]);

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Visualizar Banco',
            'menu' => 'list-banks',
            'buttonPermission' => ['ListBanks', 'UpdateBank', 'DeleteBank'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // gravar logs na tabela adms-logs
        if ($_ENV['APP_LOGS'] == 'Sim') {
            $dataLogs = [
                'table_name' => 'adms_bank_accounts',
                'action' => 'visualização',
                'record_id' => $this->data['banks']['id'],
                'description' => $this->data['banks']['bank_name'],

            ];
            // Instanciar a classe validar  o usuário
            $insertLogs = new LogsRepository();
            $insertLogs->insertLogs($dataLogs);
        }

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/banks/view", $this->data);
        $loadView->loadView();
    }
}
