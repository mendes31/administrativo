<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\LgpdFinalidadesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar uma Finalidade LGPD
 *
 * Esta classe é responsável por exibir as informações detalhadas de uma Finalidade LGPD específica. Ela recupera os dados
 * da Finalidade a partir do repositório, valida se a Finalidade existe e carrega a visualização apropriada. Se a Finalidade
 * não for encontrada, uma mensagem de erro é exibida e o usuário é redirecionado para a página de lista.
 *
 * @package App\adms\Controllers\lgpd
 * @author Rafael Mendes de Oliveira
 */
class LgpdFinalidadesView
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes da Finalidade LGPD.
     *
     * Este método gerencia a recuperação e exibição dos detalhes de uma Finalidade LGPD específica. Ele valida o ID fornecido,
     * recupera os dados da Finalidade do repositório e carrega a visualização. Se a Finalidade não for encontrada, registra
     * um erro, exibe uma mensagem e redireciona para a página de lista de Finalidades.
     *
     * @param int|string $id ID da Finalidade a ser visualizada.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Validar se o ID é um valor inteiro
        if (!(int) $id) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Finalidade não encontrada", ['id' => (int) $id]);
            $_SESSION['error'] = "Finalidade não encontrada!";
            header("Location: {$_ENV['URL_ADM']}lgpd-finalidades");
            return;
        }

        // Instanciar o Repository para recuperar o registro do banco de dados
        $viewFinalidades = new LgpdFinalidadesRepository();
        $this->data['finalidade'] = $viewFinalidades->getById((int) $id);

        // Verificar se encontrou o registro no banco de dados
        if (!$this->data['finalidade']) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Finalidade não encontrada", ['id' => (int) $id]);
            $_SESSION['error'] = "Finalidade não encontrada!";
            header("Location: {$_ENV['URL_ADM']}lgpd-finalidades");
            return;
        }

        // Registrar a visualização da Finalidade
        GenerateLog::generateLog("info", "Visualizada a Finalidade.", ['id' => (int) $id]);

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Visualizar Finalidade LGPD',
            'menu' => 'lgpd-finalidades',
            'buttonPermission' => ['LgpdFinalidades', 'UpdateLgpdFinalidades', 'DeleteLgpdFinalidades'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/lgpd/finalidades/view", $this->data);
        $loadView->loadView();
    }
} 