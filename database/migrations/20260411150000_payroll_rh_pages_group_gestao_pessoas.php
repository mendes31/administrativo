<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Páginas de importação PDF RH e CRUD de tipos: grupo "Gestão de Pessoas" e nomes (RH).
 */
final class PayrollRhPagesGroupGestaoPessoas extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $g = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'Gestão de Pessoas' LIMIT 1");
        $gid = (int)($g['id'] ?? 0);
        if ($gid <= 0) {
            return;
        }

        $map = [
            'ImportPayrollDocuments' => 'Importar documentos de RH (PDF)',
            'ListPayrollDocumentTypes' => 'Tipos de documento (RH)',
            'CreatePayrollDocumentType' => 'Criar tipo de documento (RH)',
            'UpdatePayrollDocumentType' => 'Editar tipo de documento (RH)',
            'DeletePayrollDocumentType' => 'Apagar tipo de documento (RH)',
        ];

        foreach ($map as $controller => $name) {
            $c = $this->quoteIdent($controller);
            $n = $this->quoteIdent($name);
            $this->execute(
                "UPDATE adms_pages SET adms_groups_page_id = {$gid}, name = {$n}, updated_at = NOW() WHERE controller = {$c} LIMIT 1"
            );
        }
    }

    public function down(): void
    {
        // Sem reversão automática (grupo/nome dependem da política local).
    }

    private function quoteIdent(string $s): string
    {
        return "'" . str_replace("'", "''", $s) . "'";
    }
}
