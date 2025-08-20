<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddTentativasBloqueioTemporarioToAdmsPasswordPolicy extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('adms_password_policy');
        if (!$table->hasColumn('tentativas_bloqueio_temporario')) {
            $table->addColumn('tentativas_bloqueio_temporario', 'integer', [
                'default' => 3,
                'null' => false,
                'after' => 'tentativas_bloqueio',
                'comment' => 'Tentativas antes do bloqueio temporário'
            ]);
        }
        if (!$table->hasColumn('tempo_bloqueio_temporario')) {
            $table->addColumn('tempo_bloqueio_temporario', 'integer', [
                'default' => 15,
                'null' => false,
                'after' => 'tentativas_bloqueio_temporario',
                'comment' => 'Tempo de bloqueio temporário em minutos'
            ]);
        }
        $table->update();
    }

    public function down(): void
    {
        $table = $this->table('adms_password_policy');
        if ($table->hasColumn('tentativas_bloqueio_temporario')) {
            $table->removeColumn('tentativas_bloqueio_temporario');
        }
        if ($table->hasColumn('tempo_bloqueio_temporario')) {
            $table->removeColumn('tempo_bloqueio_temporario');
        }
        $table->update();
    }
} 