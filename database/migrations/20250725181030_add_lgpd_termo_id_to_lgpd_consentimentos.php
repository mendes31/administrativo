<?php

use Phinx\Migration\AbstractMigration;

class AddLgpdTermoIdToLgpdConsentimentos extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('lgpd_consentimentos')) {
            $table = $this->table('lgpd_consentimentos');

            if (!$table->hasColumn('lgpd_termo_id')) {
                $table
                    ->addColumn('lgpd_termo_id', 'integer', [
                        'null' => true,
                        'comment' => 'ID do termo LGPD relacionado (lgpd_termos.id)',
                        'after' => 'id',
                    ])
                    ->addIndex(['lgpd_termo_id'], ['name' => 'idx_lgpd_consentimentos_termo'])
                    ->update();
            }
        }
    }

    public function down(): void
    {
        if ($this->hasTable('lgpd_consentimentos')) {
            $table = $this->table('lgpd_consentimentos');

            if ($table->hasColumn('lgpd_termo_id')) {
                $table
                    ->removeIndexByName('idx_lgpd_consentimentos_termo')
                    ->removeColumn('lgpd_termo_id')
                    ->update();
            }
        }
    }
}


