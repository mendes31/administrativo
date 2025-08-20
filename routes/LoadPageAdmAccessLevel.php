<?php

namespace Routes;

use App\adms\Helpers\GenerateLog;
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

        $accessLevelPage = new PagesRoutesRepository();
        $this->page = $accessLevelPage->getPage($this->urlController);

        if (($this->page && $this->page['public_page'] == 1) or ($this->page && $this->verifyLogin())) {
            $this->checkControllersExists();
        } else {
            GenerateLog::generateLog("error", "Controller não encontrada.", ['pagina' => $this->urlController, 'parametro' => $this->urlParameter]);
            die("Erro 003: Por favor tente novamente. Caso o problema persista, entre em contato com o administrador {$_ENV['EMAIL_ADM']}");
        }
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

        return false;
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

        // Verificar se o método "index" existe na classe
        if (method_exists($classLoad, "index")) {
            GenerateLog::generateLog("info", "Página acessada.", [
                'pagina' => $this->urlController,
                'parametro' => $this->urlParameter,
                'action_user_id' => $_SESSION['user_id'] ?? ''
            ]);
            $classLoad->{"index"}($this->urlParameter);
        } else {
            GenerateLog::generateLog("error", "Método não encontrado.", ['pagina' => $this->urlController, 'parametro' => $this->urlParameter]);
            die("Erro 004: Por favor tente novamente. Caso o problema persista, entre em contato com o administrador {$_ENV['EMAIL_ADM']}");
        }
    }
}
