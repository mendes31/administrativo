<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Documentos antigos ficaram com signature_status not_required e requires_signature_snapshot 0 após a migração
 * de ciência; o botão Assinar não aparecia mesmo com o tipo a exigir ciência. Alinha com adms_payroll_document_types ativo.
 */
final class PayrollAlignSignatureWithDocumentTypes extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_employee_payroll_documents') || !$this->hasTable('adms_payroll_document_types')) {
            return;
        }

        $this->execute(
            'UPDATE adms_employee_payroll_documents d
            INNER JOIN adms_payroll_document_types t ON t.code = d.document_type AND t.is_active = 1
            SET
              d.signature_status = CASE WHEN d.signature_status = \'signed\' THEN d.signature_status ELSE \'pending\' END,
              d.requires_signature_snapshot = CASE WHEN d.signature_status = \'signed\' THEN d.requires_signature_snapshot ELSE 1 END,
              d.signature_auth_snapshot = CASE
                WHEN d.signature_status = \'signed\' THEN d.signature_auth_snapshot
                WHEN LOWER(TRIM(COALESCE(NULLIF(t.signature_auth, \'\'), \'none\'))) IN (\'none\', \'password\', \'otp_whatsapp\', \'otp_email\', \'otp_whatsapp_fallback_email\')
                  THEN LOWER(TRIM(COALESCE(NULLIF(t.signature_auth, \'\'), \'none\')))
                ELSE \'none\'
              END
            WHERE d.status_version = \'active\'
              AND t.requires_signature = 1
              AND d.signature_status <> \'signed\'
              AND (d.signature_status = \'not_required\' OR COALESCE(d.requires_signature_snapshot, 0) = 0)'
        );
    }

    public function down(): void
    {
    }
}
