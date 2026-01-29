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
        //Definir o caminho da VIEW usando caminho absoluto
        // __DIR__ = app/adms/Views/Services
        // Subir 3 níveis para chegar na raiz do projeto
        $basePath = realpath(__DIR__ . '/../../../');
        
        // Se realpath falhar, usar caminho relativo como fallback
        if ($basePath === false) {
            $basePath = dirname(dirname(dirname(__DIR__)));
        }
        
        $this->view = $basePath . '/app/' . $this->nameView . '.php';

        // Verificar se o arquivo existe
        if (file_exists($this->view)) {
            // Incluir o layout principal (log apenas se diretório existir e for gravável)
            $logDir = __DIR__ . '/../../../logs';
            if (is_dir($logDir) && is_writable($logDir)) {
                @file_put_contents($logDir . '/filtro_global_debug.log', date('Y-m-d H:i:s') . ' - main.php executado em ' . ($_SERVER['REQUEST_URI'] ?? '') . ' user_id=' . ($_SESSION['user_id'] ?? 'null') . ' session_id=' . ($_SESSION['session_id'] ?? 'null') . PHP_EOL, FILE_APPEND);
            }
            // Usar caminho absoluto para o layout também
            $layoutPath = $basePath . '/app/adms/Views/layouts/main.php';
            if (file_exists($layoutPath)) {
                include $layoutPath;
            } else {
                error_log("Erro LoadViewService: Layout não encontrado: " . $layoutPath);
                die("Erro 005: Por favor tente novamente. Caso o problema persista, entre em contato com o adminstrador {$_ENV['EMAIL_ADM']}");
            }
        } else {
            error_log("Erro LoadViewService: Arquivo não encontrado: " . $this->view);
            error_log("BasePath: " . $basePath);
            error_log("NameView: " . $this->nameView);
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
        //Definir o caminho da VIEW usando caminho absoluto
        $basePath = realpath(__DIR__ . '/../../../');
        $this->view = $basePath . '/app/' . $this->nameView . '.php';

        // Verificar se o arquivo existe
        if (file_exists($this->view)) {
            // Incluir o layout principal usando caminho absoluto
            $layoutPath = $basePath . '/app/adms/Views/layouts/login.php';
            include $layoutPath;
        } else {
            error_log("Erro LoadViewService: Arquivo não encontrado: " . $this->view);
            die("Erro 005: Por favor tente novamente. Caso o problema persista, entre em contato com o adminstrador {$_ENV['EMAIL_ADM']}");
        }
    }
}
