<?php

namespace App\adms\Controllers\packages;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationPackageService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\PackagesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para criação de pacotes
 *
 * Esta classe é responsável pelo processo de criação de novos pacotes. Ela lida com a recepção dos dados do
 * formulário, validação dos mesmos, e criação do pacote no sistema. Além disso, é responsável por carregar
 * a visualização apropriada com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\packages
 * @author Rafael Mendes
 */
class CreatePackage
{
    /** @var array|string|null $data Dados que serão enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Método principal que gerencia a criação de pacotes.
     *
     * Este método é chamado para processar a criação de um novo pacote. Ele verifica a validade do token CSRF,
     * valida os dados do formulário e, se tudo estiver correto, cria o pacote. Caso contrário, carrega a
     * visualização de criação de pacote com mensagens de erro.
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Verificar se o token CSRF é válido
        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_create_package', $this->data['form']['csrf_token'])) {
            // Chamar o método para adicionar o pacote
            $this->addPackage();
        } else {
            // Chamar o método para carregar a view de criação de pacote
            $this->viewPackage();
        }
    }

    /**
     * Carregar a visualização de criação de pacote.
     * 
     * Este método configura os dados necessários e carrega a view para a criação de um novo pacote.
     * 
     * @return void
     */
    private function viewPackage(): void
    {
        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Cadastrar Pacote',
            'menu' => 'list-packages',
            'buttonPermission' => ['ListPackages'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/packages/create", $this->data);
        $loadView->loadView();
    }

    /**
     * Adicionar um novo pacote ao sistema.
     * 
     * Este método valida os dados do formulário usando a classe de validação `ValidationPackageService` e,
     * se não houver erros, cria o pacote no banco de dados usando o `PackagesRepository`. Caso contrário, ele
     * recarrega a visualização de criação com mensagens de erro.
     * 
     * @return void
     */
    private function addPackage(): void
    {
        // Instanciar a classe de validação dos dados do formulário
        $validationPackage = new ValidationPackageService();
        $this->data['errors'] = $validationPackage->validate($this->data['form']);

        // Se houver erros, recarregar a view com erros
        if (!empty($this->data['errors'])) {
            $this->viewPackage();
            return;
        }

        // Instanciar o Repository para criar o pacote
        $packageCreate = new PackagesRepository();
        $result = $packageCreate->createPackage($this->data['form']);

        // Se a criação do pacote for bem-sucedida
        if ($result) {
            // Mensagem de sucesso
            $_SESSION['success'] = "Pacote cadastrado com sucesso!";

            // Redirecionar para a página de visualização do pacote recém-criado
            header("Location: {$_ENV['URL_ADM']}view-package/$result");
            return;
        } else {
            // Mensagem de erro
            $this->data['errors'][] = "Pacote não cadastrado!";

            // Recarregar a view com erro
            $this->viewPackage();
        }
    }
}