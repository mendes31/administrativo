<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddRacaEmpresaContratanteToAdmsUsers extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_users')) {
            return;
        }

        $table = $this->table('adms_users');
        if (!$table->hasColumn('raca')) {
            $table->addColumn('raca', 'string', [
                'limit' => 40,
                'null' => true,
                'after' => 'escolaridade',
            ]);
        }
        if (!$table->hasColumn('empresa_contratante')) {
            $table->addColumn('empresa_contratante', 'string', [
                'limit' => 60,
                'null' => true,
                'after' => 'raca',
            ]);
        }
        $table->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_users')) {
            return;
        }

        $table = $this->table('adms_users');
        if ($table->hasColumn('empresa_contratante')) {
            $table->removeColumn('empresa_contratante');
        }
        if ($table->hasColumn('raca')) {
            $table->removeColumn('raca');
        }
        $table->update();
    }
}
