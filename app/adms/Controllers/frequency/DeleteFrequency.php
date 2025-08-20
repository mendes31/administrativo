<?php

namespace App\adms\Controllers\frequency;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\FrequencyRepository;

/**
 * Controller para exclusão de Freqência
 *
 * Esta classe gerencia o processo de exclusão de Freqência no sistema. Ela lida com a validação dos dados
 * do formulário, a exclusão do Freqência do banco de dados e o registro de logs para operações bem-sucedidas ou
 * falhas. Além disso, redireciona o Freqência para a página de listagem de Freqências com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\frequency
 * @author Rafael Mendes de Oliveira
 */
class DeleteFrequency
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes do Freqência e processar a exclusão.
     *
     * Este método verifica a validade do token CSRF e a existência do ID do Freqência.  Se válido, recupera os
     * detalhes do Freqência do banco de dados e tenta excluir o Freqência.  Redireciona o Freqência para a página de 
     * listagem de Freqências com mensagens apropriadas baseadas no sucesso ou falha da operação.
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        
        // Verificar a validade do token CSRF e a existência do ID do Freqência
        if (!isset($this->data['form']['csrf_token']) 
            || !CSRFHelper::validateCSRFToken('form_delete_frequency', $this->data['form']['csrf_token']) 
            || !isset($this->data['form']['id'])) {

            // Registrar um log de erro
            GenerateLog::generateLog("error", "Freqência não encontrado.", []);

            // Criar a mensagem de erro e redirecionar
            $_SESSION['error'] = "Freqência não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-frequencies");
            return;
        }

        // Instanciar o Repository para recuperar o Freqência
        $deleteFrequency = new FrequencyRepository();
        $this->data['frequency'] = $deleteFrequency->getFrequency((int) $this->data['form']['id']);

        // Verificar se o deleteCostCenter foi encontrado
        if (!$this->data['frequency']) {

            // Registrar um log de erro
            GenerateLog::generateLog("error", "Freqência não encontrado.", ['id' => (int) $this->data['form']['id']]);

            // Criar a mensagem de erro e redirecionar
            $_SESSION['error'] = "Freqência não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-frequencies");
            return;
        }

        // Tentar excluir o Freqência
        $result = $deleteFrequency->deleteFrequency($this->data['form']['id']);

        // Verificar se a exclusão foi bem-sucedida
        if ($result) {
            // Criar a mensagem de sucesso
            $_SESSION['success'] = "Freqência apagado com sucesso!";
        } else {
            // Criar a mensagem de erro
            $_SESSION['error'] = "Freqência não apagado!";
        }

        // Redirecionar para a página de listagem de Freqência
        header("Location: {$_ENV['URL_ADM']}list-frequencies");
        return;
    }
}
