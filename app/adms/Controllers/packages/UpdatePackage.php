<?php

namespace App\adms\Controllers\packages;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationDepartmentService;
use App\adms\Controllers\Services\Validation\ValidationPackageService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\PackagesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para editar pacote
 *
 * Esta classe é responsável por gerenciar a edição de informações de um pacote existente. Inclui a validação dos dados
 * do formulário, a atualização das informações do pacote no repositório e a renderização da visualização apropriada.
 * Caso haja algum problema, como um pacote não encontrado ou dados inválidos, mensagens de erro são exibidas e registradas.
 *
 * @package App\adms\Controllers\packages
 * @author Rafael Mendes
 */
class UpdatePackage
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Editar o pacote.
     *
     * Este método gerencia o processo de edição de um pacote. Recebe os dados do formulário, valida o CSRF token e
     * a existência do pacote, e chama o método adequado para editar o pacote ou carregar a visualização de edição.
     *
     * @param int|string $id ID do pacote a ser editado.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Validar o CSRF token e a existência do ID do pacote
        if (isset($this->data['form']['csrf_token']) && 
            CSRFHelper::validateCSRFToken('form_update_package', $this->data['form']['csrf_token'])) 
        {
            // Editar o pacote
            $this->editPackage();
        } else {
            // Recuperar o registro do pacote
            $viewPackage = new PackagesRepository();
            $this->data['form'] = $viewPackage->getPackage((int) $id);

            // Verificar se o pacote foi encontrado
            if (!$this->data['form']) {
                // Registrar o erro e redirecionar
                GenerateLog::generateLog("error", "Pacote não encontrado", ['id' => (int) $id]);
                $_SESSION['error'] = "Pacote não encontrado!";
                header("Location: {$_ENV['URL_ADM']}list-packages");
                return;
            }

            // Carregar a visualização para edição do pacote
            $this->viewPackage();
        }
    }

    /**
     * Carregar a visualização para edição do pacote.
     *
     * Este método define o título da página e carrega a visualização de edição do pacote com os dados necessários.
     * 
     * @return void
     */
    private function viewPackage(): void
    {
        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Editar Pacote',
            'menu' => 'list-packages',
            'buttonPermission' => ['ListPackages', 'ViewPackage'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/packages/update", $this->data);
        $loadView->loadView();
    }

    /**
     * Editar o pacote.
     *
     * Este método valida os dados do formulário, atualiza as informações do pacote no repositório e lida com o resultado
     * da operação. Se a atualização for bem-sucedida, o pacote é redirecionado para a página de visualização do pacote.
     * Caso contrário, uma mensagem de erro é exibida e a visualização de edição é recarregada.
     * 
     * @return void
     */
    private function editPackage(): void
    {
        // Validar os dados do formulário
        $validationPackage = new ValidationPackageService();
        $this->data['errors'] = $validationPackage->validate($this->data['form']);

        // Se houver erros de validação, recarregar a visualização
        if (!empty($this->data['errors'])) {
            $this->viewPackage();
            return;
        }

        // Atualizar o pacote
        $packageUpdate = new PackagesRepository();
        $result = $packageUpdate->updatePackage($this->data['form']);

        // Verificar o resultado da atualização
        if ($result) {
            $_SESSION['success'] = "Pacote editado com sucesso!";
            header("Location: {$_ENV['URL_ADM']}view-package/{$this->data['form']['id']}");
        } else {
            $this->data['errors'][] = "Pacote não editado!";
            $this->viewPackage();
        }
    }
}
