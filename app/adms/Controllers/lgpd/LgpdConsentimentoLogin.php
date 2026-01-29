<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdConsentimentosRepository;
use App\adms\Models\Repository\LgpdTermosRepository;
use App\adms\Models\Services\DbConnection;
use App\adms\Views\Services\LoadViewService;

/**
 * Coleta de consentimento LGPD no primeiro login do usuário do sistema.
 *
 * - Bloqueia o acesso até que o usuário aceite o termo vigente.
 * - Registra o aceite em adms_users (flag rápida) e em lgpd_consentimentos (histórico).
 */
class LgpdConsentimentoLogin
{
    private array $data = [];

    private const DEFAULT_VERSION = '1.0';

    /**
     * Verificar se existe termo ativo no sistema.
     */
    private function hasActiveTerm(): bool
    {
        $repo = new LgpdTermosRepository();
        $termo = $repo->getTermoAtivoPorTipo('login');
        
        if (!$termo) {
            $termo = $repo->getLastActiveTerm();
        }
        
        return !empty($termo);
    }

    /**
     * Obter termo vigente para login (se existir na tabela lgpd_termos).
     */
    private function getLoginTermo(): array
    {
        $repo = new LgpdTermosRepository();
        $termo = $repo->getTermoAtivoPorTipo('login');

        // Se não encontrar termo do tipo login, usar o último termo ativo como fallback
        if (!$termo) {
            $termo = $repo->getLastActiveTerm();
        }

        if (!$termo) {
            return [
                'versao' => $_ENV['LGPD_CONSENT_VERSION'] ?? self::DEFAULT_VERSION,
                'conteudo' => null,
            ];
        }

        // Garantir que o campo 'conteudo' esteja presente no retorno
        return [
            'versao' => $termo['versao'] ?? ($_ENV['LGPD_CONSENT_VERSION'] ?? self::DEFAULT_VERSION),
            'conteudo' => $termo['conteudo'] ?? null,
            'titulo' => $termo['titulo'] ?? null,
        ];
    }

    public function index(): void
    {
        // Requer usuário autenticado
        if (empty($_SESSION['user_id'])) {
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }

        // Verificar se é usuário manager - se for, redirecionar para dashboard
        $userId = (int)$_SESSION['user_id'];
        $repoConsent = new LgpdConsentimentosRepository();
        $conn = $repoConsent->getConnection();
        $stmt = $conn->prepare('SELECT username FROM adms_users WHERE id = :id');
        $stmt->bindValue(':id', $userId, \PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($user && isset($user['username']) && strtolower($user['username']) === 'manager') {
            header('Location: ' . $_ENV['URL_ADM'] . 'dashboard');
            exit;
        }

        $this->data['title_head'] = 'Uso de Dados Pessoais - LGPD';

        $termo = $this->getLoginTermo();
        
        // Se não houver termo ativo, redirecionar para dashboard
        if (empty($termo['conteudo']) && !$this->hasActiveTerm()) {
            header('Location: ' . $_ENV['URL_ADM'] . 'dashboard');
            exit;
        }
        
        $this->data['term_version'] = $termo['versao'] ?? self::DEFAULT_VERSION;
        // Garantir que o conteúdo seja passado corretamente (pode estar em 'conteudo' ou vazio)
        $this->data['term_content'] = !empty($termo['conteudo']) ? $termo['conteudo'] : null;

        $pageElements = [
            'title_head' => 'Uso de Dados Pessoais - LGPD',
            'menu' => '', // Sem menu selecionado
            'buttonPermission' => [],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/lgpd/consentimento_login', $this->data);
        $loadView->loadViewLogin();
    }

    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'lgpd-consentimento-login');
            exit;
        }

        if (empty($_SESSION['user_id'])) {
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }

        $userId = (int)$_SESSION['user_id'];

        // Verificar aceite do checkbox
        if (empty($_POST['lgpd_consent']) || $_POST['lgpd_consent'] !== '1') {
            $_SESSION['error'] = 'Você precisa aceitar o uso de dados pessoais para continuar utilizando o sistema.';
            header('Location: ' . $_ENV['URL_ADM'] . 'lgpd-consentimento-login');
            exit;
        }

        $termo = $this->getLoginTermo();
        $versao = $termo['versao'] ?? ($_ENV['LGPD_CONSENT_VERSION'] ?? self::DEFAULT_VERSION);

        // Atualizar flags em adms_users
        // Usa um repositório concreto (que herda de DbConnection) para obter a conexão
        $repoConsent = new LgpdConsentimentosRepository();
        $conn = $repoConsent->getConnection();
        $stmt = $conn->prepare(
            'UPDATE adms_users 
             SET lgpd_consent_given = 1,
                 lgpd_consent_date = NOW(),
                 lgpd_consent_version = :versao
             WHERE id = :id'
        );
        $stmt->bindValue(':versao', $versao);
        $stmt->bindValue(':id', $userId, \PDO::PARAM_INT);
        $stmt->execute();

        // Buscar dados do usuário para registrar no histórico
        $stmtUser = $conn->prepare('SELECT name, email FROM adms_users WHERE id = :id');
        $stmtUser->bindValue(':id', $userId, \PDO::PARAM_INT);
        $stmtUser->execute();
        $user = $stmtUser->fetch(\PDO::FETCH_ASSOC) ?: ['name' => 'Usuário', 'email' => null];

        // Registrar consentimento em lgpd_consentimentos (reutiliza o mesmo repositório)
        $repoConsent->create([
            'titular_nome' => $user['name'] ?? 'Usuário',
            'titular_email' => $user['email'] ?? null,
            'finalidade' => 'Uso do Sistema Administrativo Tiaraju, auditoria de acessos e registros de atividades.',
            'canal' => 'sistema_login',
            'data_consentimento' => date('Y-m-d H:i:s'),
            'status' => 'Ativo',
            'versao_termo' => $versao,
        ]);

        $_SESSION['success'] = 'Consentimento registrado com sucesso. Obrigado!';
        header('Location: ' . $_ENV['URL_ADM'] . 'dashboard');
        exit;
    }

    public function recusar(): void
    {
        // Usuário optou por não aceitar; fazer logout controlado
        $_SESSION['error'] = 'Sem o consentimento para uso de dados pessoais não é possível utilizar o sistema. ' .
            'Em caso de dúvidas, contate o departamento responsável pelo tratamento de dados.';

        // Limpar sessão
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'login');
        exit;
    }
}


