<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddSuperUsuarioPrevAccessLevelIds extends AbstractMigration
{
    public function change(): void
    {
        if (!$this->hasTable('adms_users')) {
            return;
        }

        $table = $this->table('adms_users');

        if (!$table->hasColumn('super_usuario_prev_access_level_ids')) {
            $table->addColumn('super_usuario_prev_access_level_ids', 'text', [
                'null' => true,
                'default' => null,
                'after' => 'super_usuario',
                'comment' => 'JSON com ids de adms_access_levels antes de ativar super usuário (restauração ao desmarcar)',
            ]);
        }

        $table->update();
    }
}
