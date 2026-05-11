<?php

namespace App\adms\Models\Services;

use App\adms\Helpers\GenerateLog;
use PDO;
use PDOException;

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
    private static function envDbReady(): bool
    {
        $name = self::resolveDbName();

        return isset($_ENV['DB_HOST'], $_ENV['DB_USER']) && $name !== '';
    }

    /**
     * Nome da base: DB_NAME (padrão do projeto) ou alternativas comuns (.env de outros stacks).
     */
    private static function resolveDbName(): string
    {
        foreach (['DB_NAME', 'DB_DATABASE', 'DATABASE_NAME', 'MYSQL_DATABASE'] as $key) {
            if (!empty($_ENV[$key])) {
                return trim((string) $_ENV[$key]);
            }
        }

        return '';
    }

    /**
     * Conexão PDO compartilhada entre todas as instâncias que estendem DbConnection.
     *
     * Ao usar uma conexão estática:
     * - Evitamos abrir várias conexões em uma mesma requisição (cada Repository criava a sua).
     * - Reduzimos a chance de atingir o limite de conexões do MySQL.
     * - Mantemos o mesmo tratamento de erro 001 em caso de falha de conexão.
     */
    private static ?PDO $connect = null;

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
    public function getConnection(): PDO
    {
        try {

            // Criar nova conexão com o banco de dados se não existir
            if (self::$connect === null) {

                if (!self::envDbReady()) {
                    require_once __DIR__ . '/../../Helpers/EnvLoader.php';
                    \App\adms\Helpers\EnvLoader::load();
                }

                $dbName = self::resolveDbName();
                if ($dbName === '' || !isset($_ENV['DB_HOST'], $_ENV['DB_USER'])) {
                    GenerateLog::generateLog('alert', 'Configuração de banco incompleta.', [
                        'DB_HOST' => isset($_ENV['DB_HOST']),
                        'DB_NAME' => $dbName !== '',
                        'DB_USER' => isset($_ENV['DB_USER']),
                    ]);
                    die('Erro de configuração: defina DB_NAME (ou DB_DATABASE) e credenciais no ficheiro .env na raiz do projeto.');
                }

                $host = trim((string) $_ENV['DB_HOST']);
                $port = trim((string) ($_ENV['DB_PORT'] ?? '3306'));
                $dsnPort = ($port !== '' && $port !== '3306')
                    ? ";port={$port}"
                    : '';
                $dsn = "mysql:host={$host}{$dsnPort};dbname={$dbName};charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ];
                $dbPass = array_key_exists('DB_PASS', $_ENV) ? (string) $_ENV['DB_PASS'] : '';
                self::$connect = new PDO($dsn, (string) $_ENV['DB_USER'], $dbPass, $options);
                

                // echo "Conexão realizada com sucesso!<br>";
            }

            return self::$connect;

        } catch (PDOException $err) {
            // Chamar o método para salvar log
            GenerateLog::generateLog("alert", "Conexão com o Banco de Dados não realizada.", ['error' =>  $err->getMessage()]);

            die("Erro 001: Por favor tente novamente. Caso o problema persista, entre em contato com o adminstrador {$_ENV['EMAIL_ADM']}");
        }
    }
}
