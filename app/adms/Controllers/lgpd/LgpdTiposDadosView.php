<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\LgpdTiposDadosRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar um Tipo de Dados LGPD
 *
 * Esta classe é responsável por exibir as informações detalhadas de um Tipo de Dados LGPD específico. Ela recupera os dados
 * do Tipo a partir do repositório, valida se o Tipo existe e carrega a visualização apropriada. Se o Tipo
 * não for encontrado, uma mensagem de erro é exibida e o usuário é redirecionado para a página de lista.
 *
 * @package App\adms\Controllers\lgpd
 * @author Rafael Mendes de Oliveira
 */
class LgpdTiposDadosView
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes do Tipo de Dados LGPD.
     *
     * Este método gerencia a recuperação e exibição dos detalhes de um Tipo de Dados LGPD específico. Ele valida o ID fornecido,
     * recupera os dados do Tipo do repositório e carrega a visualização. Se o Tipo não for encontrado, registra
     * um erro, exibe uma mensagem e redireciona para a página de lista de Tipos.
     *
     * @param int|string $id ID do Tipo a ser visualizado.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Validar se o ID é um valor inteiro
        if (!(int) $id) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Tipo de Dados não encontrado", ['id' => (int) $id]);
            $_SESSION['error'] = "Tipo de Dados não encontrado!";
            header("Location: {$_ENV['URL_ADM']}lgpd-tipos-dados");
            return;
        }

        // Instanciar o Repository para recuperar o registro do banco de dados
        $viewTiposDados = new LgpdTiposDadosRepository();
        $this->data['tipo_dados'] = $viewTiposDados->getById((int) $id);

        // Verificar se encontrou o registro no banco de dados
        if (!$this->data['tipo_dados']) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Tipo de Dados não encontrado", ['id' => (int) $id]);
            $_SESSION['error'] = "Tipo de Dados não encontrado!";
            header("Location: {$_ENV['URL_ADM']}lgpd-tipos-dados");
            return;
        }

        // Registrar a visualização do Tipo
        GenerateLog::generateLog("info", "Visualizado o Tipo de Dados.", ['id' => (int) $id]);

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Visualizar Tipo de Dados LGPD',
            'menu' => 'lgpd-tipos-dados',
            'buttonPermission' => ['LgpdTiposDados', 'UpdateLgpdTiposDados', 'DeleteLgpdTiposDados'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/lgpd/tipos-dados/view", $this->data);
        $loadView->loadView();
    }
} 