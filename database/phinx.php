<?php

// Carregar o Composer, que inclui todas as classes e bibliotecas necessárias.
require __DIR__ . '/../vendor/autoload.php';

// Carregar variáveis de ambiente a partir do arquivo .env usando Dotenv.
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/..');
$dotenv->load();

// Definir o fuso horário padrão da aplicação.
date_default_timezone_set($_ENV['APP_TIMEZONE']);

// Retornar a configuração para o Phinx, ferramenta de migração de banco de dados.
return
[
    'paths' => [
        // Caminhos para migrações e sementes.
        'migrations' => '%%PHINX_CONFIG_DIR%%/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/seeds'
    ],
    'environments' => [
        // Tabela para registrar migrações aplicadas.
        'default_migration_table' => 'phinxlog',
        // Ambiente padrão.
        'default_environment' => $_ENV['APP_ENV'],

        // Configurações para ambientes específicos.
        'production' => [
            'adapter' => 'mysql',
            'host' => $_ENV['DB_HOST'],
            'name' => $_ENV['DB_NAME'],
            'user' => $_ENV['DB_USER'],
            'pass' => $_ENV['DB_PASS'],
            'port' => $_ENV['DB_PORT'],
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],
        'development' => [
            'adapter' => 'mysql',
            'host' => $_ENV['DB_HOST'],
            'name' => $_ENV['DB_NAME'],
            'user' => $_ENV['DB_USER'],
            'pass' => $_ENV['DB_PASS'],
            'port' => $_ENV['DB_PORT'],
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],
        'testing' => [
            'adapter' => 'mysql',
            'host' => $_ENV['DB_HOST'],
            'name' => $_ENV['DB_NAME'],
            'user' => $_ENV['DB_USER'],
            'pass' => $_ENV['DB_PASS'],
            'port' => $_ENV['DB_PORT'],
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]
    ],
    // Ordem das migrações.
    'version_order' => 'creation'
];
