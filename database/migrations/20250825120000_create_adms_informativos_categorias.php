<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsInformativosCategorias extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('adms_informativos_categorias', [
            'id' => false,
            'primary_key' => ['id']
        ]);

        $table->addColumn('id', 'integer', [
                'identity' => true,
                'signed' => false
            ])
            ->addColumn('name', 'string', [
                'limit' => 120,
                'null' => false,
                'comment' => 'Nome da categoria de informativo'
            ])
            ->addColumn('ativo', 'boolean', [
                'default' => true,
                'comment' => 'Se a categoria está ativa'
            ])
            ->addColumn('created_at', 'datetime', [
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Data de criação'
            ])
            ->addColumn('updated_at', 'datetime', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'comment' => 'Data de atualização'
            ])
            ->addIndex(['name'], ['unique' => true])
            ->addIndex(['ativo'])
            ->create();
    }

    public function down(): void
    {
        $this->table('adms_informativos_categorias')->drop()->save();
    }
}


