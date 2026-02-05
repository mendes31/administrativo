# Solução para Erro de Permissão ao Criar Índices

## 🔴 Problema

Erro ao tentar criar índices:
```
#1142 - INDEX command denied to user 'tiaraju004_add1'@'10.19.0.12' for table 'adms_training_users'
```

**Causa:** O usuário do banco de dados não tem permissão `INDEX` ou `ALTER` nas tabelas.

---

## ✅ Soluções

### Opção 1: Solicitar ao Provedor de Hospedagem (RECOMENDADO)

Entre em contato com o suporte da **Kinghost** e solicite:

**Assunto:** Solicitação de criação de índices para otimização de performance

**Mensagem sugerida:**
```
Olá,

Preciso criar índices nas tabelas do banco de dados para otimizar 
o desempenho das queries. O usuário atual não tem permissão para 
criar índices.

Banco de dados: tiaraju04
Usuário: tiaraju004_add1

Por favor, executem o seguinte script SQL com um usuário que tenha 
permissões adequadas:

[COLAR O CONTEÚDO DO SCRIPT AQUI]

Agradeço a atenção.
```

**Arquivo para enviar:** `scripts/add_performance_indexes_training_phpmyadmin.sql`

---

### Opção 2: Verificar se Existe Usuário com Permissões

Alguns provedores criam um usuário "root" ou "admin" separado. Verifique se você tem acesso a outro usuário.

---

### Opção 3: Usar Migrations do Phinx (SE DISPONÍVEL)

Se você tiver acesso via SSH ou linha de comando, pode criar uma migration do Phinx:

**Arquivo:** `database/migrations/YYYYMMDDHHMMSS_add_performance_indexes_training.php`

```php
<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPerformanceIndexesTraining extends AbstractMigration
{
    public function up(): void
    {
        // Índices para adms_training_users
        $this->table('adms_training_users')
            ->addIndex(['adms_user_id', 'status'], ['name' => 'idx_training_users_user_status'])
            ->addIndex(['adms_training_id', 'status'], ['name' => 'idx_training_users_training_status'])
            ->addIndex(['created_at'], ['name' => 'idx_training_users_created_at'])
            ->update();

        // Índices para adms_training_applications
        $this->table('adms_training_applications')
            ->addIndex(['adms_user_id', 'adms_training_id', 'created_at'], ['name' => 'idx_training_applications_user_training_created'])
            ->addIndex(['adms_user_id', 'adms_training_id'], ['name' => 'idx_training_applications_user_training'])
            ->update();

        // Índices para adms_users
        $this->table('adms_users')
            ->addIndex(['status', 'user_department_id'], ['name' => 'idx_users_status_department'])
            ->addIndex(['status', 'user_position_id'], ['name' => 'idx_users_status_position'])
            ->update();

        // Índices para adms_trainings
        $this->table('adms_trainings')
            ->addIndex(['ativo', 'codigo'], ['name' => 'idx_trainings_ativo_codigo'])
            ->update();

        // Índices para adms_training_positions
        $this->table('adms_training_positions')
            ->addIndex(['adms_training_id', 'adms_position_id'], ['name' => 'idx_training_positions_training_position'])
            ->update();
    }

    public function down(): void
    {
        // Remover índices se necessário
        $this->table('adms_training_users')
            ->removeIndexByName('idx_training_users_user_status')
            ->removeIndexByName('idx_training_users_training_status')
            ->removeIndexByName('idx_training_users_created_at')
            ->update();

        $this->table('adms_training_applications')
            ->removeIndexByName('idx_training_applications_user_training_created')
            ->removeIndexByName('idx_training_applications_user_training')
            ->update();

        $this->table('adms_users')
            ->removeIndexByName('idx_users_status_department')
            ->removeIndexByName('idx_users_status_position')
            ->update();

        $this->table('adms_trainings')
            ->removeIndexByName('idx_trainings_ativo_codigo')
            ->update();

        $this->table('adms_training_positions')
            ->removeIndexByName('idx_training_positions_training_position')
            ->update();
    }
}
```

**Executar:**
```bash
php vendor/bin/phinx migrate
```

---

## 📊 Impacto das Otimizações SEM Índices

**Boa notícia:** As otimizações de código já implementadas funcionam mesmo sem os índices:

### ✅ Já Funcionando:
1. **Paginação** - Reduz de 1.364 registros para 50 por página
2. **Problema N+1 resolvido** - De 1.365 queries para 1 query única
3. **Query duplicada removida** - Elimina 50% das queries desnecessárias

### ⚠️ Melhorias Adicionais com Índices:
- JOINs mais rápidos (30-50% mais rápido)
- Filtros mais eficientes
- Ordenação otimizada

**Resultado esperado:**
- **Sem índices:** 70-80% mais rápido (já implementado)
- **Com índices:** 90-95% mais rápido (quando criados)

---

## 🔍 Verificar Permissões Atuais

Execute no phpMyAdmin para verificar permissões:

```sql
SHOW GRANTS FOR CURRENT_USER();
```

Ou:

```sql
SELECT * FROM information_schema.user_privileges 
WHERE grantee = CONCAT("'", SUBSTRING_INDEX(USER(), '@', 1), "'@'", SUBSTRING_INDEX(USER(), '@', -1), "'");
```

---

## 📝 Próximos Passos

1. **Imediato:** As otimizações de código já estão funcionando - teste a página e veja a melhoria
2. **Curto prazo:** Entre em contato com a Kinghost para criar os índices
3. **Após índices criados:** Execute o script novamente ou peça para o suporte executar

---

## 💡 Alternativa: Usar ALTER TABLE (Pode Funcionar)

Alguns provedores permitem `ALTER TABLE` mas não `CREATE INDEX` diretamente. Tente:

```sql
ALTER TABLE `adms_training_users` 
ADD INDEX `idx_training_users_user_status` (`adms_user_id`, `status`);
```

Se funcionar, repita para os outros índices.

---

**Última atualização:** 2025-02-05

