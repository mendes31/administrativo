<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Evolução do modelo SST: categoria ASO nas regras, exames por risco e itens complementares do ASO.
 */
final class SstCategoriaAsoRiscoExameAsoExames extends AbstractMigration
{
    private const CATEGORIAS = [
        'Admissional',
        'Periódico',
        'Mudança de função',
        'Retorno ao trabalho',
        'Demissional',
    ];

    public function up(): void
    {
        $categoriaEnum = $this->table('adms_sst_exame_necessidade');
        if ($this->hasTable('adms_sst_exame_necessidade') && !$categoriaEnum->hasColumn('categoria_aso')) {
            $this->table('adms_sst_exame_necessidade')
                ->addColumn('categoria_aso', 'enum', [
                    'values' => self::CATEGORIAS,
                    'null' => true,
                    'comment' => 'Null = aplica a todas as categorias de ASO',
                ])
                ->addIndex(['categoria_aso'])
                ->update();
        }

        if (!$this->hasTable('adms_sst_risco_exame')) {
            $this->table('adms_sst_risco_exame')
                ->addColumn('adms_sst_risco_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('adms_sst_exame_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('categoria_aso', 'enum', [
                    'values' => self::CATEGORIAS,
                    'null' => true,
                    'comment' => 'Null = todas as categorias',
                ])
                ->addColumn('periodicidade_meses', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('obrigatorio', 'boolean', ['default' => true])
                ->addColumn('observacoes', 'text', ['null' => true])
                ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['adms_sst_risco_id'])
                ->addIndex(['adms_sst_exame_id'])
                ->addIndex(['categoria_aso'])
                ->addIndex(['adms_sst_risco_id', 'adms_sst_exame_id', 'categoria_aso'], ['unique' => true, 'name' => 'uq_sst_risco_exame_categoria'])
                ->addForeignKey('adms_sst_risco_id', 'adms_sst_riscos', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('adms_sst_exame_id', 'adms_sst_exames', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }

        if (!$this->hasTable('adms_sst_aso_exames')) {
            $this->table('adms_sst_aso_exames')
                ->addColumn('adms_sst_aso_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('adms_sst_exame_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('data_realizacao', 'date', ['null' => true])
                ->addColumn('resultado', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('observacoes', 'text', ['null' => true])
                ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['adms_sst_aso_id'])
                ->addIndex(['adms_sst_exame_id'])
                ->addIndex(['adms_sst_aso_id', 'adms_sst_exame_id'], ['unique' => true, 'name' => 'uq_sst_aso_exame'])
                ->addForeignKey('adms_sst_aso_id', 'adms_sst_asos', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('adms_sst_exame_id', 'adms_sst_exames', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_sst_aso_exames')) {
            $this->table('adms_sst_aso_exames')->drop()->save();
        }
        if ($this->hasTable('adms_sst_risco_exame')) {
            $this->table('adms_sst_risco_exame')->drop()->save();
        }
        if ($this->hasTable('adms_sst_exame_necessidade') && $this->table('adms_sst_exame_necessidade')->hasColumn('categoria_aso')) {
            $this->table('adms_sst_exame_necessidade')->removeColumn('categoria_aso')->update();
        }
    }
}
