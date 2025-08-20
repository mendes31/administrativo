<?php

namespace App\adms\Controllers\positions;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationPositionService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para editar Cargo
 *
 * Esta classe é responsável por gerenciar a edição de informações de um Cargo existente. Inclui a validação dos dados
 * do formulário, a atualização das informações do Cargo no repositório e a renderização da visualização apropriada.
 * Caso haja algum problema, como um Cargo não encontrado ou dados inválidos, mensagens de erro são exibidas e registradas.
 *
 * @package App\adms\Controllers\positions
 * @author Rafael Mendes
 */
class UpdatePOsition
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Editar o Cargo.
     *
     * Este método gerencia o processo de edição de um Cargo. Recebe os dados do formulário, valida o CSRF token e
     * a existência do Cargo, e chama o método adequado para editar o Cargo ou carregar a visualização de edição.
     *
     * @param int|string $id ID do Cargo a ser editado.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Validar o CSRF token e a existência do ID do nível de acesso
        if (isset($this->data['form']['csrf_token']) && 
            CSRFHelper::validateCSRFToken('form_update_positions', $this->data['form']['csrf_token'])) 
        {
            // Editar o Cargo
            $this->editPosition();
        } else {
            // Recuperar o registro do Cargo
            $viewPosition = new PositionsRepository();
            $this->data['form'] = $viewPosition->getPosition((int) $id);

            // Verificar se o Cargo foi encontrado
            if (!$this->data['form']) {
                // Registrar o erro e redirecionar
                GenerateLog::generateLog("error", "Cargo não encontrado", ['id' => (int) $id]);
                $_SESSION['error'] = "Cargo não encontrado!";
                header("Location: {$_ENV['URL_ADM']}list-positions");
                return;
            }

            // Carregar a visualização para edição do Cargo
            $this->viewPosition();
        }
    }

    /**
     * Carregar a visualização para edição do Cargo.
     *
     * Este método define o título da página e carrega a visualização de edição do Cargo com os dados necessários.
     * 
     * @return void
     */
    private function viewPosition(): void
    {
        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Editar Cargo',
            'menu' => 'list-positions',
            'buttonPermission' => ['ListPositions', 'ViewPosition'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/positions/update", $this->data);
        $loadView->loadView();
    }

    /**
     * Editar o Cargo.
     *
     * Este método valida os dados do formulário, atualiza as informações do Cargo no repositório e lida com o resultado
     * da operação. Se a atualização for bem-sucedida, o Cargo é redirecionado para a página de visualização do Cargo.
     * Caso contrário, uma mensagem de erro é exibida e a visualização de edição é recarregada.
     * 
     * @return void
     */
    private function editPosition(): void
    {
        // Validar os dados do formulário
        $validationPOsition = new ValidationPositionService();
        $this->data['errors'] = $validationPOsition->validate($this->data['form']);

        // Se houver erros de validação, recarregar a visualização
        if (!empty($this->data['errors'])) {
            $this->viewPosition();
            return;
        }

        // Atualizar o Cargo
        $positionUpdate = new PositionsRepository();
        $result = $positionUpdate->updatePosition($this->data['form']);

        // Verificar o resultado da atualização
        if ($result) {
            $matrixService = new \App\adms\Controllers\trainings\TrainingMatrixService();
            $matrixService->updateMatrixForAllUsers();
            $_SESSION['success'] = "Cargo editado com sucesso!";
            header("Location: {$_ENV['URL_ADM']}view-position/{$this->data['form']['id']}");
        } else {
            $this->data['errors'][] = "Cargo não editado!";
            $this->viewPosition();
        }
    }
}
