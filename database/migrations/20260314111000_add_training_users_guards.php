<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddTrainingUsersGuards extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_training_users')) {
            return;
        }

        $indexes = $this->fetchAll("SHOW INDEX FROM adms_training_users WHERE Key_name = 'idx_tu_user_training_tipo_status'");
        if (empty($indexes)) {
            $this->table('adms_training_users')
                ->addIndex(
                    ['adms_user_id', 'adms_training_id', 'tipo_vinculo', 'status', 'id'],
                    ['name' => 'idx_tu_user_training_tipo_status']
                )
                ->update();
        }
        // Sem triggers por limitação de permissão no ambiente de produção.
        // As regras de integridade ficam garantidas no PHP (repositórios/controladores).
    }

    public function down(): void
    {
        if ($this->hasTable('adms_training_users')) {
            $indexes = $this->fetchAll("SHOW INDEX FROM adms_training_users WHERE Key_name = 'idx_tu_user_training_tipo_status'");
            if (!empty($indexes)) {
                $this->table('adms_training_users')
                    ->removeIndexByName('idx_tu_user_training_tipo_status')
                    ->update();
            }
        }
    }
}

