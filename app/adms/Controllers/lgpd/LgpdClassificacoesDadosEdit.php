<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationClassificacaoDadosService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\LgpdClassificacoesDadosRepository;
use App\adms\Models\Repository\LgpdBasesLegaisRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para editar Classificação de Dados LGPD
 *
 * Esta classe é responsável por gerenciar a edição de informações de uma Classificação de Dados LGPD existente. Inclui a validação dos dados
 * do formulário, a atualização das informações da Classificação no repositório e a renderização da visualização apropriada.
 * Caso haja algum problema, como uma Classificação não encontrada ou dados inválidos, mensagens de erro são exibidas e registradas.
 *
 * @package App\adms\Controllers\lgpd
 * @author Rafael Mendes
 */
class LgpdClassificacoesDadosEdit
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Editar a Classificação de Dados LGPD.
     *
     * Este método gerencia o processo de edição de uma Classificação de Dados LGPD. Recebe os dados do formulário, valida o CSRF token e
     * a existência da Classificação, e chama o método adequado para editar a Classificação ou carregar a visualização de edição.
     *
     * @param int|string $id ID da Classificação a ser editada.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Validar o CSRF token e a existência do ID da classificação
        if (isset($this->data['form']['csrf_token']) && 
            CSRFHelper::validateCSRFToken('form_edit_classificacao_dados', $this->data['form']['csrf_token'])) 
        {
            // Editar a Classificação
            $this->editClassificacao();
        } else {
            // Recuperar o registro da Classificação
            $viewClassificacao = new LgpdClassificacoesDadosRepository();
            $this->data['form'] = $viewClassificacao->getById((int) $id);

            // Verificar se a Classificação foi encontrada
            if (!$this->data['form']) {
                // Registrar o erro e redirecionar
                GenerateLog::generateLog("error", "Classificação de Dados não encontrada", ['id' => (int) $id]);
                $_SESSION['error'] = "Classificação de Dados não encontrada!";
                header("Location: {$_ENV['URL_ADM']}lgpd-classificacoes-dados");
                return;
            }

            // Carregar a visualização para edição da Classificação
            $this->viewClassificacao();
        }
    }

    /**
     * Carregar a visualização para edição da Classificação de Dados.
     *
     * Este método define o título da página e carrega a visualização de edição da Classificação com os dados necessários.
     * 
     * @return void
     */
    private function viewClassificacao(): void
    {
        // Carregar bases legais para o select
        $basesLegaisRepository = new LgpdBasesLegaisRepository();
        $this->data['bases_legais'] = $basesLegaisRepository->getActiveBasesLegais();

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Editar Classificação de Dados LGPD',
            'menu' => 'lgpd-classificacoes-dados',
            'buttonPermission' => ['LgpdClassificacoesDados', 'ViewLgpdClassificacoesDados'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/lgpd/classificacoes-dados/edit", $this->data);
        $loadView->loadView();
    }

    /**
     * Editar a Classificação de Dados LGPD.
     *
     * Este método valida os dados do formulário, atualiza as informações da Classificação no repositório e lida com o resultado
     * da operação. Se a atualização for bem-sucedida, o usuário é redirecionado para a página de visualização da Classificação.
     * Caso contrário, uma mensagem de erro é exibida e a visualização de edição é recarregada.
     * 
     * @return void
     */
    private function editClassificacao(): void
    {
        // Validar os dados do formulário
        $validationClassificacao = new ValidationClassificacaoDadosService();
        $this->data['errors'] = $validationClassificacao->validate($this->data['form']);

        // Se houver erros de validação, recarregar a visualização
        if (!empty($this->data['errors'])) {
            $this->viewClassificacao();
            return;
        }

        // Atualizar a Classificação
        $classificacaoUpdate = new LgpdClassificacoesDadosRepository();
        $result = $classificacaoUpdate->update($this->data['form']);

        // Verificar o resultado da atualização
        if ($result) {
            $_SESSION['success'] = "Classificação de Dados editada com sucesso!";
            header("Location: {$_ENV['URL_ADM']}view-lgpd-classificacoes-dados/{$this->data['form']['id']}");
        } else {
            $this->data['errors'][] = "Classificação de Dados não editada!";
            $this->viewClassificacao();
        }
    }
} 