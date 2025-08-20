<?php

namespace App\adms\Controllers\accessLevels;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\AccessLevelsRepository;
use App\adms\Models\Repository\LogsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar um nível de acesso
 *
 * Esta classe é responsável por exibir as informações detalhadas de um nível de acesso específico. Ela recupera os dados
 * do nível de acesso a partir do repositório, valida se o nível de acesso existe e carrega a visualização apropriada. Se o nível de acesso
 * não for encontrado, uma mensagem de erro é exibida e o nível de acesso é redirecionado para a página de lista.
 *
 * @package App\adms\Controllers\acessLevels
 * @author Rafael Mendes
 */
class ViewAccessLevel
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes do nível de acesso.
     *
     * Este método gerencia a recuperação e exibição dos detalhes de um nível de acesso específico. Ele valida o ID fornecido,
     * recupera os dados do nível de acesso do repositório e carrega a visualização. Se o nível de acesso não for encontrado, registra
     * um erro, exibe uma mensagem e redireciona para a página de lista de níveis de acesso.
     *
     * @param int|string $id ID do nível de acesso a ser visualizado.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Validar se o ID é um valor inteiro
        if (!(int) $id) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Nível de acesso não encontrado", ['id' => (int) $id]);
            $_SESSION['error'] = "Nível de acesso não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-access-levels");
            return;
        }

        // Instanciar o Repository para recuperar o registro do banco de dados
        $viewAccessLevels = new AccessLevelsRepository();
        $this->data['accessLevel'] = $viewAccessLevels->getAccessLevel((int) $id);

        // Verificar se encontrou o registro no banco de dados
        if (!$this->data['accessLevel']) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Nível de acesso não encontrado", ['id' => (int) $id]);
            $_SESSION['error'] = "Nível de acesso não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-access-levls");
            return;
        }

        // Registrar a visualização do nível de acesso
        GenerateLog::generateLog("info", "Visualizado o nível de acesso.", ['id' => (int) $id]);

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão  
        $pageElements = [
            'title_head' => 'Visualizar nível de acesso',
            'menu' => 'list-access-levels',
            'buttonPermission' => ['ListAccessLevels', 'UpdateAccessLevel', 'DeleteAccessLevel'],
        ];
        $pageLayoutService = new PageLayoutService(); 
        // Combinar os valores do atributos 'data' com o array dos elementos da página
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // gravar logs na tabela adms-logs
        if ($_ENV['APP_LOGS'] == 'Sim') {
            $dataLogs = [
                'table_name' => 'adms_access_levels',
                'action' => 'visualização',
                'record_id' => $this->data['accessLevel']['id'],
                'description' => $this->data['accessLevel']['name'],

            ];
            // Instanciar a classe validar  o usuário
            $insertLogs = new LogsRepository();
            $insertLogs->insertLogs($dataLogs);
        }

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/accessLevels/view", $this->data);
        $loadView->loadView();
    }
}
