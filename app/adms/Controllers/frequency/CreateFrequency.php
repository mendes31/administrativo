<?php

namespace App\adms\Controllers\frequency;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationCostCenterService;
use App\adms\Controllers\Services\Validation\ValidationFrequencyService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\CostCentersRepository;
use App\adms\Models\Repository\FrequencyRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para criação de Frequências de pagamento
 *
 * Esta classe é responsável pelo processo de criação de novas Frequências de pagamento. Ela lida com a recepção dos dados do
 * formulário, validação dos mesmos, e criação do Frequências de pagamento no sistema. Além disso, é responsável por carregar
 * a visualização apropriada com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\frequency
 * @author Rafael Mendes
 */
class CreateFrequency
{
    /** @var array|string|null $data Dados que serão enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Método principal que gerencia a criação do Frequências de pagamento
     *
     * Este método é chamado para processar a criação de um novo Frequências de pagamento Ele verifica a validade do token CSRF,
     * valida os dados do formulário e, se tudo estiver correto, cria o Frequências de pagamento Caso contrário, carrega a
     * visualização de criação do Frequências de pagamentocom mensagens de erro.
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Verificar se o token CSRF é válido
        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_create_frequency', $this->data['form']['csrf_token'])) {
            // Chamar o método para adicionar o Frequências de pagamento            
            $this->addFrequency();
        } else {
            // Chamar o método para carregar a view de criação de Frequências de pagamento 
            $this->viewFrequency();
        }
    }

    /**
     * Carregar a visualização de criação do Frequências de pagamento
     * 
     * Este método configura os dados necessários e carrega a view para a criação de um novo Frequências de pagamento
     * 
     * @return void
     */
    private function viewFrequency(): void
    {
        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Cadastrar Frequências de pagamento',
            'menu' => 'list-frequencies',
            'buttonPermission' => ['ListFrequencies'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/frequency/create", $this->data);
        $loadView->loadView();
    }

    /**
     * Adicionar um novo Frequências de pagamento ao sistema.
     * 
     * Este método valida os dados do formulário usando a classe de validação `ValidationCostCenterService` e,
     * se não houver erros, cria o departametno no banco de dados usando o `UsersRepository`. Caso contrário, ele
     * recarrega a visualização de criação com mensagens de erro.
     * 
     * @return void
     */
    private function addFrequency(): void
    {
        // Instanciar a classe de validação dos dados do formulário
        $validationFrequency = new ValidationFrequencyService();
        $this->data['errors'] = $validationFrequency->validate($this->data['form']);

        // Se houver erros, recarregar a view com erros
        if (!empty($this->data['errors'])) {
            $this->viewFrequency();
            return;
        }

        // Instanciar o Repository para criar o Frequências de pagamento        
        $frequencyCreate = new FrequencyRepository();
        $result = $frequencyCreate->createFrequency($this->data['form']);

        // Se a criação do Frequências de pagamento for bem-sucedida
        if ($result) {
            // Mensagem de sucesso
            $_SESSION['success'] = "Frequências de pagamento cadastrado com sucesso!";

            // Redirecionar para a página de visualização do Frequências de pagamento recém-criado
            header("Location: {$_ENV['URL_ADM']}view-frequency/$result");
            return;
        } else {
            // Mensagem de erro
            $this->data['errors'][] = "Frequências de pagamento não cadastrado!";

            // Recarregar a view com erro
            $this->viewFrequency();
        }
    }
}
