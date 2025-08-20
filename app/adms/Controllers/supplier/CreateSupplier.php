<?php

namespace App\adms\Controllers\supplier;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationCustomerService;
use App\adms\Controllers\Services\Validation\ValidationSupplierService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\CustomerRepository;
use App\adms\Models\Repository\SupplierRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para criação de Fornecedor
 *
 * Esta classe é responsável pelo processo de criação de novas Fornecedor. Ela lida com a recepção dos dados do
 * formulário, validação dos mesmos, e criação do Fornecedor no sistema. Além disso, é responsável por carregar
 * a visualização apropriada com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\supplier
 * @author Rafael Mendes
 */
class CreateSupplier
{
    /** @var array|string|null $data Dados que serão enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Método principal que gerencia a criação do Fornecedor
     *
     * Este método é chamado para processar a criação de um novo Fornecedor Ele verifica a validade do token CSRF,
     * valida os dados do formulário e, se tudo estiver correto, cria o Fornecedor Caso contrário, carrega a
     * visualização de criação do Fornecedorcom mensagens de erro.
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Verificar se o token CSRF é válido
        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_create_supplier', $this->data['form']['csrf_token'])) {
            // Chamar o método para adicionar o Fornecedor            
            $this->addSupplier();
        } else {
            // Chamar o método para carregar a view de criação de Fornecedor 
            $this->viewSupplier();
        }
    }

    /**
     * Carregar a visualização de criação do Fornecedor
     * 
     * Este método configura os dados necessários e carrega a view para a criação de um novo Fornecedor
     * 
     * @return void
     */
    private function viewSupplier(): void
    {
        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Cadastrar Fornecedor',
            'menu' => 'list-suppliers',
            'buttonPermission' => ['ListSuppliers'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

       $this->nextCode();


        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/supplier/create", $this->data);
        $loadView->loadView();
    }

    /**
     * Adicionar um novo Fornecedor ao sistema.
     * 
     * Este método valida os dados do formulário usando a classe de validação `ValidationSupplierService` e,
     * se não houver erros, cria o departametno no banco de dados usando o `SupplierRepository`. Caso contrário, ele
     * recarrega a visualização de criação com mensagens de erro.
     * 
     * @return void
     */
    private function addSupplier(): void
    {
        // Instanciar a classe de validação dos dados do formulário
        $validationSupplier = new ValidationSupplierService();
        $this->data['errors'] = $validationSupplier->validate($this->data['form']);

        // Se houver erros, recarregar a view com erros
        if (!empty($this->data['errors'])) {
            $this->viewSupplier();
            return;
        }

        // Instanciar o Repository para criar o Fornecedor        
        $supplierCreate = new SupplierRepository();
                
        // Criar o Fornecedor com os dados (incluindo o novo código)
        $result = $supplierCreate->createSupplier($this->data['form']);

        // Se a criação do Fornecedor for bem-sucedida
        if ($result) {
            // Mensagem de sucesso
            $_SESSION['success'] = "Fornecedor cadastrado com sucesso!";

            // Redirecionar para a página de visualização do Fornecedor recém-criado
            header("Location: {$_ENV['URL_ADM']}view-supplier/$result");
            return;
        } else {
            // Mensagem de erro
            $this->data['errors'][] = "Fornecedor não cadastrado!";

            // Recarregar a view com erro
            $this->viewSupplier();
        }
    }

    public function nextCode(): void
    {
         // Instanciar o Repository para criar o Fornecedor        
         $supplierCreate = new SupplierRepository();

         // Gerar o próximo código do Fornecedor
         $nextCode = $supplierCreate->getNextSupplierCode();
 
         // Adicionar o próximo código ao array de dados
         $this->data['form']['card_code'] = $nextCode;
    }
}
