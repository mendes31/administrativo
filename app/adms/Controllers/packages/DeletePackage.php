<?php

namespace App\adms\Controllers\packages;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\PackagesRepository;

/**
 * Controller para exclusão de pacotes
 *
 * Esta classe gerencia o processo de exclusão de pacotes no sistema. Ela lida com a validação dos dados
 * do formulário, a exclusão do pacote do banco de dados e o registro de logs para operações bem-sucedidas ou
 * falhas. Além disso, redireciona o usuário para a página de listagem de pacotes com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\packages
 * @author Rafael Mendes de Oliveira
 */
class DeletePackage
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes do pacote e processar a exclusão.
     *
     * Este método verifica a validade do token CSRF e a existência do ID do pacote. Se válido, recupera os
     * detalhes do pacote do banco de dados e tenta excluí-lo. Redireciona o usuário para a página de 
     * listagem de pacotes com mensagens apropriadas baseadas no sucesso ou falha da operação.
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        
        // Verificar a validade do token CSRF e a existência do ID do pacote
        if (!isset($this->data['form']['csrf_token']) 
            || !CSRFHelper::validateCSRFToken('form_delete_package', $this->data['form']['csrf_token']) 
            || !isset($this->data['form']['id'])) {

            // Registrar um log de erro
            GenerateLog::generateLog("error", "Pacote não encontrado.", []);

            // Criar a mensagem de erro e redirecionar
            $_SESSION['error'] = "Pacote não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-packages");
            return;
        }

        // Instanciar o Repository para recuperar o Pacote
        $deletePackage = new PackagesRepository();
        $this->data['packages'] = $deletePackage->getPackage((int) $this->data['form']['id']);

        // Verificar se o Pacote foi encontrado
        if (!$this->data['packages']) {

            // Registrar um log de erro
            GenerateLog::generateLog("error", "Pacote não encontrado.", ['id' => (int) $this->data['form']['id']]);

            // Criar a mensagem de erro e redirecionar
            $_SESSION['error'] = "Pacote não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-packages");
            return;
        }

        // Tentar excluir o Pacote
        $result = $deletePackage->deletePackage($this->data['form']['id']);

        // Verificar se a exclusão foi bem-sucedida
        if ($result) {
            // Criar a mensagem de sucesso
            $_SESSION['success'] = "Pacote apagado com sucesso!";
        } else {
            // Criar a mensagem de erro
            $_SESSION['error'] = "Pacote não apagado!";
        }

        // Redirecionar para a página de listagem de pacotes
        header("Location: {$_ENV['URL_ADM']}list-packages");
        return;
    }
}