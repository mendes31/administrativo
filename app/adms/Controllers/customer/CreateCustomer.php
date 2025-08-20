<?php

namespace App\adms\Controllers\customer;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationCustomerService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\CustomerRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para criação de Cliente
 *
 * Esta classe é responsável pelo processo de criação de novas Cliente. Ela lida com a recepção dos dados do
 * formulário, validação dos mesmos, e criação do Cliente no sistema. Além disso, é responsável por carregar
 * a visualização apropriada com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\customer
 * @author Rafael Mendes
 */
class CreateCustomer
{
    /** @var array|string|null $data Dados que serão enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Método principal que gerencia a criação do Cliente
     *
     * Este método é chamado para processar a criação de um novo Cliente Ele verifica a validade do token CSRF,
     * valida os dados do formulário e, se tudo estiver correto, cria o Cliente Caso contrário, carrega a
     * visualização de criação do Clientecom mensagens de erro.
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Verificar se o token CSRF é válido
        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_create_customer', $this->data['form']['csrf_token'])) {
            // Chamar o método para adicionar o Cliente            
            $this->addCustomer();
        } else {
            // Chamar o método para carregar a view de criação de Cliente 
            $this->viewCustomer();
        }
    }

    /**
     * Carregar a visualização de criação do Cliente
     * 
     * Este método configura os dados necessários e carrega a view para a criação de um novo Cliente
     * 
     * @return void
     */
    private function viewCustomer(): void
    {
        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Cadastrar Cliente',
            'menu' => 'list-customers',
            'buttonPermission' => ['ListCustomers'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

       $this->nextCode();


        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/customer/create", $this->data);
        $loadView->loadView();
    }

    /**
     * Adicionar um novo Cliente ao sistema.
     * 
     * Este método valida os dados do formulário usando a classe de validação `ValidationCostCenterService` e,
     * se não houver erros, cria o departametno no banco de dados usando o `UsersRepository`. Caso contrário, ele
     * recarrega a visualização de criação com mensagens de erro.
     * 
     * @return void
     */
    private function addCustomer(): void
    {
        // Instanciar a classe de validação dos dados do formulário
        $validationCustomer = new ValidationCustomerService();
        $this->data['errors'] = $validationCustomer->validate($this->data['form']);

        // Se houver erros, recarregar a view com erros
        if (!empty($this->data['errors'])) {
            $this->viewCustomer();
            return;
        }

        // Instanciar o Repository para criar o Cliente        
        $customerCreate = new CustomerRepository();
                
        // Criar o cliente com os dados (incluindo o novo código)
        $result = $customerCreate->createCustomer($this->data['form']);

        // Se a criação do Cliente for bem-sucedida
        if ($result) {
            // Mensagem de sucesso
            $_SESSION['success'] = "Cliente cadastrado com sucesso!";

            // Redirecionar para a página de visualização do Cliente recém-criado
            header("Location: {$_ENV['URL_ADM']}view-customer/$result");
            return;
        } else {
            // Mensagem de erro
            $this->data['errors'][] = "Cliente não cadastrado!";

            // Recarregar a view com erro
            $this->viewCustomer();
        }
    }

    public function nextCode(): void
    {
         // Instanciar o Repository para criar o Cliente        
         $customerCreate = new CustomerRepository();

         // Gerar o próximo código do cliente
         $nextCode = $customerCreate->getNextCustomerCode();
 
         // Adicionar o próximo código ao array de dados
         $this->data['form']['card_code'] = $nextCode;
    }
}
