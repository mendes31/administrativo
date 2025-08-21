#!/bin/bash

# Script para atualizar URLs hardcoded para usar a variável de ambiente URL_ADM
# Autor: Assistente AI
# Data: 2025
# Versão: 2.0 - Otimizado para sistema administrativo

echo "🚀 Iniciando atualização de URLs hardcoded..."
echo "=================================================="

# Cria diretório de backup com timestamp
BACKUP_DIR="backup_urls_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"
echo "📁 Diretório de backup criado: $BACKUP_DIR"

# Cria arquivo .env
echo "📝 Criando arquivo .env..."
cat > .env << 'EOF'
# Configurações do Sistema Administrativo
# URL base para o sistema administrativo
URL_ADM=http://localhost/administrativo/

# Outras configurações podem ser adicionadas aqui
# DB_HOST=localhost
# DB_NAME=administrativo
# DB_USER=root
# DB_PASS=

# Configurações de ambiente
ENVIRONMENT=development
DEBUG=true
EOF
echo "✅ Arquivo .env criado com sucesso!"

# Atualiza .htaccess
echo "🔧 Atualizando .htaccess..."
if [ -f .htaccess ]; then
    # Backup do arquivo original
    cp .htaccess "$BACKUP_DIR/.htaccess.backup"
    
    # Substitui RewriteBase
    sed -i 's|RewriteBase /administrativo[0-9]*/|RewriteBase /administrativo/|g' .htaccess
    
    # Substitui ErrorDocument
    sed -i 's|ErrorDocument 403 http://localhost/administrativo[0-9]*/error403|ErrorDocument 403 http://localhost/administrativo/error403|g' .htaccess
    
    echo "✅ Arquivo .htaccess atualizado com sucesso!"
else
    echo "❌ Arquivo .htaccess não encontrado!"
fi

# Cria carregador de ambiente PHP
echo "📦 Criando carregador de variáveis de ambiente..."
mkdir -p app/Helpers

cat > app/Helpers/EnvLoader.php << 'EOF'
<?php
/**
 * Carregador de variáveis de ambiente
 * Este arquivo deve ser incluído no início de todos os scripts PHP
 */

// Carrega o arquivo .env se existir
function loadEnv() {
    $envFile = __DIR__ . '/../../.env';
    
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove aspas se existirem
                if (($value[0] === '"' && $value[-1] === '"') || 
                    ($value[0] === "'" && $value[-1] === "'")) {
                    $value = substr($value, 1, -1);
                }
                
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
    
    // Define valores padrão se não existirem
    if (!isset($_ENV['URL_ADM'])) {
        $_ENV['URL_ADM'] = 'http://localhost/administrativo/';
        putenv('URL_ADM=http://localhost/administrativo/');
    }
}

// Carrega as variáveis de ambiente
loadEnv();

// Função helper para obter variável de ambiente
function env($key, $default = null) {
    return $_ENV[$key] ?? $default;
}

// Função helper para construir URLs
function buildUrl($path = '') {
    $baseUrl = env('URL_ADM', 'http://localhost/administrativo/');
    $path = ltrim($path, '/');
    return $baseUrl . ($path ? $path : '');
}
?>
EOF
echo "✅ Carregador de variáveis de ambiente criado: app/Helpers/EnvLoader.php"

# Atualiza index.php
echo "🔧 Atualizando index.php..."
if [ -f index.php ]; then
    # Verifica se já inclui o EnvLoader
    if ! grep -q "EnvLoader.php" index.php; then
        # Cria backup
        cp index.php "$BACKUP_DIR/index.php.backup"
        
        # Adiciona o carregador no início
        sed -i '1i\<?php\n// Carrega variáveis de ambiente\nrequire_once __DIR__ . "/app/Helpers/EnvLoader.php";\n\n' index.php
        
        echo "✅ index.php atualizado para incluir carregador de ambiente"
    else
        echo "ℹ️  index.php já inclui carregador de ambiente"
    fi
else
    echo "❌ Arquivo index.php não encontrado!"
fi

# Cria configuração JavaScript
echo "📱 Criando configuração JavaScript..."
mkdir -p public/adms/js

cat > public/adms/js/config.js << 'EOF'
// Configuração global do sistema administrativo
// Este arquivo deve ser incluído antes de outros scripts JavaScript

// Define a URL base do sistema administrativo
window.URL_ADM = '/administrativo/';

// Função para construir URLs completas
window.buildUrl = function(path) {
    return window.URL_ADM + path.replace(/^\/+/, '');
};

// Função para redirecionar
window.redirectTo = function(path) {
    window.location.href = window.buildUrl(path);
};

