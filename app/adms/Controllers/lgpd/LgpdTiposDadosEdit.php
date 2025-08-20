<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationTipoDadosService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\LgpdTiposDadosRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para editar Tipo de Dados LGPD
 *
 * Esta classe é responsável por gerenciar a edição de informações de um Tipo de Dados LGPD existente. Inclui a validação dos dados
 * do formulário, a atualização das informações do Tipo no repositório e a renderização da visualização apropriada.
 * Caso haja algum problema, como um Tipo não encontrado ou dados inválidos, mensagens de erro são exibidas e registradas.
 *
 * @package App\adms\Controllers\lgpd
 * @author Rafael Mendes
 */
class LgpdTiposDadosEdit
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Editar o Tipo de Dados LGPD.
     *
     * Este método gerencia o processo de edição de um Tipo de Dados LGPD. Recebe os dados do formulário, valida o CSRF token e
     * a existência do Tipo, e chama o método adequado para editar o Tipo ou carregar a visualização de edição.
     *
     * @param int|string $id ID do Tipo a ser editado.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Validar o CSRF token e a existência do ID do tipo
        if (isset($this->data['form']['csrf_token']) && 
            CSRFHelper::validateCSRFToken('form_edit_tipo_dados', $this->data['form']['csrf_token'])) 
        {
            // Editar o Tipo
            $this->editTipoDados();
        } else {
            // Recuperar o registro do Tipo
            $viewTipoDados = new LgpdTiposDadosRepository();
            $this->data['form'] = $viewTipoDados->getById((int) $id);

            // Verificar se o Tipo foi encontrado
            if (!$this->data['form']) {
                // Registrar o erro e redirecionar
                GenerateLog::generateLog("error", "Tipo de Dados não encontrado", ['id' => (int) $id]);
                $_SESSION['error'] = "Tipo de Dados não encontrado!";
                header("Location: {$_ENV['URL_ADM']}lgpd-tipos-dados");
                return;
            }

            // Carregar a visualização para edição do Tipo
            $this->viewTipoDados();
        }
    }

    /**
     * Carregar a visualização para edição do Tipo de Dados.
     *
     * Este método define o título da página e carrega a visualização de edição do Tipo com os dados necessários.
     * 
     * @return void
     */
    private function viewTipoDados(): void
    {
        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Editar Tipo de Dados LGPD',
            'menu' => 'lgpd-tipos-dados',
            'buttonPermission' => ['LgpdTiposDados', 'ViewLgpdTiposDados'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/lgpd/tipos-dados/edit", $this->data);
        $loadView->loadView();
    }

    /**
     * Editar o Tipo de Dados LGPD.
     *
     * Este método valida os dados do formulário, atualiza as informações do Tipo no repositório e lida com o resultado
     * da operação. Se a atualização for bem-sucedida, o usuário é redirecionado para a página de visualização do Tipo.
     * Caso contrário, uma mensagem de erro é exibida e a visualização de edição é recarregada.
     * 
     * @return void
     */
    private function editTipoDados(): void
    {
        // Validar os dados do formulário
        $validationTipoDados = new ValidationTipoDadosService();
        $this->data['errors'] = $validationTipoDados->validate($this->data['form']);

        // Se houver erros de validação, recarregar a visualização
        if (!empty($this->data['errors'])) {
            $this->viewTipoDados();
            return;
        }

        // Atualizar o Tipo
        $tipoDadosUpdate = new LgpdTiposDadosRepository();
        $result = $tipoDadosUpdate->update($this->data['form']);

        // Verificar o resultado da atualização
        if ($result) {
            $_SESSION['success'] = "Tipo de Dados editado com sucesso!";
            header("Location: {$_ENV['URL_ADM']}view-lgpd-tipos-dados/{$this->data['form']['id']}");
        } else {
            $this->data['errors'][] = "Tipo de Dados não editado!";
            $this->viewTipoDados();
        }
    }
} 