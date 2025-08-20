<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationBaseLegalService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\LgpdBasesLegaisRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para editar Base Legal LGPD
 *
 * Esta classe é responsável por gerenciar a edição de informações de uma Base Legal LGPD existente. Inclui a validação dos dados
 * do formulário, a atualização das informações da Base Legal no repositório e a renderização da visualização apropriada.
 * Caso haja algum problema, como uma Base Legal não encontrada ou dados inválidos, mensagens de erro são exibidas e registradas.
 *
 * @package App\adms\Controllers\lgpd
 * @author Rafael Mendes
 */
class LgpdBasesLegaisEdit
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Editar a Base Legal LGPD.
     *
     * Este método gerencia o processo de edição de uma Base Legal LGPD. Recebe os dados do formulário, valida o CSRF token e
     * a existência da Base Legal, e chama o método adequado para editar a Base Legal ou carregar a visualização de edição.
     *
     * @param int|string $id ID da Base Legal a ser editada.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Validar o CSRF token e a existência do ID da base legal
        if (isset($this->data['form']['csrf_token']) && 
            CSRFHelper::validateCSRFToken('form_edit_base_legal', $this->data['form']['csrf_token'])) 
        {
            // Editar a Base Legal
            $this->editBaseLegal();
        } else {
            // Recuperar o registro da Base Legal
            $viewBaseLegal = new LgpdBasesLegaisRepository();
            $this->data['form'] = $viewBaseLegal->getById((int) $id);

            // Verificar se a Base Legal foi encontrada
            if (!$this->data['form']) {
                // Registrar o erro e redirecionar
                GenerateLog::generateLog("error", "Base Legal não encontrada", ['id' => (int) $id]);
                $_SESSION['error'] = "Base Legal não encontrada!";
                header("Location: {$_ENV['URL_ADM']}lgpd-bases-legais");
                return;
            }

            // Carregar a visualização para edição da Base Legal
            $this->viewBaseLegal();
        }
    }

    /**
     * Carregar a visualização para edição da Base Legal.
     *
     * Este método define o título da página e carrega a visualização de edição da Base Legal com os dados necessários.
     * 
     * @return void
     */
    private function viewBaseLegal(): void
    {
        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Editar Base Legal LGPD',
            'menu' => 'lgpd-bases-legais',
            'buttonPermission' => ['LgpdBasesLegais', 'ViewLgpdBasesLegais'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/lgpd/bases-legais/edit", $this->data);
        $loadView->loadView();
    }

    /**
     * Editar a Base Legal LGPD.
     *
     * Este método valida os dados do formulário, atualiza as informações da Base Legal no repositório e lida com o resultado
     * da operação. Se a atualização for bem-sucedida, o usuário é redirecionado para a página de visualização da Base Legal.
     * Caso contrário, uma mensagem de erro é exibida e a visualização de edição é recarregada.
     * 
     * @return void
     */
    private function editBaseLegal(): void
    {
        // Validar os dados do formulário
        $validationBaseLegal = new ValidationBaseLegalService();
        $this->data['errors'] = $validationBaseLegal->validate($this->data['form']);

        // Se houver erros de validação, recarregar a visualização
        if (!empty($this->data['errors'])) {
            $this->viewBaseLegal();
            return;
        }

        // Atualizar a Base Legal
        $baseLegalUpdate = new LgpdBasesLegaisRepository();
        $result = $baseLegalUpdate->update($this->data['form']);

        // Verificar o resultado da atualização
        if ($result) {
            $_SESSION['success'] = "Base Legal editada com sucesso!";
            header("Location: {$_ENV['URL_ADM']}view-lgpd-bases-legais/{$this->data['form']['id']}");
        } else {
            $this->data['errors'][] = "Base Legal não editada!";
            $this->viewBaseLegal();
        }
    }
} 