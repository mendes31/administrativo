<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddBloqueioTemporarioToAdmsPasswordPolicy extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_password_policy')) {
            $table = $this->table('adms_password_policy');
            $table
                ->addColumn('bloqueio_temporario', 'string', ['limit' => 3, 'default' => 'Não', 'null' => false, 'comment' => 'Sim/Não'])
                ->addColumn('tempo_bloqueio_temporario', 'integer', ['default' => 15, 'null' => false, 'comment' => 'Tempo em minutos'])
                ->addColumn('notificar_usuario_bloqueio', 'string', ['limit' => 3, 'default' => 'Não', 'null' => false, 'comment' => 'Sim/Não'])
                ->addColumn('notificar_admins_bloqueio', 'string', ['limit' => 3, 'default' => 'Não', 'null' => false, 'comment' => 'Sim/Não'])
                ->addColumn('forcar_logout_troca_senha', 'string', ['limit' => 3, 'default' => 'Não', 'null' => false, 'comment' => 'Sim/Não'])
                ->update();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_password_policy')) {
            $table = $this->table('adms_password_policy');
            $table
                ->removeColumn('bloqueio_temporario')
                ->removeColumn('tempo_bloqueio_temporario')
                ->removeColumn('notificar_usuario_bloqueio')
                ->removeColumn('notificar_admins_bloqueio')
                ->removeColumn('forcar_logout_troca_senha')
                ->update();
        }
    }
} 