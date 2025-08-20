<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddObservacoesToAdmsTrainingUsers extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_training_users')) {
            // Verificar se o campo já existe usando SQL direto
            $columns = $this->query("SHOW COLUMNS FROM adms_training_users LIKE 'observacoes'")->fetchAll();
            if (empty($columns)) {
                $this->execute("ALTER TABLE adms_training_users ADD COLUMN observacoes TEXT NULL");
            }
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_training_users')) {
            // Verificar se o campo existe antes de remover
            $columns = $this->query("SHOW COLUMNS FROM adms_training_users LIKE 'observacoes'")->fetchAll();
            if (!empty($columns)) {
                $this->execute("ALTER TABLE adms_training_users DROP COLUMN observacoes");
            }
        }
    }
} 