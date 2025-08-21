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

// Extrair o caminho da URL_ADM
    $urlAdm = getenv('URL_ADM');
    if (!$urlAdm && isset($_ENV['URL_ADM'])) {
        $urlAdm = $_ENV['URL_ADM'];
    }
    if (!$urlAdm) {
        $urlAdm = 'http://localhost/administrativo/';
    }

    $path = parse_url($urlAdm, PHP_URL_PATH) ?: '/administrativo/';
    $path = rtrim($path, '/') . '/';

echo "🔧 Gerando .htaccess dinamicamente...\n";
echo "📁 URL configurada: $urlAdm\n";
echo "📁 Caminho extraído: $path\n\n";

// Conteúdo do .htaccess
$htaccessContent = <<<HTACCESS
# Documentação: https://httpd.apache.org/docs/2.4/rewrite/flags.html
# Arquivo gerado automaticamente pelo script scripts/generate-htaccess.php
# Para alterar, edite o arquivo .env e execute o script novamente
# 
# Ativa o módulo Rewrite, que faz a reescrita de URL.
RewriteEngine On
RewriteBase {$path}

# Não reescrever se for arquivo ou diretório físico
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

# Redireciona tudo para index.php, preservando subdiretórios e pontos
RewriteRule ^(.+)$ index.php?url=$1 [QSA,L]

# Quando houver o erro 403 redirecionar o usuário (caminho relativo)
ErrorDocument 403 {$path}error403   

# Bloquear a opção listar os arquivos do diretório
Options -Indexes

# Bloquear acesso direto aos diretórios pela URL
RewriteRule ^app/ - [F]
RewriteRule ^database/ - [F]
RewriteRule ^logs/ - [F]
RewriteRule ^routes/ - [F]
RewriteRule ^vendor/ - [F]

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

// Salvar o arquivo .htaccess
if (file_put_contents('.htaccess', $htaccessContent)) {
    echo "✅ .htaccess gerado com sucesso!\n";
    echo "📁 Caminho configurado: $path\n";
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
