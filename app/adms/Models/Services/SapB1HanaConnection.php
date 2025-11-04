<?php

namespace App\adms\Models\Services;

use PDO;
use PDOException;
use Exception;

class SapB1HanaConnection
{
    private static ?PDO $instance = null;
    private static array $config = [];

    public static function init(): void
    {
        self::$config = [
            'dsn' => $_ENV['SAP_HANA_DSN'] ?? $_ENV['SAP_B1_DSN'] ?? null,
            'host' => $_ENV['SAP_HANA_HOST'] ?? $_ENV['SAP_B1_HOST'] ?? '',
            'port' => $_ENV['SAP_HANA_PORT'] ?? $_ENV['SAP_B1_PORT'] ?? '30015',
            'database' => $_ENV['SAP_SL_COMPANY'] ?? $_ENV['SAP_HANA_DATABASE'] ?? $_ENV['SAP_B1_DATABASE'] ?? 'SBO_TIARAJU_HOM',
            'schema' => $_ENV['SAP_SL_COMPANY'] ?? $_ENV['SAP_HANA_SCHEMA'] ?? 'SBO_TIARAJU_HOM',
            'user' => $_ENV['SAP_HANA_USERNAME'] ?? $_ENV['SAP_B1_USER'] ?? '',
            'password' => $_ENV['SAP_HANA_PASSWORD'] ?? $_ENV['SAP_B1_PASSWORD'] ?? '',
            'timeout' => $_ENV['SAP_HANA_TIMEOUT'] ?? $_ENV['SAP_B1_TIMEOUT'] ?? 30
        ];
    }

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::init();
            self::connect();
        }
        return self::$instance;
    }

    private static function connect(): void
    {
        try {
            if (!empty(self::$config['dsn'])) {
                $dsn = 'odbc:' . self::$config['dsn'];
                $user = self::$config['user'];
                $password = self::$config['password'];
            } else {
                $dsn = sprintf('odbc:Driver={HDBODBC};ServerNode=%s:%s;Database=%s',
                    self::$config['host'], self::$config['port'], self::$config['database']);
                $user = self::$config['user'];
                $password = self::$config['password'];
            }

            self::$instance = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => (int)self::$config['timeout']
            ]);
            
            // IMPORTANTE: Definir schema padrão para encontrar tabelas SAP B1
            $schema = self::$config['schema'] ?? self::$config['database'] ?? 'SBO_TIARAJU_HOM';
            self::$instance->exec("SET SCHEMA \"$schema\"");

            error_log("✅ Conectado ao SAP B1 HANA - Schema: $schema");
        } catch (PDOException $e) {
            throw new Exception("Erro ao conectar no SAP B1 HANA: " . $e->getMessage());
        }
    }

    public static function testConnection(): array
    {
        try {
            $pdo = self::getInstance();
            $stmt = $pdo->query('SELECT CURRENT_DATABASE, CURRENT_SCHEMA, CURRENT_USER FROM DUMMY');
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return ['success' => true, 'database' => $result['CURRENT_DATABASE'], 
                    'schema' => $result['CURRENT_SCHEMA'], 'user' => $result['CURRENT_USER'],
                    'message' => 'Conexão estabelecida com sucesso!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function query(string $sql, array $params = []): array
    {
        $pdo = self::getInstance();
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(is_int($key) ? $key + 1 : $key, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function queryFirst(string $sql, array $params = []): ?array
    {
        $results = self::query($sql, $params);
        return $results[0] ?? null;
    }

    public static function querySingle(string $sql, array $params = [])
    {
        $result = self::queryFirst($sql, $params);
        return $result ? array_values($result)[0] : null;
    }

    public static function getConfig(): array
    {
        self::init();
        $config = self::$config;
        $config['password'] = str_repeat('*', strlen($config['password']));
        return $config;
    }
}

