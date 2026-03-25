<?php

namespace Routes;

use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\SlugController;
use App\adms\Models\Repository\PagesRoutesRepository;

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
            // POST do formulário da timeline; mesmo motivo (cadastro/ directory incorreto no banco gerava Erro 004).
            'CreateTimelinePost' => "\\App\\adms\\Controllers\\timeline\\CreateTimelinePost",
            // JSON da timeline (curtidas, comentários, denúncia) — evita Erro 004/HTML quando adms_pages está inconsistente.
            'TimelineLike'    => "\\App\\adms\\Controllers\\timeline\\TimelineLike",
            'TimelineComment' => "\\App\\adms\\Controllers\\timeline\\TimelineComment",
            'TimelineReport'  => "\\App\\adms\\Controllers\\timeline\\TimelineReport",
            // Renovação de sessão (AJAX) — evita falha de rota se adms_pages estiver incompleto.
            'ExtendSession' => "\\App\\adms\\Controllers\\session\\ExtendSession",
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
        $this->page = $accessLevelPage->getPage($this->urlController);

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
            if (!empty($requestUri)
                && !str_contains($requestUri, 'login')
                && (!isset($_SESSION['return_url']) || empty($_SESSION['return_url'])))
            {
                // Montar URL absoluta baseado em URL_ADM
                $base = rtrim($_ENV['URL_ADM'] ?? '', '/');
                $_SESSION['return_url'] = $base . $requestUri;
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

        // Para requisições normais (navegador), em vez de "prender" na tela de erro 003,
        // limpar a sessão e redirecionar para o login com uma mensagem clara.
        $_SESSION['error'] = 'Seu usuário não possui permissão para acessar esta área. Faça login com outro usuário ou contate o administrador.';

        // Opcional: limpar dados principais da sessão para evitar redireciono automático
        unset($_SESSION['user_id'], $_SESSION['session_id']);

        header("Location: {$_ENV['URL_ADM']}login");
        exit;
    }

    private function verifyLogin(): bool
    {
        if ($_SESSION['user_id'] ?? false) {

            $accessLevelPage = new PagesRoutesRepository();
            if ($accessLevelPage->checkUserPagePermission($this->page['id_ap'])) {
                return true;
            }
        }
        return false;
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
        // Criar o caminho da controller/classe
        $this->classLoad = "\\App\\{$this->page['name_app']}\\Controllers\\{$this->page['directory']}\\" . $this->urlController;

        // Verificar se a classe existe
        if (class_exists($this->classLoad)) {
            // Verificar se o método existe na classe
            $this->loadMetodo();
            return true;
        }

        GenerateLog::generateLog("error", "Classe da controller não encontrada pelo autoload.", [
            'class' => $this->classLoad,
            'pagina' => $this->urlController,
        ]);
        die("Erro: controller não encontrada. Verifique o cadastro da página (diretório/pacote) ou o namespace da classe.");
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
