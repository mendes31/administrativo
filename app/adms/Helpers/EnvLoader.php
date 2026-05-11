<?php

namespace App\adms\Helpers;

/**
 * Helper para carregar variáveis de ambiente (.env) em scripts isolados
 * 
 * Este helper garante que o arquivo .env seja carregado corretamente
 * em scripts que podem ser executados fora do fluxo principal da aplicação,
 * como scripts CLI, jobs, testes, etc.
 * 
 * @package App\adms\Helpers
 * @author Rafael Mendes <raffaell_mendez@hotmail.com>
 */
class EnvLoader
{
    /**
     * Carrega o arquivo .env se ainda não foi carregado
     * 
     * @return bool True se carregado com sucesso, false caso contrário
     */
    public static function load(): bool
    {
        // Só considerar carregado se o essencial existir (Apache/sistema podem definir só DB_HOST).
        $dbName = $_ENV['DB_NAME'] ?? $_ENV['DB_DATABASE'] ?? $_ENV['DATABASE_NAME'] ?? $_ENV['MYSQL_DATABASE'] ?? '';
        if (isset($_ENV['DB_HOST'], $_ENV['DB_USER']) && $dbName !== '') {
            return true;
        }

        // Carregar apenas o autoload e o .env, sem manipular sessão
        $autoloadPath = __DIR__ . '/../../../vendor/autoload.php';
        if (!file_exists($autoloadPath)) {
            error_log('Autoload não encontrado em: ' . $autoloadPath);
            return false;
        }
        require_once $autoloadPath;

        $envPath = __DIR__ . '/../../../.env';
        if (!file_exists($envPath)) {
            error_log('Arquivo .env não encontrado em: ' . $envPath);
            return false;
        }

        try {
            $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../../../');
            $dotenv->load();

            // Garantir timezone consistente em qualquer contexto que use EnvLoader::load()
            // Usa APP_TIMEZONE do .env se disponível; caso contrário, cai para UTC.
            $timezone = $_ENV['APP_TIMEZONE'] ?? ini_get('date.timezone') ?: 'UTC';
            if ($timezone) {
                date_default_timezone_set($timezone);
            }

            return true;
        } catch (\Exception $e) {
            error_log('Erro ao carregar .env: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Carrega o .env e define timezone padrão
     * 
     * @return bool True se carregado com sucesso, false caso contrário
     */
    public static function loadWithTimezone(): bool
    {
        // Mantida apenas por compatibilidade; load() já define o timezone
        return self::load();
    }
} 