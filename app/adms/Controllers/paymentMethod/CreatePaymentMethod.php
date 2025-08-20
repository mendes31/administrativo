<?php

namespace App\adms\Controllers\paymentMethod;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationPaymentMethodService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\PaymentMethodsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para criação de forma pagamento
 *
 * Esta classe é responsável pelo processo de criação de novos forma pagamentos. Ela lida com a recepção dos dados do
 * formulário, validação dos mesmos, e criação do forma pagamentos no sistema. Além disso, é responsável por carregar
 * a visualização apropriada com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\paymentMethod
 * @author Rafael Mendes
 */
class CreatePaymentMethod
{
    /** @var array|string|null $data Dados que serão enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Método principal que gerencia a criação do forma pagamento.
     *
     * Este método é chamado para processar a criação de um novo forma pagamento. Ele verifica a validade do token CSRF,
     * valida os dados do formulário e, se tudo estiver correto, cria o forma pagamento. Caso contrário, carrega a
     * visualização de criação do forma pagamento com mensagens de erro.
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Verificar se o token CSRF é válido
        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_create_payment_method', $this->data['form']['csrf_token'])) {
            // Chamar o método para adicionar o forma pagamento
            $this->addPaymentMethod();
        } else {
            // Chamar o método para carregar a view de criação de forma pagamento
            $this->viewPaymentMethod();
        }
    }

    /**
     * Carregar a visualização de criação do forma pagamento.
     * 
     * Este método configura os dados necessários e carrega a view para a criação de um novo forma pagamento.
     * 
     * @return void
     */
    private function viewPaymentMethod(): void
    {
        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Cadastrar forma pagamento',
            'menu' => 'list-payment-methods',
            'buttonPermission' => ['ListPaymentMethods'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/paymentMethod/create", $this->data);
        $loadView->loadView();
    }

    /**
     * Adicionar um novo forma pagamento ao sistema.
     * 
     * Este método valida os dados do formulário usando a classe de validação `ValidationPaymentMethodService` e,
     * se não houver erros, cria o forma pagamento no banco de dados usando o `PaymentMethodsRepository`. Caso contrário, ele
     * recarrega a visualização de criação com mensagens de erro.
     * 
     * @return void
     */
    private function addPaymentMethod(): void
    {
        // Instanciar a classe de validação dos dados do formulário
        $validationPaymentMethods = new ValidationPaymentMethodService();
        $this->data['errors'] = $validationPaymentMethods->validate($this->data['form']);

        // Se houver erros, recarregar a view com erros
        if (!empty($this->data['errors'])) {
            $this->viewPaymentMethod();
            return;
        }

        // Instanciar o Repository para criar o forma pagamento
        $paymentMethodCreate = new PaymentMethodsRepository();
        $result = $paymentMethodCreate->createPaymentMethod($this->data['form']);

        // Se a criação do forma pagamento for bem-sucedida
        if ($result) {
            // Mensagem de sucesso
            $_SESSION['success'] = "Forma pagamento cadastrada com sucesso!";

            // Redirecionar para a página de visualização do forma pagamento recém-criado
            header("Location: {$_ENV['URL_ADM']}view-payment-method/$result");
            return;
        } else {
            // Mensagem de erro
            $this->data['errors'][] = "Forma pagamento não cadastrada!";

            // Recarregar a view com erro
            $this->viewPaymentMethod();
        }
    }
}
