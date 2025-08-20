<?php

namespace App\adms\Models\Services;

use App\adms\Helpers\GenerateLog;
use PDO;
use PDOException;

// Reforço do carregamento do .env
if (!isset($_ENV['DB_HOST'])) {
    require_once __DIR__ . '/../../Helpers/EnvLoader.php';
    \App\adms\Helpers\EnvLoader::load();
}

/**
 * Classe responsável pela conexão com o banco de dados.
 *
 * Esta classe fornece uma abstração para a conexão com o banco de dados usando PDO. 
 * Ela garante que a conexão seja estabelecida apenas uma vez e fornece um método 
 * para recuperar a conexão. Em caso de erro na conexão, um log é gerado e uma 
 * mensagem de erro é exibida.
 *
 * @package App\adms\Models\Services
 * @author Rafael Mendes <raffaell_mendez@hotmail.com>
 */
abstract class DbConnection
{
    /** @var object $connect Recebe a conexão com o banco de dados */
    private object $connect;

    /**
     * Realiza a conexão com o banco de dados.
     *
     * Este método estabelece uma conexão com o banco de dados usando as credenciais e 
     * detalhes fornecidos nas variáveis de ambiente. Se a conexão falhar, um log é gerado 
     * e uma mensagem de erro é exibida. Se a conexão já estiver estabelecida, o método 
     * retorna a conexão existente.
     *
     * @return object Retorna a conexão com o banco de dados.
     * @throws PDOException Se ocorrer um erro durante a tentativa de conexão com o banco de dados.
     */
    public function getConnection(): object
    {
        try {

            // Criar nova conexão com o bandco de dados se não existir
            if (!isset($this->connect)) {

                // Conexão com a porta
                // $this->connect = new PDO("mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname=" . $_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASS']);

                // Conexão sem a porta, forçando charset/collation corretos
                $dsn = "mysql:host={$_ENV['DB_HOST']};dbname=" . $_ENV['DB_NAME'] . ";charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ];
                $this->connect = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], $options);
                

                // echo "Conexão realizada com sucesso!<br>";
            }

            return $this->connect;

        } catch (PDOException $err) {
            // Chamar o método para salvar log
            GenerateLog::generateLog("alert", "Conexão com o Banco de Dados não realizada.", ['error' =>  $err->getMessage()]);

            die("Erro 001: Por favor tente novamente. Caso o problema persista, entre em contato com o adminstrador {$_ENV['EMAIL_ADM']}");
        }
    }
}
