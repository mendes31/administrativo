<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddBloqueioTemporarioToAdmsUsers extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('adms_users');
        if (!$table->hasColumn('bloqueado_temporario')) {
            $table->addColumn('bloqueado_temporario', 'string', ['limit' => 3, 'default' => 'Não', 'null' => false, 'after' => 'bloqueado']);
        }
        if (!$table->hasColumn('data_bloqueio_temporario')) {
            $table->addColumn('data_bloqueio_temporario', 'datetime', ['null' => true, 'after' => 'bloqueado_temporario']);
        }
        if (!$table->hasColumn('tentativas_login_temporario')) {
            $table->addColumn('tentativas_login_temporario', 'integer', ['default' => 0, 'null' => false, 'after' => 'data_bloqueio_temporario']);
        }
        $table->update();
    }

    public function down(): void
    {
        $table = $this->table('adms_users');
        if ($table->hasColumn('bloqueado_temporario')) {
            $table->removeColumn('bloqueado_temporario');
        }
        if ($table->hasColumn('data_bloqueio_temporario')) {
            $table->removeColumn('data_bloqueio_temporario');
        }
        if ($table->hasColumn('tentativas_login_temporario')) {
            $table->removeColumn('tentativas_login_temporario');
        }
        $table->update();
    }
} 