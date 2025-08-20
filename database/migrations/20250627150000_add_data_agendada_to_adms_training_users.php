<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddDataAgendadaToAdmsTrainingUsers extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_training_users')) {
            $columns = $this->query("SHOW COLUMNS FROM adms_training_users LIKE 'data_agendada'")->fetchAll();
            if (empty($columns)) {
                $this->execute("ALTER TABLE adms_training_users ADD COLUMN data_agendada DATE NULL AFTER data_realizacao");
            }
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_training_users')) {
            $columns = $this->query("SHOW COLUMNS FROM adms_training_users LIKE 'data_agendada'")->fetchAll();
            if (!empty($columns)) {
                $this->execute("ALTER TABLE adms_training_users DROP COLUMN data_agendada");
            }
        }
    }
} 