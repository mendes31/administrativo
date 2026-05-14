<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Remove o conceito de vínculo utilizador ↔ CardCode: quem tem permissão no módulo
 * opera para todos os parceiros via SAP. Elimina tabela local, páginas CRUD e ACL.
 */
final class RemoveSalesPortalUserPartnerLinks extends AbstractMigration
{
    public function up(): void
    {
        $controllers = [
            'SalesPortalListUserLinks',
            'SalesPortalCreateUserLink',
            'SalesPortalViewUserLink',
            'SalesPortalUpdateUserLink',
            'SalesPortalDeleteUserLink',
        ];
        foreach ($controllers as $ctrl) {
            $esc = str_replace("'", "''", $ctrl);
            $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = '{$esc}' LIMIT 1");
            if (!$row || empty($row['id'])) {
                continue;
            }
            $pid = (int) $row['id'];
            if ($this->hasTable('adms_access_levels_pages')) {
                $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
            }
            if ($this->hasTable('adms_pages')) {
                $this->execute("DELETE FROM adms_pages WHERE id = {$pid} LIMIT 1");
            }
        }

        if ($this->hasTable('sales_portal_user_links')) {
            $this->table('sales_portal_user_links')->drop()->save();
        }
    }
}
