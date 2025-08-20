<?php

namespace App\adms\Controllers\frequency;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationFrequencyService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\FrequencyRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para editar Frequência
 *
 * Esta classe é responsável por gerenciar a edição de informações de um Frequência existente. Inclui a validação dos dados
 * do formulário, a atualização das informações do Frequência no repositório e a renderização da visualização apropriada.
 * Caso haja algum problema, como um Frequência não encontrado ou dados inválidos, mensagens de erro são exibidas e registradas.
 *
 * @package App\adms\Controllers\frequency;
 * @author Rafael Mendes
 */
class UpdateFrequency
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Editar o Frequência.
     *
     * Este método gerencia o processo de edição de um Frequência. Recebe os dados do formulário, valida o CSRF token e
     * a existência do Frequência, e chama o método adequado para editar o Frequência ou carregar a visualização de edição.
     *
     * @param int|string $id ID do Frequência a ser editado.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Validar o CSRF token e a existência do ID do nível de acesso
        if (isset($this->data['form']['csrf_token']) && 
            CSRFHelper::validateCSRFToken('form_update_frequency', $this->data['form']['csrf_token'])) 
        {
            // Editar o Frequência
            $this->editFrequency();
        } else {
            // Recuperar o registro do Frequência
            $viewFrequency = new FrequencyRepository();
            $this->data['form'] = $viewFrequency->getFrequency((int) $id);

            // Verificar se o Frequência foi encontrado
            if (!$this->data['form']) {
                // Registrar o erro e redirecionar
                GenerateLog::generateLog("error", "Frequência não encontrado", ['id' => (int) $id]);
                $_SESSION['error'] = "Frequência não encontrado!";
                header("Location: {$_ENV['URL_ADM']}list-frequencies");
                return;
            }

            // Carregar a visualização para edição do Frequência
            $this->viewFrequency();
        }
    }

    /**
     * Carregar a visualização para edição do Frequência.
     *
     * Este método define o título da página e carrega a visualização de edição do Frequência com os dados necessários.
     * 
     * @return void
     */
    private function viewFrequency(): void
    {
        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Editar Frequência',
            'menu' => 'list-frequencies',
            'buttonPermission' => ['ListFrequencies', 'ViewFrequency'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/frequency/update", $this->data);
        $loadView->loadView();
    }

    /**
     * Editar o Frequência.
     *
     * Este método valida os dados do formulário, atualiza as informações do Frequência no repositório e lida com o resultado
     * da operação. Se a atualização for bem-sucedida, o Frequência é redirecionado para a página de visualização do Frequência.
     * Caso contrário, uma mensagem de erro é exibida e a visualização de edição é recarregada.
     * 
     * @return void
     */
    private function editFrequency(): void
    {
        // Validar os dados do formulário
        $validationFrequency = new ValidationFrequencyService();
        $this->data['errors'] = $validationFrequency->validate($this->data['form']);

        // Se houver erros de validação, recarregar a visualização
        if (!empty($this->data['errors'])) {
            $this->viewFrequency();
            return;
        }

        // Atualizar o Frequência
        $frequencyUpdate = new FrequencyRepository();
        $result = $frequencyUpdate->updateFrequency($this->data['form']);

        // Verificar o resultado da atualização
        if ($result) {
            $_SESSION['success'] = "Frequência editado com sucesso!";
            header("Location: {$_ENV['URL_ADM']}view-frequency/{$this->data['form']['id']}");
        } else {
            $this->data['errors'][] = "Frequência não editado!";
            $this->viewFrequency();
        }
    }
}
