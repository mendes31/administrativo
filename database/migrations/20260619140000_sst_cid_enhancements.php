<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class SstCidEnhancements extends AbstractMigration
{
    public function change(): void
    {
        if ($this->hasTable('adms_sst_cids')) {
            $table = $this->table('adms_sst_cids');
            if (!$table->hasColumn('capitulo_num')) {
                $table->addColumn('capitulo_num', 'integer', ['null' => true, 'signed' => false, 'after' => 'descricao'])
                    ->addColumn('capitulo_nome', 'string', ['limit' => 255, 'null' => true, 'after' => 'capitulo_num'])
                    ->addColumn('categoria', 'string', ['limit' => 10, 'null' => true, 'after' => 'capitulo_nome'])
                    ->addColumn('frequente', 'boolean', ['default' => false, 'after' => 'categoria'])
                    ->addIndex(['capitulo_num'])
                    ->addIndex(['frequente'])
                    ->addIndex(['categoria'])
                    ->update();
            }
        }

        if ($this->hasTable('adms_sst_afastamentos')) {
            $table = $this->table('adms_sst_afastamentos');
            if (!$table->hasColumn('adms_sst_cid_secundario_id')) {
                $table->addColumn('adms_sst_cid_secundario_id', 'integer', [
                    'null' => true,
                    'signed' => false,
                    'after' => 'adms_sst_cid_id',
                ])
                    ->addColumn('natureza', 'enum', [
                        'values' => [
                            'Doença comum',
                            'Doença ocupacional',
                            'Acidente de trabalho',
                            'Acidente de trajeto',
                        ],
                        'null' => true,
                        'after' => 'tipo',
                    ])
                    ->addIndex(['adms_sst_cid_secundario_id'])
                    ->addIndex(['natureza'])
                    ->addForeignKey('adms_sst_cid_secundario_id', 'adms_sst_cids', 'id', [
                        'delete' => 'SET_NULL',
                        'update' => 'CASCADE',
                    ])
                    ->update();
            }
        }

        if ($this->hasTable('adms_sst_acidentes')) {
            $table = $this->table('adms_sst_acidentes');
            if (!$table->hasColumn('natureza')) {
                $table->addColumn('natureza', 'enum', [
                    'values' => [
                        'Doença comum',
                        'Doença ocupacional',
                        'Acidente de trabalho',
                        'Acidente de trajeto',
                    ],
                    'null' => true,
                    'after' => 'tipo',
                ])
                    ->addColumn('parte_corpo', 'string', ['limit' => 120, 'null' => true, 'after' => 'local'])
                    ->addIndex(['natureza'])
                    ->update();
            }
        }
    }
}
