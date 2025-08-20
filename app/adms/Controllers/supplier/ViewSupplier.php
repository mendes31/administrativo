<?php

namespace App\adms\Controllers\supplier;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\SupplierRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar um Fornecedor
 *
 * Esta classe é responsável por exibir as informações detalhadas de um Fornecedor específico. Ela recupera os dados
 * do Fornecedor a partir do repositório, valida se o Fornecedor existe e carrega a visualização apropriada. Se o Fornecedor
 * não for encontrado, uma mensagem de erro é exibida e o Fornecedor é redirecionado para a página de lista.
 *
 * @package App\adms\Controllers\supplier
 * @author Rafael Mendes de Oliveira
 */
class ViewSupplier
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes do Fornecedor.
     *
     * Este método gerencia a recuperação e exibição dos detalhes de um Fornecedor específico. Ele valida o ID fornecido,
     * recupera os dados do Fornecedor do repositório e carrega a visualização. Se o Fornecedor não for encontrado, registra
     * um erro, exibe uma mensagem e redireciona para a página de lista de Fornecedor.
     *
     * @param int|string $id ID do Fornecedor a ser visualizado.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Validar se o ID é um valor inteiro
        if (!(int) $id) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Fornecedor não encontrado", ['id' => (int) $id]);
            $_SESSION['error'] = "Fornecedor não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-suppliers");
            return;
        }

        // Instanciar o Repository para recuperar o registro do banco de dados
        $viewSupplier = new SupplierRepository();
        $this->data['supplier'] = $viewSupplier->getSupplier((int) $id);

        // Verificar se encontrou o registro no banco de dados
        if (!$this->data['supplier']) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Fornecedor não encontrado", ['id' => (int) $id]);
            $_SESSION['error'] = "Fornecedor não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-suppliers");
            return;
        }

        // Registrar a visualização do Fornecedor
        GenerateLog::generateLog("info", "Visualizado o Fornecedor.", ['id' => (int) $id]);

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Visualizar Fornecedor',
            'menu' => 'list-suppliers',
            'buttonPermission' => ['ListSuppliers', 'UpdateSupplier', 'DeleteSupplier'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/supplier/view", $this->data);
        $loadView->loadView();
    }
}
