<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddNotificationTimestampFieldsToAdmsTrainingUsers extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_training_users')) {
            // Verificar se os campos já existem usando SQL direto
            $columns = $this->query("SHOW COLUMNS FROM adms_training_users LIKE 'last_notification_expiring'")->fetchAll();
            if (empty($columns)) {
                $this->execute("ALTER TABLE adms_training_users ADD COLUMN last_notification_expiring TIMESTAMP NULL");
            }
            
            $columns = $this->query("SHOW COLUMNS FROM adms_training_users LIKE 'last_notification_expired'")->fetchAll();
            if (empty($columns)) {
                $this->execute("ALTER TABLE adms_training_users ADD COLUMN last_notification_expired TIMESTAMP NULL");
            }
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_training_users')) {
            // Verificar se os campos existem antes de remover
            $columns = $this->query("SHOW COLUMNS FROM adms_training_users LIKE 'last_notification_expiring'")->fetchAll();
            if (!empty($columns)) {
                $this->execute("ALTER TABLE adms_training_users DROP COLUMN last_notification_expiring");
            }
            
            $columns = $this->query("SHOW COLUMNS FROM adms_training_users LIKE 'last_notification_expired'")->fetchAll();
            if (!empty($columns)) {
                $this->execute("ALTER TABLE adms_training_users DROP COLUMN last_notification_expired");
            }
        }
    }
} 