<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddSexoFilhosToAdmsUsers extends AbstractMigration
{
    public function change(): void
    {
        if (!$this->hasTable('adms_users')) {
            return;
        }

        $table = $this->table('adms_users');

        if (!$table->hasColumn('sexo')) {
            $table->addColumn('sexo', 'char', [
                'limit' => 1,
                'null' => true,
                'default' => null,
                'after' => 'data_nascimento',
                'comment' => 'M=Masculino, F=Feminino, O=Outros',
            ]);
        }

        if (!$table->hasColumn('filhos')) {
            $table->addColumn('filhos', 'char', [
                'limit' => 1,
                'null' => true,
                'default' => null,
                'after' => 'sexo',
                'comment' => 'S=Sim possui filhos, N=Não',
            ]);
        }

        $table->update();
    }
}
