<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Matrícula do colaborador (dados contratuais).
 */
final class AddMatriculaToAdmsUsers extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_users')) {
            return;
        }

        $table = $this->table('adms_users');
        if ($table->hasColumn('matricula')) {
            return;
        }

        $opts = [
            'limit' => 40,
            'null' => true,
            'default' => null,
            'comment' => 'Matrícula funcional do colaborador',
        ];
        if ($table->hasColumn('empresa_contratante')) {
            $opts['after'] = 'empresa_contratante';
        } elseif ($table->hasColumn('data_admissao')) {
            $opts['after'] = 'data_admissao';
        }

        $table->addColumn('matricula', 'string', $opts)->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_users')) {
            return;
        }

        $table = $this->table('adms_users');
        if ($table->hasColumn('matricula')) {
            $table->removeColumn('matricula')->update();
        }
    }
}
