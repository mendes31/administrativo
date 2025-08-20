<?php

namespace App\adms\Controllers\paymentMethod;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationPaymentMethodService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\PaymentMethodsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para editar Forma de pagamento
 *
 * Esta classe é responsável por gerenciar a edição de informações de um Forma de pagamento existente. Inclui a validação dos dados
 * do formulário, a atualização das informações do Forma de pagamento no repositório e a renderização da visualização apropriada.
 * Caso haja algum problema, como um Forma de pagamento não encontrado ou dados inválidos, mensagens de erro são exibidas e registradas.
 *
 * @package App\adms\Controllers\paymentMethod
 * @author Rafael Mendes
 */
class UpdatePaymentMethod
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Editar o Forma de pagamento.
     *
     * Este método gerencia o processo de edição de um Forma de pagamento. Recebe os dados do formulário, valida o CSRF token e
     * a existência do Forma de pagamento, e chama o método adequado para editar o Forma de pagamento ou carregar a visualização de edição.
     *
     * @param int|string $id ID do Forma de pagamento a ser editado.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Validar o CSRF token e a existência do ID do nível de acesso
        if (isset($this->data['form']['csrf_token']) && 
            CSRFHelper::validateCSRFToken('form_update_payment_method', $this->data['form']['csrf_token'])) 
        {
            // Editar o Forma de pagamento
            $this->editPaymentMethod();
        } else {
            // Recuperar o registro do Forma de pagamento
            $viewPaymentMethod = new PaymentMethodsRepository();
            $this->data['form'] = $viewPaymentMethod->getPaymentMethod((int) $id);

            // Verificar se o Forma de pagamento foi encontrada
            if (!$this->data['form']) {
                // Registrar o erro e redirecionar
                GenerateLog::generateLog("error", "Forma de pagamento não encontrado", ['id' => (int) $id]);
                $_SESSION['error'] = "Forma de pagamento não encontrado!";
                header("Location: {$_ENV['URL_ADM']}list-payment-methods");
                return;
            }

            // Carregar a visualização para edição do Forma de pagamento
            $this->viewPaymentMethod();
        }
    }

    /**
     * Carregar a visualização para edição do Forma de pagamento.
     *
     * Este método define o título da página e carrega a visualização de edição do Forma de pagamento com os dados necessários.
     * 
     * @return void
     */
    private function viewPaymentMethod(): void
    {
        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Editar Forma de pagamento',
            'menu' => 'list-payment-methods',
            'buttonPermission' => ['ListPaymentMethods', 'ViewPaymentMethod'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/paymentMethod/update", $this->data);
        $loadView->loadView();
    }

    /**
     * Editar o Forma de pagamento.
     *
     * Este método valida os dados do formulário, atualiza as informações do Forma de pagamento no repositório e lida com o resultado
     * da operação. Se a atualização for bem-sucedida, o Forma de pagamento é redirecionado para a página de visualização do Forma de pagamento.
     * Caso contrário, uma mensagem de erro é exibida e a visualização de edição é recarregada.
     * 
     * @return void
     */
    private function editPaymentMethod(): void
    {
        // Validar os dados do formulário
        $validationPaymentMethod = new ValidationPaymentMethodService();
        $this->data['errors'] = $validationPaymentMethod->validate($this->data['form']);

        // Se houver erros de validação, recarregar a visualização
        if (!empty($this->data['errors'])) {
            $this->viewPaymentMethod();
            return;
        }

        // Atualizar o Forma de pagamento
        $paymentMethodUpdate = new PaymentMethodsRepository();
        $result = $paymentMethodUpdate->updatePaymentMethod($this->data['form']);

        // Verificar o resultado da atualização
        if ($result) {
            $_SESSION['success'] = "Forma de pagamento editado com sucesso!";
            header("Location: {$_ENV['URL_ADM']}view-payment-method/{$this->data['form']['id']}");
        } else {
            $this->data['errors'][] = "Forma de pagamento não editada!";
            $this->viewPaymentMethod();
        }
    }
}
