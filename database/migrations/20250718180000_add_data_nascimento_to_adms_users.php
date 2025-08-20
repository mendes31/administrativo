<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddDataNascimentoToAdmsUsers extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('adms_users');
        $table->addColumn('data_nascimento', 'date', [
            'null' => true,
            'after' => 'image',
            'comment' => 'Data de nascimento do usuário'
        ])->update();
    }

    public function down(): void
    {
        $table = $this->table('adms_users');
        $table->removeColumn('data_nascimento')->update();
    }
} 