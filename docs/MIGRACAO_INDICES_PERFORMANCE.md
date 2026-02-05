# Migration de Índices de Performance

## 📋 Sobre

Foi criada uma migration do Phinx para garantir que os índices de performance sejam criados automaticamente ao executar as migrations.

**Arquivo:** `database/migrations/20250205180000_add_performance_indexes_training.php`

---

## ✅ O que a Migration Faz

A migration adiciona **9 índices** nas seguintes tabelas:

1. **adms_training_users** (3 índices)
   - `idx_training_users_user_status`
   - `idx_training_users_training_status`
   - `idx_training_users_created_at`

2. **adms_training_applications** (2 índices)
   - `idx_training_applications_user_training_created` ⚠️ **CRÍTICO** (resolve N+1)
   - `idx_training_applications_user_training`

3. **adms_users** (2 índices)
   - `idx_users_status_department`
   - `idx_users_status_position`

4. **adms_trainings** (1 índice)
   - `idx_trainings_ativo_codigo`

5. **adms_training_positions** (1 índice)
   - `idx_training_positions_training_position`

---

## 🔄 Como Funciona ao Importar o Banco

### Cenário 1: Importar dump SQL completo

1. **Importar o dump SQL** (sem os índices)
2. **Executar migrations:**
   ```bash
   php vendor/bin/phinx migrate
   ```
3. **Resultado:** Os índices serão criados automaticamente ✅

### Cenário 2: Banco já tem os índices

A migration verifica se os índices já existem antes de criar:
- Se existir: **ignora** (não dá erro)
- Se não existir: **cria** o índice

**Seguro executar múltiplas vezes!** ✅

---

## 🚀 Como Executar

### Opção 1: Via linha de comando (recomendado)

```bash
cd /caminho/do/projeto
php vendor/bin/phinx migrate
```

### Opção 2: Executar apenas esta migration

```bash
php vendor/bin/phinx migrate -t 20250205180000
```

### Opção 3: Verificar status

```bash
php vendor/bin/phinx status
```

---

## ⚠️ Importante

### Se você já criou os índices manualmente:

**Não há problema!** A migration verifica se os índices existem antes de criar. Se já existirem, ela simplesmente ignora.

### Se você importar o banco novamente:

1. Os índices serão **perdidos** (se não estiverem no dump SQL)
2. Execute `php vendor/bin/phinx migrate` para recriá-los automaticamente
3. Ou execute o script SQL manualmente novamente

---

## 🔙 Rollback (Desfazer)

Se precisar remover os índices:

```bash
php vendor/bin/phinx rollback -t 20250205180000
```

**Cuidado:** Isso removerá todos os índices criados por esta migration.

---

## 📝 Notas Técnicas

### Verificação de Índices

A migration usa `SHOW INDEX` para verificar se um índice já existe antes de tentar criá-lo:

```php
$indexes = $this->fetchAll("SHOW INDEX FROM tabela WHERE Key_name = 'nome_indice'");
if (empty($indexes)) {
    // Criar índice
}
```

### Índice com DESC

O índice `idx_training_applications_user_training_created` usa `DESC` na coluna `created_at`. Como o Phinx não suporta isso diretamente, a migration usa SQL direto:

```php
$this->execute("ALTER TABLE ... ADD INDEX ... (`created_at` DESC)");
```

---

## ✅ Checklist

- [x] Migration criada
- [x] Verificação de índices existentes implementada
- [x] Rollback implementado
- [x] Documentação criada

---

**Última atualização:** 2025-02-05

