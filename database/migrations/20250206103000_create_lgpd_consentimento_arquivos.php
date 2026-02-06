<?php

use Phinx\Migration\AbstractMigration;

class CreateLgpdConsentimentoArquivos extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('lgpd_consentimento_arquivos')) {
            $this->table('lgpd_consentimento_arquivos', ['id' => 'id'])
                ->addColumn('consentimento_id', 'integer', [
                    'null' => false,
                    'comment' => 'ID do consentimento (lgpd_consentimentos.id)',
                ])
                ->addColumn('nome_original', 'string', [
                    'limit' => 255,
                    'null' => false,
                    'comment' => 'Nome original do arquivo enviado',
                ])
                ->addColumn('arquivo_path', 'string', [
                    'limit' => 255,
                    'null' => false,
                    'comment' => 'Caminho relativo do arquivo no storage',
                ])
                ->addColumn('mime_type', 'string', [
                    'limit' => 100,
                    'null' => true,
                    'comment' => 'Content-Type do arquivo',
                ])
                ->addColumn('tamanho_bytes', 'biginteger', [
                    'null' => true,
                    'comment' => 'Tamanho do arquivo em bytes',
                ])
                ->addColumn('created_at', 'datetime', [
                    'default' => 'CURRENT_TIMESTAMP',
                ])
                ->addIndex(['consentimento_id'], ['name' => 'idx_consentimento_arquivos_cons'])
                ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('lgpd_consentimento_arquivos')) {
            $this->table('lgpd_consentimento_arquivos')->drop()->save();
        }
    }
}


