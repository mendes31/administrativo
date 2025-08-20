<?php

namespace App\adms\Controllers\frequency;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\FrequencyRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar um Frequencia
 *
 * Esta classe é responsável por exibir as informações detalhadas de um Frequencia específico. Ela recupera os dados
 * do Frequencia a partir do repositório, valida se o Frequencia existe e carrega a visualização apropriada. Se o Frequencia
 * não for encontrado, uma mensagem de erro é exibida e o Frequencia é redirecionado para a página de lista.
 *
 * @package App\adms\Controllers\frequency
 * @author Rafael Mendes de Oliveira
 */
class ViewFrequency
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes do Frequencia.
     *
     * Este método gerencia a recuperação e exibição dos detalhes de um Frequencia específico. Ele valida o ID fornecido,
     * recupera os dados do Frequencia do repositório e carrega a visualização. Se o Frequencia não for encontrado, registra
     * um erro, exibe uma mensagem e redireciona para a página de lista de Frequencia.
     *
     * @param int|string $id ID do Frequencia a ser visualizado.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Validar se o ID é um valor inteiro
        if (!(int) $id) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Frequencia não encontrado", ['id' => (int) $id]);
            $_SESSION['error'] = "Frequencia não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-frequencies");
            return;
        }

        // Instanciar o Repository para recuperar o registro do banco de dados
        $viewFrequency = new FrequencyRepository();
        $this->data['frequency'] = $viewFrequency->getFrequency((int) $id);

        // Verificar se encontrou o registro no banco de dados
        if (!$this->data['frequency']) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Frequencia não encontrado", ['id' => (int) $id]);
            $_SESSION['error'] = "Frequencia não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-frequencies");
            return;
        }

        // Registrar a visualização do Frequencia
        GenerateLog::generateLog("info", "Visualizado o Frequencia.", ['id' => (int) $id]);

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Visualizar Frequência',
            'menu' => 'list-frequencies',
            'buttonPermission' => ['ListFrequencies', 'UpdateFrequency', 'DeleteFrequency'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/frequency/view", $this->data);
        $loadView->loadView();
    }
}
