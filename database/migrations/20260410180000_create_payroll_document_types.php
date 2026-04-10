<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Tipos de documento RH (cadastro + regras futuras) e seeds iniciais.
 * Páginas CRUD no grupo Gestão de Pessoas; ACL copiada da página ImportPayrollDocuments.
 */
final class CreatePayrollDocumentTypes extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_payroll_document_types')) {
            $this->table('adms_payroll_document_types', ['id' => 'id', 'primary_key' => ['id']])
                ->addColumn('code', 'string', ['limit' => 64, 'null' => false, 'comment' => 'Identificador estável (ex.: payroll), usado em document_type'])
                ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('description', 'text', ['null' => true])
                ->addColumn('default_title_prefix', 'string', ['limit' => 200, 'null' => true, 'comment' => 'Prefixo padrão do título ao colaborador'])
                ->addColumn('icon', 'string', ['limit' => 100, 'null' => true, 'comment' => 'Classe Font Awesome (ex.: fa-file-pdf), sem prefixo fas'])
                ->addColumn('requires_signature', 'boolean', ['default' => false, 'comment' => 'Exigir ciência/assinatura formal (futuro)'])
                ->addColumn('signature_auth', 'string', [
                    'limit' => 40,
                    'default' => 'none',
                    'comment' => 'none|password|otp_whatsapp|otp_email|otp_whatsapp_fallback_email',
                ])
                ->addColumn('require_auth_download', 'boolean', ['default' => false, 'comment' => 'Exigir reautenticação para download (futuro)'])
                ->addColumn('rules_json', 'text', ['null' => true, 'comment' => 'JSON com regras adicionais'])
                ->addColumn('is_active', 'boolean', ['default' => true])
                ->addColumn('sort_order', 'integer', ['default' => 0])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['code'], ['unique' => true])
                ->addIndex(['is_active'])
                ->addIndex(['sort_order'])
                ->create();
        }

        $now = date('Y-m-d H:i:s');
        $seeds = [
            [
                'code' => 'payroll',
                'name' => 'Folha de pagamento',
                'description' => 'Holerite / recibo de pagamento de salário.',
                'default_title_prefix' => 'Folha de pagamento',
                'icon' => 'fa-file-invoice-dollar',
                'sort_order' => 10,
            ],
            [
                'code' => 'vacation_receipt',
                'name' => 'Recibo de férias',
                'description' => 'Recibo de pagamento de férias.',
                'default_title_prefix' => 'Recibo de férias',
                'icon' => 'fa-umbrella-beach',
                'sort_order' => 20,
            ],
            [
                'code' => 'ir_statement',
                'name' => 'Informe de IR',
                'description' => 'Informe de rendimentos para Imposto de Renda.',
                'default_title_prefix' => 'Informe de IR',
                'icon' => 'fa-file-alt',
                'sort_order' => 30,
            ],
            [
                'code' => 'time_bank',
                'name' => 'Banco de horas',
                'description' => 'Extrato ou recibo de banco de horas.',
                'default_title_prefix' => 'Banco de horas',
                'icon' => 'fa-clock',
                'sort_order' => 40,
            ],
            [
                'code' => 'other',
                'name' => 'Outros',
                'description' => 'Outros documentos trabalhistas distribuídos pelo RH.',
                'default_title_prefix' => 'Documento',
                'icon' => 'fa-file',
                'sort_order' => 50,
            ],
        ];

        foreach ($seeds as $row) {
            $exists = $this->fetchRow(
                'SELECT id FROM adms_payroll_document_types WHERE code = ' . $this->quote((string)$row['code']) . ' LIMIT 1'
            );
            if ($exists) {
                continue;
            }
            $this->table('adms_payroll_document_types')->insert([
                'code' => $row['code'],
                'name' => $row['name'],
                'description' => $row['description'],
                'default_title_prefix' => $row['default_title_prefix'],
                'icon' => $row['icon'],
                'requires_signature' => 0,
                'signature_auth' => 'none',
                'require_auth_download' => 0,
                'rules_json' => null,
                'is_active' => 1,
                'sort_order' => $row['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ])->save();
        }

        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $refAcl = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'ImportPayrollDocuments' LIMIT 1");
        if (!$refAcl) {
            return;
        }
        $gestao = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'Gestão de Pessoas' LIMIT 1");
        $gid = (int)($gestao['id'] ?? 0);
        if ($gid <= 0) {
            $refImp = $this->fetchRow("SELECT adms_groups_page_id FROM adms_pages WHERE controller = 'ImportPayrollDocuments' LIMIT 1");
            $gid = (int)($refImp['adms_groups_page_id'] ?? 0);
        }
        if ($gid <= 0) {
            return;
        }

        $pages = [
            [
                'name' => 'Tipos de documento (RH)',
                'controller' => 'ListPayrollDocumentTypes',
                'controller_url' => 'list-payroll-document-types',
                'obs' => 'Cadastro de tipos para importação de documentos RH e regras futuras (OTP, etc.).',
            ],
            [
                'name' => 'Criar tipo de documento (RH)',
                'controller' => 'CreatePayrollDocumentType',
                'controller_url' => 'create-payroll-document-type',
                'obs' => 'Formulário para novo tipo de documento de RH.',
            ],
            [
                'name' => 'Editar tipo de documento (RH)',
                'controller' => 'UpdatePayrollDocumentType',
                'controller_url' => 'update-payroll-document-type',
                'obs' => 'Formulário para editar tipo de documento de RH.',
            ],
            [
                'name' => 'Apagar tipo de documento (RH)',
                'controller' => 'DeletePayrollDocumentType',
                'controller_url' => 'delete-payroll-document-type',
                'obs' => 'Remove tipo de documento se não estiver em uso.',
            ],
        ];

        foreach ($pages as $p) {
            $exists = $this->fetchRow(
                'SELECT id FROM adms_pages WHERE controller = ' . $this->quote((string)$p['controller']) . ' LIMIT 1'
            );
            if ($exists) {
                continue;
            }
            $this->table('adms_pages')->insert([
                'name' => $p['name'],
                'controller' => $p['controller'],
                'controller_url' => $p['controller_url'],
                'directory' => 'portal',
                'obs' => $p['obs'],
                'public_page' => 0,
                'default_page' => 0,
                'page_status' => 1,
                'adms_packages_page_id' => 1,
                'adms_groups_page_id' => $gid,
                'created_at' => $now,
                'updated_at' => $now,
            ])->save();

            $newRow = $this->fetchRow('SELECT LAST_INSERT_ID() AS id');
            $newId = (int)($newRow['id'] ?? 0);
            if ($newId <= 0 || !$this->hasTable('adms_access_levels_pages')) {
                continue;
            }
            $refAclId = (int)$refAcl['id'];
            $this->execute(
                "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                 SELECT permission, adms_access_level_id, {$newId}, '{$now}', '{$now}'
                 FROM adms_access_levels_pages
                 WHERE adms_page_id = {$refAclId}"
            );
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_pages')) {
            foreach (['ListPayrollDocumentTypes', 'CreatePayrollDocumentType', 'UpdatePayrollDocumentType', 'DeletePayrollDocumentType'] as $ctrl) {
                $row = $this->fetchRow('SELECT id FROM adms_pages WHERE controller = ' . $this->quote($ctrl) . ' LIMIT 1');
                if (!$row) {
                    continue;
                }
                $pid = (int)$row['id'];
                if ($this->hasTable('adms_access_levels_pages')) {
                    $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
                }
                $this->execute("DELETE FROM adms_pages WHERE id = {$pid} LIMIT 1");
            }
        }

        if ($this->hasTable('adms_payroll_document_types')) {
            $this->table('adms_payroll_document_types')->drop()->save();
        }
    }

    private function quote(string $s): string
    {
        return "'" . str_replace("'", "''", $s) . "'";
    }
}
