<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\LgpdBasesLegaisRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar uma Base Legal LGPD
 *
 * Esta classe é responsável por exibir as informações detalhadas de uma Base Legal LGPD específica. Ela recupera os dados
 * da Base Legal a partir do repositório, valida se a Base Legal existe e carrega a visualização apropriada. Se a Base Legal
 * não for encontrada, uma mensagem de erro é exibida e o usuário é redirecionado para a página de lista.
 *
 * @package App\adms\Controllers\lgpd
 * @author Rafael Mendes de Oliveira
 */
class LgpdBasesLegaisView
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes da Base Legal LGPD.
     *
     * Este método gerencia a recuperação e exibição dos detalhes de uma Base Legal LGPD específica. Ele valida o ID fornecido,
     * recupera os dados da Base Legal do repositório e carrega a visualização. Se a Base Legal não for encontrada, registra
     * um erro, exibe uma mensagem e redireciona para a página de lista de Bases Legais.
     *
     * @param int|string $id ID da Base Legal a ser visualizada.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Validar se o ID é um valor inteiro
        if (!(int) $id) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Base Legal não encontrada", ['id' => (int) $id]);
            $_SESSION['error'] = "Base Legal não encontrada!";
            header("Location: {$_ENV['URL_ADM']}lgpd-bases-legais");
            return;
        }

        // Instanciar o Repository para recuperar o registro do banco de dados
        $viewBasesLegais = new LgpdBasesLegaisRepository();
        $this->data['base_legal'] = $viewBasesLegais->getById((int) $id);

        // Verificar se encontrou o registro no banco de dados
        if (!$this->data['base_legal']) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Base Legal não encontrada", ['id' => (int) $id]);
            $_SESSION['error'] = "Base Legal não encontrada!";
            header("Location: {$_ENV['URL_ADM']}lgpd-bases-legais");
            return;
        }

        // Registrar a visualização da Base Legal
        GenerateLog::generateLog("info", "Visualizada a Base Legal.", ['id' => (int) $id]);

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Visualizar Base Legal LGPD',
            'menu' => 'lgpd-bases-legais',
            'buttonPermission' => ['LgpdBasesLegais', 'UpdateLgpdBasesLegais', 'DeleteLgpdBasesLegais'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/lgpd/bases-legais/view", $this->data);
        $loadView->loadView();
    }
} 