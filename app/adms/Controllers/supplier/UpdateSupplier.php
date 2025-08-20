<?php

namespace App\adms\Controllers\supplier;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationSupplierService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\SupplierRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para editar Fornecedores
 *
 * Esta classe é responsável por gerenciar a edição de informações de um Fornecedores existente. Inclui a validação dos dados
 * do formulário, a atualização das informações do Fornecedores no repositório e a renderização da visualização apropriada.
 * Caso haja algum problema, como um Fornecedores não encontrado ou dados inválidos, mensagens de erro são exibidas e registradas.
 *
 * @package App\adms\Controllers\supplier;
 * @author Rafael Mendes
 */
class UpdateSupplier
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Editar o Fornecedores.
     *
     * Este método gerencia o processo de edição de um Fornecedores. Recebe os dados do formulário, valida o CSRF token e
     * a existência do Fornecedores, e chama o método adequado para editar o Fornecedores ou carregar a visualização de edição.
     *
     * @param int|string $id ID do Fornecedores a ser editado.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Validar o CSRF token e a existência do ID do nível de acesso
        if (isset($this->data['form']['csrf_token']) && 
            CSRFHelper::validateCSRFToken('form_update_supplier', $this->data['form']['csrf_token'])) 
        {
            // Editar o Fornecedores
            $this->editSupplier();
        } else {
            // Recuperar o registro do Fornecedores
            $viewSupplier = new SupplierRepository();
            $this->data['form'] = $viewSupplier->getSupplier((int) $id);

            // Verificar se o Fornecedores foi encontrado
            if (!$this->data['form']) {
                // Registrar o erro e redirecionar
                GenerateLog::generateLog("error", "Fornecedores não encontrado", ['id' => (int) $id]);
                $_SESSION['error'] = "Fornecedores não encontrado!";
                header("Location: {$_ENV['URL_ADM']}list-suppliers");
                return;
            }

            // Carregar a visualização para edição do Fornecedores
            $this->viewSupplier();
        }
    }

    /**
     * Carregar a visualização para edição do Fornecedores.
     *
     * Este método define o título da página e carrega a visualização de edição do Fornecedores com os dados necessários.
     * 
     * @return void
     */
    private function viewSupplier(): void
    {
        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Editar Fornecedores',
            'menu' => 'list-suppliers',
            'buttonPermission' => ['ListSuppliers', 'ViewSupplier'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/supplier/update", $this->data);
        $loadView->loadView();
    }

    /**
     * Editar o Fornecedores.
     *
     * Este método valida os dados do formulário, atualiza as informações do Fornecedores no repositório e lida com o resultado
     * da operação. Se a atualização for bem-sucedida, o Fornecedores é redirecionado para a página de visualização do Fornecedores.
     * Caso contrário, uma mensagem de erro é exibida e a visualização de edição é recarregada.
     * 
     * @return void
     */
    private function editSupplier(): void
    {
        // Validar os dados do formulário
        $validationSupplier = new ValidationSupplierService();
        $this->data['errors'] = $validationSupplier->validate($this->data['form']);

        // Se houver erros de validação, recarregar a visualização
        if (!empty($this->data['errors'])) {
            $this->viewSupplier();
            return;
        }

        // Atualizar o Fornecedores
        $supplierUpdate = new SupplierRepository();
        $result = $supplierUpdate->updateSupplier($this->data['form']);

        // Verificar o resultado da atualização
        if ($result) {
            $_SESSION['success'] = "Fornecedores editado com sucesso!";
            header("Location: {$_ENV['URL_ADM']}view-supplier/{$this->data['form']['id']}");
        } else {
            $this->data['errors'][] = "Fornecedores não editado!";
            $this->viewSupplier();
        }
    }
}
