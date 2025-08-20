<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\LgpdClassificacoesDadosRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar uma Classificação de Dados LGPD
 *
 * Esta classe é responsável por exibir as informações detalhadas de uma Classificação de Dados LGPD específica. Ela recupera os dados
 * da Classificação a partir do repositório, valida se a Classificação existe e carrega a visualização apropriada. Se a Classificação
 * não for encontrada, uma mensagem de erro é exibida e o usuário é redirecionado para a página de lista.
 *
 * @package App\adms\Controllers\lgpd
 * @author Rafael Mendes de Oliveira
 */
class LgpdClassificacoesDadosView
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes da Classificação de Dados LGPD.
     *
     * Este método gerencia a recuperação e exibição dos detalhes de uma Classificação de Dados LGPD específica. Ele valida o ID fornecido,
     * recupera os dados da Classificação do repositório e carrega a visualização. Se a Classificação não for encontrada, registra
     * um erro, exibe uma mensagem e redireciona para a página de lista de Classificações.
     *
     * @param int|string $id ID da Classificação a ser visualizada.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Validar se o ID é um valor inteiro
        if (!(int) $id) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Classificação de Dados não encontrada", ['id' => (int) $id]);
            $_SESSION['error'] = "Classificação de Dados não encontrada!";
            header("Location: {$_ENV['URL_ADM']}lgpd-classificacoes-dados");
            return;
        }

        // Instanciar o Repository para recuperar o registro do banco de dados
        $viewClassificacoes = new LgpdClassificacoesDadosRepository();
        $this->data['classificacao'] = $viewClassificacoes->getById((int) $id);

        // Verificar se encontrou o registro no banco de dados
        if (!$this->data['classificacao']) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Classificação de Dados não encontrada", ['id' => (int) $id]);
            $_SESSION['error'] = "Classificação de Dados não encontrada!";
            header("Location: {$_ENV['URL_ADM']}lgpd-classificacoes-dados");
            return;
        }

        // Registrar a visualização da Classificação
        GenerateLog::generateLog("info", "Visualizada a Classificação de Dados.", ['id' => (int) $id]);

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Visualizar Classificação de Dados LGPD',
            'menu' => 'lgpd-classificacoes-dados',
            'buttonPermission' => ['LgpdClassificacoesDados', 'UpdateLgpdClassificacoesDados', 'DeleteLgpdClassificacoesDados'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/lgpd/classificacoes-dados/view", $this->data);
        $loadView->loadView();
    }
} 