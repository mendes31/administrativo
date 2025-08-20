<?php

namespace App\adms\Controllers\paymentMethod;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\PaymentMethodsRepository;

/**
 * Controller para exclusão de forma de pagamento
 *
 * Esta classe gerencia o processo de exclusão de forma de pagamento no sistema. Ela lida com a validação dos dados
 * do formulário, a exclusão do forma de pagamento do banco de dados e o registro de logs para operações bem-sucedidas ou
 * falhas. Além disso, redireciona o forma de pagamento para a página de listagem de forma de pagamentos com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\paymentMethod
 * @author Rafael Mendes de Oliveira
 */
class DeletePaymentMethod
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
        
        // Verificar a validade do token CSRF e a existência do ID do forma de pagamento
        if (!isset($this->data['form']['csrf_token']) 
            || !CSRFHelper::validateCSRFToken('form_delete_payment_method', $this->data['form']['csrf_token']) 
            || !isset($this->data['form']['id'])) {

            // Registrar um log de erro
            GenerateLog::generateLog("error", "Forma de pagamento não encontrada.", []);

            // Criar a mensagem de erro e redirecionar
            $_SESSION['error'] = "forma de pagamento não encontrada!";
            header("Location: {$_ENV['URL_ADM']}list-payment-methods");
            return;
        }

        // Instanciar o Repository para recuperar o forma de pagamento
        $deletePaymentMethod = new PaymentMethodsRepository();
        $this->data['paymentMethod'] = $deletePaymentMethod->getPaymentMethod((int) $this->data['form']['id']);

        // Verificar se o deletePosition foi encontrado
        if (!$this->data['paymentMethod']) {

            // Registrar um log de erro
            GenerateLog::generateLog("error", "Forma de pagamento não encontrada.", ['id' => (int) $this->data['form']['id']]);

            // Criar a mensagem de erro e redirecionar
            $_SESSION['error'] = "Forma de pagamento não encontrada!";
            header("Location: {$_ENV['URL_ADM']}list-payment-methods");
            return;
        }

        // Tentar excluir o forma de pagamento
        $result = $deletePaymentMethod->deletePaymentMethod($this->data['form']['id']);

        // Verificar se a exclusão foi bem-sucedida
        if ($result) {
            // Criar a mensagem de sucesso
            $_SESSION['success'] = "Forma de pagamento excluida com sucesso!";
        } else {
            // Criar a mensagem de erro
            $_SESSION['error'] = "Forma de pagamento não excluida!";
        }

        // Redirecionar para a página de listagem de forma de pagamento
        header("Location: {$_ENV['URL_ADM']}list-payment-methods");
        return;
    }
}
