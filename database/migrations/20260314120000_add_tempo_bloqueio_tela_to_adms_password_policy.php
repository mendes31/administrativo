<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddTempoBloqueioTelaToAdmsPasswordPolicy extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_password_policy')) {
            $table = $this->table('adms_password_policy');
            $table->addColumn('tempo_bloqueio_tela', 'integer', [
                    'default' => 1,
                    'null' => false,
                    'after' => 'tempo_expiracao_sessao',
                    'comment' => 'Minutos antes da expiração para bloquear a tela (lock screen)',
                ])
                ->update();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_password_policy')) {
            $table = $this->table('adms_password_policy');
            if ($table->hasColumn('tempo_bloqueio_tela')) {
                $table->removeColumn('tempo_bloqueio_tela')
                      ->update();
            }
        }
    }
}

