<?php

use Phinx\Migration\AbstractMigration;

class AddDocumentoCodigoToLgpdTermos extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('lgpd_termos')) {
            $table = $this->table('lgpd_termos');

            if (!$table->hasColumn('documento_codigo')) {
                $table
                    ->addColumn('documento_codigo', 'string', [
                        'limit' => 100,
                        'null' => true,
                        'default' => null,
                        'comment' => 'Identificador lógico do documento (ex.: TERMO_LOGIN_X)',
                    ])
                    ->addIndex(['documento_codigo'])
                    ->update();
            }
        }
    }

    public function down(): void
    {
        if ($this->hasTable('lgpd_termos')) {
            $table = $this->table('lgpd_termos');

            if ($table->hasColumn('documento_codigo')) {
                $table
                    ->removeIndex(['documento_codigo'])
                    ->removeColumn('documento_codigo')
                    ->update();
            }
        }
    }
}


