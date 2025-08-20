<?php

namespace App\adms\Controllers\receive;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\LogsRepository;
use App\adms\Models\Repository\PartialValuesRepository;
use App\adms\Models\Repository\PaymentsRepository;
use App\adms\Models\Repository\ReceiptsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar um Conta à Pagar
 *
 * Esta classe é responsável por exibir as informações detalhadas de um Conta à Pagar específico. Ela recupera os dados
 * do Conta à Pagar a partir do repositório, valida se o Conta à Pagar existe e carrega a visualização apropriada. Se o Conta à Pagar
 * não for encontrado, uma mensagem de erro é exibida e o Conta à Pagar é redirecionado para a página de lista.
 *
 * @package App\adms\Controllers\pay
 * @author Rafael Mendes de Oliveira
 */
class ViewReceive
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $dataPgto = null;

    /**
     * Recuperar os detalhes do Conta à Pagar.
     *
     * Este método gerencia a recuperação e exibição dos detalhes de um Conta à Pagar específico. Ele valida o ID fornecido,
     * recupera os dados do Conta à Pagar do repositório e carrega a visualização. Se o Conta à Pagar não for encontrado, registra
     * um erro, exibe uma mensagem e redireciona para a página de lista de Conta à Pagar.
     *
     * @param int|string $id ID do Conta à Pagar a ser visualizado.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {

        // Validar se o ID é um valor inteiro
        if (!(int) $id) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Conta à Receber não encontrada", ['id' => (int) $id]);
            $_SESSION['error'] = "Conta à Receber não encontrada!";
            header("Location: {$_ENV['URL_ADM']}list-receipts");
            return;
        }

        // Instanciar o Repository para recuperar o registro do Conta à Receber de dados
        $viewReceive = new ReceiptsRepository();
        $this->data['receive'] = $viewReceive->getReceive((int) $id);

        // Instanciar o Repository para recuperar o registro do Conta à Pagar de dados
        $viewMovementValues = new PartialValuesRepository();
        $this->data['movementValues'] = $viewMovementValues->getMovementValues((int) $id);

        // Verificar se encontrou o registro no Conta à Receber de dados
        if (!$this->data['receive']) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Conta à Receber não encontrado", ['id' => (int) $id]);
            $_SESSION['error'] = "Conta à Receber não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-receipts");
            return;
        }

        // Registrar a visualização do Conta à Pagar
        GenerateLog::generateLog("info", "Visualizado o Conta à Pagar.", ['id' => (int) $id]);

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Visualizar Conta à Receber',
            'menu' => 'list-receipts',
            'buttonPermission' => ['ListReceipts', 'UpdateReceive', 'DeleteMovementReceive', 'EditMovementReceive','DeleteMovement'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // gravar logs na tabela adms-logs
        if ($_ENV['APP_LOGS'] == 'Sim') {
            $dataLogs = [
                'table_name' => 'adms_receive',
                'action' => 'visualização',
                'record_id' => $this->data['receive']['id_receive'],
                'description' => $this->data['receive']['num_doc'],

            ];
            // Instanciar a classe validar  o usuário
            $insertLogs = new LogsRepository();
            $insertLogs->insertLogs($dataLogs);
        }

              

        // Atualizar o campo busy e user_temp
        $receiveRepo = new ReceiptsRepository();
        $receiveRepo->updateBusy((int) $id, $_SESSION['user_id']); // ou use o ID de usuário que tiver

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/receive/view", $this->data);
        $loadView->loadView();
    }
}

