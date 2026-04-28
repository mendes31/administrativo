<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddEscolaridadeToAdmsUsers extends AbstractMigration
{
    // Observação: migration idempotente para suportar reexecução segura em ambientes
    // onde o deploy do arquivo possa ocorrer após execuções prévias do Phinx.
    public function up(): void
    {
        if (!$this->hasTable('adms_users')) {
            return;
        }

        $table = $this->table('adms_users');
        if (!$table->hasColumn('escolaridade')) {
            $table
                ->addColumn('escolaridade', 'string', [
                    'limit' => 120,
                    'null' => true,
                    'after' => 'estado_civil',
                ])
                ->update();
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_users')) {
            return;
        }

        $table = $this->table('adms_users');
        if ($table->hasColumn('escolaridade')) {
            $table->removeColumn('escolaridade')->update();
        }
    }
}

