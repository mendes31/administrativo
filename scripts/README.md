# Scripts de Desenvolvimento

Esta pasta contém scripts úteis para desenvolvimento e manutenção do sistema.

## 📁 Scripts Disponíveis

### `generate-htaccess.php`
Script para gerar o arquivo `.htaccess` dinamicamente baseado nas configurações do `.env`.

**⚠️ IMPORTANTE:** Este script também corrige URLs hardcoded no `.htaccess`, mas para corrigir URLs hardcoded nos arquivos PHP, use os scripts de correção abaixo.

### `fix_urls.py` 🐍
Script Python avançado para corrigir TODOS os URLs hardcoded no sistema.

#### 🚀 Como usar:
```bash
# Da raiz do projeto
python3 scripts/fix_urls.py
```

#### 📝 O que faz:
1. Cria backup automático de todos os arquivos
2. Cria/atualiza arquivo `.env` com `URL_ADM`
3. Corrige URLs hardcoded em arquivos PHP, JS e .htaccess
4. Cria `EnvLoader.php` para variáveis de ambiente
5. Cria `config.js` para configuração JavaScript
6. Atualiza `index.php` e layout principal

#### 💡 Quando usar:
- ✅ Primeira vez corrigindo URLs hardcoded
- ✅ Mudou de ambiente (dev → staging → produção)
- ✅ Clonou o projeto em uma pasta diferente
- ✅ Quer correção completa e automática

### `fix_urls.sh` 🐚
Script Bash simples para correções básicas de URLs.

#### 🚀 Como usar:
```bash
# Da raiz do projeto
bash scripts/fix_urls.sh
# ou
./scripts/fix_urls.sh
```

#### 📝 O que faz:
1. Cria backup com timestamp
2. Corrige URLs básicas em arquivos principais
3. Cria estrutura de variáveis de ambiente
4. Mais rápido, mas menos abrangente

#### 💡 Quando usar:
- ✅ Correções rápidas e simples
- ✅ Sistema Linux/Unix
- ✅ Não tem Python instalado
- ✅ Quer controle manual das alterações

#### 🚀 Como usar:
```bash
# Da raiz do projeto
php scripts/generate-htaccess.php
```

#### 📝 O que faz:
1. Lê a variável `URL_ADM` do arquivo `.env`
2. Extrai o caminho da URL
3. Gera um `.htaccess` com as configurações corretas
4. Atualiza automaticamente o arquivo

#### 💡 Quando usar:
- ✅ Mudou a URL no `.env`
- ✅ Mudou de ambiente (dev → staging → produção)
- ✅ Clonou o projeto em uma pasta diferente
- ✅ Mudou a estrutura de pastas do servidor

#### 🔧 Exemplo de uso:
```env
# .env
URL_ADM=http://localhost/administrativo/

# Executar script
php scripts/generate-htaccess.php

# Resultado: .htaccess com RewriteBase /administrativo/
```

## 📋 Adicionando Novos Scripts

Para adicionar novos scripts:

1. Crie o arquivo na pasta `scripts/`
2. Adicione documentação neste README
3. Teste o script antes de commitar
4. Considere se deve ser versionado no Git

## ⚠️ Importante

- **Execute sempre da raiz do projeto**
- **Verifique as permissões** se houver erros
- **Teste o sistema** após gerar o `.htaccess`
- **Backup** do `.htaccess` antes de executar (opcional)

## 🆘 Solução de Problemas

### Erro: "Execute este script da raiz do projeto"
```bash
# ❌ Erro
cd scripts
php generate-htaccess.php

# ✅ Correto
cd /caminho/para/administrativo
php scripts/generate-htaccess.php
```

### Erro: "Erro ao gerar o arquivo .htaccess"
- Verifique se tem permissão de escrita na pasta
- Verifique se o arquivo `.env` existe
- Verifique se o Composer está instalado
