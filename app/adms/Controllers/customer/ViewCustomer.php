<?php

namespace App\adms\Controllers\customer;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\CustomerRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar um Cliente
 *
 * Esta classe é responsável por exibir as informações detalhadas de um Cliente específico. Ela recupera os dados
 * do Cliente a partir do repositório, valida se o Cliente existe e carrega a visualização apropriada. Se o Cliente
 * não for encontrado, uma mensagem de erro é exibida e o Cliente é redirecionado para a página de lista.
 *
 * @package App\adms\Controllers\customer
 * @author Rafael Mendes de Oliveira
 */
class ViewCustomer
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes do Cliente.
     *
     * Este método gerencia a recuperação e exibição dos detalhes de um Cliente específico. Ele valida o ID fornecido,
     * recupera os dados do Cliente do repositório e carrega a visualização. Se o Cliente não for encontrado, registra
     * um erro, exibe uma mensagem e redireciona para a página de lista de Cliente.
     *
     * @param int|string $id ID do Cliente a ser visualizado.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Validar se o ID é um valor inteiro
        if (!(int) $id) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Cliente não encontrado", ['id' => (int) $id]);
            $_SESSION['error'] = "Cliente não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-customers");
            return;
        }

        // Instanciar o Repository para recuperar o registro do banco de dados
        $viewCustomer = new CustomerRepository();
        $this->data['customer'] = $viewCustomer->getCustomer((int) $id);

        // Verificar se encontrou o registro no banco de dados
        if (!$this->data['customer']) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Cliente não encontrado", ['id' => (int) $id]);
            $_SESSION['error'] = "Cliente não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-customers");
            return;
        }

        // Registrar a visualização do Cliente
        GenerateLog::generateLog("info", "Visualizado o Cliente.", ['id' => (int) $id]);

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Visualizar Cliente',
            'menu' => 'list-customers',
            'buttonPermission' => ['ListCustomers', 'UpdateCustomer', 'DeleteCustomer'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/customer/view", $this->data);
        $loadView->loadView();
    }
}
