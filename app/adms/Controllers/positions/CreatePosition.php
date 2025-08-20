<?php

namespace App\adms\Controllers\positions;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationPositionService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para criação de cargo
 *
 * Esta classe é responsável pelo processo de criação de novos cargos. Ela lida com a recepção dos dados do
 * formulário, validação dos mesmos, e criação do cargos no sistema. Além disso, é responsável por carregar
 * a visualização apropriada com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\positions
 * @author Rafael Mendes
 */
class CreatePosition
{
    /** @var array|string|null $data Dados que serão enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Método principal que gerencia a criação do cargo.
     *
     * Este método é chamado para processar a criação de um novo departamento. Ele verifica a validade do token CSRF,
     * valida os dados do formulário e, se tudo estiver correto, cria o cargo. Caso contrário, carrega a
     * visualização de criação do cargo com mensagens de erro.
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Verificar se o token CSRF é válido
        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_create_position', $this->data['form']['csrf_token'])) {
            // Chamar o método para adicionar o Departamento
            $this->addPosition();
        } else {
            // Chamar o método para carregar a view de criação de Departamento
            $this->viewPosition();
        }
    }

    /**
     * Carregar a visualização de criação do cargo.
     * 
     * Este método configura os dados necessários e carrega a view para a criação de um novo Cargo.
     * 
     * @return void
     */
    private function viewPosition(): void
    {
        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Cadastrar Cargo',
            'menu' => 'list-positions',
            'buttonPermission' => ['ListPositions'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/positions/create", $this->data);
        $loadView->loadView();
    }

    /**
     * Adicionar um novo cargo ao sistema.
     * 
     * Este método valida os dados do formulário usando a classe de validação `ValidationDepartmentsService` e,
     * se não houver erros, cria o cargo no banco de dados usando o `PositionsRepository`. Caso contrário, ele
     * recarrega a visualização de criação com mensagens de erro.
     * 
     * @return void
     */
    private function addPosition(): void
    {
        // Instanciar a classe de validação dos dados do formulário
        $validationPositions = new ValidationPositionService();
        $this->data['errors'] = $validationPositions->validate($this->data['form']);

        // Se houver erros, recarregar a view com erros
        if (!empty($this->data['errors'])) {
            $this->viewPosition();
            return;
        }

        // Instanciar o Repository para criar o cargo
        $positionCreate = new PositionsRepository();
        $result = $positionCreate->createPosition($this->data['form']);

        // Se a criação do cargo for bem-sucedida
        if ($result) {
            $matrixService = new \App\adms\Controllers\trainings\TrainingMatrixService();
            $matrixService->updateMatrixForAllUsers();
            // Mensagem de sucesso
            $_SESSION['success'] = "Cargo cadastrado com sucesso!";

            // Redirecionar para a página de visualização do cargo recém-criado
            header("Location: {$_ENV['URL_ADM']}view-position/$result");
            return;
        } else {
            // Mensagem de erro
            $this->data['errors'][] = "Cargo não cadastrado!";

            // Recarregar a view com erro
            $this->viewPosition();
        }
    }
}
