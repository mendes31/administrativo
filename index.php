<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Routes\PageController;

session_start(); // Iniciar a sessão

ob_start(); // Limpar o Buffer de saída

// Raiz do projeto (deploy/Linux: rotas usam isto para require_once de controllers).
if (!defined('APP_ROOT')) {
    define('APP_ROOT', __DIR__);
}

// PHP 8+ removeu magic_quotes_*; setasign/fpdf ainda invoca (ex.: FPDI / folha PDF).
if (!function_exists('get_magic_quotes_runtime')) {
    function get_magic_quotes_runtime(): bool
    {
        return false;
    }
}
if (!function_exists('set_magic_quotes_runtime')) {
    function set_magic_quotes_runtime($new_setting): bool
    {
        return false;
    }
}

// Carregar o Composer
require './vendor/autoload.php';

// Instanciar a dependência de variáveis de ambiente.
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
$dotenv->load();

// Definir o timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'UTC');


// Instanciar a classe PageController, responsável em tratar a URL
$url = new PageController();

// Chamar o método para carregar a página/controller
$url->loadPage();