# 🔄 Importação Incremental de Backup (Ignorar Registros Existentes)

## ✅ Resposta Rápida

**SIM!** Você pode fazer importação incremental usando `INSERT IGNORE` ou `ON DUPLICATE KEY UPDATE`. Isso ignora registros que já existem (criados pelas seeds).

## 🎯 Solução: INSERT IGNORE

### O que faz?

- **Ignora** registros com chave primária duplicada
- **Insere** apenas registros novos
- **Não gera erro** se o registro já existir

### Como Aplicar?

#### Opção 1: Script Automático (Recomendado)

```bash
php scripts/corrigir_backup_insert_ignore.php backup.sql backup_incremental.sql
```

Depois importe `backup_incremental.sql`.

#### Opção 2: Manual (PowerShell)

```powershell
(Get-Content backup.sql) -replace 'INSERT INTO', 'INSERT IGNORE INTO' | Set-Content backup_incremental.sql
```

#### Opção 3: Manual (Linux/Mac)

```bash
sed 's/INSERT INTO/INSERT IGNORE INTO/g' backup.sql > backup_incremental.sql
```

## 📋 Exemplo do Resultado

**Antes (backup.sql):**
```sql
INSERT INTO `adms_access_levels` (`id`, `name`, `create_at`, `update_at`) 
VALUES (1, 'Super Administrador', '2015-09-11 10:53:20', NULL);
```

**Depois (backup_incremental.sql):**
```sql
INSERT IGNORE INTO `adms_access_levels` (`id`, `name`, `create_at`, `update_at`) 
VALUES (1, 'Super Administrador', '2015-09-11 10:53:20', NULL);
```

## 🔍 Como Funciona?

### INSERT IGNORE

```sql
INSERT IGNORE INTO tabela (id, campo) VALUES (1, 'valor');
```

- ✅ Se ID=1 **não existe** → Insere o registro
- ✅ Se ID=1 **já existe** → Ignora silenciosamente (sem erro)
- ✅ Continua com os próximos registros

### Comparação com INSERT Normal

| Comportamento | INSERT INTO | INSERT IGNORE INTO |
|--------------|-------------|-------------------|
| Registro novo | ✅ Insere | ✅ Insere |
| Registro existente | ❌ Erro #1062 | ✅ Ignora (sem erro) |
| Continua importação | ❌ Para no erro | ✅ Continua |

## 🚀 Passo a Passo Completo

### 1. Fazer Backup Incremental

No phpMyAdmin, ao exportar:
- ✅ Exportar apenas **DADOS** (não estrutura)
- ✅ Salvar como `backup_dados.sql`

### 2. Corrigir o Backup

```bash
# No Windows (PowerShell)
php scripts/corrigir_backup_insert_ignore.php backup_dados.sql backup_incremental.sql

# Ou manualmente
(Get-Content backup_dados.sql) -replace 'INSERT INTO', 'INSERT IGNORE INTO' | Set-Content backup_incremental.sql
```

### 3. Importar

No phpMyAdmin:
- Vá em **Importar**
- Selecione `backup_incremental.sql`
- Clique em **Executar**

**Resultado:**
- ✅ Registros que já existem (das seeds) → Ignorados
- ✅ Registros novos → Inseridos
- ✅ Sem erros de duplicata

## 🔧 Alternativa: ON DUPLICATE KEY UPDATE

Se você quiser **atualizar** registros existentes em vez de ignorar:

```sql
INSERT INTO `adms_access_levels` (`id`, `name`, `create_at`, `update_at`) 
VALUES (1, 'Super Administrador', '2015-09-11 10:53:20', NULL)
ON DUPLICATE KEY UPDATE 
    name = VALUES(name),
    update_at = VALUES(update_at);
```

**Quando usar:**
- ✅ Se você quer **atualizar** dados existentes
- ✅ Se os dados do backup são mais recentes

**Quando NÃO usar:**
- ❌ Se você quer **manter** os dados das seeds
- ❌ Se os dados das seeds são mais importantes

## 📊 Comparação de Métodos

| Método | Comportamento | Uso Recomendado |
|--------|---------------|-----------------|
| `INSERT INTO` | Erro se duplicado | ❌ Não usar para incremental |
| `INSERT IGNORE INTO` | Ignora duplicados | ✅ **Recomendado** para incremental |
| `REPLACE INTO` | Substitui duplicados | ⚠️ Usar com cuidado (pode perder dados) |
| `ON DUPLICATE KEY UPDATE` | Atualiza duplicados | ✅ Se quiser atualizar dados existentes |

## ⚠️ Limitações do INSERT IGNORE

1. **Só funciona com chaves primárias/únicas:**
   - Se a tabela não tem chave primária, pode inserir duplicatas
   - Funciona perfeitamente para seu caso (tabelas têm IDs)

2. **Ignora TODOS os erros:**
   - Não só duplicatas, mas também outros erros (ex: constraint violations)
   - Verifique os logs se algo não funcionar como esperado

3. **Não atualiza dados existentes:**
   - Se o registro já existe, mantém os dados antigos
   - Use `ON DUPLICATE KEY UPDATE` se quiser atualizar

## 🎯 Fluxo Recomendado

```
1. Executar migrations → Cria estrutura
2. Executar seeds → Dados iniciais (ex: usuário manager)
3. Corrigir backup → INSERT IGNORE
4. Importar backup → Apenas registros novos
5. ✅ Sistema completo com dados incrementais
```

## 📝 Checklist

- [ ] Exportar backup apenas com DADOS (não estrutura)
- [ ] Corrigir backup com `INSERT IGNORE`
- [ ] Verificar se o arquivo foi corrigido corretamente
- [ ] Importar backup corrigido
- [ ] Verificar se os dados foram importados (apenas novos)
- [ ] Confirmar que dados das seeds foram preservados

## 🔍 Verificar Resultado

Após importar, verifique:

```sql
-- Ver quantos registros foram inseridos
SELECT COUNT(*) FROM adms_access_levels;

-- Verificar se os dados das seeds ainda estão lá
SELECT * FROM adms_access_levels WHERE id = 1;
```

Se tudo funcionou:
- ✅ Dados das seeds ainda existem
- ✅ Dados novos do backup foram adicionados
- ✅ Sem erros de duplicata

