<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationFinalidadeService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\LgpdFinalidadesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para criação de Finalidade LGPD
 *
 * Esta classe é responsável pelo processo de criação de novas Finalidades LGPD. Ela lida com a recepção dos dados do
 * formulário, validação dos mesmos, e criação da Finalidade no sistema. Além disso, é responsável por carregar
 * a visualização apropriada com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\lgpd
 * @author Rafael Mendes
 */
class LgpdFinalidadesCreate
{
    /** @var array|string|null $data Dados que serão enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Método principal que gerencia a criação da Finalidade LGPD
     *
     * Este método é chamado para processar a criação de uma nova Finalidade LGPD. Ele verifica a validade do token CSRF,
     * valida os dados do formulário e, se tudo estiver correto, cria a Finalidade. Caso contrário, carrega a
     * visualização de criação da Finalidade com mensagens de erro.
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Verificar se o token CSRF é válido
        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_create_finalidade', $this->data['form']['csrf_token'])) {
            // Chamar o método para adicionar a Finalidade            
            $this->addFinalidade();
        } else {
            // Chamar o método para carregar a view de criação de Finalidade 
            $this->viewFinalidade();
        }
    }

    /**
     * Carregar a visualização de criação da Finalidade
     * 
     * Este método configura os dados necessários e carrega a view para a criação de uma nova Finalidade
     * 
     * @return void
     */
    private function viewFinalidade(): void
    {
        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Cadastrar Finalidade LGPD',
            'menu' => 'lgpd-finalidades',
            'buttonPermission' => ['LgpdFinalidades'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/lgpd/finalidades/create", $this->data);
        $loadView->loadView();
    }

    /**
     * Adicionar uma nova Finalidade LGPD ao sistema.
     * 
     * Este método valida os dados do formulário usando a classe de validação `ValidationFinalidadeService` e,
     * se não houver erros, cria a finalidade no banco de dados usando o `LgpdFinalidadesRepository`. Caso contrário, ele
     * recarrega a visualização de criação com mensagens de erro.
     * 
     * @return void
     */
    private function addFinalidade(): void
    {
        // Instanciar a classe de validação dos dados do formulário
        $validationFinalidade = new ValidationFinalidadeService();
        $this->data['errors'] = $validationFinalidade->validate($this->data['form']);

        // Se houver erros, recarregar a view com erros
        if (!empty($this->data['errors'])) {
            $this->viewFinalidade();
            return;
        }

        // Instanciar o Repository para criar a Finalidade        
        $finalidadeCreate = new LgpdFinalidadesRepository();
        $result = $finalidadeCreate->create($this->data['form']);

        // Se a criação da Finalidade for bem-sucedida
        if ($result) {
            // Mensagem de sucesso
            $_SESSION['success'] = "Finalidade cadastrada com sucesso!";

            // Redirecionar para a página de visualização da finalidade recém-criada
            header("Location: {$_ENV['URL_ADM']}view-lgpd-finalidades/$result");
            return;
        } else {
            // Mensagem de erro
            $this->data['errors'][] = "Finalidade não cadastrada!";

            // Recarregar a view com erro
            $this->viewFinalidade();
        }
    }
} 