<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationFinalidadeService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\LgpdFinalidadesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para editar Finalidade LGPD
 *
 * Esta classe é responsável por gerenciar a edição de informações de uma Finalidade LGPD existente. Inclui a validação dos dados
 * do formulário, a atualização das informações da Finalidade no repositório e a renderização da visualização apropriada.
 * Caso haja algum problema, como uma Finalidade não encontrada ou dados inválidos, mensagens de erro são exibidas e registradas.
 *
 * @package App\adms\Controllers\lgpd
 * @author Rafael Mendes
 */
class LgpdFinalidadesEdit
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Editar a Finalidade LGPD.
     *
     * Este método gerencia o processo de edição de uma Finalidade LGPD. Recebe os dados do formulário, valida o CSRF token e
     * a existência da Finalidade, e chama o método adequado para editar a Finalidade ou carregar a visualização de edição.
     *
     * @param int|string $id ID da Finalidade a ser editada.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Validar o CSRF token e a existência do ID da finalidade
        if (isset($this->data['form']['csrf_token']) && 
            CSRFHelper::validateCSRFToken('form_edit_finalidade', $this->data['form']['csrf_token'])) 
        {
            // Editar a Finalidade
            $this->editFinalidade();
        } else {
            // Recuperar o registro da Finalidade
            $viewFinalidade = new LgpdFinalidadesRepository();
            $this->data['form'] = $viewFinalidade->getById((int) $id);

            // Verificar se a Finalidade foi encontrada
            if (!$this->data['form']) {
                // Registrar o erro e redirecionar
                GenerateLog::generateLog("error", "Finalidade não encontrada", ['id' => (int) $id]);
                $_SESSION['error'] = "Finalidade não encontrada!";
                header("Location: {$_ENV['URL_ADM']}lgpd-finalidades");
                return;
            }

            // Carregar a visualização para edição da Finalidade
            $this->viewFinalidade();
        }
    }

    /**
     * Carregar a visualização para edição da Finalidade.
     *
     * Este método define o título da página e carrega a visualização de edição da Finalidade com os dados necessários.
     * 
     * @return void
     */
    private function viewFinalidade(): void
    {
        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Editar Finalidade LGPD',
            'menu' => 'lgpd-finalidades',
            'buttonPermission' => ['LgpdFinalidades', 'ViewLgpdFinalidades'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/lgpd/finalidades/edit", $this->data);
        $loadView->loadView();
    }

    /**
     * Editar a Finalidade LGPD.
     *
     * Este método valida os dados do formulário, atualiza as informações da Finalidade no repositório e lida com o resultado
     * da operação. Se a atualização for bem-sucedida, o usuário é redirecionado para a página de visualização da Finalidade.
     * Caso contrário, uma mensagem de erro é exibida e a visualização de edição é recarregada.
     * 
     * @return void
     */
    private function editFinalidade(): void
    {
        // Validar os dados do formulário
        $validationFinalidade = new ValidationFinalidadeService();
        $this->data['errors'] = $validationFinalidade->validate($this->data['form']);

        // Se houver erros de validação, recarregar a visualização
        if (!empty($this->data['errors'])) {
            $this->viewFinalidade();
            return;
        }

        // Atualizar a Finalidade
        $finalidadeUpdate = new LgpdFinalidadesRepository();
        $result = $finalidadeUpdate->update($this->data['form']);

        // Verificar o resultado da atualização
        if ($result) {
            $_SESSION['success'] = "Finalidade editada com sucesso!";
            header("Location: {$_ENV['URL_ADM']}view-lgpd-finalidades/{$this->data['form']['id']}");
        } else {
            $this->data['errors'][] = "Finalidade não editada!";
            $this->viewFinalidade();
        }
    }
} 