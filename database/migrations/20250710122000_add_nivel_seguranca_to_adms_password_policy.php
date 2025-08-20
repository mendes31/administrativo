<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddNivelSegurancaToAdmsPasswordPolicy extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_password_policy')) {
            $table = $this->table('adms_password_policy');
            $table->addColumn('nivel_seguranca', 'enum', [
                'values' => ['Baixo', 'Médio', 'Elevado', 'Customizado'],
                'default' => 'Baixo',
                'null' => false
            ])->update();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_password_policy')) {
            $table = $this->table('adms_password_policy');
            $table->removeColumn('nivel_seguranca')->update();
        }
    }
} 