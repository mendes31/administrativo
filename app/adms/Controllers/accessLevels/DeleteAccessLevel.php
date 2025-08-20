<?php

namespace App\adms\Controllers\accessLevels;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\AccessLevelsRepository;
use App\adms\Models\Repository\LogsRepository;

/**
 * Controller para exclusão de nível de acesso
 *
 * Esta classe gerencia o processo de exclusão de nível de acesso no sistema. Ela lida com a validação dos dados
 * do formulário, a exclusão do nível de acesso do banco de dados e o registro de logs para operações bem-sucedidas ou
 * falhas. Além disso, redireciona o nível de acesso para a página de listagem de níveis de acessos com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\acessLevels
 * @author Rafael Mendes
 */
class DeleteAccessLevel
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes do nível de acesso e processar a exclusão.
     *
     * Este método verifica a validade do token CSRF e a existência do ID do nível de acesso  Se válido, recupera os
     * detalhes do nível de acesso do banco de dados e tenta excluir o nível de acesso  Redireciona o nível de acesso para a página de 
     * listagem de níveis de acesso com mensagens apropriadas baseadas no sucesso ou falha da operação.
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
                
        // Verificar a validade do token CSRF e a existência do ID do nível de acesso
        if (!isset($this->data['form']['csrf_token']) 
            || !CSRFHelper::validateCSRFToken('form_delete_access_level', $this->data['form']['csrf_token']) 
            || !isset($this->data['form']['id'])) {

            // Registrar um log de erro
            GenerateLog::generateLog("error", "Nível de acesso não encontrado.", []);

            // Criar a mensagem de erro e redirecionar
            $_SESSION['error'] = "Nível de acesso não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-access-levels");
            return;
        }

        // Instanciar o Repository para recuperar o Nível de acesso
        $deleteAccesslevel = new AccessLevelsRepository();
        $this->data['user'] = $deleteAccesslevel->getAccessLevel((int) $this->data['form']['id']);

        // Verificar se o nível de acesso foi encontrado
        if (!$this->data['user']) {

            // Registrar um log de erro
            GenerateLog::generateLog("error", "Nível de acesso não encontrado.", ['id' => (int) $this->data['form']['id']]);

            // Criar a mensagem de erro e redirecionar
            $_SESSION['error'] = "Nível de acesso não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-access-levels");
            return;
        }

        // Tentar excluir o nível de acesso
        $result = $deleteAccesslevel->deleteAccessLevel($this->data['form']['id']);

        // Verificar se a exclusão foi bem-sucedida
        if ($result) {

            // gravar logs na tabela adms-logs
            if ($_ENV['APP_LOGS'] == 'Sim') {
                $dataLogs = [
                    'table_name' => 'adms_access_levels',
                    'action' => 'exclusão',
                    'record_id' => $result,
                    'description' => $this->data['form']['name'],
    
                ];
                // Instanciar a classe validar  o usuário
                $insertLogs = new LogsRepository();
                $insertLogs->insertLogs($dataLogs);
            }
            
            // Criar a mensagem de sucesso
            $_SESSION['success'] = "Nível de acesso apagado com sucesso!";
        } else {
            // Criar a mensagem de erro
            $_SESSION['error'] = "Nível de acesso não apagado!";
        }

        // Redirecionar para a página de listagem de níves de acesso
        header("Location: {$_ENV['URL_ADM']}list-access-levels");
        return;
    }
}
