<?php

namespace App\adms\Controllers\customer;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationCustomerService;
use App\adms\Controllers\Services\Validation\ValidationFrequencyService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\CustomerRepository;
use App\adms\Models\Repository\FrequencyRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para editar Cliente
 *
 * Esta classe é responsável por gerenciar a edição de informações de um Cliente existente. Inclui a validação dos dados
 * do formulário, a atualização das informações do Cliente no repositório e a renderização da visualização apropriada.
 * Caso haja algum problema, como um Cliente não encontrado ou dados inválidos, mensagens de erro são exibidas e registradas.
 *
 * @package App\adms\Controllers\customer;
 * @author Rafael Mendes
 */
class UpdateCustomer
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Editar o Cliente.
     *
     * Este método gerencia o processo de edição de um Cliente. Recebe os dados do formulário, valida o CSRF token e
     * a existência do Cliente, e chama o método adequado para editar o Cliente ou carregar a visualização de edição.
     *
     * @param int|string $id ID do Cliente a ser editado.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Validar o CSRF token e a existência do ID do nível de acesso
        if (isset($this->data['form']['csrf_token']) && 
            CSRFHelper::validateCSRFToken('form_update_customer', $this->data['form']['csrf_token'])) 
        {
            // Editar o Cliente
            $this->editCustomer();
        } else {
            // Recuperar o registro do Cliente
            $viewCustomer = new CustomerRepository();
            $this->data['form'] = $viewCustomer->getCustomer((int) $id);

            // Verificar se o Cliente foi encontrado
            if (!$this->data['form']) {
                // Registrar o erro e redirecionar
                GenerateLog::generateLog("error", "Cliente não encontrado", ['id' => (int) $id]);
                $_SESSION['error'] = "Cliente não encontrado!";
                header("Location: {$_ENV['URL_ADM']}list-customers");
                return;
            }

            // Carregar a visualização para edição do Cliente
            $this->viewCustomer();
        }
    }

    /**
     * Carregar a visualização para edição do Cliente.
     *
     * Este método define o título da página e carrega a visualização de edição do Cliente com os dados necessários.
     * 
     * @return void
     */
    private function viewCustomer(): void
    {
        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Editar Cliente',
            'menu' => 'list-customers',
            'buttonPermission' => ['ListCustomers', 'ViewCustomer'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/customer/update", $this->data);
        $loadView->loadView();
    }

    /**
     * Editar o Cliente.
     *
     * Este método valida os dados do formulário, atualiza as informações do Cliente no repositório e lida com o resultado
     * da operação. Se a atualização for bem-sucedida, o Cliente é redirecionado para a página de visualização do Cliente.
     * Caso contrário, uma mensagem de erro é exibida e a visualização de edição é recarregada.
     * 
     * @return void
     */
    private function editCustomer(): void
    {
        // Validar os dados do formulário
        $validationCustomer = new ValidationCustomerService();
        $this->data['errors'] = $validationCustomer->validate($this->data['form']);

        // Se houver erros de validação, recarregar a visualização
        if (!empty($this->data['errors'])) {
            $this->viewCustomer();
            return;
        }

        // Atualizar o Cliente
        $customerUpdate = new CustomerRepository();
        $result = $customerUpdate->updateCustomer($this->data['form']);

        // Verificar o resultado da atualização
        if ($result) {
            $_SESSION['success'] = "Cliente editado com sucesso!";
            header("Location: {$_ENV['URL_ADM']}view-customer/{$this->data['form']['id']}");
        } else {
            $this->data['errors'][] = "Cliente não editado!";
            $this->viewCustomer();
        }
    }
}
