<?php

namespace App\adms\Controllers\receive;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\LogsRepository;
use App\adms\Models\Repository\ReceiptsRepository;

/**
 * Controller para exclusão de Conta à Pagar
 *
 * Esta classe gerencia o processo de exclusão de Conta à Pagar no sistema. Ela lida com a validação dos dados
 * do formulário, a exclusão do Conta à Pagar do Conta à Pagar de dados e o registro de logs para operações bem-sucedidas ou
 * falhas. Além disso, redireciona o Conta à Pagar para a página de listagem de Conta à Pagars com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\receive;
 * @author Rafael Mendes de Oliveira
 */
class DeleteReceive
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes do Conta a receber e processar a exclusão.
     *
     * Este método verifica a validade do token CSRF e a existência do ID do Conta a receber.  Se válido, recupera os
     * detalhes do Conta a receber do Conta à Receber de dados e tenta excluir o Conta a receber.  Redireciona o Conta a receber para a página de 
     * listagem de Conta a recebers com mensagens apropriadas baseadas no sucesso ou falha da operação.
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Verificar a validade do token CSRF e a existência do ID do Conta à Receber
        if (
            !isset($this->data['form']['csrf_token'])
            || !CSRFHelper::validateCSRFToken('form_delete_pay', $this->data['form']['csrf_token'])
            || !isset($this->data['form']['id'])
        ) {

            // Registrar um log de erro
            GenerateLog::generateLog("error", "Conta à Receber não encontrada.", []);

            // Criar a mensagem de erro e redirecionar
            $_SESSION['error'] = "Conta à Receber não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-receipts");
            return;
        }

        // Instanciar o Repository para recuperar o Conta à Receber
        $deleteReceive = new ReceiptsRepository();
        $this->data['receive'] = $deleteReceive->getReceive((int) $this->data['form']['id']);

        // var_dump($this->data['pay']);
        // exit;

        // // Verificar se o Conta à Receber foi encontrado
        // if (!$this->data['pay']) {

        //     // Registrar um log de erro
        //     GenerateLog::generateLog("error", "Conta à Receber não encontrado.", ['id' => (int) $this->data['form']['id']]);

        //     // Criar a mensagem de erro e redirecionar
        //     $_SESSION['error'] = "Conta à Receber não encontrado!";
        //     header("Location: {$_ENV['URL_ADM']}list-receipts");
        //     return;
        // }

        // Verificar se o Conta à Receber foi encontrado
        if (!$this->data['receive']) {

            // Registrar um log de erro
            GenerateLog::generateLog("error", "Conta à Receber não encontrado.", ['id' => (int) $this->data['form']['id']]);

            // Criar a mensagem de erro e redirecionar
            $_SESSION['error'] = "Conta à Receber não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-receipts");
            return;
        }

        // Verificar se o campo 'paid' é igual a 0 antes de deletar
        if ($this->data['receive']['paid'] != 0) {

            // Registrar um log de erro
            GenerateLog::generateLog("error", "Tentativa de exclusão de Conta à Receber já paga.", ['id' => (int) $this->data['form']['id']]);

            // Criar a mensagem de erro e redirecionar
            $_SESSION['error'] = "Não é possível excluir uma Conta à Receber que já foi paga!";
            header("Location: {$_ENV['URL_ADM']}list-receipts");
            return;
        }

        // Tentar excluir o Conta à Receber
        $result = $deleteReceive->deleteReceive($this->data['form']['id']);

        // Verificar se a exclusão foi bem-sucedida
        if ($result) {

            // gravar logs na tabela adms-logs
            if ($_ENV['APP_LOGS'] == 'Sim') {
                $dataLogs = [
                    'table_name' => 'adms_receive',
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
        header("Location: {$_ENV['URL_ADM']}list-receipts");
        return;
    }
}
