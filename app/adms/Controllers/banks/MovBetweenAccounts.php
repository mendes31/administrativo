<?php

namespace App\adms\Controllers\banks;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\MovBetweenAccountsRepository;
use App\adms\Models\Repository\LogsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para transferência entre contas
 *
 * Esta classe é responsável por gerenciar as transferências entre contas bancárias. Ela lida com a recepção dos dados do
 * formulário, validação dos mesmos e execução da transferência no sistema. Além disso, é responsável por carregar
 * a visualização apropriada com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\banks
 * @author Rafael Mendes
 */
class MovBetweenAccounts
{
    /** @var array|string|null $data Dados que serão enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Método principal que gerencia a transferência entre contas
     *
     * Este método é chamado para processar a transferência entre contas. Ele verifica a validade do token CSRF,
     * valida os dados do formulário e, se tudo estiver correto, executa a transferência. Caso contrário, carrega a
     * visualização de transferência com mensagens de erro.
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Verificar se o token CSRF é válido
        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_create_mov_between_accounts', $this->data['form']['csrf_token'])) {
            // Chamar o método para executar a transferência
            $this->transfer();
        } else {
            // Chamar o método para carregar a view de transferência
            $this->viewTransfer();
        }
    }

    /**
     * Carregar a visualização de transferência entre contas
     * 
     * Este método configura os dados necessários e carrega a view para a transferência entre contas.
     * 
     * @return void
     */
    private function viewTransfer(): void
    {
        $model = new MovBetweenAccountsRepository();
        $this->data['accounts'] = $model->getAllAccounts();
        // var_dump($this->data['accounts']);

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Transferência entre Contas',
            'menu' => 'list-mov-between-accounts',
            'buttonPermission' => ['ListMovBetweenAccounts'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/banks/move", $this->data);
        $loadView->loadView();
    }

    /**
     * Executa a transferência entre contas
     *
     * Este método processa a transferência entre contas após validação do token CSRF e dos dados do formulário.
     * 
     * @return void
     */
    private function transfer(): void
    {
        // Log início controller
        // echo "<pre>Controller: Início do método transfer</pre>";
        \App\adms\Helpers\GenerateLog::generateLog('debug', 'Controller: Início do método transfer', $_POST);

        $from = $this->data['form']['source_account_id'] ?? null;
        $to = $this->data['form']['destination_account_id'] ?? null;
        $amount = floatval($this->data['form']['value'] ?? 0);
        $description = $this->data['form']['description'] ?? null;  // <-- NOVA LINHA  
        // Log dos dados recebidos
        //echo "<pre>Controller: Dados recebidos: "; var_dump($from, $to, $amount); echo "</pre>";
        \App\adms\Helpers\GenerateLog::generateLog('debug', 'Controller: Dados recebidos', ['from' => $from, 'to' => $to, 'amount' => $amount]);

        if (empty($from) || empty($to) || $amount <= 0) {
            // echo "<pre>Controller: Dados inválidos para transferência</pre>";
            \App\adms\Helpers\GenerateLog::generateLog('debug', 'Controller: Dados inválidos para transferência', ['from' => $from, 'to' => $to, 'amount' => $amount]);
            $_SESSION['msg'] = "Erro: Dados inválidos para a transferência.";
            $this->viewTransfer();
            return;
        }

        if ($from === $to) {
            // echo "<pre>Controller: Transferência para mesma conta</pre>";
            \App\adms\Helpers\GenerateLog::generateLog('debug', 'Controller: Transferência para mesma conta', ['from' => $from, 'to' => $to]);
            $_SESSION['msg'] = "Erro: Não é possível transferir para a mesma conta.";
            $this->viewTransfer();
            return;
        }

        $model = new \App\adms\Models\Repository\MovBetweenAccountsRepository();
        $transferId = $model->transfer($from, $to, $amount, $description);

        // Log do resultado da transferência
        //echo "<pre>Controller: Resultado da transferência: "; var_dump($transferId); echo "</pre>";
        \App\adms\Helpers\GenerateLog::generateLog('debug', 'Controller: Resultado da transferência', ['transferId' => $transferId]);

        if ($transferId) {
            if ($_ENV['APP_LOGS'] == 'Sim') {
                $dataLogs = [
                    'table_name' => 'adms_bank_transfers',
                    'action' => 'transferência',
                    'record_id' => $transferId,
                    'description' => "Transferência de R$ " . number_format($amount, 2, ',', '.') . " entre contas",
                ];
                $insertLogs = new \App\adms\Models\Repository\LogsRepository();
                $insertLogs->insertLogs($dataLogs);
            }
            // echo "<pre>Controller: Transferência realizada com sucesso!</pre>";
            $_SESSION['success'] = "Transferência realizada com sucesso!";
            header("Location: " . $_ENV['URL_ADM'] . "list-mov-between-accounts");
            // exit;
        } else {
            // echo "<pre>Controller: Erro ao realizar transferência</pre>";
            $_SESSION['msg'] = "Erro: Saldo insuficiente ou dados inválidos.";
            $this->viewTransfer();
        }
    }
}