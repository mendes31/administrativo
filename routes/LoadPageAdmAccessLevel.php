<?php

namespace Routes;

use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\NotificationOpenHelper;
use App\adms\Helpers\SlugController;
use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\PagesRoutesRepository;

/**
 * Roteador principal do administrativo com checagem de rota e permissão.
 *
 * Fluxo resumido:
 * 1. Resolve a página em `adms_pages` (via {@see PagesRoutesRepository}) pelo controller ou slug.
 * 2. Se `public_page = 1`: carrega a controller **sem** exigir login nem ACL.
 * 3. Caso contrário: exige sessão e {@see PagesRoutesRepository::checkUserPagePermission} (matriz `adms_access_levels_pages`).
 * Exceções pontuais: ServeFile (sem sessão/ACL — segurança no FileServer), central de notificações com sessão, etc.
 *
 * Campos `default_page` / `basicControllers` **não** são consultados aqui; afetam só a inicialização da matriz
 * de permissões (ver {@see \App\adms\Models\Repository\AccessLevelsPagesRepository::initializeForNewAccessLevel}).
 */
class LoadPageAdmAccessLevel
{

    /** 
     * @var string $urlController Recebe da URL o nome da controller 
     */
    private string $urlController;

    /** 
     * @var string $urlParameter Recebe da URL o parâmetro 
     */
    private string $urlParameter;

    /** 
     * @var string $classLoad Controller que deve ser carregada 
     */
    private string $classLoad;

    /** 
     * @var array|bool $page Armazena o resultado da busca pela página 
     */
    private array|bool $page;

    public function loadPageAdm(string|null $urlController, string|null $urlParameter)
    {

        $this->urlController = $urlController;
        $this->urlParameter = $urlParameter;

        // Tratamento especial: validação de senha na tela de bloqueio
        // Esta rota é usada apenas para o próprio usuário validar a senha
        // e não deve depender da permissão da página de Administração de Senhas.
        if ($this->urlController === 'AjaxPasswordPolicy' && in_array($this->urlParameter, ['validate-password', 'validatePassword'], true)) {
            $isAjax = (
                !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
            ) || (
                isset($_SERVER['HTTP_ACCEPT']) &&
                str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')
            );

            if (!$isAjax) {
                // Bloquear acesso direto via navegador
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'error'   => 'Endpoint disponível apenas para requisições AJAX.'
                ]);
                exit;
            }

