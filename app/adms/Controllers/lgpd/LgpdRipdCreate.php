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
 * Controller responsável pela criação de Relatórios de Impacto à Proteção de Dados (RIPD).
 *
 * @package App\adms\Controllers\lgpd
 */
class LgpdRipdCreate
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
     * Método principal para exibir o formulário de criação de RIPD.
     *
     * @return void
     */
    public function index(): void
    {
        try {
            // Verificar se é um POST request
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->processPostRequest();
                return; // Não continuar para carregar a view
            }
            
            // Carregar dados necessários para o formulário
            $this->data['aipds'] = $this->aipdRepo->getAll([], 1, 1000); // Buscar todas as AIPDs
            $this->data['users'] = $this->usersRepo->getAllUsersForSelect();
            
            // Configurar elementos da página
            $pageElements = [
                'title_head' => 'Criar Relatório de Impacto à Proteção de Dados (RIPD)',
                'menu' => 'lgpd-ripd',
                'buttonPermission' => ['LgpdRipd', 'LgpdRipdCreate', 'LgpdRipdEdit', 'LgpdRipdView', 'LgpdRipdDelete'],
            ];
            
            $pageLayoutService = new PageLayoutService();
            $pageData = $pageLayoutService->configurePageElements($pageElements);
            
            // Mesclar dados de forma segura para evitar sobrescrever chaves existentes
            foreach ($pageData as $key => $value) {
                if (!isset($this->data[$key])) {
                    $this->data[$key] = $value;
                }
            }
            
            // Debug: Verificar dados antes de carregar a view
            error_log("DEBUG - Dados finais antes da view: " . json_encode(array_keys($this->data)));
            error_log("DEBUG - AIPDs carregadas: " . count($this->data['aipds'] ?? []));
            error_log("DEBUG - Usuários carregados: " . count($this->data['users'] ?? []));

            // Carregar a VIEW
            $loadView = new LoadViewService("adms/Views/lgpd/ripd/create", $this->data);
            $loadView->loadView();
        } catch (Exception $e) {
            error_log("Erro no controller LgpdRipdCreate: " . $e->getMessage());
            $_SESSION['error'] = "Erro ao carregar formulário de criação de RIPD!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-ripd");
            exit;
        }
    }
    
    /**
     * Processa requisições POST (criação e geração automática)
     */
    private function processPostRequest(): void
    {
        $this->data['form'] = [];
        
        // Debug: Log dos dados recebidos
        error_log("RIPD Create - POST recebido: " . json_encode($_POST));
        
        // Verificar se é geração automática ou criação manual
        if (isset($_POST['action']) && $_POST['action'] === 'generateFromAipd') {
            error_log("RIPD Create - Iniciando geração automática");
            // Geração automática
            $this->processGenerateFromAipd();
        } else {
            error_log("RIPD Create - Iniciando criação manual");
            // Criação manual
            $this->processManualCreation();
        }
    }

    /**
     * Processa a geração automática de RIPD baseado em AIPD
     */
    private function processGenerateFromAipd(): void
    {
        error_log("RIPD - processGenerateFromAipd iniciado");
        
        // Verificar CSRF
        if (!CSRFHelper::validateCSRFToken('ripd_generate', $_POST['csrf_token'] ?? '')) {
            error_log("RIPD - CSRF inválido");
            $_SESSION['error'] = "Token de segurança inválido!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-ripd-create");
            exit;
        }

        $aipdId = (int) ($_POST['aipd_id'] ?? 0);
        $elaboradorId = (int) ($_SESSION['user_id'] ?? 1);
        
        error_log("RIPD - AIPD ID: $aipdId, Elaborador ID: $elaboradorId");

        if (empty($aipdId)) {
            error_log("RIPD - AIPD ID vazio");
            $_SESSION['error'] = "AIPD não especificada!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-ripd-create");
            exit;
        }

        // Verificar se já existe RIPD para esta AIPD
        $existingRipd = $this->ripdRepo->getRipdByAipdId($aipdId);
        if ($existingRipd) {
            error_log("RIPD - Já existe RIPD para AIPD ID: $aipdId");
            $_SESSION['error'] = "Já existe um RIPD para esta AIPD!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-ripd-create");
            exit;
        }

        // Gerar RIPD automaticamente
        error_log("RIPD - Gerando RIPD automaticamente");
        $ripdData = $this->ripdRepo->generateRipdFromAipd($aipdId, $elaboradorId);
        
        if ($ripdData && $this->ripdRepo->create($ripdData)) {
            error_log("RIPD - RIPD criado com sucesso");
            $_SESSION['success'] = "RIPD gerado automaticamente com sucesso!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-ripd");
            exit;
        } else {
            error_log("RIPD - Erro ao criar RIPD");
            $_SESSION['error'] = "Erro ao gerar RIPD automaticamente!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-ripd-create");
            exit;
        }
    }

    /**
     * Processa a criação manual de RIPD
     */
    private function processManualCreation(): void
    {
        // Verificar CSRF
        if (!CSRFHelper::validateCSRFToken('ripd_create', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = "Token de segurança inválido!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-ripd-create");
            exit;
        }

        // Validar dados obrigatórios
        if (empty($_POST['aipd_id']) || empty($_POST['titulo']) || empty($_POST['elaborador_id'])) {
            $_SESSION['error'] = "Preencha todos os campos obrigatórios!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-ripd-create");
            exit;
        }

        // Preparar dados para criação
        $ripdData = [
            'aipd_id' => (int) $_POST['aipd_id'],
            'titulo' => trim($_POST['titulo']),
            'versao' => $_POST['versao'] ?? '1.0',
            'data_elaboracao' => $_POST['data_elaboracao'] ?? date('Y-m-d'),
            'elaborador_id' => (int) $_POST['elaborador_id'],
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

        // Criar o RIPD
        if ($this->ripdRepo->create($ripdData)) {
            $_SESSION['success'] = "RIPD criado com sucesso!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-ripd");
            exit;
        } else {
            $_SESSION['error'] = "Erro ao criar RIPD!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-ripd-create");
            exit;
        }
    }
}
