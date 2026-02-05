# Como Aplicar Índices de Performance no Windows

## 📋 Opções para Aplicar os Índices

### Opção 1: Via phpMyAdmin (RECOMENDADO)

1. Acesse o phpMyAdmin do seu servidor
2. Selecione o banco de dados `administrativo` (ou o nome do seu banco)
3. Clique na aba **SQL**
4. Abra o arquivo `scripts/add_performance_indexes_crm_hierarchy_phpmyadmin.sql`
5. Copie todo o conteúdo do arquivo
6. Cole no campo SQL do phpMyAdmin
7. Clique em **Executar**

**Vantagem:** Mais fácil e visual, não precisa de linha de comando

---

### Opção 2: Via PowerShell (Windows)

No PowerShell do Windows, use o comando `Get-Content` para ler o arquivo:

```powershell
# Primeiro, conecte-se ao MySQL
# Substitua: usuario, senha, nome_banco
$usuario = "tiaraju004_add1"
$senha = "pb3wPDi4J@1T"
$banco = "administrativo"

# Ler o arquivo SQL e executar
Get-Content scripts/add_performance_indexes_crm_hierarchy.sql | mysql -u $usuario -p$senha $banco
```

**OU** usando o caminho completo do MySQL:

```powershell
# Se o MySQL estiver no PATH, ou use o caminho completo
& "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe" -u tiaraju004_add1 -p"pb3wPDi4J@1T" administrativo -e "source scripts/add_performance_indexes_crm_hierarchy.sql"
```

**OU** executar o conteúdo diretamente:

```powershell
# Ler o arquivo
$sql = Get-Content scripts/add_performance_indexes_crm_hierarchy.sql -Raw

# Executar (ajuste o caminho do MySQL)
& "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe" -u tiaraju004_add1 -p"pb3wPDi4J@1T" administrativo -e $sql
```

---

### Opção 3: Via CMD (Prompt de Comando)

No CMD tradicional, o redirecionamento funciona:

```cmd
mysql -u tiaraju004_add1 -p"pb3wPDi4J@1T" administrativo < scripts/add_performance_indexes_crm_hierarchy.sql
```

**OU** se o MySQL não estiver no PATH:

```cmd
"C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe" -u tiaraju004_add1 -p"pb3wPDi4J@1T" administrativo < scripts/add_performance_indexes_crm_hierarchy.sql
```

---

### Opção 4: Via Servidor (SSH/Putty) - RECOMENDADO PARA PRODUÇÃO

Se você tem acesso SSH ao servidor de produção:

```bash
# Conectar ao servidor
ssh usuario@servidor

# Navegar até o projeto
cd /home/tiaraju02/www/administrativo

# Executar o script
mysql -u tiaraju004_add1 -p"pb3wPDi4J@1T" administrativo < scripts/add_performance_indexes_crm_hierarchy.sql
```

---

## ⚠️ Importante

1. **Backup antes de aplicar:** Sempre faça backup do banco antes de aplicar índices
2. **Teste primeiro:** Aplique primeiro em um ambiente de teste
3. **Verificar permissões:** O usuário do banco precisa ter permissão para criar índices

---

## ✅ Verificar se os Índices Foram Criados

Após executar, verifique se os índices foram criados:

```sql
-- Ver índices da tabela crm_partners
SHOW INDEXES FROM crm_partners;

-- Ver índices da tabela adms_users
SHOW INDEXES FROM adms_users;

-- Ver todos os índices do banco
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'administrativo'
AND INDEX_NAME LIKE 'idx_%'
ORDER BY TABLE_NAME, INDEX_NAME;
```

---

## 🔧 Se Der Erro de Permissão

Se você receber erro de permissão, use o script alternativo com `ALTER TABLE`:

```sql
-- Exemplo para um índice:
ALTER TABLE crm_partners 
ADD INDEX idx_crm_partners_responsible_user (responsible_user_id);
```

Ou entre em contato com o suporte do hosting para criar os índices.

---

**Última atualização:** 2025-02-05

