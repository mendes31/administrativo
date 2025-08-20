<?php

namespace App\adms\Controllers\pages;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\PagesRepository;

/**
 * Controller para exclusão de página
 *
 * Esta classe gerencia o processo de exclusão de páginas no sistema. Ela lida com a validação dos dados
 * do formulário, a exclusão da página do banco de dados e o registro de logs para operações bem-sucedidas ou
 * falhas. Além disso, redireciona o usuário para a página de listagem de páginas com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\pages
 * @author Rafael Mendes
 */
class DeletePage
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes da página e processar a exclusão.
     *
     * Este método verifica a validade do token CSRF e a existência do ID da página. Se válido, recupera os
     * detalhes da página do banco de dados e tenta excluir a página. Redireciona o usuário para a página de 
     * listagem de páginas com mensagens apropriadas baseadas no sucesso ou falha da operação.
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        
        // Verificar a validade do token CSRF e a existência do ID da página
        if (!isset($this->data['form']['csrf_token']) 
            || !CSRFHelper::validateCSRFToken('form_delete_page', $this->data['form']['csrf_token']) 
            || !isset($this->data['form']['id'])) {

            // Registrar um log de erro
            GenerateLog::generateLog("error", "Página não encontrada.", []);

            // Criar a mensagem de erro e redirecionar
            $_SESSION['error'] = "Página não encontrada!";
            header("Location: {$_ENV['URL_ADM']}list-pages");
            return;
        }

        // Instanciar o Repository para recuperar a página
        $deletePage = new PagesRepository();
        $this->data['page'] = $deletePage->getPage((int) $this->data['form']['id']);

        // Verificar se a página foi encontrada
        if (!$this->data['page']) {

            // Registrar um log de erro
            GenerateLog::generateLog("error", "Página não encontrada.", ['id' => (int) $this->data['form']['id']]);

            // Criar a mensagem de erro e redirecionar
            $_SESSION['error'] = "Página não encontrada!";
            header("Location: {$_ENV['URL_ADM']}list-pages");
            return;
        }

        // Tentar excluir a página
        $result = $deletePage->deletePage($this->data['form']['id']);

        // Verificar se a exclusão foi bem-sucedida
        if ($result) {
            // Criar a mensagem de sucesso
            $_SESSION['success'] = "Página apagada com sucesso!";
        } else {
            // Criar a mensagem de erro
            $_SESSION['error'] = "Página não apagada!";
        }

        // Redirecionar para a página de listagem de páginas
        header("Location: {$_ENV['URL_ADM']}list-pages");
        return;
    }
}
