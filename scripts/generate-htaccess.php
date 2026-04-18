<?php
/**
 * Script para gerar .htaccess dinamicamente baseado nas variáveis de ambiente
 * 
 * USO:
 * 1. Altere a URL no arquivo .env
 * 2. Execute: php scripts/generate-htaccess.php
 * 3. O .htaccess será atualizado automaticamente
 * 
 * @author Sistema Administrativo
 * @version 1.0
 */

// Tentar carregar variáveis de ambiente (opcional na CI)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    if (file_exists(__DIR__ . '/../.env') && class_exists('Dotenv\\Dotenv')) {
        $dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/..');
        $dotenv->load();
    }
}

// Extrair o caminho da URL_ADM (deve bater com o URL path real do Apache, não só com a pasta no disco)
    $urlAdm = getenv('URL_ADM');
    if (!$urlAdm && isset($_ENV['URL_ADM'])) {
        $urlAdm = $_ENV['URL_ADM'];
    }
    if (!$urlAdm) {
        $urlAdm = 'http://localhost/administrativo/';
    }

    $urlPath = parse_url($urlAdm, PHP_URL_PATH);
    if (!is_string($urlPath) || $urlPath === '' || $urlPath === '/') {
        // Vhost com document root = pasta do projeto (ex.: ~/www/administrativo) → URLs na raiz: /serve-file
        $rewriteBase = '/';
        $uriDirPrefix = '';
    } else {
        // App num subcaminho (ex.: /administrativo/)
        $rewriteBase = rtrim($urlPath, '/') . '/';
        $uriDirPrefix = rtrim($urlPath, '/');
    }

    $publicUriRx = ($uriDirPrefix === '')
        ? '^/public/'
        : '^' . preg_quote($uriDirPrefix, '#') . '/public/';
    $scriptsUriRx = ($uriDirPrefix === '')
        ? '^/scripts/.*\\.php$'
        : '^' . preg_quote($uriDirPrefix, '#') . '/scripts/.*\\.php$';

echo "🔧 Gerando .htaccess dinamicamente...\n";
echo "📁 URL configurada: $urlAdm\n";
echo "📁 RewriteBase: $rewriteBase\n\n";

// Conteúdo do .htaccess
$htaccessContent = <<<HTACCESS
# Documentação: https://httpd.apache.org/docs/2.4/rewrite/flags.html
# Arquivo gerado automaticamente pelo script scripts/generate-htaccess.php
# Para alterar, edite o arquivo .env e execute o script novamente
# 
# Ativa o módulo Rewrite, que faz a reescrita de URL.
RewriteEngine On
RewriteBase {$rewriteBase}

# Bloquear pastas sensíveis antes do catch-all
RewriteRule ^app/ - [F]
RewriteRule ^database/ - [F]
RewriteRule ^logs/ - [F]
RewriteRule ^routes/ - [F]
RewriteRule ^vendor/ - [F]

# Não reescrever se for arquivo ou diretório físico
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

# Assets estáticos em public/ (não passar pelo index.php)
RewriteCond %{REQUEST_URI} !{$publicUriRx}

# Scripts PHP de diagnóstico (opcional)
RewriteCond %{REQUEST_URI} !{$scriptsUriRx}

# Redireciona o resto para index.php, preservando query string (QSA) — necessário para serve-file?path=...
RewriteRule ^(.+)$ index.php?url=$1 [QSA,L]

# Quando houver o erro 403 redirecionar o usuário (caminho relativo ao host)
ErrorDocument 403 {$rewriteBase}error403

# Bloquear a opção listar os arquivos do diretório
Options -Indexes

# Bloquear acesso direto aos arquivos pela URL
<FilesMatch "^\.env$">
    Require all denied
</FilesMatch>

<FilesMatch "^\.env\.exemple$">
    Require all denied
</FilesMatch>

<FilesMatch "^\.gitignore$">
    Require all denied
</FilesMatch>

<FilesMatch "^\.htaccess$">
    Require all denied
</FilesMatch>

<FilesMatch "^composer\.json$">
    Require all denied
</FilesMatch>

<FilesMatch "^composer\.lock$">
    Require all denied
</FilesMatch>

<FilesMatch "^LICENSE\.txt$">
    Require all denied
</FilesMatch>

<FilesMatch "^README\.md$">
    Require all denied
</FilesMatch>
HTACCESS;

// Salvar o arquivo .htaccess (sempre na raiz do projeto)
$targetHtaccess = __DIR__ . '/../.htaccess';
if (file_put_contents($targetHtaccess, $htaccessContent)) {
    echo "✅ .htaccess gerado com sucesso!\n";
    echo "📁 RewriteBase configurado: $rewriteBase\n";
    echo "🚀 Agora teste acessando: $urlAdm\n\n";
    
    echo "💡 Para mudar a URL no futuro:\n";
    echo "   1. Edite o arquivo .env\n";
    echo "   2. Execute: php scripts/generate-htaccess.php\n";
    echo "   3. O sistema funcionará automaticamente!\n";
} else {
    echo "❌ Erro ao gerar o arquivo .htaccess\n";
    echo "💡 Verifique as permissões da pasta\n";
    exit(1);
}
?>
