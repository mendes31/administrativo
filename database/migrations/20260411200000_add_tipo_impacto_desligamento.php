<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Classificação de desligamento (regrettable / non-regrettable) para People Analytics e integração tipo Power BI.
 */
final class AddTipoImpactoDesligamento extends AbstractMigration
{
    private const ENUM_VALUES = ['regrettable', 'non_regrettable', 'nao_classificado'];

    public function up(): void
    {
        if ($this->hasTable('adms_users') && !$this->table('adms_users')->hasColumn('tipo_impacto_desligamento')) {
            $this->table('adms_users')
                ->addColumn('tipo_impacto_desligamento', 'enum', [
                    'values' => self::ENUM_VALUES,
                    'null' => true,
                    'default' => null,
                    'comment' => 'Impacto do desligamento para análise de turnover (RH)',
                    'after' => 'motivo_desligamento',
                ])
                ->update();
        }

        if ($this->hasTable('adms_employment_history') && !$this->table('adms_employment_history')->hasColumn('tipo_impacto_desligamento')) {
            $this->table('adms_employment_history')
                ->addColumn('tipo_impacto_desligamento', 'enum', [
                    'values' => self::ENUM_VALUES,
                    'null' => true,
                    'default' => null,
                    'comment' => 'Impacto do desligamento neste período de vínculo',
                    'after' => 'motivo_desligamento',
                ])
                ->update();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_employment_history') && $this->table('adms_employment_history')->hasColumn('tipo_impacto_desligamento')) {
            $this->table('adms_employment_history')->removeColumn('tipo_impacto_desligamento')->update();
        }
        if ($this->hasTable('adms_users') && $this->table('adms_users')->hasColumn('tipo_impacto_desligamento')) {
            $this->table('adms_users')->removeColumn('tipo_impacto_desligamento')->update();
        }
    }
}
