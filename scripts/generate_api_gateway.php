<?php
/**
 * Script para gerar estrutura do API Gateway automaticamente
 * 
 * Execute: php scripts/generate_api_gateway.php /caminho/destino
 */

if ($argc < 2) {
    echo "❌ Uso: php generate_api_gateway.php /caminho/destino\n";
    echo "Exemplo: php generate_api_gateway.php /opt/sap-api-gateway\n";
    exit(1);
}

$targetDir = rtrim($argv[1], '/');

echo "🚀 Gerando estrutura do API Gateway...\n";
echo "📁 Diretório: {$targetDir}\n\n";

// Criar estrutura de diretórios
$dirs = [
    '',
    '/public',
    '/src',
    '/src/Config',
    '/src/Controllers',
    '/src/Middleware',
    '/src/Services',
    '/logs',
    '/cache'
];

foreach ($dirs as $dir) {
    $path = $targetDir . $dir;
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
        echo "✅ Criado: {$dir}\n";
    }
}

echo "\n📝 Gerando arquivos...\n\n";

// composer.json
file_put_contents($targetDir . '/composer.json', <<<'JSON'
{
    "name": "empresa/sap-api-gateway",
    "description": "API Gateway para SAP Business One",
    "type": "project",
    "require": {
        "php": ">=8.1",
        "slim/slim": "^4.0",
        "slim/psr7": "^1.6",
        "vlucas/phpdotenv": "^5.5",
        "firebase/php-jwt": "^6.0"
    },
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    }
}
JSON
);
echo "✅ composer.json\n";

// .env.example
file_put_contents($targetDir . '/.env.example', <<<'ENV'
# SAP HANA
SAP_HANA_DSN=nome_do_dsn_odbc
SAP_HANA_USER=usuario_readonly
SAP_HANA_PASSWORD=senha_forte
SAP_HANA_SCHEMA=SBO_PRODUCAO

# Segurança
JWT_SECRET=gere_uma_chave_forte_aqui_256_bits
ALLOWED_ORIGIN=https://seu-dominio.com.br

# Rate Limiting
RATE_LIMIT_MAX_REQUESTS=100
RATE_LIMIT_WINDOW=60
ENV
);
echo "✅ .env.example\n";

// README.md
file_put_contents($targetDir . '/README.md', <<<'MD'
# SAP API Gateway

API Gateway para acesso seguro ao SAP Business One.

## Instalação

```bash
composer install
cp .env.example .env
# Editar .env com suas credenciais
```

## Executar

```bash
php -S localhost:8080 -t public
```

## Endpoints

- `GET /health` - Health check
- `POST /auth/login` - Autenticação
- `GET /api/sales/total` - Total de vendas
- `GET /api/sales/by-seller` - Vendas por vendedor
- `GET /api/sales/top-products` - Top produtos

## Documentação

Consulte GUIA_API_GATEWAY_SAP_B1.md para detalhes completos.
MD
);
echo "✅ README.md\n";

// public/index.php (básico)
file_put_contents($targetDir . '/public/index.php', <<<'PHP'
<?php
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

// Health check
$app->get('/health', function ($request, $response) {
    $response->getBody()->write(json_encode([
        'status' => 'ok',
        'timestamp' => date('Y-m-d H:i:s')
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();
PHP
);
echo "✅ public/index.php\n";

// .gitignore
file_put_contents($targetDir . '/.gitignore', <<<'GITIGNORE'
/vendor/
.env
/logs/*.log
/cache/*
!.gitkeep
composer.lock
GITIGNORE
);
echo "✅ .gitignore\n";

// .gitkeep para diretórios vazios
file_put_contents($targetDir . '/logs/.gitkeep', '');
file_put_contents($targetDir . '/cache/.gitkeep', '');

echo "\n✅ Estrutura criada com sucesso!\n\n";
echo "📋 Próximos passos:\n";
echo "   1. cd {$targetDir}\n";
echo "   2. composer install\n";
echo "   3. cp .env.example .env\n";
echo "   4. Editar .env com suas credenciais\n";
echo "   5. Implementar controllers (consulte GUIA_API_GATEWAY_SAP_B1.md)\n";
echo "   6. php -S localhost:8080 -t public\n\n";
echo "📚 Documentação completa: GUIA_API_GATEWAY_SAP_B1.md\n\n";

