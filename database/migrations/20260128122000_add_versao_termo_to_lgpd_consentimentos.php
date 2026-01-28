<?php

use Phinx\Migration\AbstractMigration;

class AddVersaoTermoToLgpdConsentimentos extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('lgpd_consentimentos')) {
            $table = $this->table('lgpd_consentimentos');

            if (!$table->hasColumn('versao_termo')) {
                $table->addColumn('versao_termo', 'string', [
                    'limit' => 20,
                    'null' => true,
                    'after' => 'status',
                    'comment' => 'Versão do termo aceito pelo titular'
                ]);
                $table->update();
            }
        }
    }

    public function down(): void
    {
        if ($this->hasTable('lgpd_consentimentos')) {
            $table = $this->table('lgpd_consentimentos');
            if ($table->hasColumn('versao_termo')) {
                $table->removeColumn('versao_termo');
                $table->update();
            }
        }
    }
}


