<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class FixAddCpfCelularToAdmsUsers extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_users')) {
            // Verificar se as colunas já existem
            $columns = $this->query("SHOW COLUMNS FROM adms_users LIKE 'cpf'")->fetchAll();
            if (empty($columns)) {
                $this->execute("ALTER TABLE adms_users ADD COLUMN cpf VARCHAR(14) NULL COMMENT 'CPF do usuário no formato 000.000.000-00' AFTER username");
            }
            
            $columns = $this->query("SHOW COLUMNS FROM adms_users LIKE 'celular'")->fetchAll();
            if (empty($columns)) {
                $this->execute("ALTER TABLE adms_users ADD COLUMN celular VARCHAR(20) NULL COMMENT 'Celular do usuário, ex: (00) 00000-0000' AFTER cpf");
            }
            
            // Verificar se o índice já existe
            $indexes = $this->query("SHOW INDEX FROM adms_users WHERE Key_name = 'idx_adms_users_cpf_unique'")->fetchAll();
            if (empty($indexes)) {
                $this->execute("ALTER TABLE adms_users ADD UNIQUE INDEX idx_adms_users_cpf_unique (cpf)");
            }
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_users')) {
            // Remover índice único de CPF, se existir
            $indexes = $this->query("SHOW INDEX FROM adms_users WHERE Key_name = 'idx_adms_users_cpf_unique'")->fetchAll();
            if (!empty($indexes)) {
                $this->execute("ALTER TABLE adms_users DROP INDEX idx_adms_users_cpf_unique");
            }
            
            // Remover colunas, se existirem
            $columns = $this->query("SHOW COLUMNS FROM adms_users LIKE 'celular'")->fetchAll();
            if (!empty($columns)) {
                $this->execute("ALTER TABLE adms_users DROP COLUMN celular");
            }
            
            $columns = $this->query("SHOW COLUMNS FROM adms_users LIKE 'cpf'")->fetchAll();
            if (!empty($columns)) {
                $this->execute("ALTER TABLE adms_users DROP COLUMN cpf");
            }
        }
    }
}
