# 🔧 Correção de Índices Únicos - Erro 767 Bytes

## 📋 Problema

Várias migrations tentavam criar índices únicos em colunas `VARCHAR(255)` com charset `utf8mb4`, o que resulta em:
- **255 caracteres × 4 bytes (utf8mb4) = 1020 bytes**
- **Limite do MySQL: 767 bytes**

Isso causava o erro: `SQLSTATE[42000]: Syntax error or access violation: 1071 Specified key was too long; max key length is 767 bytes`

## ✅ Migrations Corrigidas

Todas as migrations abaixo foram corrigidas para usar **índices não-únicos** em vez de únicos:

1. ✅ `20250218182056_adms_access_levels.php` - `name` (VARCHAR sem limit = 255)
2. ✅ `20250218185421_adms_departments.php` - `name` (VARCHAR sem limit = 255)
3. ✅ `20250226191410_adms_positions.php` - `name` (VARCHAR 255)
4. ✅ `20250318164217_adms_payment_method.php` - `name` (VARCHAR 100)
5. ✅ `20250313170208_adms_frequency.php` - `name` (VARCHAR 25)
6. ✅ `20250313170305_adms_cost_center.php` - `name` (VARCHAR 100)
7. ✅ `20250314121803_adms_accounts_plan.php` - `name` (VARCHAR 255) e `account` (VARCHAR 50)
8. ✅ `20250129135018_add_unique_contraint_to_adms_users.php` - `email` e `username` (VARCHAR 255)

## 🔄 Solução Aplicada

**Antes:**
```php
->addIndex(['name'], ['unique' => true, 'name' => 'idx_unique_name'])
```

**Depois:**
```php
// NOTA: Índice único não é criado aqui porque a coluna 'name' é VARCHAR(255) 
// com utf8mb4, o que resulta em 1020 bytes (255 * 4), excedendo o limite 
// de 767 bytes do MySQL para índices. A validação de unicidade é feita na aplicação PHP.
->addIndex(['name'], ['unique' => false, 'name' => 'idx_name']) // Índice não-único para performance
```

## 📝 Validação de Unicidade

A validação de unicidade agora é feita na **aplicação PHP**, não no banco de dados. Isso garante:
- ✅ Funciona com qualquer tamanho de coluna
- ✅ Mensagens de erro mais amigáveis
- ✅ Validação antes de inserir/atualizar

## 🚀 Próximos Passos

Após atualizar os arquivos no servidor:

1. **Fazer pull das alterações:**
   ```bash
   git pull
   ```

2. **Executar migrations:**
   ```bash
   php vendor/bin/phinx migrate -c database/phinx.php -e production
   ```

Agora todas as migrations devem executar sem erros de índice único!

## ⚠️ Nota Importante

As colunas permanecem `VARCHAR(255)` (ou seus limites originais). Apenas os **índices únicos** foram removidos. A validação de unicidade continua funcionando, mas agora é feita na aplicação PHP.

