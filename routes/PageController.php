<?php

namespace Routes;

use App\adms\Helpers\ClearUrl;
use App\adms\Helpers\SlugController;

/**
 * Classe PageController
 * 
 * Esta classe é responsável por receber a URL da aplicação, manipulá-la para extrair o nome da controller e o parâmetro, 
 * e posteriormente carregar a página correspondente. Ela utiliza helpers para limpar a URL e formatar o nome da controller.
 * 
 * @package App\adms\Controllers\Services
 * @author Rafael Mendes
 */
class PageController
{
    /** @var string $url Receber a URL do .htaccess */
    private string $url;

    /** @var array $urlArray Recebe a URL convertida para um array */
    private array $urlArray;

    /** @var string $urlController Recebe da URL o nome da controller */
    private string $urlController = "";

    /** @var string $urlParameter Recebe da URL o parametro */
    private string $urlParameter = "";


    /**
     * Construtor da classe PageController
     * 
     * O construtor recebe a URL através do .htaccess, limpa-a usando a classe `ClearUrl`, e então a divide em partes.
     * O primeiro segmento da URL é tratado como o nome da controller, e o segundo segmento, se presente, é tratado como parâmetro.
     * Se a URL estiver vazia, o nome da controller é definido como "login".
     */
    public function __construct()
    {

        // Verificar se tem valor na variável url enviada pelo .htaccess
        if (!empty(filter_input(INPUT_GET, 'url', FILTER_DEFAULT))) {

            // Recebe o valor da variável url
            $this->url = filter_input(INPUT_GET, 'url', FILTER_DEFAULT);



            // Chamar um classe helper para limpar a URL
            $this->url = ClearUrl::clearUrl($this->url);
            // var_dump($this->url);

            // Converter a string da URL em array
            $this->urlArray = explode("/", $this->url);
            // var_dump($this->urlArray);

            // Verificar se existe a controller na URL
            if (isset($this->urlArray[0])) {
                // Chamar uma classe helper para converter a controller enviada na URL para o formato da classe

                $this->urlController = SlugController::slugController($this->urlArray[0]);
            } else {
                $this->urlController = SlugController::slugController("Login");
            }
            // Verificar se existe o parametro na URL
            if (isset($this->urlArray[1])) {
                // Junta todos os parâmetros após o nome da controller, separados por "/"
                $this->urlParameter = implode('/', array_slice($this->urlArray, 1));
            }
        } else {
            $this->urlController = SlugController::slugController("Login");
        }
        //    var_dump($this->urlController);
        //    var_dump($this->urlParameter);
    }

    /**
     * Carregar página/controller
     * 
     * Este método instancia a classe `LoadPageAdm`, responsável por validar e carregar a página correspondente.
     * Ele passa o nome da controller e o parâmetro extraído da URL para o método `loadPageAdm` da classe `LoadPageAdm`.
     * 
     * @return void
     */
    public function loadPage(): void
    {
         // Instanciar a classe responsável por validar e carregar a página/controller

        // Carregar sem nível de acesso
        // $loadPageAdm = new LoadPageAdm();

        // Carregar com nível de acesso e verificar no banco de dados
        $loadPageAdm = new LoadPageAdmAccessLevel();

        // Chamar o método para carregar a página, passando a controller e o parâmetro da URL
        $loadPageAdm->loadPageAdm($this->urlController, $this->urlParameter);

    }
}

// Rota para avaliações do colaborador
$routes['minhas-avaliacoes'] = 'evaluations/MyEvaluations/index';
