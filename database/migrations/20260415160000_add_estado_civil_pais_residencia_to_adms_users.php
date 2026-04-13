<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Estado civil e país de residência (ISO 3166-1 alpha-2) para cadastro e People Analytics.
 */
final class AddEstadoCivilPaisResidenciaToAdmsUsers extends AbstractMigration
{
    private const ESTADO_CIVIL = [
        'solteiro',
        'casado',
        'uniao_estavel',
        'divorciado',
        'viuvo',
        'separado',
        'outro',
    ];

    public function up(): void
    {
        if (!$this->hasTable('adms_users')) {
            return;
        }

        $table = $this->table('adms_users');

        if (!$table->hasColumn('estado_civil')) {
            $after = $table->hasColumn('filhos') ? 'filhos' : 'sexo';
            $table->addColumn('estado_civil', 'enum', [
                'values' => self::ESTADO_CIVIL,
                'null' => true,
                'default' => null,
                'comment' => 'Estado civil (cadastro RH)',
                'after' => $after,
            ]);
        }

        if (!$table->hasColumn('pais_residencia_iso')) {
            $table->addColumn('pais_residencia_iso', 'char', [
                'limit' => 2,
                'null' => true,
                'default' => null,
                'comment' => 'País de residência ISO 3166-1 alpha-2',
                'after' => 'estado_civil',
            ]);
        }

        $table->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_users')) {
            return;
        }

        $table = $this->table('adms_users');
        if ($table->hasColumn('pais_residencia_iso')) {
            $table->removeColumn('pais_residencia_iso');
        }
        if ($table->hasColumn('estado_civil')) {
            $table->removeColumn('estado_civil');
        }
        $table->update();
    }
}
