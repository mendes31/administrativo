<?php

namespace App\adms\Controllers\pay;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\BanksRepository;
use App\adms\Models\Repository\LogsRepository;
use App\adms\Models\Repository\PaymentsRepository;

/**
 * Controller para exclusão de Conta à Pagar
 *
 * Esta classe gerencia o processo de exclusão de Conta à Pagar no sistema. Ela lida com a validação dos dados
 * do formulário, a exclusão do Conta à Pagar do Conta à Pagar de dados e o registro de logs para operações bem-sucedidas ou
 * falhas. Além disso, redireciona o Conta à Pagar para a página de listagem de Conta à Pagars com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\pay
 * @author Rafael Mendes de Oliveira
 */
class DeletePay
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes do departamento e processar a exclusão.
     *
     * Este método verifica a validade do token CSRF e a existência do ID do departamento.  Se válido, recupera os
     * detalhes do departamento do Conta à Pagar de dados e tenta excluir o departamento.  Redireciona o departamento para a página de 
     * listagem de departamentos com mensagens apropriadas baseadas no sucesso ou falha da operação.
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Verificar a validade do token CSRF e a existência do ID do Conta à Pagar
        if (
            !isset($this->data['form']['csrf_token'])
            || !CSRFHelper::validateCSRFToken('form_delete_pay', $this->data['form']['csrf_token'])
            || !isset($this->data['form']['id'])
        ) {

            // Registrar um log de erro
            GenerateLog::generateLog("error", "Conta à Pagar não encontrado.", []);

            // Criar a mensagem de erro e redirecionar
            $_SESSION['error'] = "Conta à Pagar não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-payments");
            return;
        }

        // Instanciar o Repository para recuperar o Conta à Pagar
        $deletePay = new PaymentsRepository();
        $this->data['pay'] = $deletePay->getPay((int) $this->data['form']['id']);

        // var_dump($this->data['pay']);
        // exit;

        // // Verificar se o Conta à Pagar foi encontrado
        // if (!$this->data['pay']) {

        //     // Registrar um log de erro
        //     GenerateLog::generateLog("error", "Conta à Pagar não encontrado.", ['id' => (int) $this->data['form']['id']]);

        //     // Criar a mensagem de erro e redirecionar
        //     $_SESSION['error'] = "Conta à Pagar não encontrado!";
        //     header("Location: {$_ENV['URL_ADM']}list-payments");
        //     return;
        // }

        // Verificar se o Conta à Pagar foi encontrado
        if (!$this->data['pay']) {

            // Registrar um log de erro
            GenerateLog::generateLog("error", "Conta à Pagar não encontrado.", ['id' => (int) $this->data['form']['id']]);

            // Criar a mensagem de erro e redirecionar
            $_SESSION['error'] = "Conta à Pagar não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-payments");
            return;
        }

        // Verificar se o campo 'paid' é igual a 0 antes de deletar
        if ($this->data['pay']['paid'] != 0) {

            // Registrar um log de erro
            GenerateLog::generateLog("error", "Tentativa de exclusão de Conta à Pagar já paga.", ['id' => (int) $this->data['form']['id']]);

            // Criar a mensagem de erro e redirecionar
            $_SESSION['error'] = "Não é possível excluir uma Conta à Pagar que já foi paga!";
            header("Location: {$_ENV['URL_ADM']}list-payments");
            return;
        }

        // Tentar excluir o Conta à Pagar
        $result = $deletePay->deletePay($this->data['form']['id']);

        // Verificar se a exclusão foi bem-sucedida
        if ($result) {

            // gravar logs na tabela adms-logs
            if ($_ENV['APP_LOGS'] == 'Sim') {
                $dataLogs = [
                    'table_name' => 'adms_pay',
                    'action' => 'exclusão',
                    'record_id' => $result,
                    'description' => $this->data['form']['num_doc'],

                ];
                // Instanciar a classe validar  o usuário
                $insertLogs = new LogsRepository();
                $insertLogs->insertLogs($dataLogs);
            }

            // Criar a mensagem de sucesso
            $_SESSION['success'] = "Conta à Pagar excluida com sucesso!";
        } else {
            // Criar a mensagem de erro
            $_SESSION['error'] = "Conta à Pagar não excluida!";
        }

        // Redirecionar para a página de listagem de Conta à Pagar
        header("Location: {$_ENV['URL_ADM']}list-payments");
        return;
    }
}
