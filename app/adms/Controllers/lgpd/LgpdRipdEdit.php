<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdRipdRepository;
use App\adms\Models\Repository\LgpdAipdRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Helpers\CSRFHelper;
use Exception;

/**
 * Controller responsável pela edição de Relatórios de Impacto à Proteção de Dados (RIPD).
 *
 * @package App\adms\Controllers\lgpd
 */
class LgpdRipdEdit
{
    /** @var array $data Recebe os dados que devem ser enviados para a VIEW */
    private array $data = [];

    /** @var LgpdRipdRepository $ripdRepo */
    private LgpdRipdRepository $ripdRepo;

    /** @var LgpdAipdRepository $aipdRepo */
    private LgpdAipdRepository $aipdRepo;

    /** @var UsersRepository $usersRepo */
    private UsersRepository $usersRepo;

    public function __construct()
    {
        $this->ripdRepo = new LgpdRipdRepository();
        $this->aipdRepo = new LgpdAipdRepository();
        $this->usersRepo = new UsersRepository();
    }

    /**
     * Método principal para exibir o formulário de edição de RIPD.
     *
     * @param int $id ID do RIPD
     * @return void
     */
    public function index(int $id): void
    {
        try {
            // Buscar dados do RIPD
            $ripd = $this->ripdRepo->getRipdById($id);
            
            if (!$ripd) {
                $_SESSION['error'] = "RIPD não encontrado!";
                header("Location: " . $_ENV['URL_ADM'] . "lgpd-ripd");
                exit;
            }
            
            $this->data['ripd'] = $ripd;
            $this->data['aipds'] = $this->aipdRepo->getAll([], 1, 1000);
            $this->data['users'] = $this->usersRepo->getAllUsersForSelect();
            
            // Configurar elementos da página
            $pageElements = [
                'title_head' => 'Editar RIPD - ' . $ripd['codigo'],
                'menu' => 'lgpd-ripd',
                'buttonPermission' => ['LgpdRipd', 'LgpdRipdCreate', 'LgpdRipdEdit', 'LgpdRipdView', 'LgpdRipdDelete'],
            ];
            
            $pageLayoutService = new PageLayoutService();
            $pageLayoutData = $pageLayoutService->configurePageElements($pageElements);
            
            // Mesclar dados de forma segura
            foreach ($pageLayoutData as $key => $value) {
                $this->data[$key] = $value;
            }

            // Carregar a VIEW
            $loadView = new LoadViewService("adms/Views/lgpd/ripd/edit", $this->data);
            $loadView->loadView();
        } catch (Exception $e) {
            error_log("Erro no controller LgpdRipdEdit: " . $e->getMessage());
            $_SESSION['error'] = "Erro ao carregar formulário de edição de RIPD!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-ripd");
            exit;
        }
    }

    /**
     * Método para processar a atualização do RIPD.
     *
     * @param int $id ID do RIPD
     * @return void
     */
    public function update(int $id): void
    {
        try {
            // Verificar CSRF
            if (!CSRFHelper::validateCSRFToken('ripd_edit', $_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = "Token de segurança inválido!";
                header("Location: " . $_ENV['URL_ADM'] . "lgpd-ripd-edit/" . $id);
                exit;
            }

            // Verificar se o RIPD existe
            $existingRipd = $this->ripdRepo->getRipdById($id);
            if (!$existingRipd) {
                $_SESSION['error'] = "RIPD não encontrado!";
                header("Location: " . $_ENV['URL_ADM'] . "lgpd-ripd");
                exit;
            }

            // Validar dados obrigatórios
            if (empty($_POST['titulo']) || empty($_POST['elaborador_id'])) {
                $_SESSION['error'] = "Preencha todos os campos obrigatórios!";
                header("Location: " . $_ENV['URL_ADM'] . "lgpd-ripd-edit/" . $id);
                exit;
            }

            // Preparar dados para atualização
            $ripdData = [
                'titulo' => trim($_POST['titulo']),
                'versao' => $_POST['versao'] ?? '1.0',
                'data_elaboracao' => $_POST['data_elaboracao'] ?? date('Y-m-d'),
                'revisor_id' => !empty($_POST['revisor_id']) ? (int) $_POST['revisor_id'] : null,
                'aprovador_id' => !empty($_POST['aprovador_id']) ? (int) $_POST['aprovador_id'] : null,
                'status' => $_POST['status'] ?? 'Rascunho',
                'data_aprovacao' => !empty($_POST['data_aprovacao']) ? $_POST['data_aprovacao'] : null,
                'observacoes_revisao' => trim($_POST['observacoes_revisao'] ?? ''),
                'observacoes_aprovacao' => trim($_POST['observacoes_aprovacao'] ?? ''),
                'conclusao_geral' => trim($_POST['conclusao_geral'] ?? ''),
                'recomendacoes_finais' => trim($_POST['recomendacoes_finais'] ?? ''),
                'proximos_passos' => trim($_POST['proximos_passos'] ?? ''),
                'prazo_implementacao' => !empty($_POST['prazo_implementacao']) ? $_POST['prazo_implementacao'] : null,
                'responsavel_implementacao' => !empty($_POST['responsavel_implementacao']) ? (int) $_POST['responsavel_implementacao'] : null
            ];

            // Atualizar o RIPD
            if ($this->ripdRepo->update($id, $ripdData)) {
                $_SESSION['success'] = "RIPD atualizado com sucesso!";
                header("Location: " . $_ENV['URL_ADM'] . "lgpd-ripd-view/" . $id);
                exit;
            } else {
                $_SESSION['error'] = "Erro ao atualizar RIPD!";
                header("Location: " . $_ENV['URL_ADM'] . "lgpd-ripd-edit/" . $id);
                exit;
            }
        } catch (Exception $e) {
            error_log("Erro ao atualizar RIPD: " . $e->getMessage());
            $_SESSION['error'] = "Erro interno ao atualizar RIPD!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-ripd-edit/" . $id);
            exit;
        }
    }
}
