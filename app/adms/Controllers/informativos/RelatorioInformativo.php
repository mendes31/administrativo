<?php

namespace App\adms\Controllers\informativos;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Repository\InformativosRepository;
use App\adms\Models\Services\InformativoRelatorioService;
use App\adms\Models\Services\InformativosPermissionService;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para relatórios de informativos
 * 
 * @author Rafael Mendes <raffaell_mendez@hotmail.com>
 */
class RelatorioInformativo
{
    /** @var array|string|null $data Recebe os dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Página do relatório
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        
        // Receber também via GET (quando clicado no botão da listagem)
        $informativoId = $this->data['form']['informativo_id'] ?? $_GET['informativo_id'] ?? null;

        // Se foi solicitado um relatório específico
        if (!empty($informativoId)) {
            $this->gerarRelatorio($informativoId);
        } else {
            $this->viewRelatorio();
        }
    }

    /**
     * Carregar a visualização do relatório
     */
    private function viewRelatorio(): void
    {
        // Criar o título da página
        $this->data['title_head'] = "Relatório de Informativos";

        // Buscar informativos para o select (apenas os que o usuário pode gerenciar, exceto super)
        $informativosRepo = new InformativosRepository();
        $all = $informativosRepo->getAllInformativos(1, 1000, []);
        $userId = InformativosPermissionService::sessionUserId();
        $userDept = InformativosPermissionService::sessionUserDepartmentId();
        $this->data['informativos'] = array_values(array_filter(
            $all,
            static fn (array $row): bool => InformativosPermissionService::canManageRecord($row, $userId, $userDept)
        ));

        // Configurar elementos de layout (menu e permissões)
        $pageElements = [
            'title_head' => 'Relatório de Informativos',
            'menu' => 'list-informativos',
            'buttonPermission' => [],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/informativos/relatorio", $this->data);
        $loadView->loadView();
    }

    /**
     * Gerar o relatório específico
     */
    private function gerarRelatorio(string $informativoId): void
    {
        $informativoId = (int)$informativoId;
        
        // Buscar dados do informativo
        $informativosRepo = new InformativosRepository();
        $informativo = $informativosRepo->getInformativoById($informativoId);
        
        if (!$informativo) {
            $_SESSION['error'] = "Informativo não encontrado!";
            $this->viewRelatorio();
            return;
        }

        $perm = new ButtonPermissionUserRepository();
        $relBtn = $perm->buttonPermission(['RelatorioInformativo']);
        if (!is_array($relBtn) || count($relBtn) === 0) {
            $_SESSION['error'] = 'Você não tem permissão para acessar o relatório de informativos.';
            $this->viewRelatorio();

            return;
        }

        $userId = InformativosPermissionService::sessionUserId();
        $userDept = InformativosPermissionService::sessionUserDepartmentId();
        if (!InformativosPermissionService::canManageRecord($informativo, $userId, $userDept)) {
            $_SESSION['error'] = 'Você não tem permissão para gerar relatório deste informativo.';
            $this->viewRelatorio();

            return;
        }

        $relatorio = (new InformativoRelatorioService())->build($informativoId, $informativo);

        $this->data['informativo'] = $informativo;
        $this->data['dados_relatorio'] = $relatorio['ativos'];
        $this->data['dados_inativos_historico'] = $relatorio['inativos_historico'];
        $this->data['excluidos_sem_historico'] = $relatorio['excluidos_sem_historico'];
        $this->data['relatorio_kpis'] = $relatorio['kpis'];
        $this->data['requires_ack'] = $relatorio['requires_ack'];
        $this->data['title_head'] = "Relatório: " . $informativo['titulo'];

        // Configurar elementos de layout (menu e permissões)
        $pageElements = [
            'title_head' => 'Relatório de Informativos',
            'menu' => 'list-informativos',
            'buttonPermission' => [],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW do relatório
        $loadView = new LoadViewService("adms/Views/informativos/relatorioResultado", $this->data);
        $loadView->loadView();
    }
}
