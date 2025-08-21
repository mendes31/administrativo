#!/usr/bin/env python3
"""
Script para atualizar URLs hardcoded para usar a variável de ambiente URL_ADM
Autor: Assistente AI
Data: 2025
Versão: 2.0 - Otimizado para sistema administrativo
"""

import os
import re
import sys
import shutil
from pathlib import Path
from datetime import datetime

def create_backup_dir():
    """Cria diretório de backup com timestamp"""
    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    backup_dir = f"backup_urls_{timestamp}"
    os.makedirs(backup_dir, exist_ok=True)
    print(f"📁 Diretório de backup criado: {backup_dir}")
    return backup_dir

def backup_file(file_path, backup_dir):
    """Cria backup de um arquivo"""
    if os.path.exists(file_path):
        backup_path = os.path.join(backup_dir, os.path.basename(file_path))
        shutil.copy2(file_path, backup_path)
        return True
    return False

def create_env_file():
    """Cria arquivo .env com a variável URL_ADM"""
    env_content = """# Configurações do Sistema Administrativo
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
"""
    
    with open('.env', 'w', encoding='utf-8') as f:
        f.write(env_content)
    
    print("✅ Arquivo .env criado com sucesso!")

def update_htaccess(backup_dir):
    """Atualiza o arquivo .htaccess para usar variável de ambiente"""
    htaccess_path = '.htaccess'
    
    if not os.path.exists(htaccess_path):
        print(f"❌ Arquivo {htaccess_path} não encontrado!")
        return False
    
    # Backup do arquivo
    backup_file(htaccess_path, backup_dir)
    
    # Lê o conteúdo atual
    with open(htaccess_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    old_content = content
    
    # Substitui as URLs hardcoded
    # Substitui RewriteBase
    content = re.sub(
        r'RewriteBase\s+/administrativo\d*/',
        'RewriteBase /administrativo/',
        content
    )
    
    # Substitui ErrorDocument
    content = re.sub(
        r'ErrorDocument\s+403\s+http://localhost/administrativo\d*/error403',
        'ErrorDocument 403 http://localhost/administrativo/error403',
        content
    )
    
    # Se houve mudanças, salva o arquivo
    if content != old_content:
        with open(htaccess_path, 'w', encoding='utf-8') as f:
            f.write(content)
        print("✅ Arquivo .htaccess atualizado com sucesso!")
        return True
    else:
        print("ℹ️  Arquivo .htaccess já está atualizado.")
        return True

def update_php_files(backup_dir):
    """Atualiza arquivos PHP para usar variável de ambiente"""
    php_files = []
    
    # Encontra todos os arquivos PHP
    for root, dirs, files in os.walk('.'):
        # Ignora diretórios específicos
        dirs[:] = [d for d in dirs if d not in ['.git', 'vendor', 'node_modules', 'logs', 'backup_urls_*']]
        
        for file in files:
            if file.endswith('.php'):
                php_files.append(os.path.join(root, file))
    
    updated_files = 0
    
    for php_file in php_files:
        try:
            # Backup do arquivo
            backup_file(php_file, backup_dir)
            
            with open(php_file, 'r', encoding='utf-8') as f:
                content = f.read()
            
            old_content = content
            
            # Substitui URLs hardcoded em PHP
            # Substitui '/administrativo' por variável de ambiente
            content = re.sub(
                r"'/administrativo\d*",
                "' . \$_ENV['URL_ADM'] . '",
                content
            )
            
            # Substitui "/administrativo" por variável de ambiente
            content = re.sub(
                r'"/administrativo\d*',
                '" . \$_ENV["URL_ADM"] . "',
                content
            )
            
            # Substitui window.location.href
            content = re.sub(
                r'window\.location\.href\s*=\s*"/administrativo\d*',
                'window.location.href = "' . \$_ENV["URL_ADM"] . '"',
                content
            )
            
            # Substitui header Location
            content = re.sub(
                r"header\('Location:\s*/administrativo\d*",
                "header('Location: " . \$_ENV['URL_ADM'] . "'",
                content
            )
            
            # Substitui fetch URLs
            content = re.sub(
                r"fetch\('/administrativo\d*",
                "fetch('" . \$_ENV['URL_ADM'] . "'",
                content
            )
            
            # Substitui src URLs
            content = re.sub(
                r'src="/administrativo\d*',
                'src="' . \$_ENV['URL_ADM'] . '"',
                content
            )
            
            # Substitui href URLs
            content = re.sub(
                r'href="/administrativo\d*',
                'href="' . \$_ENV['URL_ADM'] . '"',
                content
            )
            
            # Substitui action URLs
            content = re.sub(
                r'action="/administrativo\d*',
                'action="' . \$_ENV['URL_ADM'] . '"',
                content
            )
            
            # Se houve mudanças, salva o arquivo
            if content != old_content:
                with open(php_file, 'w', encoding='utf-8') as f:
                    f.write(content)
                updated_files += 1
                print(f"✅ {php_file} atualizado")
        
        except Exception as e:
            print(f"❌ Erro ao processar {php_file}: {e}")
    
    print(f"✅ {updated_files} arquivos PHP atualizados!")
    return updated_files

def update_js_files(backup_dir):
    """Atualiza arquivos JavaScript para usar variável de ambiente"""
    js_files = []
    
    # Encontra todos os arquivos JS
    for root, dirs, files in os.walk('.'):
        dirs[:] = [d for d in dirs if d not in ['.git', 'vendor', 'node_modules', 'logs', 'backup_urls_*']]
        
        for file in files:
            if file.endswith('.js'):
                js_files.append(os.path.join(root, file))
    
    updated_files = 0
    
    for js_file in js_files:
        try:
            # Backup do arquivo
            backup_file(js_file, backup_dir)
            
            with open(js_file, 'r', encoding='utf-8') as f:
                content = f.read()
            
            old_content = content
            
            # Substitui URLs hardcoded em JavaScript
            content = re.sub(
                r"'/administrativo\d*",
                "' + window.URL_ADM + '",
                content
            )
            
            content = re.sub(
                r'"/administrativo\d*',
                '" + window.URL_ADM + "',
                content
            )
            
            # Se houve mudanças, salva o arquivo
            if content != old_content:
                with open(js_file, 'w', encoding='utf-8') as f:
                    f.write(content)
                updated_files += 1
                print(f"✅ {js_file} atualizado")
        
        except Exception as e:
            print(f"❌ Erro ao processar {js_file}: {e}")
    
    print(f"✅ {updated_files} arquivos JavaScript atualizados!")
    return updated_files

def create_js_config():
    """Cria arquivo de configuração JavaScript para definir URL_ADM"""
    js_config = """// Configuração global do sistema administrativo
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
"""
    
    # Cria diretório se não existir
    os.makedirs('public/adms/js', exist_ok=True)
    
    with open('public/adms/js/config.js', 'w', encoding='utf-8') as f:
        f.write(js_config)
    
    print("✅ Arquivo de configuração JavaScript criado: public/adms/js/config.js")

def update_main_layout():
    """Atualiza o layout principal para incluir a configuração JavaScript"""
    main_layout_path = 'app/adms/Views/layouts/main.php'
    
    if not os.path.exists(main_layout_path):
        print(f"❌ Arquivo {main_layout_path} não encontrado!")
        return False
    
    try:
        with open(main_layout_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Verifica se já inclui o config.js
        if 'config.js' not in content:
            # Adiciona antes do fechamento do </head>
            content = content.replace(
                '</head>',
                '    <script src="<?php echo $_ENV[\'URL_ADM\'] ?>public/adms/js/config.js"></script>\n</head>'
            )
            
            with open(main_layout_path, 'w', encoding='utf-8') as f:
                f.write(content)
            
            print("✅ Layout principal atualizado para incluir config.js")
        else:
            print("ℹ️  Layout principal já inclui config.js")
        
        return True
    
    except Exception as e:
        print(f"❌ Erro ao atualizar layout principal: {e}")
        return False

def create_env_loader():
    """Cria um carregador de variáveis de ambiente para PHP"""
    env_loader = """<?php
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
"""
    
    # Cria diretório se não existir
    os.makedirs('app/Helpers', exist_ok=True)
    
    with open('app/Helpers/EnvLoader.php', 'w', encoding='utf-8') as f:
        f.write(env_loader)
    
    print("✅ Carregador de variáveis de ambiente criado: app/Helpers/EnvLoader.php")

def update_index_php():
    """Atualiza o index.php para incluir o carregador de ambiente"""
    index_path = 'index.php'
    
    if not os.path.exists(index_path):
        print(f"❌ Arquivo {index_path} não encontrado!")
        return False
    
    try:
        with open(index_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Verifica se já inclui o EnvLoader
        if 'EnvLoader.php' not in content:
            # Adiciona no início do arquivo
            content = "<?php\n// Carrega variáveis de ambiente\nrequire_once __DIR__ . '/app/Helpers/EnvLoader.php';\n\n" + content[5:]
            
            with open(index_path, 'w', encoding='utf-8') as f:
                f.write(content)
            
            print("✅ index.php atualizado para incluir carregador de ambiente")
        else:
            print("ℹ️  index.php já inclui carregador de ambiente")
        
        return True
    
    except Exception as e:
        print(f"❌ Erro ao atualizar index.php: {e}")
        return False

def main():
    """Função principal do script"""
    print("🚀 Iniciando atualização de URLs hardcoded...")
    print("=" * 60)
    
    # Cria diretório de backup
    backup_dir = create_backup_dir()
    print()
    
    # Cria arquivo .env
    create_env_file()
    print()
    
    # Atualiza .htaccess
    update_htaccess(backup_dir)
    print()
    
    # Cria carregador de ambiente
    create_env_loader()
    print()
    
    # Atualiza index.php
    update_index_php()
    print()
    
    # Atualiza arquivos PHP
    update_php_files(backup_dir)
    print()
    
    # Atualiza arquivos JavaScript
    update_js_files(backup_dir)
    print()
    
    # Cria configuração JavaScript
    create_js_config()
    print()
    
    # Atualiza layout principal
    update_main_layout()
    print()
    
    print("=" * 60)
    print("✅ Atualização concluída com sucesso!")
    print()
    print("📋 Resumo das alterações:")
    print("   • Arquivo .env criado com URL_ADM=http://localhost/administrativo/")
    print("   • .htaccess atualizado para usar variáveis")
    print("   • EnvLoader.php criado para carregar variáveis")
    print("   • index.php atualizado para incluir EnvLoader")
    print("   • Arquivos PHP atualizados para usar \$_ENV['URL_ADM']")
    print("   • Arquivos JS atualizados para usar window.URL_ADM")
    print("   • config.js criado para configuração JavaScript")
    print("   • Layout principal atualizado")
    print()
    print(f"💾 Backup criado em: {backup_dir}")
    print()
    print("🔧 Para usar em produção:")
    print("   1. Ajuste a variável URL_ADM no arquivo .env")
    print("   2. Certifique-se de que o .env está no .gitignore")
    print("   3. Teste todas as funcionalidades do sistema")
    print("   4. Execute: php scripts/generate-htaccess.php")

if __name__ == "__main__":
    main()
