# Seeds Seguras - Verificação Antes de Inserir

Todas as seeds importantes foram atualizadas ou já possuem verificações para inserir apenas dados novos, evitando duplicatas e erros em produção.

## Seeds do CRM Atualizadas

### ✅ `AddCrmPipelineStages.php`
- **Status:** Já tinha verificação ✓
- **Verificação:** Verifica por `name` e `display_order` antes de inserir
- **Comportamento:** Insere apenas etapas que não existem

### ✅ `AddCrmSampleData.php`
- **Status:** Atualizada ✓
- **Verificação:** 
  - **Parceiros:** Verifica por `code` antes de inserir cada parceiro
  - **Oportunidades:** Verifica por `code` e valida `partner_id` antes de inserir
- **Comportamento:** 
  - Insere apenas parceiros novos (por código)
  - Busca IDs dos parceiros existentes para vincular oportunidades
  - Insere apenas oportunidades válidas que não existem

## Seeds Principais do Sistema

### ✅ `AddAccessLevels.php`
- **Status:** Já tinha verificação ✓
- **Verificação:** Verifica por `name` antes de inserir

### ✅ `AddDepartments.php`
- **Status:** Já tinha verificação ✓
- **Verificação:** Verifica por `name` antes de inserir

### ✅ `AddAdmsPositions.php`
- **Status:** Já tinha verificação ✓
- **Verificação:** Verifica por `name` antes de inserir

### ✅ `AddAdmsPages.php`
- **Status:** Já tinha verificação ✓
- **Verificação:** Verifica por `name` antes de inserir cada página
- **Nota:** Seed muito grande, mas todas as páginas são verificadas individualmente

### ✅ `AddInventoryBasics.php`
- **Status:** Já tinha verificação ✓
- **Verificação:** 
  - Unidades: verifica por `code`
  - Categorias: verifica por `name`
  - Motivos: verifica por `type` e `code`

## Padrão de Verificação

Todas as seeds seguem o padrão:

```php
public function run(): void
{
    $data = [];
    
    foreach ($items as $item) {
        // Verificar se já existe
        $exists = $this->query(
            'SELECT id FROM tabela WHERE campo_unico = :valor',
            ['valor' => $item['campo_unico']]
        )->fetch();
        
        // Se não existir, adicionar ao array
        if (!$exists) {
            $data[] = $item;
        }
    }
    
    // Inserir apenas se houver dados novos
    if (!empty($data)) {
        $table = $this->table('tabela');
        $table->insert($data)->saveData();
        echo "✓ " . count($data) . " registro(s) criado(s)\n";
    } else {
        echo "⚠️ Todos os registros já existem.\n";
    }
}
```

## Como Executar Seeds com Segurança

```bash
# Executar todas as seeds
vendor/bin/phinx seed:run -c database/phinx.php

# Executar seed específica
vendor/bin/phinx seed:run -c database/phinx.php -s AddCrmPipelineStages
vendor/bin/phinx seed:run -c database/phinx.php -s AddCrmSampleData
```

## Vantagens

1. **Idempotência:** Seeds podem ser executadas múltiplas vezes sem gerar duplicatas
2. **Segurança:** Dados existentes não são sobrescritos
3. **Produção:** Pode executar em produção sem medo de duplicar dados
4. **Desenvolvimento:** Facilita testes e reset de ambiente

## Próximos Passos

Se precisar criar novas seeds, sempre seguir o padrão de verificação antes de inserir!

