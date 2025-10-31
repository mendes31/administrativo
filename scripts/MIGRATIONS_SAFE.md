# Migrations Seguras - CRM

Todas as migrations do CRM foram atualizadas para verificar a existência de tabelas e colunas antes de tentar criá-las, evitando erros em produção quando as estruturas já existem.

## Migrations Atualizadas

### Criação de Tabelas

Todas as migrations que criam tabelas agora verificam se a tabela já existe:

- ✅ `20251028100000_create_crm_partners.php`
- ✅ `20251028100001_create_crm_pipeline_stages.php`
- ✅ `20251028100002_create_crm_opportunities.php`
- ✅ `20251028100003_create_crm_activities.php`
- ✅ `20251028100004_create_crm_stage_history.php`
- ✅ `20251028100005_create_crm_notes.php`
- ✅ `20251028100006_create_crm_documents.php`
- ✅ `20251028100007_create_crm_tags.php`
- ✅ `20251028100008_create_crm_partner_tags.php`
- ✅ `20251029110000_create_crm_custom_fields.php` (3 tabelas)
- ✅ `20251029120000_create_crm_automations.php` (2 tabelas)

### Alteração de Tabelas

Migrations que alteram tabelas existentes verificam se a coluna já existe:

- ✅ `20251029130000_add_country_to_crm_partners.php` - Verifica se a coluna `country` já existe
- ✅ `20251030120001_remove_tags_column_from_crm_partners.php` - Já tinha verificação

## Como Funciona

### Verificação de Tabela

```php
public function change()
{
    // Verificar se a tabela já existe
    if ($this->hasTable('crm_partners')) {
        return; // Sair se já existe
    }

    $table = $this->table('crm_partners');
    // ... resto do código de criação
}
```

### Verificação de Coluna

```php
public function change(): void
{
    // Verificar se a tabela existe
    if (!$this->hasTable('crm_partners')) {
        return;
    }

    $table = $this->table('crm_partners');
    
    // Verificar se a coluna já existe antes de adicionar
    if (!$table->hasColumn('country')) {
        $table->addColumn('country', 'string', [...])
              ->update();
    }
}
```

## Execução em Produção

Agora você pode executar as migrations com segurança:

```bash
vendor/bin/phinx migrate -c database/phinx.php
```

Se as tabelas já existirem, as migrations serão puladas automaticamente sem gerar erros.

## Script de Ajuda

Caso precise marcar migrations como já executadas manualmente:

```bash
php scripts/mark_crm_migrations_as_executed.php
```

Ou execute o SQL diretamente:

```bash
mysql -u usuario -p database < scripts/mark_crm_migrations_as_executed.sql
```

