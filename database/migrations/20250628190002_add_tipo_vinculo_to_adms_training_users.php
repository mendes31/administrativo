<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddTipoVinculoToAdmsTrainingUsers extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_training_users') && !$this->table('adms_training_users')->hasColumn('tipo_vinculo')) {
            $table = $this->table('adms_training_users');
            $table->addColumn('tipo_vinculo', 'enum', [
                'values' => ['cargo', 'individual'],
                'default' => 'individual',
                'null' => false,
                'after' => 'adms_training_id',
            ])->update();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_training_users') && $this->table('adms_training_users')->hasColumn('tipo_vinculo')) {
            $table = $this->table('adms_training_users');
            $table->removeColumn('tipo_vinculo')->update();
        }
    }
} 