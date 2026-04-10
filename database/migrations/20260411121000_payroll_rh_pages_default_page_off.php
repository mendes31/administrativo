<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Páginas de importação de PDF RH, tipos de documento e documentos do colaborador
 * não devem ser "padrão" (não entram automaticamente em novos pacotes de permissões).
 */
final class PayrollRhPagesDefaultPageOff extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $controllers = [
            'ImportPayrollDocuments',
            'MyPayrollDocuments',
            'ViewPayrollDocument',
            'ListPayrollDocumentTypes',
            'CreatePayrollDocumentType',
            'UpdatePayrollDocumentType',
            'DeletePayrollDocumentType',
        ];

        $in = implode("','", array_map(addslashes(...), $controllers));

        $this->execute(
            "UPDATE adms_pages SET default_page = 0, updated_at = NOW()
             WHERE controller IN ('{$in}') AND default_page <> 0"
        );
    }

    public function down(): void
    {
        // Sem reversão: não sabemos quais tinham sido marcadas como padrão intencionalmente antes.
    }
}
