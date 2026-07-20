<?php

namespace App\adms\Controllers\users;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Helpers\UserFormHelper;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\WorkShiftsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para listar usuários
 *
 * Esta classe é responsável por recuperar e exibir uma lista de usuários no sistema. Utiliza um repositório
 * para obter dados dos usuários e um serviço de paginação para gerenciar a navegação entre páginas de resultados.
 * Em seguida, carrega a visualização correspondente com os dados recuperados.
 * 
 * @package App\adms\Controllers\users
 * @author Rfael Mendes <raffaell_mendez@hotmail.com>
 */
class ListUsers
{
    /** @var array|string|null $data Recebe os dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /** @var int $limitResult Recebe a quantidade de registros que deve retornar do banco de dados */
    private int $limitResult = 10; // Padrão 10 por página

    /**
     * Recuperar e listar usuários com paginação.
     * 
     * Este método recupera os usuários a partir do repositório de usuários com base na página atual e no limite
     * de registros por página. Gera os dados de paginação e carrega a visualização para exibir a lista de usuários.
     * 
     * @param string|int $page Página atual para a exibição dos resultados. O padrão é 1.
     * 
     * @return void
     */
    public function index(string|int $page = 1)
    {
        // Inicializar sessão de filtros se não existir
        if (!isset($_SESSION['filtros_list_users'])) {
            $_SESSION['filtros_list_users'] = [];
        }
        
        // Verificar se deve limpar filtros
        if (isset($_GET['limpar_filtros']) && $_GET['limpar_filtros'] == '1') {
            unset($_SESSION['filtros_list_users']);
            header('Location: ' . $_ENV['URL_ADM'] . 'list-users');
            exit;
        }
        
        // Capturar o parâmetro page da URL, se existir
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int)$_GET['page'];
        }
        
        // Tratar filtros - priorizar GET, senão usar sessão.
        // Drill do People Analytics envia snapshot completo (from=people-analytics):
        // não herdar valores antigos da sessão (ex.: desligado=1 ao abrir admissões).
        $fromPeopleAnalytics = isset($_GET['from'])
            && (string) $_GET['from'] === 'people-analytics';

        if ($fromPeopleAnalytics) {
            $filtros = [
                'nome' => (string) ($_GET['nome'] ?? ''),
                'usuario' => (string) ($_GET['usuario'] ?? ''),
                'departamento_id' => (string) ($_GET['departamento_id'] ?? ''),
                'cargo_id' => (string) ($_GET['cargo_id'] ?? ''),
                'turno_id' => (string) ($_GET['turno_id'] ?? ''),
                'status' => (string) ($_GET['status'] ?? ''),
                'bloqueado' => (string) ($_GET['bloqueado'] ?? ''),
                'desligado' => (string) ($_GET['desligado'] ?? ''),
                'sexo' => (string) ($_GET['sexo'] ?? ''),
                'filhos' => (string) ($_GET['filhos'] ?? ''),
                'empresa_contratante' => (string) ($_GET['empresa_contratante'] ?? ''),
                'periodo_tipo' => (string) ($_GET['periodo_tipo'] ?? ''),
                'data_de' => (string) ($_GET['data_de'] ?? ''),
                'data_ate' => (string) ($_GET['data_ate'] ?? ''),
            ];
            $_SESSION['filtros_list_users'] = $filtros;
        } else {
            $filtros = [
                'nome' => $_GET['nome'] ?? $_SESSION['filtros_list_users']['nome'] ?? '',
                'usuario' => $_GET['usuario'] ?? $_SESSION['filtros_list_users']['usuario'] ?? '',
                'departamento_id' => $_GET['departamento_id'] ?? $_SESSION['filtros_list_users']['departamento_id'] ?? '',
                'cargo_id' => $_GET['cargo_id'] ?? $_SESSION['filtros_list_users']['cargo_id'] ?? '',
                'turno_id' => $_GET['turno_id'] ?? $_SESSION['filtros_list_users']['turno_id'] ?? '',
                'status' => $_GET['status'] ?? $_SESSION['filtros_list_users']['status'] ?? '',
                'bloqueado' => $_GET['bloqueado'] ?? $_SESSION['filtros_list_users']['bloqueado'] ?? '',
                'desligado' => $_GET['desligado'] ?? $_SESSION['filtros_list_users']['desligado'] ?? '',
                'sexo' => $_GET['sexo'] ?? $_SESSION['filtros_list_users']['sexo'] ?? '',
                'filhos' => $_GET['filhos'] ?? $_SESSION['filtros_list_users']['filhos'] ?? '',
                'empresa_contratante' => $_GET['empresa_contratante'] ?? $_SESSION['filtros_list_users']['empresa_contratante'] ?? '',
                'periodo_tipo' => $_GET['periodo_tipo'] ?? $_SESSION['filtros_list_users']['periodo_tipo'] ?? '',
                'data_de' => $_GET['data_de'] ?? $_SESSION['filtros_list_users']['data_de'] ?? '',
                'data_ate' => $_GET['data_ate'] ?? $_SESSION['filtros_list_users']['data_ate'] ?? '',
            ];

            // Salvar filtros na sessão (apenas se vierem via GET)
            if (isset($_GET['nome']) || isset($_GET['usuario']) ||
                isset($_GET['departamento_id']) || isset($_GET['cargo_id']) || isset($_GET['turno_id']) ||
                isset($_GET['status']) || isset($_GET['bloqueado']) || isset($_GET['desligado']) ||
                isset($_GET['sexo']) || isset($_GET['filhos']) || isset($_GET['empresa_contratante']) ||
                isset($_GET['periodo_tipo']) || isset($_GET['data_de']) || isset($_GET['data_ate'])) {
                $_SESSION['filtros_list_users'] = $filtros;
            }
        }
        
        // Tratar per_page
        if (isset($_GET['per_page']) && in_array((int)$_GET['per_page'], [10, 20, 50, 100])) {
            $this->limitResult = (int)$_GET['per_page'];
            $_SESSION['filtros_list_users']['per_page'] = $this->limitResult;
        } elseif (isset($_SESSION['filtros_list_users']['per_page'])) {
            $this->limitResult = (int)$_SESSION['filtros_list_users']['per_page'];
        }
        
        // Instanciar o Repository para recuperar os registros do banco de dados
        $listUsers = new UsersRepository();
        
        // Carregar departamentos e cargos para os filtros
        $this->data['departments'] = $listUsers->getDepartmentsForFilter();
        $this->data['positions'] = $listUsers->getPositionsForFilter();
        $this->data['empresas_contratantes'] = UserFormHelper::empresaContratanteOptions();
        $workShiftsRepo = new WorkShiftsRepository();
        $this->data['work_shifts'] = $workShiftsRepo->getAllWorkShiftsSelect();
        
        $totalUsers = $listUsers->getAmountUsers($filtros);
        $this->data['users'] = $listUsers->getAllUsers((int) $page, (int) $this->limitResult, $filtros);
        $pagination = PaginationService::generatePagination((int) $totalUsers, (int) $this->limitResult, (int) $page, 'list-users', array_merge($filtros, ['per_page' => $this->limitResult]));
        $this->data['pagination'] = $pagination;
        $this->data['pagination_total'] = $totalUsers;
        $this->data['per_page'] = $this->limitResult;
        $this->data['filtros'] = $filtros; // Passar filtros para a view
        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Listar Usuários',
            'menu' => 'list-users',
            'buttonPermission' => ['CreateUser', 'ViewUser', 'UpdateUser', 'DeleteUser', 'ImportUsers'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/users/list", $this->data);
        $loadView->loadView();
    }
}
