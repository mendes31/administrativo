<?php

namespace App\adms\Controllers\packages;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\PackagesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar um pacote
 *
 * Esta classe é responsável por exibir as informações detalhadas de um pacote específico. Ela recupera os dados
 * do pacote a partir do repositório, valida se o pacote existe e carrega a visualização apropriada. Se o pacote
 * não for encontrado, uma mensagem de erro é exibida e o usuário é redirecionado para a página de lista.
 *
 * @package App\adms\Controllers\packages
 * @author Rafael Mendes de Oliveira
 */
class ViewPackage
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes do pacote.
     *
     * Este método gerencia a recuperação e exibição dos detalhes de um pacote específico. Ele valida o ID fornecido,
     * recupera os dados do pacote do repositório e carrega a visualização. Se o pacote não for encontrado, registra
     * um erro, exibe uma mensagem e redireciona para a página de lista de pacotes.
     *
     * @param int|string $id ID do pacote a ser visualizado.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Validar se o ID é um valor inteiro
        if (!(int) $id) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Pacote não encontrado", ['id' => (int) $id]);
            $_SESSION['error'] = "Pacote não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-packages");
            return;
        }

        // Instanciar o Repository para recuperar o registro do banco de dados
        $viewPackages = new PackagesRepository();
        $this->data['packages'] = $viewPackages->getPackage((int) $id);

        // Verificar se encontrou o registro no banco de dados
        if (!$this->data['packages']) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Pacote não encontrado", ['id' => (int) $id]);
            $_SESSION['error'] = "Pacote não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-packages");
            return;
        }

        // Registrar a visualização do pacote
        GenerateLog::generateLog("info", "Visualizado o pacote.", ['id' => (int) $id]);

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Visualizar Pacote',
            'menu' => 'list-packages',
            'buttonPermission' => ['ListPackages', 'UpdatePackage', 'DeletePackage'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/packages/view", $this->data);
        $loadView->loadView();
    }
}
