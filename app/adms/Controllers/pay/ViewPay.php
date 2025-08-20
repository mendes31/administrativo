<?php

namespace App\adms\Controllers\pay;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\LogsRepository;
use App\adms\Models\Repository\PartialValuesRepository;
use App\adms\Models\Repository\PaymentsRepository;
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
class ViewPay
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
            GenerateLog::generateLog("error", "Conta à Pagar não encontrada", ['id' => (int) $id]);
            $_SESSION['error'] = "Conta à Pagar não encontrada!";
            header("Location: {$_ENV['URL_ADM']}list-payments");
            return;
        }

        // Instanciar o Repository para recuperar o registro do Conta à Pagar de dados
        $viewPay = new PaymentsRepository();
        $this->data['pay'] = $viewPay->getPay((int) $id);

        // Instanciar o Repository para recuperar o registro do Conta à Pagar de dados
        $viewMovementValues = new PartialValuesRepository();
        $this->data['movementValues'] = $viewMovementValues->getMovementValues((int) $id);

        // Verificar se encontrou o registro no Conta à Pagar de dados
        if (!$this->data['pay']) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Conta à Pagar não encontrado", ['id' => (int) $id]);
            $_SESSION['error'] = "Conta à Pagar não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-payments");
            return;
        }

        // Registrar a visualização do Conta à Pagar
        GenerateLog::generateLog("info", "Visualizado o Conta à Pagar.", ['id' => (int) $id]);

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Visualizar Conta à Pagar',
            'menu' => 'list-payments',
            'buttonPermission' => ['ListPayments', 'UpdatePay', 'DeletePay', 'EditMovement','DeleteMovement'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // gravar logs na tabela adms-logs
        if ($_ENV['APP_LOGS'] == 'Sim') {
            $dataLogs = [
                'table_name' => 'adms_pay',
                'action' => 'visualização',
                'record_id' => $this->data['pay']['id_pay'],
                'description' => $this->data['pay']['num_doc'],

            ];
            // Instanciar a classe validar  o usuário
            $insertLogs = new LogsRepository();
            $insertLogs->insertLogs($dataLogs);
        }

              

        // Atualizar o campo busy e user_temp
        $payRepo = new PaymentsRepository();
        $payRepo->updateBusy((int) $id, $_SESSION['user_id']); // ou use o ID de usuário que tiver

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/pay/view", $this->data);
        $loadView->loadView();
    }
}

