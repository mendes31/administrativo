# 🔄 Reorganização de Migrations - Correção de Dependências

## 📋 Problemas Identificados e Corrigidos

### 1. **LGPD Consentimentos** ✅ CORRIGIDO

**Problema:** Migrations tentavam modificar `lgpd_consentimentos` antes da tabela ser criada.

**Migrations Reorganizadas:**
- `20250206000000_add_audit_fields_to_lgpd_consentimentos.php` 
  → Movida para: `20250725181010_add_audit_fields_to_lgpd_consentimentos.php`
  
- `20250206090000_add_adms_user_id_to_lgpd_consentimentos.php`
  → Movida para: `20250725181020_add_adms_user_id_to_lgpd_consentimentos.php`
  
- `20250206100000_add_lgpd_termo_id_to_lgpd_consentimentos.php`
  → Movida para: `20250725181030_add_lgpd_termo_id_to_lgpd_consentimentos.php`
  
- `20250206103000_create_lgpd_consentimento_arquivos.php`
  → Movida para: `20250725181040_create_lgpd_consentimento_arquivos.php`

**Ordem Correta Agora:**
1. `20250725181000` - Cria `lgpd_consentimentos`
2. `20250725181010` - Adiciona campos de auditoria
3. `20250725181020` - Adiciona `adms_user_id`
4. `20250725181030` - Adiciona `lgpd_termo_id`
5. `20250725181040` - Cria `lgpd_consentimento_arquivos`

---

### 2. **Strategic Plan Observations** ✅ CORRIGIDO

**Problema:** Migration criava tabela com foreign keys para `adms_strategic_plans` que só era criada depois.

**Migration Reorganizada:**
- `20250120130000_create_adms_strategic_plan_observations.php`
  → Movida para: `20250710160010_create_adms_strategic_plan_observations.php`

**Ordem Correta Agora:**
1. `20250710160000` - Cria `adms_strategic_plans`
2. `20250710160010` - Cria `adms_strategic_plan_observations` (sem FKs inicialmente)
3. `20250710160020` - Adiciona foreign keys (migration separada)
4. `20250710161000` - Cria `adms_strategic_indicators`

---

### 3. **Índices de Performance** ✅ CORRIGIDO

**Problema:** Migration tentava criar índices em colunas que ainda não existiam.

**Migration Reorganizada:**
- `20250205180000_add_performance_indexes_training.php`
  → Movida para: `20260128130000_add_performance_indexes_training.php`

**Motivo:** Agora executa por último, garantindo que todas as colunas já existam.

---

### 4. **Índices Únicos em adms_users** ✅ CORRIGIDO

**Problema:** Tentativa de criar índices únicos em VARCHAR(255) com utf8mb4 excedia 767 bytes.

**Solução:** Migration modificada para criar índices não-únicos (validação de unicidade na aplicação PHP).

---

## 📊 Resumo das Alterações

| Migration Original | Nova Data | Motivo |
|-------------------|-----------|--------|
| `20250206000000_add_audit_fields_to_lgpd_consentimentos.php` | `20250725181010` | Executar após criação da tabela |
| `20250206090000_add_adms_user_id_to_lgpd_consentimentos.php` | `20250725181020` | Executar após criação da tabela |
| `20250206100000_add_lgpd_termo_id_to_lgpd_consentimentos.php` | `20250725181030` | Executar após criação da tabela |
| `20250206103000_create_lgpd_consentimento_arquivos.php` | `20250725181040` | Executar após criação da tabela |
| `20250120130000_create_adms_strategic_plan_observations.php` | `20250710160010` | Executar após criação de strategic_plans |
| `20250205180000_add_performance_indexes_training.php` | `20260128130000` | Executar por último (após todas as colunas) |

---

## ✅ Verificações Implementadas

Todas as migrations problemáticas agora incluem verificações de segurança:

1. **Verificação de existência de tabela** (`hasTable()`)
2. **Verificação de existência de colunas** (`hasColumn()`)
3. **Verificação de existência de índices** antes de criar

---

## 🚀 Próximos Passos

1. **Fazer commit das alterações:**
   ```bash
   git add database/migrations/
   git commit -m "Reorganizar migrations para corrigir dependências"
   ```

2. **No servidor, fazer pull:**
   ```bash
   git pull
   ```

3. **Se migrations antigas já foram executadas:**
   - Fazer rollback das migrations problemáticas (se possível)
   - Ou marcar as novas como já executadas no `phinxlog`

4. **Executar migrations:**
   ```bash
   php vendor/bin/phinx migrate -c database/phinx.php -e production
   ```

---

## 📝 Notas Importantes

- As migrations reorganizadas mantêm verificações de segurança (`hasTable`, `hasColumn`)
- A migration de índices de performance agora executa por último
- Índices únicos em VARCHAR(255) foram substituídos por índices não-únicos (validação no PHP)
- Foreign keys são adicionadas em migrations separadas quando necessário

---

## 🔍 Scripts Criados

- `scripts/analisar_dependencias_migrations.php` - Analisa dependências entre migrations
- `scripts/reorganizar_migrations_problematicas.php` - Reorganiza migrations problemáticas
- `scripts/ajustar_datas_migrations_lgpd.php` - Ajusta datas sequenciais para evitar conflitos

