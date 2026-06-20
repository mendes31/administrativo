<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Configurações globais de vistorias + overrides por equipamento.
 */
final class AddSstEquipamentoVistoriaSettings extends AbstractMigration
{
    public function change(): void
    {
        if (!$this->hasTable('adms_sst_equipamento_settings')) {
            $this->table('adms_sst_equipamento_settings')
                ->addColumn('dia_geracao_vistorias', 'integer', [
                    'default' => 1,
                    'signed' => false,
                    'comment' => 'Dia do mês em que o cron abre novas vistorias (1-28)',
                ])
                ->addColumn('dia_previsto_padrao', 'integer', [
                    'default' => 1,
                    'signed' => false,
                    'comment' => 'Dia previsto padrão para execução da vistoria (1-28)',
                ])
                ->addColumn('periodicidade_meses_padrao', 'integer', [
                    'default' => 1,
                    'signed' => false,
                    'comment' => 'Periodicidade padrão ao cadastrar equipamento',
                ])
                ->addColumn('gerar_vistoria_na_criacao', 'boolean', [
                    'default' => true,
                    'comment' => 'Gera 1ª vistoria ao cadastrar equipamento ativo',
                ])
                ->addColumn('dias_tolerancia_vencimento', 'integer', [
                    'default' => 0,
                    'signed' => false,
                    'comment' => 'Dias após data prevista antes de marcar Vencida',
                ])
                ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->create();

            $this->table('adms_sst_equipamento_settings')->insert([
                'dia_geracao_vistorias' => 1,
                'dia_previsto_padrao' => 1,
                'periodicidade_meses_padrao' => 1,
                'gerar_vistoria_na_criacao' => 1,
                'dias_tolerancia_vencimento' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ])->save();
        }

        if ($this->hasTable('adms_sst_equipamentos')) {
            $table = $this->table('adms_sst_equipamentos');
            if (!$table->hasColumn('dia_previsto_vistoria')) {
                $table->addColumn('dia_previsto_vistoria', 'integer', [
                    'null' => true,
                    'signed' => false,
                    'after' => 'data_referencia_inspecao',
                    'comment' => 'Dia previsto (1-28). NULL = padrão do módulo',
                ])->update();
            }
            if (!$table->hasColumn('vistoria_automatica')) {
                $table->addColumn('vistoria_automatica', 'boolean', [
                    'default' => true,
                    'after' => 'dia_previsto_vistoria',
                    'comment' => 'Gera vistorias automaticamente para este equipamento',
                ])->update();
            }
        }
    }
}
