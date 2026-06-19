<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Fluxo ASO em andamento: status aguardando exames e exigência nos complementares.
 */
final class SstAsoStatusAguardando extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_sst_asos')) {
            $aso = $this->table('adms_sst_asos');
            if (!$aso->hasColumn('status')) {
                $aso->addColumn('status', 'enum', [
                    'values' => ['Aguardando exames', 'Concluído'],
                    'default' => 'Concluído',
                    'null' => false,
                    'comment' => 'Aguardando exames = solicitação aberta sem resultados',
                ])
                    ->addIndex(['status'])
                    ->addIndex(['adms_user_id', 'tipo', 'status'], ['name' => 'idx_sst_aso_user_tipo_status'])
                    ->update();
            }

            $this->execute("UPDATE adms_sst_asos SET status = 'Concluído' WHERE status IS NULL OR status = ''");

            if ($aso->hasColumn('data_realizacao')) {
                $this->table('adms_sst_asos')
                    ->changeColumn('data_realizacao', 'date', ['null' => true])
                    ->update();
            }
            if ($aso->hasColumn('resultado')) {
                $this->table('adms_sst_asos')
                    ->changeColumn('resultado', 'enum', [
                        'values' => ['Apto', 'Inapto', 'Apto com restrição'],
                        'null' => true,
                        'default' => null,
                    ])
                    ->update();
            }
        }

        if ($this->hasTable('adms_sst_aso_exames')) {
            $ae = $this->table('adms_sst_aso_exames');
            if (!$ae->hasColumn('exigencia')) {
                $ae->addColumn('exigencia', 'enum', [
                    'values' => ['obrigatorio', 'recomendado', 'adicional'],
                    'null' => true,
                    'comment' => 'Origem do exame no pacote do ASO',
                ])->update();
            }
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_sst_aso_exames') && $this->table('adms_sst_aso_exames')->hasColumn('exigencia')) {
            $this->table('adms_sst_aso_exames')->removeColumn('exigencia')->update();
        }

        if ($this->hasTable('adms_sst_asos')) {
            $aso = $this->table('adms_sst_asos');
            if ($aso->hasColumn('status')) {
                $this->execute("DELETE FROM adms_sst_asos WHERE status = 'Aguardando exames'");
                $aso->removeColumn('status')->update();
            }
            if ($aso->hasColumn('data_realizacao')) {
                $this->table('adms_sst_asos')
                    ->changeColumn('data_realizacao', 'date', ['null' => false])
                    ->update();
            }
            if ($aso->hasColumn('resultado')) {
                $this->table('adms_sst_asos')
                    ->changeColumn('resultado', 'enum', [
                        'values' => ['Apto', 'Inapto', 'Apto com restrição'],
                        'default' => 'Apto',
                        'null' => false,
                    ])
                    ->update();
            }
        }
    }
}
