<?php

use Phinx\Migration\AbstractMigration;

class AddAdmsUserIdToLgpdConsentimentos extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('lgpd_consentimentos')) {
            $table = $this->table('lgpd_consentimentos');

            if (!$table->hasColumn('adms_user_id')) {
                $table
                    ->addColumn('adms_user_id', 'integer', [
                        'null' => true,
                        'comment' => 'ID do usuário (adms_users) vinculado ao consentimento',
                        'after' => 'id',
                    ])
                    ->addIndex(['adms_user_id'], ['name' => 'idx_lgpd_consentimentos_user'])
                    ->update();
            }
        }
    }

    public function down(): void
    {
        if ($this->hasTable('lgpd_consentimentos')) {
            $table = $this->table('lgpd_consentimentos');

            if ($table->hasColumn('adms_user_id')) {
                $table
                    ->removeIndexByName('idx_lgpd_consentimentos_user')
                    ->removeColumn('adms_user_id')
                    ->update();
            }
        }
    }
}


