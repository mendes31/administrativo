<?php

namespace App\adms\Controllers\paymentMethod;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\PaymentMethodsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar um Formas de Pagamento
 *
 * Esta classe é responsável por exibir as informações detalhadas de um Formas de Pagamento específico. Ela recupera os dados
 * do Formas de Pagamento a partir do repositório, valida se o Formas de Pagamento existe e carrega a visualização apropriada. Se o Formas de Pagamento
 * não for encontrado, uma mensagem de erro é exibida e o Formas de Pagamento é redirecionado para a página de lista.
 *
 * @package App\adms\Controllers\paymentMethod
 * @author Rafael Mendes de Oliveira
 */
class ViewPaymentMethod
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes do Formas de Pagamento.
     *
     * Este método gerencia a recuperação e exibição dos detalhes de um Formas de Pagamento específico. Ele valida o ID fornecido,
     * recupera os dados do Formas de Pagamento do repositório e carrega a visualização. Se o Formas de Pagamento não for encontrado, registra
     * um erro, exibe uma mensagem e redireciona para a página de lista de Formas de Pagamento.
     *
     * @param int|string $id ID do Formas de Pagamento a ser visualizado.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Validar se o ID é um valor inteiro
        if (!(int) $id) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Formas de Pagamento não encontrado", ['id' => (int) $id]);
            $_SESSION['error'] = "Formas de Pagamento não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-payment-metohds");
            return;
        }

        // Instanciar o Repository para recuperar o registro do banco de dados
        $viewPaymentMethod = new PaymentMethodsRepository();
        $this->data['paymentMethod'] = $viewPaymentMethod->getPaymentMethod((int) $id);

        // Verificar se encontrou o registro no banco de dados
        if (!$this->data['paymentMethod']) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Formas de Pagamento não encontrado", ['id' => (int) $id]);
            $_SESSION['error'] = "Formas de Pagamento não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-payment-methods");
            return;
        }

        // Registrar a visualização do Formas de Pagamento
        GenerateLog::generateLog("info", "Visualizado a Forma de Pagamento.", ['id' => (int) $id]);

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Visualizar Forma de Pagamento',
            'menu' => 'list-payment-methods',
            'buttonPermission' => ['ListPaymentMethods', 'UpdatePaymentMethod', 'DeletePaymentMethod'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/paymentMethod/view", $this->data);
        $loadView->loadView();
    }
}
