<?php

namespace App\adms\Views\Services;

/**
 * Carregar as páginas da View
 * 
 * Classe responsável por carregar arquivos de view e incluí-los no layout principal.
 * 
 * @author Rafael Mendes <raffaell_mendez@hotmail.com>
 */
class LoadViewService
{
    /** @var string $view Recebe o endereço da VIEW */
    private string $view;

    /**
     * Receber o endereço da VIEW e os dados.
     *
     * Construtor que inicializa o endereço da VIEW e os dados a serem passados para a VIEW.
     * 
     * @param string $nameView Endereço da VIEW que deve ser carregada. 
     * @param array|string|null $data Dados que a VIEW deve receber (opcional).
     */
    public function __construct(private string $nameView, private array|string|null $data)
    {
        // Inicializa os parâmetros da classe
    }

    /**
     * Carregar a VIEW.
     * 
     * Verifica se o arquivo da VIEW existe e, se existir, inclui o layout principal que carregará a VIEW.
     * Caso o arquivo da VIEW não seja encontrado, exibe uma mensagem de erro e encerra a execução.
     * 
     * @return void
     * 
     * @throws Exception Se o arquivo da VIEW não for encontrado, exibe uma mensagem de erro e encerra a execução.
     */
    public function loadView(): void
    {
        //Definir o caminho da VIEW
        $this->view = './app/' . $this->nameView . '.php';

        // Verificar se o arquivo existe
        if (file_exists($this->view)) {
            // Incluir o layout principal
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/administrativo2/app/logs/filtro_global_debug.log', date('Y-m-d H:i:s') . ' - main.php executado em ' . ($_SERVER['REQUEST_URI'] ?? '') . ' user_id=' . ($_SESSION['user_id'] ?? 'null') . ' session_id=' . ($_SESSION['session_id'] ?? 'null') . PHP_EOL, FILE_APPEND);
            include './app/adms/Views/layouts/main.php';
        } else {
            die("Erro 005: Por favor tente novamente. Caso o problema persista, entre em contato com o adminstrador {$_ENV['EMAIL_ADM']}");
        }
    }

    /**
     * Carregar a VIEW Login.
     * 
     * Verifica se o arquivo da VIEW existe e, se existir, inclui o layout login que carregará a VIEW.
     * Caso o arquivo da VIEW não seja encontrado, exibe uma mensagem de erro e encerra a execução.
     * 
     * @return void
     * 
     * @throws Exception Se o arquivo da VIEW não for encontrado, exibe uma mensagem de erro e encerra a execução.
     */
    public function loadViewLogin(): void
    {
        //Definir o caminho da VIEW
        $this->view = './app/' . $this->nameView . '.php';

        // Verificar se o arquivo existe
        if (file_exists($this->view)) {
            // Incluir o layout principal
            include './app/adms/Views/layouts/login.php';
        } else {
            die("Erro 005: Por favor tente novamente. Caso o problema persista, entre em contato com o adminstrador {$_ENV['EMAIL_ADM']}");
        }
    }
}
