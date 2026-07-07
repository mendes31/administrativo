<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Tabelas do Canal de Denúncias (whistleblowing).
 * Conteúdo sensível armazenado criptografado; sem IP nem vínculo com usuário do portal.
 */
final class CreateWhistleblowingTables extends AbstractMigration
{
    public function change(): void
    {
        if (!$this->hasTable('adms_whistleblowing_reports')) {
            $reports = $this->table('adms_whistleblowing_reports');

            $reports
                ->addColumn('uuid', 'string', ['limit' => 36, 'null' => false])
                ->addColumn('protocol', 'string', ['limit' => 32, 'null' => false])
                ->addColumn('password_hash', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('category', 'string', ['limit' => 80, 'null' => false])
                ->addColumn('risk_level', 'enum', [
                    'values' => ['Baixo', 'Médio', 'Alto', 'Crítico'],
                    'default' => 'Médio',
                ])
                ->addColumn('content_encrypted', 'text', ['null' => false, 'comment' => 'JSON criptografado: relato e envolvidos'])
                ->addColumn('status', 'enum', [
                    'values' => [
                        'Recebida',
                        'Em triagem',
                        'Em análise',
                        'Comitê',
                        'Investigação',
                        'Providências',
                        'Encerrada',
                    ],
                    'default' => 'Recebida',
                ])
                ->addColumn('assigned_user_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('first_response_at', 'datetime', ['null' => true])
                ->addColumn('closed_at', 'datetime', ['null' => true])
                ->addColumn('retention_archive_at', 'datetime', ['null' => true, 'comment' => 'LGPD: arquivar após 5 anos'])
                ->addColumn('retention_delete_at', 'datetime', ['null' => true, 'comment' => 'LGPD: excluir após 10 anos'])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['uuid'], ['unique' => true])
                ->addIndex(['protocol'], ['unique' => true])
                ->addIndex(['status'])
                ->addIndex(['category'])
                ->addIndex(['risk_level'])
                ->addIndex(['assigned_user_id'])
                ->addIndex(['created_at'])
                ->addForeignKey('assigned_user_id', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }

        if (!$this->hasTable('adms_whistleblowing_messages')) {
            $messages = $this->table('adms_whistleblowing_messages');

            $messages
                ->addColumn('report_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('sender_type', 'enum', ['values' => ['denunciante', 'comite'], 'default' => 'denunciante'])
                ->addColumn('message_encrypted', 'text', ['null' => false])
                ->addColumn('is_internal_note', 'boolean', ['default' => 0])
                ->addColumn('user_id', 'integer', ['null' => true, 'signed' => false, 'comment' => 'Somente comitê; nunca denunciante'])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['report_id'])
                ->addIndex(['sender_type'])
                ->addIndex(['created_at'])
                ->addForeignKey('report_id', 'adms_whistleblowing_reports', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('user_id', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }

        if (!$this->hasTable('adms_whistleblowing_attachments')) {
            $attachments = $this->table('adms_whistleblowing_attachments');

            $attachments
                ->addColumn('report_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('message_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('stored_name', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('original_name_encrypted', 'string', ['limit' => 512, 'null' => false])
                ->addColumn('mime_type', 'string', ['limit' => 120, 'null' => true])
                ->addColumn('size_bytes', 'integer', ['signed' => false, 'default' => 0])
                ->addColumn('uploaded_by', 'enum', ['values' => ['denunciante', 'comite'], 'default' => 'denunciante'])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['report_id'])
                ->addIndex(['message_id'])
                ->addForeignKey('report_id', 'adms_whistleblowing_reports', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('message_id', 'adms_whistleblowing_messages', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }

        if (!$this->hasTable('adms_whistleblowing_status_log')) {
            $statusLog = $this->table('adms_whistleblowing_status_log');

            $statusLog
                ->addColumn('report_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('from_status', 'string', ['limit' => 40, 'null' => true])
                ->addColumn('to_status', 'string', ['limit' => 40, 'null' => false])
                ->addColumn('user_id', 'integer', ['null' => true, 'signed' => false])
                ->addColumn('notes_encrypted', 'text', ['null' => true])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['report_id'])
                ->addIndex(['created_at'])
                ->addForeignKey('report_id', 'adms_whistleblowing_reports', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('user_id', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                ->create();
        }

        if (!$this->hasTable('adms_whistleblowing_access_log')) {
            $accessLog = $this->table('adms_whistleblowing_access_log');

            $accessLog
                ->addColumn('report_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('action', 'string', ['limit' => 60, 'null' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['report_id'])
                ->addIndex(['user_id'])
                ->addIndex(['created_at'])
                ->addForeignKey('report_id', 'adms_whistleblowing_reports', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('user_id', 'adms_users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->create();
        }
    }
}