            // Exigir usuário logado, mas sem checar permissão de página
            if (empty($_SESSION['user_id'])) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'sucesso' => false,
                    'logout'  => true,
                    'mensagem' => 'Sessão expirada. Faça login novamente.'
                ]);
                exit;
            }

            $controller = new \App\adms\Controllers\settings\AjaxPasswordPolicy();
            if (method_exists($controller, 'validatePassword')) {
                $controller->validatePassword();
                exit;
            }

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error'   => 'Método de validação de senha não encontrado.'
            ]);
            exit;
        }

        // Rotas técnicas internas (AJAX) que ainda não estão mapeadas em pages_routes,
        // mas precisam funcionar normalmente e responder em JSON (ex.: Kanban RH, planilhas).
        $internalAjaxMap = [
            'UploadSpreadsheet'           => "\\App\\adms\\Controllers\\dashboards\\UploadSpreadsheet",
            'GetSpreadsheetFields'        => "\\App\\adms\\Controllers\\dashboards\\GetSpreadsheetFields",
            'RhAtualizarStatusCandidatura' => "\\App\\adms\\Controllers\\rh\\RhAtualizarStatusCandidatura",
            // Exportações usadas em relatórios (podem não estar cadastradas em pages_routes).
            // Bypass para evitar Erro 003 quando a entrada de rota no banco ainda não existe.
            'ExportRelatorioInformativoExcel' => "\\App\\adms\\Controllers\\informativos\\ExportRelatorioInformativoExcel",
            'ExportRelatorioPolicyExcel'      => "\\App\\adms\\Controllers\\policies\\ExportRelatorioPolicyExcel",
            // Autocomplete de menções na timeline (GET JSON); evita "rota não encontrada" se adms_pages ainda não tiver a página.
            'TimelineSearchUsers' => "\\App\\adms\\Controllers\\timeline\\TimelineSearchUsers",
            // Busca de colaboradores para RSVP manual (gestor/creator).
            'CompanyEventsSearchUsers' => "\\App\\adms\\Controllers\\companyEvents\\CompanyEventsSearchUsers",
            // POST do formulário da timeline; mesmo motivo (cadastro/ directory incorreto no banco gerava Erro 004).
            'CreateTimelinePost' => "\\App\\adms\\Controllers\\timeline\\CreateTimelinePost",
            // JSON da timeline (curtidas, comentários, denúncia) — evita Erro 004/HTML quando adms_pages está inconsistente.
            'TimelineLike'    => "\\App\\adms\\Controllers\\timeline\\TimelineLike",
            // Reações em comentário (JSON); mesmo motivo que TimelineLike/TimelineComment.
            'TimelineCommentLike' => "\\App\\adms\\Controllers\\timeline\\TimelineCommentLike",
            'TimelinePostReactions' => "\\App\\adms\\Controllers\\timeline\\TimelinePostReactions",
            'UpdateTimelinePost' => "\\App\\adms\\Controllers\\timeline\\UpdateTimelinePost",
            'DeleteTimelinePost' => "\\App\\adms\\Controllers\\timeline\\DeleteTimelinePost",
            'TimelineComment' => "\\App\\adms\\Controllers\\timeline\\TimelineComment",
            'TimelineReport'  => "\\App\\adms\\Controllers\\timeline\\TimelineReport",
            'MarkNotificationsRead' => "\\App\\adms\\Controllers\\notifications\\MarkNotificationsRead",
            'PushSubscribe' => "\\App\\adms\\Controllers\\push\\PushSubscribe",
            'DashboardBirthdaysAjax' => "\\App\\adms\\Controllers\\dashboard\\DashboardBirthdaysAjax",
            // Renovação de sessão (AJAX) — evita falha de rota se adms_pages estiver incompleto.
            'ExtendSession' => "\\App\\adms\\Controllers\\session\\ExtendSession",
            // Reserva de salas: bloqueio temporário de intervalo (book-room).
            'RoomBookingSlotHold' => "\\App\\adms\\Controllers\\rooms\\RoomBookingSlotHold",
            // Reserva de salas: modelo CSV + POST de importação por sala (rota técnica; permissão no controller).
            'ImportRoomBookings' => "\\App\\adms\\Controllers\\rooms\\ImportRoomBookings",
            'ImportInvCostDre' => "\\App\\adms\\Controllers\\inventory\\ImportInvCostDre",
            'SaveInvCostAllocationRules' => "\\App\\adms\\Controllers\\inventory\\SaveInvCostAllocationRules",
            'SaveInvEnergyClassFactors' => "\\App\\adms\\Controllers\\inventory\\SaveInvEnergyClassFactors",
            'SaveInvComplexityLevelFactors' => "\\App\\adms\\Controllers\\inventory\\SaveInvComplexityLevelFactors",
            'SaveInvCostPeriodItems' => "\\App\\adms\\Controllers\\inventory\\SaveInvCostPeriodItems",
            'SaveInvCostPeriodScenarioProduction' => "\\App\\adms\\Controllers\\inventory\\SaveInvCostPeriodScenarioProduction",
            'ExportInvCostPeriodSkuResults' => "\\App\\adms\\Controllers\\inventory\\ExportInvCostPeriodSkuResults",
            'DownloadInvCostDreTemplate' => "\\App\\adms\\Controllers\\inventory\\DownloadInvCostDreTemplate",
            'DeleteInvCostPeriod' => "\\App\\adms\\Controllers\\inventory\\DeleteInvCostPeriod",
            'SstPacoteExamesAso' => "\\App\\adms\\Controllers\\sst\\SstPacoteExamesAso",
        ];
        if (isset($internalAjaxMap[$this->urlController])) {
            $this->classLoad = $internalAjaxMap[$this->urlController];

            if (class_exists($this->classLoad)) {
                // Garantir usuário logado (exportação é endpoint privado).
                if (empty($_SESSION['user_id']) && empty($_SESSION['user_name']) && empty($_SESSION['user_email'])) {
                    header("Location: {$_ENV['URL_ADM']}login");
                    exit;
                }
                $this->loadMetodo();
                return;
            }
        }

        $accessLevelPage = new PagesRoutesRepository();
        $this->page = $accessLevelPage->getPage($this->urlController ?? '');
        // Cron de lembretes folha: slug com underscores não mapeia para PayrollRemindersCron no SlugController.
        if (!$this->page) {
            $uriCron = (string)($_SERVER['REQUEST_URI'] ?? '');
            if (str_contains($uriCron, 'payroll_reminders_cron') || str_contains($uriCron, 'payroll-reminders-cron')) {
                $this->page = $accessLevelPage->getPageByControllerUrl('payroll-reminders-cron');
                if ($this->page) {
                    $this->urlController = 'PayrollRemindersCron';
                }
            }
        }
        if (!$this->page) {
            $uriCron = (string)($_SERVER['REQUEST_URI'] ?? '');
            if (str_contains($uriCron, 'informativos_publish_cron') || str_contains($uriCron, 'informativos-publish-cron')) {
                $this->page = $accessLevelPage->getPageByControllerUrl('informativos-publish-cron');
                if ($this->page) {
                    $this->urlController = 'InformativosPublishCron';
                }
            }
        }
        // URI pode trazer o slug mesmo se o primeiro segmento vier vazio/corrompido em alguns hosts.
        if (!$this->page && preg_match('#list-connected-users#i', (string)($_SERVER['REQUEST_URI'] ?? ''))) {
            $this->page = $accessLevelPage->getPageByControllerUrl('list-connected-users');
            if ($this->page) {
                $this->urlController = 'ListConnectedUsers';
            }
        }
        if (!$this->page && preg_match('#list-users-last-access#i', (string)($_SERVER['REQUEST_URI'] ?? ''))) {
            $this->page = $accessLevelPage->getPageByControllerUrl('list-users-last-access');
            if ($this->page) {
                $this->urlController = 'ListUsersLastAccess';
            }
        }

        // Imagens/anexos via serve-file: se a linha em adms_pages faltar ou estiver inativa, ainda resolvemos a rota.
        if (!$this->page && preg_match('#serve-file|servefile#i', (string)($_SERVER['REQUEST_URI'] ?? ''))) {
            $this->page = $accessLevelPage->getPageByControllerUrl('serve-file') ?: $accessLevelPage->getPage('ServeFile');
            if (!$this->page) {
                $this->page = [
                    'id_ap' => 0,
                    'controller' => 'ServeFile',
                    'controller_url' => 'serve-file',
                    'directory' => 'serveFile',
                    'public_page' => 1,
                    'name_app' => 'adms',
                ];
            }
            $this->urlController = 'ServeFile';
        }

        // URI pública na raiz: /canaldenuncia (gateway fora de /administrativo/)
        if (!$this->page && preg_match('#/canaldenuncia(?:/|\\?|$)#i', (string)($_SERVER['REQUEST_URI'] ?? ''))) {
            $this->page = $accessLevelPage->getPageByControllerUrl('canaldenuncia')
                ?: $accessLevelPage->getPage('CanalDenuncia');
            if ($this->page) {
                $this->urlController = 'CanalDenuncia';
            }
        }

        // URI pública na raiz: /lgpd (gateway fora de /administrativo/)
        if (!$this->page && preg_match('#/lgpd(?:/|\\?|$)#i', (string)($_SERVER['REQUEST_URI'] ?? ''))) {
            $this->page = $accessLevelPage->getPageByControllerUrl('lgpd')
                ?: $accessLevelPage->getPage('LgpdPublico');
            if ($this->page) {
                $this->urlController = 'LgpdPublico';
            }
        }

        // 1) Página não encontrada no cadastro de rotas/páginas
        if (!$this->page) {
            GenerateLog::generateLog("error", "Página/rota não encontrada em pages_routes.", [
                'pagina'   => $this->urlController,
                'parametro'=> $this->urlParameter,
                'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            ]);

            $isAjax = (
                !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
            ) || (
                isset($_SERVER['HTTP_ACCEPT']) &&
                str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')
            );

            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'error'   => 'Rota não encontrada. Verifique o cadastro de páginas/rotas.'
                ]);
                exit;
            }

            die("Erro 003: Por favor tente novamente. Caso o problema persista, entre em contato com o administrador {$_ENV['EMAIL_ADM']}");
        }

        // 2) Página pública: não precisa verificar login/permissão
        if ($this->page['public_page'] == 1) {
            $this->checkControllersExists();
            return;
        }

        // 2b) ServeFile: ficheiros apenas sob public/adms/uploads — validação em {@see FileServer}.
        // Não exigir sessão nem permissão em adms_access_levels_pages: sem isto, níveis sem linha para a
        // página "Servir arquivo" quebram avatares e imagens em todo o painel (GET de <img> não leva ACL).
        // Alinhado ao comentário em {@see LoadPageAdm::$listPgPublic} (ServeFile como rota técnica pública).
        if ($this->isServeFileRoute()) {
            $this->checkControllersExists();
            return;
        }

        // 2c) Central de notificações do próprio usuário (dados filtrados por user_id na sessão).
        // Sem isso, "Ver todas" no sino e itens sem link_url negam acesso por ACL indevidamente.
        if ($this->isPersonalNotificationsCenterRoute() && !empty($_SESSION['user_id'])) {
            $this->checkControllersExists();
            return;
        }

        // 3) Página restrita: precisa estar logado e ter permissão
        if ($this->verifyLogin()) {
            $this->checkControllersExists();
            return;
        }

        // 4) Usuário não logado ou sem permissão:
        //    - Se não estiver logado, redirecionar para login (evita Erro 003 após expiração de sessão)
        //    - Se estiver logado mas sem permissão, registrar log e exibir mensagem apropriada

        $estaLogado = !empty($_SESSION['user_id']);

        if (!$estaLogado) {
            // Sessão expirada ou usuário não autenticado
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';

            GenerateLog::generateLog("info", "Acesso a página restrita sem sessão válida. Redirecionando para login.", [
                'pagina'      => $this->urlController,
                'request_uri' => $requestUri,
            ]);

            // Guardar URL de retorno para, após o login, voltar para a página que o usuário estava
            if (empty($_SESSION['return_url'])) {
                \App\adms\Helpers\ReturnUrlHelper::storeFromCurrentRequest();
            }

            $_SESSION['error'] = "Sua sessão expirou ou você não está logado. Faça login novamente.";
            header("Location: {$_ENV['URL_ADM']}login");
            exit;
        }

        // Usuário logado, mas sem permissão para a página
        GenerateLog::generateLog("error", "Acesso negado: usuário sem permissão para a página.", [
            'pagina'      => $this->urlController,
            'parametro'   => $this->urlParameter,
            'user_id'     => $_SESSION['user_id'] ?? null,
            'access_page' => $this->page['id_ap'] ?? null,
        ]);

        $isAjax = (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        ) || (
            isset($_SERVER['HTTP_ACCEPT']) &&
            str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')
        );

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error'   => 'Você não tem permissão para acessar este recurso.'
            ]);
            exit;
        }

        // Flash só em tentativa explícita de navegação para página HTML.
        // Evita poluir o dashboard com aviso gerado por chamadas indiretas/background.
        $accept = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
        $dest = strtolower((string)($_SERVER['HTTP_SEC_FETCH_DEST'] ?? ''));
        $mode = strtolower((string)($_SERVER['HTTP_SEC_FETCH_MODE'] ?? ''));
        $userNav = (string)($_SERVER['HTTP_SEC_FETCH_USER'] ?? '');
        $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $isSubResource = in_array($dest, ['image', 'style', 'script', 'font', 'audio', 'video', 'track'], true);
        $isHtmlDocument = str_contains($accept, 'text/html');
        $isDocumentNavigation = ($dest === 'document') || ($mode === 'navigate' && $userNav === '?1');
        $isGetNavigation = $method === 'GET';
        $shouldFlashPermissionDenied = !$isSubResource && $isHtmlDocument && $isDocumentNavigation && $isGetNavigation;

        if ($shouldFlashPermissionDenied) {
            $_SESSION['msg'] = '<div class="alert alert-warning">Você não possui permissão para acessar esta página.</div>';
        }
        header("Location: {$_ENV['URL_ADM']}dashboard");
        exit;
    }

    private function verifyLogin(): bool
    {
        if ($_SESSION['user_id'] ?? false) {

            $accessLevelPage = new PagesRoutesRepository();
            if ($accessLevelPage->checkUserPagePermission($this->page['id_ap'])) {
                return true;
            }

            $ctrl = (string) ($this->page['controller'] ?? '');
            // Listagem de salas: alinhado ao menu (quem vê/edita/apaga salas acede à lista mesmo sem página "ListMeetingRooms").
            if ($ctrl === 'ListMeetingRooms' && $accessLevelPage->checkUserAnyPagePermissionForControllers([
                'ListMeetingRooms', 'ViewMeetingRoom', 'UpdateMeetingRoom', 'DeleteMeetingRoom',
            ])) {
                return true;
            }
            // Calendário: quem lista ou reserva salas deve conseguir abrir o calendário.
            if ($ctrl === 'RoomCalendar' && $accessLevelPage->checkUserAnyPagePermissionForControllers([
                'RoomCalendar', 'ListMeetingRooms', 'BookRoom',
            ])) {
                return true;
            }
            // Lista de reservas: utilizadores com fluxo de reserva sem página "ListBookings" explícita.
            if ($ctrl === 'ListBookings' && $accessLevelPage->checkUserAnyPagePermissionForControllers([
                'ListBookings', 'ViewBooking', 'CreateBooking', 'UpdateBooking', 'CancelBooking',
            ])) {
                return true;
            }
            // Eventos LNT: quem recebe o alerta no sino (equipe de treinamentos) deve conseguir abrir a lista,
            // mesmo sem a página ListTrainingLntEvents explícita no nível de acesso.
            if ($ctrl === 'ListTrainingLntEvents' && $accessLevelPage->checkUserAnyPagePermissionForControllers([
                'ListTrainingLntEvents',
                'ListTrainings',
                'CreateTraining',
                'TrainingPositions',
                'LinkTrainingUsers',
                'MatrixByUser',
                'ListTrainingStatus',
            ])) {
                return true;
            }
            if ($ctrl === 'ImportInvCostDre' && $accessLevelPage->checkUserAnyPagePermissionForControllers([
                'ImportInvCostDre', 'ViewInvCostPeriod', 'ListInvCostPeriods',
            ])) {
                return true;
            }
            if ($ctrl === 'SaveInvCostAllocationRules' && $accessLevelPage->checkUserAnyPagePermissionForControllers([
                'SaveInvCostAllocationRules', 'ViewInvCostPeriod', 'ListInvCostPeriods',
            ])) {
                return true;
            }
            if ($ctrl === 'SaveInvEnergyClassFactors' && $accessLevelPage->checkUserAnyPagePermissionForControllers([
                'SaveInvEnergyClassFactors', 'ListInvEnergyClassFactors', 'ListInvCostPeriods',
            ])) {
                return true;
            }
            if ($ctrl === 'SaveInvComplexityLevelFactors' && $accessLevelPage->checkUserAnyPagePermissionForControllers([
                'SaveInvComplexityLevelFactors', 'ListInvComplexityLevelFactors', 'ListInvCostPeriods',
            ])) {
                return true;
            }
            if ($ctrl === 'SaveInvCostPeriodItems' && $accessLevelPage->checkUserAnyPagePermissionForControllers([
                'SaveInvCostPeriodItems', 'ViewInvCostPeriod', 'ListInvCostPeriods',
            ])) {
                return true;
            }
            if ($ctrl === 'SaveInvCostPeriodScenarioProduction' && $accessLevelPage->checkUserAnyPagePermissionForControllers([
                'SaveInvCostPeriodScenarioProduction', 'ViewInvCostPeriod', 'ListInvCostPeriods',
            ])) {
                return true;
            }
            if ($ctrl === 'ExportInvCostPeriodSkuResults' && $accessLevelPage->checkUserAnyPagePermissionForControllers([
                'ExportInvCostPeriodSkuResults', 'ViewInvCostPeriod', 'ListInvCostPeriods',
            ])) {
                return true;
            }
            if ($ctrl === 'DownloadInvCostDreTemplate' && $accessLevelPage->checkUserAnyPagePermissionForControllers([
                'DownloadInvCostDreTemplate', 'ViewInvCostPeriod', 'ImportInvCostDre', 'ListInvCostPeriods',
            ])) {
                return true;
            }
            if ($ctrl === 'DeleteInvCostPeriod' && $accessLevelPage->checkUserAnyPagePermissionForControllers([
                'DeleteInvCostPeriod', 'CreateInvCostPeriod', 'UpdateInvCostPeriod', 'ListInvCostPeriods',
            ])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Identifica a rota de arquivos em public/adms/uploads (evita depender só de adms_pages.controller).
     */
    private function isServeFileRoute(): bool
    {
        if (($this->urlController ?? '') === 'ServeFile') {
            return true;
        }
        $page = is_array($this->page) ? $this->page : [];
        $ctrl = (string)($page['controller'] ?? '');
        if ($ctrl === 'ServeFile') {
            return true;
        }
        $slug = strtolower((string)($page['controller_url'] ?? ''));
        if ($slug === 'serve-file' || $slug === 'servefile') {
            return true;
        }
        $uri = strtolower((string)($_SERVER['REQUEST_URI'] ?? ''));
        if (str_contains($uri, 'serve-file') || str_contains($uri, 'servefile')) {
            return true;
        }

        return false;
    }

    /**
     * Lista de notificações do colaborador (Notificacoes / list-notifications legado).
     */
    private function isPersonalNotificationsCenterRoute(): bool
    {
        $uc = (string)($this->urlController ?? '');
        if ($uc === 'Notificacoes' || $uc === 'ListNotifications') {
            return true;
        }
        $ctrl = (string)($this->page['controller'] ?? '');
        if ($ctrl === 'Notificacoes' || $ctrl === 'ListNotifications') {
            return true;
        }
        $slug = strtolower((string)($this->page['controller_url'] ?? ''));
        if (in_array($slug, ['notificacoes', 'list-notifications'], true)) {
            return true;
        }
        $uri = strtolower((string)($_SERVER['REQUEST_URI'] ?? ''));
        if (preg_match('#/(notificacoes|list-notifications)(/|\\?|$)#', $uri)) {
            return true;
        }

        return false;
    }

    /**
     * Pacote vindo de adms_packages_pages.name deve coincidir com o namespace (PSR-4).
     * No Linux, "Adms" ≠ "adms" — a pasta real é app/adms/ (minúsculo).
     */
    private function normalizeAppPackageName(string $name): string
    {
        if ($name !== '' && strcasecmp($name, 'adms') === 0) {
            return 'adms';
        }

        return $name;
    }

    /**
     * Diretório em adms_pages.directory deve coincidir com a pasta em app/adms/Controllers/ (PSR-4).
     * No Windows costuma funcionar com caixa errada; no Linux o autoload falha.
     */
    private function canonicalizeControllerDirectory(string $directory): string
    {
        if ($directory === '') {
            return $directory;
        }

        static $knownOnDisk = [
            'accessLevels', 'accountsPlan', 'analytics', 'banks', 'branches', 'companyEvents',
            'cashFlow', 'costCenter', 'crm', 'customer', 'dashboard', 'dashboards', 'departments',
            'documents', 'errors', 'evaluations', 'financialReports', 'frequency',
            'groupsPages', 'informativos', 'inventory', 'legal', 'lgpd', 'login', 'logs',
            'movement', 'notifications', 'packages', 'pages', 'pay', 'paymentMethod',
            'performance', 'permission', 'pdi', 'policies', 'portal', 'portaria', 'positions', 'projects',
            'receive', 'reports', 'rh', 'rooms', 'serveFile', 'Services', 'session',
            'settings', 'strategicIndicators', 'strategicPlans', 'supplier', 'timeline', 'gamification',
            'trainings', 'ti', 'users', 'workShifts', 'whistleblowing', 'imports',
        ];

        foreach ($knownOnDisk as $canonical) {
            if (strcasecmp($directory, $canonical) === 0) {
                return $canonical;
            }
        }

        return $directory;
    }

    /**
     * `adms_pages.controller` deve ser o nome da classe PHP (PascalCase), ex.: ListConnectedUsers.
     * O slug da URL fica em `controller_url` (ex.: list-connected-users). Se o slug foi gravado no
     * campo errado, o autoload monta um identificador inválido e falha no Linux.
     */
    private function resolvePhpControllerClassName(string $fromDb, string $fromUrl): string
    {
        $fromDb = trim($fromDb);
        if ($fromDb === '') {
            return $fromUrl;
        }
        if (str_contains($fromDb, '-')) {
            return SlugController::slugController($fromDb);
        }

        return $fromDb;
    }

    private function projectRoot(): string
    {
        return defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__);
    }

    /**
     * Garante que a classe exista: Composer, depois require_once em app/ (vários caminhos no Linux).
     */
    private function ensureControllerClassLoaded(): bool
    {
        if (class_exists($this->classLoad, false)) {
            return true;
        }
        if (class_exists($this->classLoad)) {
            return true;
        }

        foreach ($this->candidateControllerPhpFiles() as $file) {
            if ($file === '' || !is_readable($file)) {
                continue;
            }
            require_once $file;
            if (class_exists($this->classLoad, false)) {
                return true;
            }
        }

        return class_exists($this->classLoad);
    }

    /**
     * @return list<string>
     */
    private function candidateControllerPhpFiles(): array
    {
        $root = $this->projectRoot();
        $out = [];

        $primary = $this->resolveControllerFilePathFromFqcn();
        if ($primary !== null) {
            $out[] = $primary;
        }

        $fqcn = ltrim($this->classLoad, '\\');
        if (str_ends_with($fqcn, 'ListConnectedUsers')) {
            $out[] = $root . '/app/adms/Controllers/logs/ListConnectedUsers.php';
            $out[] = $root . '/app/adms/Controllers/Logs/ListConnectedUsers.php';
        }
        if (str_ends_with($fqcn, 'ListUsersLastAccess')) {
            $out[] = $root . '/app/adms/Controllers/logs/ListUsersLastAccess.php';
            $out[] = $root . '/app/adms/Controllers/Logs/ListUsersLastAccess.php';
        }
        if (str_ends_with($fqcn, 'ExportUsersLastAccessPdf')) {
            $out[] = $root . '/app/adms/Controllers/logs/ExportUsersLastAccessPdf.php';
        }
        if (str_ends_with($fqcn, 'ExportUsersLastAccessExcel')) {
            $out[] = $root . '/app/adms/Controllers/logs/ExportUsersLastAccessExcel.php';
        }

        return array_values(array_unique(array_filter($out)));
    }

    /**
     * FQN App\adms\Controllers\logs\ListConnectedUsers → app/adms/Controllers/logs/ListConnectedUsers.php
     */
    private function resolveControllerFilePathFromFqcn(): ?string
    {
        $fqcn = ltrim($this->classLoad, '\\');
        $parts = explode('\\', $fqcn);
        if (count($parts) < 2 || ($parts[0] ?? '') !== 'App') {
            return null;
        }

        return $this->projectRoot() . '/app/' . implode('/', array_slice($parts, 1)) . '.php';
    }

    /**
     * Verificar se a controller existe.
     * 
     * Este método percorre os pacotes e diretórios definidos para verificar se a classe controller correspondente à página existe.
     * Se a classe for encontrada, o método `loadMetodo` é chamado para verificar a existência do método "index" e carregá-lo.
     * 
     * @return bool Retorna verdadeiro se a controller existir, falso caso contrário.
     */
    private function checkControllersExists(): bool
    {
        // Nome da classe: cadastro (ap.controller), corrigindo slug no lugar do nome da classe.
        $controllerClass = $this->resolvePhpControllerClassName(
            (string)($this->page['controller'] ?? ''),
            $this->urlController
        );
        $pkg = $this->normalizeAppPackageName((string)($this->page['name_app'] ?? 'adms'));
        $directory = $this->canonicalizeControllerDirectory((string)($this->page['directory'] ?? ''));

        $this->classLoad = "\\App\\{$pkg}\\Controllers\\{$directory}\\{$controllerClass}";

        // Rota conhecida → FQN fixo (não depender só de controller_url no array PDO / cadastro).
        $slug = (string)($this->page['controller_url'] ?? '');
        $uri = (string)($_SERVER['REQUEST_URI'] ?? '');
        if ($slug === 'list-connected-users'
            || $this->urlController === 'ListConnectedUsers'
            || preg_match('#list-connected-users#i', $uri)) {
            $this->classLoad = \App\adms\Controllers\logs\ListConnectedUsers::class;
        }
        if ($slug === 'list-users-last-access'
            || $this->urlController === 'ListUsersLastAccess'
            || preg_match('#list-users-last-access#i', $uri)) {
            $this->classLoad = \App\adms\Controllers\logs\ListUsersLastAccess::class;
        }
        if ($slug === 'export-users-last-access-pdf'
            || $this->urlController === 'ExportUsersLastAccessPdf'
            || preg_match('#export-users-last-access-pdf#i', $uri)) {
            $this->classLoad = \App\adms\Controllers\logs\ExportUsersLastAccessPdf::class;
        }
        if ($slug === 'export-users-last-access-excel'
            || $this->urlController === 'ExportUsersLastAccessExcel'
            || preg_match('#export-users-last-access-excel#i', $uri)) {
            $this->classLoad = \App\adms\Controllers\logs\ExportUsersLastAccessExcel::class;
        }

        if ($this->ensureControllerClassLoaded()) {
            $this->loadMetodo();

            return true;
        }

        GenerateLog::generateLog("error", "Classe da controller não encontrada pelo autoload.", [
            'class' => $this->classLoad,
            'pagina' => $this->urlController,
            'directory_cadastro' => $this->page['directory'] ?? null,
            'name_app_cadastro' => $this->page['name_app'] ?? null,
            'app_root' => $this->projectRoot(),
            'candidatos_arquivo' => $this->candidateControllerPhpFiles(),
            'acesso_total_sistema' => UserAccessHelper::hasFullSystemAccess(),
        ]);
        $hintPath = $this->resolveControllerFilePathFromFqcn();
        $hint = $hintPath !== null
            ? ('Esperado (PSR-4): ' . str_replace($this->projectRoot() . '/', '', $hintPath))
            : ('Classe: ' . $this->classLoad);
        die(
            'Erro 006: arquivo da controller não carregado (classe PHP ausente ou caminho incorreto no servidor). '
            . 'Não é falta de permissão: super administrador já tem acesso total na ACL. '
            . $hint . '. Veja candidatos_arquivo no log. '
            . 'Contato: ' . ($_ENV['EMAIL_ADM'] ?? '')
        );
    }

    /**
     * Verificar se o método "index" existe na controller e carregar a página.
     * 
     * Este método instancia a controller correspondente e verifica se o método "index" está presente. 
     * Se o método existir, ele é executado com o parâmetro fornecido. Caso contrário, um log de erro é gerado e uma mensagem de erro é exibida.
     * 
     * @return void
     */
    private function loadMetodo(): void
    {
        if (!empty($_SESSION['user_id'])) {
            NotificationOpenHelper::markFromRequestIfPresent();
        }

        // Instanciar a classe da página que deve ser carregada
        $classLoad = new $this->classLoad();

        // Detectar se é requisição AJAX/JSON
        $isAjax = (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        ) || (
            isset($_SERVER['HTTP_ACCEPT']) &&
            str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')
        );

        // Controllers internas de AJAX que não devem receber parâmetros de rota
        $internalAjaxControllers = ['UploadSpreadsheet', 'GetSpreadsheetFields'];

        // Determinar qual método chamar
        $metodo = 'index';
        $parametro = $this->urlParameter;
        
        // Se urlParameter não for vazio e não for numérico, pode ser um nome de método
        if (!empty($this->urlParameter) && !is_numeric($this->urlParameter)) {
            // Converter kebab-case para camelCase (ex: get-application -> getApplication)
            $metodoCamelCase = lcfirst(SlugController::slugController($this->urlParameter));
            // is_callable (não method_exists): method_exists retorna true para métodos privados/protected,
            // e o roteador escolhia "uploadImage" etc. em CreateTimelinePost — depois falhava ao invocar.
            if (is_callable([$classLoad, $metodoCamelCase])) {
                $metodo = $metodoCamelCase;
                $parametro = null; // Se for um método, não passar como parâmetro
            }
        }

        // Se o nome resolvido não for invocável, mas index() existir, cair no padrão (evita Erro 004 em rotas ambíguas)
        if (!is_callable([$classLoad, $metodo]) && is_callable([$classLoad, 'index'])) {
            $metodo = 'index';
        }
        
        // Só métodos publicamente invocáveis na instância (evita falso positivo com privados)
        if (is_callable([$classLoad, $metodo])) {
            try {
                GenerateLog::generateLog("info", "Página acessada.", [
                    'pagina' => $this->urlController,
                    'parametro' => $this->urlParameter,
                    'metodo' => $metodo,
                    'action_user_id' => $_SESSION['user_id'] ?? ''
                ]);

                if (in_array($this->urlController, $internalAjaxControllers, true)) {
                    // UploadSpreadsheet não precisa de parâmetro, mas GetSpreadsheetFields precisa do ID
                    if ($this->urlController === 'UploadSpreadsheet') {
                        $classLoad->{"index"}();
                    } else {
                        // GetSpreadsheetFields precisa do ID da planilha
                        // Se urlParameter estiver vazio, tentar extrair da URL
                        $param = $this->urlParameter;
                        
                        // Debug
                        error_log("[LoadPageAdmAccessLevel] Controller: {$this->urlController}");
                        error_log("[LoadPageAdmAccessLevel] urlParameter original: " . var_export($this->urlParameter, true));
                        error_log("[LoadPageAdmAccessLevel] REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A'));
                        
                        if (empty($param) && !empty($_SERVER['REQUEST_URI'])) {
                            // Extrair o ID da URL: get-spreadsheet-fields/123 ou get-spreadsheet-fields-123
                            $uri = $_SERVER['REQUEST_URI'];
                            if (preg_match('/get-spreadsheet-fields[\/\-](\d+)/', $uri, $matches)) {
                                $param = $matches[1];
                                error_log("[LoadPageAdmAccessLevel] ID extraído da URI: {$param}");
                            }
                        }
                        
                        error_log("[LoadPageAdmAccessLevel] Parâmetro final passado para controller: " . var_export($param, true));
                        $classLoad->{"index"}($param);
                    }
                } else {
                    // Se o método for diferente de index, chamar sem parâmetro (ou com parâmetro se necessário)
                    if ($metodo === 'index') {
                        // PHP 8+: não passar string vazia — controllers com index(): void quebram com index('');
                        if ($parametro === '' || $parametro === null) {
                            $classLoad->index();
                        } else {
                            $classLoad->index($parametro);
                        }
                    } else {
                        $classLoad->{$metodo}();
                    }
                }
            } catch (\Throwable $e) {
                GenerateLog::generateLog("error", "Erro ao executar controller.", [
                    'pagina' => $this->urlController,
                    'parametro' => $this->urlParameter,
                    'message' => $e->getMessage()
                ]);

                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'success' => false,
                        'error' => $e->getMessage()
                    ]);
                    exit;
                }

                die("Erro 004: Por favor tente novamente. Caso o problema persista, entre em contato com o administrador {$_ENV['EMAIL_ADM']}");
            }
        } else {
            GenerateLog::generateLog("error", "Método não encontrado ou não invocável.", [
                'pagina' => $this->urlController,
                'parametro' => $this->urlParameter,
                'metodo' => $metodo,
                'classe' => $this->classLoad,
            ]);
            die("Erro 004: Por favor tente novamente. Caso o problema persista, entre em contato com o administrador {$_ENV['EMAIL_ADM']}");
        }
    }
}
