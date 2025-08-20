<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdTiaRepository;
use App\adms\Models\Repository\LgpdRopaRepository;
use App\adms\Models\Repository\LgpdDataGroupsRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Helpers\CSRFHelper;
use Exception;

/**
 * Controller responsável pela edição de Testes de Impacto às Atividades (TIA).
 *
 * @package App\adms\Controllers\lgpd
 */
class LgpdTiaEdit
{
    /** @var array $data Recebe os dados que devem ser enviados para a VIEW */
    private array $data = [];

    /** @var LgpdTiaRepository $tiaRepo */
    private LgpdTiaRepository $tiaRepo;

    public function __construct()
    {
        $this->tiaRepo = new LgpdTiaRepository();
    }

    /**
     * Método para editar um teste TIA específico.
     *
     * @param int $id ID do teste TIA
     * @return void
     */
    public function index(int $id): void
    {
        try {
            // Buscar dados do teste TIA
            $tia = $this->tiaRepo->getTiaById($id);
            
            if (!$tia) {
                $_SESSION['error'] = "Teste TIA não encontrado!";
                header("Location: " . $_ENV['URL_ADM'] . "lgpd-tia");
                exit;
            }
            
            $this->data['form'] = $tia;
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->data['form'] = $_POST;
                
                // Validar dados obrigatórios
                if ($this->validarDados($this->data['form'])) {
                    $result = $this->tiaRepo->update($id, $this->data['form']);
                    
                    if ($result) {
                        $_SESSION['success'] = "Teste TIA atualizado com sucesso!";
                        header("Location: " . $_ENV['URL_ADM'] . "lgpd-tia-view/" . $id);
                        exit;
                    } else {
                        $this->data['errors'][] = "Teste TIA não atualizado!";
                    }
                }
            }
            
            // Buscar grupos de dados relacionados
            $this->data['data_groups'] = $this->tiaRepo->getDataGroupsByTiaId($id);
            
            // Carregar dados para os selects
            $departmentsRepo = new DepartmentsRepository();
            $ropaRepo = new LgpdRopaRepository();
            $dataGroupsRepo = new LgpdDataGroupsRepository();
            $usersRepo = new UsersRepository();
            
            $this->data['departamentos'] = $departmentsRepo->getAllDepartmentsSelect();
            $this->data['ropas'] = $ropaRepo->getAllRopaForSelect();
            $this->data['todos_data_groups'] = $dataGroupsRepo->getAllDataGroupsForSelect();
            $this->data['usuarios'] = $usersRepo->getAllUsersForSelect();
            
            // Configurar elementos da página
            $pageElements = [
                'title_head' => 'Editar Teste TIA: ' . $tia['codigo'],
                'menu' => 'lgpd-tia',
                'buttonPermission' => ['LgpdTiaEdit'],
            ];
            
            $pageLayoutService = new PageLayoutService();
            $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

            // Carregar a VIEW
            $loadView = new LoadViewService("adms/Views/lgpd/tia/edit", $this->data);
            $loadView->loadView();
        } catch (Exception $e) {
            error_log("Erro no controller LgpdTiaEdit: " . $e->getMessage());
            $_SESSION['error'] = "Erro ao carregar teste TIA!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-tia");
            exit;
        }
    }

    /**
     * Valida os dados do formulário
     *
     * @param array $data
     * @return bool
     */
    private function validarDados(array $data): bool
    {
        $errors = [];
        
        if (empty($data['titulo'])) {
            $errors[] = "Título é obrigatório";
        }
        
        if (empty($data['departamento_id'])) {
            $errors[] = "Departamento é obrigatório";
        }
        
        if (empty($data['data_teste'])) {
            $errors[] = "Data do teste é obrigatória";
        }
        
        if (empty($data['resultado'])) {
            $errors[] = "Resultado é obrigatório";
        }
        
        if (empty($data['status'])) {
            $errors[] = "Status é obrigatório";
        }
        
        if (!empty($errors)) {
            $this->data['errors'] = $errors;
            return false;
        }
        
        return true;
    }
}
