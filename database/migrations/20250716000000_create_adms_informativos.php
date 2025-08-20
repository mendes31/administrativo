<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsInformativos extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('adms_informativos', [
            'id' => false,
            'primary_key' => ['id']
        ]);
        
        $table->addColumn('id', 'integer', [
            'identity' => true,
            'signed' => false
        ])
        ->addColumn('titulo', 'string', [
            'limit' => 255,
            'null' => false,
            'comment' => 'Título do informativo'
        ])
        ->addColumn('conteudo', 'text', [
            'null' => false,
            'comment' => 'Conteúdo completo do informativo'
        ])
        ->addColumn('resumo', 'text', [
            'null' => true,
            'comment' => 'Resumo do informativo (primeiras 150 letras)'
        ])
        ->addColumn('categoria', 'string', [
            'limit' => 100,
            'null' => false,
            'default' => 'Geral',
            'comment' => 'Categoria do informativo (RH, Financeiro, TI, Geral, etc.)'
        ])
        ->addColumn('imagem', 'string', [
            'limit' => 255,
            'null' => true,
            'comment' => 'Caminho da imagem do informativo'
        ])
        ->addColumn('anexo', 'string', [
            'limit' => 255,
            'null' => true,
            'comment' => 'Caminho do anexo do informativo'
        ])
        ->addColumn('urgente', 'boolean', [
            'default' => false,
            'comment' => 'Se o informativo é urgente'
        ])
        ->addColumn('ativo', 'boolean', [
            'default' => true,
            'comment' => 'Status do informativo (ativo/inativo)'
        ])
        ->addColumn('usuario_id', 'integer', [
            'signed' => false,
            'null' => false,
            'comment' => 'ID do usuário que criou o informativo'
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
        ->addIndex(['categoria'])
        ->addIndex(['ativo'])
        ->addIndex(['urgente'])
        ->addIndex(['created_at'])
        ->addIndex(['usuario_id'])
        ->addForeignKey('usuario_id', 'adms_users', 'id', [
            'delete' => 'CASCADE',
            'update' => 'CASCADE'
        ])
        ->create();
    }

    public function down(): void
    {
        $this->table('adms_informativos')->drop()->save();
    }
} 