<?php

namespace App\adms\Controllers\groupsPages;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\GroupsPagesRepository;

/**
 * Controller para exclusão de grupos de página
 *
 * Esta classe gerencia o processo de exclusão de grupos de página no sistema. Ela lida com a validação dos dados
 * do formulário, a exclusão do grupo de página do banco de dados e o registro de logs para operações bem-sucedidas ou
 * falhas. Além disso, redireciona o usuário para a página de listagem de grupos de página com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\groups
 * @author Rafael Mendes
 */
class DeleteGroupPage
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes do grupo de página e processar a exclusão.
     *
     * Este método verifica a validade do token CSRF e a existência do ID do grupo de página. Se válido, recupera os
     * detalhes do grupo de página do banco de dados e tenta excluir o grupo. Redireciona o usuário para a página de 
     * listagem de grupos de página com mensagens apropriadas baseadas no sucesso ou falha da operação.
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        
        // Verificar a validade do token CSRF e a existência do ID do grupo de página
        if (!isset($this->data['form']['csrf_token']) 
            || !CSRFHelper::validateCSRFToken('form_delete_group_page', $this->data['form']['csrf_token']) 
            || !isset($this->data['form']['id'])) {

            // Registrar um log de erro
            GenerateLog::generateLog("error", "Pacote de página não encontrado.", []);

            // Criar a mensagem de erro e redirecionar
            $_SESSION['error'] = "Pacote de página não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-groups-pages");
            return;
        }

        // Instanciar o Repository para recuperar o Pacote
        $deleteGroupPage = new GroupsPagesRepository();
        $this->data['groupPage'] = $deleteGroupPage->getGroupPage((int) $this->data['form']['id']);

        // Verificar se o Pacote foi encontrado
        if (!$this->data['groupPage']) {

            // Registrar um log de erro
            GenerateLog::generateLog("error", "Grupo de página não encontrado.", ['id' => (int) $this->data['form']['id']]);

            // Criar a mensagem de erro e redirecionar
            $_SESSION['error'] = "Grupo de página não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-groups-pages");
            return;
        }

        // Tentar excluir o Pacote
        $result = $deleteGroupPage->deleteGroupPage($this->data['form']['id']);

        // Verificar se a exclusão foi bem-sucedida
        if ($result) {
            // Criar a mensagem de sucesso
            $_SESSION['success'] = "Grupo de página apagado com sucesso!";
        } else {
            // Criar a mensagem de erro
            $_SESSION['error'] = "Grupo de página não apagado!";
        }

        // Redirecionar para a página de listagem de grupos de página
        header("Location: {$_ENV['URL_ADM']}list-groups-pages");
        return;
    }
}
