<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Versão + hash por documento; assinatura/ciência; OTP; eventos; lembretes D+X.
 */
final class PayrollVersioningSignatureOtpEvents extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_employee_payroll_documents')) {
            return;
        }

        $t = $this->table('adms_employee_payroll_documents');
        if (!$t->hasColumn('document_group_key')) {
            $t->addColumn('document_group_key', 'string', [
                'limit' => 64,
                'null' => true,
                'comment' => 'Chave lógica: titular + tipo + competência',
            ]);
        }
        if (!$t->hasColumn('document_version')) {
            $t->addColumn('document_version', 'integer', ['signed' => false, 'null' => false, 'default' => 1]);
        }
        if (!$t->hasColumn('status_version')) {
            $t->addColumn('status_version', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => 'active',
                'comment' => 'active | superseded | cancelled',
            ]);
        }
        if (!$t->hasColumn('supersedes_document_id')) {
            $t->addColumn('supersedes_document_id', 'integer', ['signed' => false, 'null' => true]);
        }
        if (!$t->hasColumn('file_hash_sha256')) {
            $t->addColumn('file_hash_sha256', 'char', ['limit' => 64, 'null' => true]);
        }
        if (!$t->hasColumn('signature_status')) {
            $t->addColumn('signature_status', 'string', [
                'limit' => 24,
                'null' => false,
                'default' => 'not_required',
                'comment' => 'not_required | pending | signed',
            ]);
        }
        if (!$t->hasColumn('requires_signature_snapshot')) {
            $t->addColumn('requires_signature_snapshot', 'boolean', ['default' => false]);
        }
        if (!$t->hasColumn('signature_auth_snapshot')) {
            $t->addColumn('signature_auth_snapshot', 'string', ['limit' => 40, 'null' => false, 'default' => 'none']);
        }
        if (!$t->hasColumn('signed_at')) {
            $t->addColumn('signed_at', 'datetime', ['null' => true]);
        }
        if (!$t->hasColumn('signed_ip')) {
            $t->addColumn('signed_ip', 'string', ['limit' => 45, 'null' => true]);
        }
        if (!$t->hasColumn('signed_user_agent')) {
            $t->addColumn('signed_user_agent', 'string', ['limit' => 512, 'null' => true]);
        }
        if (!$t->hasColumn('signed_auth_method')) {
            $t->addColumn('signed_auth_method', 'string', ['limit' => 40, 'null' => true]);
        }
        if (!$t->hasColumn('signed_document_hash_sha256')) {
            $t->addColumn('signed_document_hash_sha256', 'char', ['limit' => 64, 'null' => true]);
        }
        if (!$t->hasColumn('published_at')) {
            $t->addColumn('published_at', 'datetime', ['null' => true]);
        }
        if (!$t->hasColumn('reminder_stage')) {
            $t->addColumn('reminder_stage', 'integer', [
                'limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY,
                'signed' => false,
                'null' => false,
                'default' => 0,
                'comment' => '0–3 lembretes D+X',
            ]);
        }
        $t->update();

        $this->execute(
            "UPDATE adms_employee_payroll_documents SET
                document_group_key = LOWER(SHA2(CONCAT_WS('|', user_id, document_type, reference_year, IFNULL(reference_month, '')), 256)),
                document_version = 1,
                status_version = 'active',
                published_at = COALESCE(published_at, created_at)
             WHERE document_group_key IS NULL OR document_group_key = ''"
        );

        $this->execute('ALTER TABLE adms_employee_payroll_documents MODIFY document_group_key VARCHAR(64) NOT NULL');

        try {
            $this->execute(
                'ALTER TABLE adms_employee_payroll_documents
                 ADD CONSTRAINT fk_payroll_doc_supersedes FOREIGN KEY (supersedes_document_id)
                 REFERENCES adms_employee_payroll_documents (id) ON DELETE SET NULL ON UPDATE CASCADE'
            );
        } catch (\Throwable) {
        }

        foreach (
            [
                'idx_payroll_docs_user_status' => 'CREATE INDEX idx_payroll_docs_user_status ON adms_employee_payroll_documents (user_id, status_version)',
                'idx_payroll_docs_group_version' => 'CREATE INDEX idx_payroll_docs_group_version ON adms_employee_payroll_documents (document_group_key, document_version)',
                'idx_payroll_docs_pending_reminder' => 'CREATE INDEX idx_payroll_docs_pending_reminder ON adms_employee_payroll_documents (signature_status, published_at, reminder_stage)',
            ] as $name => $sql
        ) {
            try {
                $row = $this->fetchRow(
                    "SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE()
                     AND table_name = 'adms_employee_payroll_documents' AND index_name = " . $this->quote($name)
                );
                if (!$row) {
                    $this->execute($sql);
                }
            } catch (\Throwable) {
            }
        }

        if (!$this->hasTable('adms_payroll_document_otp_challenges')) {
            $this->table('adms_payroll_document_otp_challenges', ['id' => true])
                ->addColumn('employee_payroll_document_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('code_hash', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('expires_at', 'datetime', ['null' => false])
                ->addColumn('attempts', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
                ->addColumn('max_attempts', 'integer', ['signed' => false, 'null' => false, 'default' => 5])
                ->addColumn('channel', 'string', ['limit' => 20, 'null' => false, 'default' => 'whatsapp'])
                ->addColumn('consumed_at', 'datetime', ['null' => true])
                ->addColumn('created_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
                ->addForeignKey('employee_payroll_document_id', 'adms_employee_payroll_documents', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'CASCADE',
                ])
                ->addIndex(['employee_payroll_document_id', 'user_id'], ['name' => 'idx_payroll_otp_doc_user'])
                ->addIndex(['user_id', 'created_at'], ['name' => 'idx_payroll_otp_user_time'])
                ->create();
        }

        if (!$this->hasTable('adms_payroll_document_events')) {
            $this->table('adms_payroll_document_events', ['id' => true])
                ->addColumn('employee_payroll_document_id', 'integer', ['signed' => false, 'null' => true])
                ->addColumn('user_id', 'integer', ['signed' => false, 'null' => true])
                ->addColumn('event_type', 'string', ['limit' => 48, 'null' => false])
                ->addColumn('meta_json', 'text', ['null' => true])
                ->addColumn('ip', 'string', ['limit' => 45, 'null' => true])
                ->addColumn('user_agent', 'string', ['limit' => 512, 'null' => true])
                ->addColumn('created_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
                ->addForeignKey('employee_payroll_document_id', 'adms_employee_payroll_documents', 'id', [
                    'delete' => 'SET_NULL',
                    'update' => 'CASCADE',
                ])
                ->addIndex(['employee_payroll_document_id', 'created_at'], ['name' => 'idx_payroll_ev_doc_time'])
                ->addIndex(['event_type', 'created_at'], ['name' => 'idx_payroll_ev_type_time'])
                ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_payroll_document_events')) {
            $this->table('adms_payroll_document_events')->drop()->save();
        }
        if ($this->hasTable('adms_payroll_document_otp_challenges')) {
            $this->table('adms_payroll_document_otp_challenges')->drop()->save();
        }

        if ($this->hasTable('adms_employee_payroll_documents')) {
            try {
                $this->execute('ALTER TABLE adms_employee_payroll_documents DROP FOREIGN KEY fk_payroll_doc_supersedes');
            } catch (\Throwable) {
            }
            foreach (['idx_payroll_docs_user_status', 'idx_payroll_docs_group_version', 'idx_payroll_docs_pending_reminder'] as $idx) {
                try {
                    $this->execute("ALTER TABLE adms_employee_payroll_documents DROP INDEX `{$idx}`");
                } catch (\Throwable) {
                }
            }

            $table = $this->table('adms_employee_payroll_documents');
            foreach (
                [
                    'reminder_stage', 'published_at', 'signed_document_hash_sha256', 'signed_auth_method',
                    'signed_user_agent', 'signed_ip', 'signed_at', 'signature_auth_snapshot',
                    'requires_signature_snapshot', 'signature_status', 'file_hash_sha256',
                    'supersedes_document_id', 'status_version', 'document_version', 'document_group_key',
                ] as $col
            ) {
                if ($table->hasColumn($col)) {
                    $table->removeColumn($col);
                }
            }
            $table->update();
        }
    }
}