// Função para fazer fetch com URL base
window.fetchUrl = function(path, options = {}) {
    return fetch(window.buildUrl(path), options);
};

console.log('Configuração do sistema carregada. URL_ADM:', window.URL_ADM);
EOF
echo "✅ Arquivo de configuração JavaScript criado: public/adms/js/config.js"

# Atualiza layout principal
echo "🎨 Atualizando layout principal..."
if [ -f "app/adms/Views/layouts/main.php" ]; then
    # Verifica se já inclui o config.js
    if ! grep -q "config.js" "app/adms/Views/layouts/main.php"; then
        # Cria backup
        cp "app/adms/Views/layouts/main.php" "$BACKUP_DIR/main.php.backup"
        
        # Adiciona o script antes do fechamento do </head>
        sed -i 's|</head>|    <script src="<?php echo $_ENV["URL_ADM"] ?>public/adms/js/config.js"></script>\n</head>|' "app/adms/Views/layouts/main.php"
        
        echo "✅ Layout principal atualizado para incluir config.js"
    else
        echo "ℹ️  Layout principal já inclui config.js"
    fi
else
    echo "❌ Arquivo main.php não encontrado!"
fi

# Atualiza arquivos PHP (substituições básicas)
echo "🔧 Atualizando arquivos PHP..."
PHP_FILES=$(find . -name "*.php" -not -path "./vendor/*" -not -path "./.git/*" -not -path "./logs/*" -not -path "./$BACKUP_DIR/*")

UPDATED_PHP=0
for file in $PHP_FILES; do
    if [ -f "$file" ]; then
        # Backup do arquivo
        cp "$file" "$BACKUP_DIR/$(basename "$file").backup"
        
        # Substituições básicas
        sed -i 's|"/administrativo[0-9]*|" . $_ENV["URL_ADM"] . "|g' "$file"
        sed -i "s|'/administrativo[0-9]*|' . \$_ENV['URL_ADM'] . '|g" "$file"
        
        # Conta arquivos atualizados
        if grep -q "URL_ADM" "$file"; then
            UPDATED_PHP=$((UPDATED_PHP + 1))
            echo "✅ $file atualizado"
        fi
    fi
done

echo "✅ $UPDATED_PHP arquivos PHP atualizados!"

# Atualiza arquivos JavaScript (substituições básicas)
echo "🔧 Atualizando arquivos JavaScript..."
JS_FILES=$(find . -name "*.js" -not -path "./vendor/*" -not -path "./.git/*" -not -path "./logs/*" -not -path "./$BACKUP_DIR/*")

UPDATED_JS=0
for file in $JS_FILES; do
    if [ -f "$file" ]; then
        # Backup do arquivo
        cp "$file" "$BACKUP_DIR/$(basename "$file").backup"
        
        # Substituições básicas
        sed -i 's|"/administrativo[0-9]*|" + window.URL_ADM + "|g' "$file"
        sed -i "s|'/administrativo[0-9]*|' + window.URL_ADM + '|g" "$file"
        
        # Conta arquivos atualizados
        if grep -q "URL_ADM" "$file"; then
            UPDATED_JS=$((UPDATED_JS + 1))
            echo "✅ $file atualizado"
        fi
    fi
done

echo "✅ $UPDATED_JS arquivos JavaScript atualizados!"

echo ""
echo "=================================================="
echo "✅ Atualização concluída com sucesso!"
echo ""
echo "📋 Resumo das alterações:"
echo "   • Arquivo .env criado com URL_ADM=http://localhost/administrativo/"
echo "   • .htaccess atualizado para usar variáveis"
echo "   • EnvLoader.php criado para carregar variáveis"
echo "   • index.php atualizado para incluir EnvLoader"
echo "   • config.js criado para configuração JavaScript"
echo "   • Layout principal atualizado"
echo "   • $UPDATED_PHP arquivos PHP atualizados"
echo "   • $UPDATED_JS arquivos JavaScript atualizados"
echo ""
echo "💾 Backup criado em: $BACKUP_DIR"
echo ""
echo "🔧 Para usar em produção:"
echo "   1. Ajuste a variável URL_ADM no arquivo .env"
echo "   2. Certifique-se de que o .env está no .gitignore"
echo "   3. Teste todas as funcionalidades do sistema"
echo "   4. Execute: php scripts/generate-htaccess.php"
echo ""
echo "📝 Arquivos de backup criados:"
echo "   • .htaccess.backup"
echo "   • index.php.backup"
echo "   • main.php.backup"
echo "   • Todos os arquivos PHP e JS processados"
echo ""
echo "🚀 Para correção mais avançada, use o script Python:"
echo "   python3 scripts/fix_urls.py"
