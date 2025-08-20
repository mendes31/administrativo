<?php

namespace App\adms\Controllers\banks;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\BanksRepository;
use App\adms\Models\Repository\LogsRepository;

/**
 * Controller para exclusão de Banco
 *
 * Esta classe gerencia o processo de exclusão de Banco no sistema. Ela lida com a validação dos dados
 * do formulário, a exclusão do Banco do banco de dados e o registro de logs para operações bem-sucedidas ou
 * falhas. Além disso, redireciona o Banco para a página de listagem de Bancos com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\banks
 * @author Rafael Mendes de Oliveira
 */
class DeleteBank
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes do departamento e processar a exclusão.
     *
     * Este método verifica a validade do token CSRF e a existência do ID do departamento.  Se válido, recupera os
     * detalhes do departamento do banco de dados e tenta excluir o departamento.  Redireciona o departamento para a página de 
     * listagem de departamentos com mensagens apropriadas baseadas no sucesso ou falha da operação.
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        
        // Verificar a validade do token CSRF e a existência do ID do Banco
        if (!isset($this->data['form']['csrf_token']) 
            || !CSRFHelper::validateCSRFToken('form_delete_bank', $this->data['form']['csrf_token']) 
            || !isset($this->data['form']['id'])) {

            // Registrar um log de erro
            GenerateLog::generateLog("error", "Banco não encontrado.", []);

            // Criar a mensagem de erro e redirecionar
            $_SESSION['error'] = "Banco não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-banks");
            return;
        }

        // Instanciar o Repository para recuperar o Banco
        $deleteBank = new BanksRepository();
        $this->data['bank'] = $deleteBank->getBank((int) $this->data['form']['id']);

        // var_dump($this->data['bank']);
        // exit;

        // Verificar se o banco foi encontrado
        if (!$this->data['bank']) {

            // Registrar um log de erro
            GenerateLog::generateLog("error", "Banco não encontrado.", ['id' => (int) $this->data['form']['id']]);

            // Criar a mensagem de erro e redirecionar
            $_SESSION['error'] = "Banco não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-banks");
            return;
        }

        // Tentar excluir o Banco
        $result = $deleteBank->deleteBank($this->data['form']['id']);

        // Verificar se a exclusão foi bem-sucedida
        if ($result) {

            // gravar logs na tabela adms-logs
            if ($_ENV['APP_LOGS'] == 'Sim') {
                $dataLogs = [
                    'table_name' => 'adms_bank_accounts',
                    'action' => 'exclusão',
                    'record_id' => $result,
                    'description' => $this->data['form']['bank_name'],
    
                ];
                // Instanciar a classe validar  o usuário
                $insertLogs = new LogsRepository();
                $insertLogs->insertLogs($dataLogs);
            }

            // Criar a mensagem de sucesso
            $_SESSION['success'] = "Banco apagado com sucesso!";
        } else {
            // Criar a mensagem de erro
            $_SESSION['error'] = "Banco não apagado!";
        }

        // Redirecionar para a página de listagem de Banco
        header("Location: {$_ENV['URL_ADM']}list-banks");
        return;
    }
}
