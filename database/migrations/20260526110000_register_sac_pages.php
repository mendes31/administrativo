<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Registra as páginas do módulo SAC (SmartSAC) em adms_pages e cria o grupo SAC.
 */
final class RegisterSacPages extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_groups_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $existsGroup = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'SAC' LIMIT 1");
        if (!$existsGroup) {
            $this->execute(
                "INSERT INTO adms_groups_pages (name, obs, created_at)
                 VALUES ('SAC', 'Módulo de Atendimento ao Cliente (SmartSAC)', '{$now}')"
            );
        }

        $groupRow = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'SAC' LIMIT 1");
        if (!$groupRow) {
            return;
        }
        $gid = (int) $groupRow['id'];

        $pages = [
            ['name' => 'Dashboard SAC', 'controller' => 'SacDashboard', 'controller_url' => 'sac-dashboard', 'directory' => 'sac'],
            ['name' => 'Listar Categorias SAC', 'controller' => 'SacListCategories', 'controller_url' => 'sac-list-categories', 'directory' => 'sac'],
            ['name' => 'Cadastrar Categoria SAC', 'controller' => 'SacCreateCategory', 'controller_url' => 'sac-create-category', 'directory' => 'sac'],
            ['name' => 'Editar Categoria SAC', 'controller' => 'SacUpdateCategory', 'controller_url' => 'sac-update-category', 'directory' => 'sac'],
            ['name' => 'Excluir Categoria SAC', 'controller' => 'SacDeleteCategory', 'controller_url' => 'sac-delete-category', 'directory' => 'sac'],
            ['name' => 'Listar SLAs SAC', 'controller' => 'SacListSlaRules', 'controller_url' => 'sac-list-sla-rules', 'directory' => 'sac'],
            ['name' => 'Cadastrar SLA SAC', 'controller' => 'SacCreateSlaRule', 'controller_url' => 'sac-create-sla-rule', 'directory' => 'sac'],
            ['name' => 'Editar SLA SAC', 'controller' => 'SacUpdateSlaRule', 'controller_url' => 'sac-update-sla-rule', 'directory' => 'sac'],
            ['name' => 'Excluir SLA SAC', 'controller' => 'SacDeleteSlaRule', 'controller_url' => 'sac-delete-sla-rule', 'directory' => 'sac'],
            ['name' => 'Listar Clientes SAC', 'controller' => 'SacListClients', 'controller_url' => 'sac-list-clients', 'directory' => 'sac'],
            ['name' => 'Cadastrar Cliente SAC', 'controller' => 'SacCreateClient', 'controller_url' => 'sac-create-client', 'directory' => 'sac'],
            ['name' => 'Visualizar Cliente SAC', 'controller' => 'SacViewClient', 'controller_url' => 'sac-view-client', 'directory' => 'sac'],
            ['name' => 'Editar Cliente SAC', 'controller' => 'SacUpdateClient', 'controller_url' => 'sac-update-client', 'directory' => 'sac'],
            ['name' => 'Excluir Cliente SAC', 'controller' => 'SacDeleteClient', 'controller_url' => 'sac-delete-client', 'directory' => 'sac'],
            ['name' => 'Listar Chamados SAC', 'controller' => 'SacListTickets', 'controller_url' => 'sac-list-tickets', 'directory' => 'sac'],
            ['name' => 'Abrir Chamado SAC', 'controller' => 'SacCreateTicket', 'controller_url' => 'sac-create-ticket', 'directory' => 'sac'],
            ['name' => 'Visualizar Chamado SAC', 'controller' => 'SacViewTicket', 'controller_url' => 'sac-view-ticket', 'directory' => 'sac'],
            ['name' => 'Editar Chamado SAC', 'controller' => 'SacUpdateTicket', 'controller_url' => 'sac-update-ticket', 'directory' => 'sac'],
            ['name' => 'Excluir Chamado SAC', 'controller' => 'SacDeleteTicket', 'controller_url' => 'sac-delete-ticket', 'directory' => 'sac'],
            ['name' => 'Responder Chamado SAC', 'controller' => 'SacReplyTicket', 'controller_url' => 'sac-reply-ticket', 'directory' => 'sac'],
            ['name' => 'Transferir Chamado SAC', 'controller' => 'SacTransferTicket', 'controller_url' => 'sac-transfer-ticket', 'directory' => 'sac'],
        ];

        foreach ($pages as $p) {
            $exists = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = '{$p['controller']}' LIMIT 1");
            if ($exists) {
                continue;
            }

            $this->table('adms_pages')->insert([
                'name' => $p['name'],
                'controller' => $p['controller'],
                'controller_url' => $p['controller_url'],
                'directory' => $p['directory'],
                'obs' => 'Módulo SAC (SmartSAC).',
                'public_page' => 0,
                'default_page' => 0,
                'page_status' => 1,
                'adms_packages_page_id' => 1,
                'adms_groups_page_id' => $gid,
                'created_at' => $now,
                'updated_at' => $now,
            ])->save();
        }

        // Garantir que todas as páginas SAC nascem sem permissão
        if ($this->hasTable('adms_access_levels_pages')) {
            $sacPages = $this->fetchAll(
                "SELECT id FROM adms_pages WHERE adms_groups_page_id = {$gid} AND public_page = 0"
            );
            foreach ($sacPages as $sp) {
                $pid = (int) $sp['id'];
                $this->execute(
                    "UPDATE adms_access_levels_pages
                     SET permission = 0, updated_at = '{$now}'
                     WHERE adms_page_id = {$pid}
                       AND NOT EXISTS (
                            SELECT 1
                            FROM (SELECT adms_page_id FROM adms_access_levels_pages WHERE permission = 1) keep
                            WHERE keep.adms_page_id = {$pid}
                       )"
                );
            }
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $groupRow = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'SAC' LIMIT 1");
        if (!$groupRow) {
            return;
        }
        $gid = (int) $groupRow['id'];

        $sacPages = $this->fetchAll("SELECT id FROM adms_pages WHERE adms_groups_page_id = {$gid}");
        foreach ($sacPages as $sp) {
            $pid = (int) $sp['id'];
            if ($this->hasTable('adms_access_levels_pages')) {
                $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
            }
            $this->execute("DELETE FROM adms_pages WHERE id = {$pid}");
        }

        $this->execute("DELETE FROM adms_groups_pages WHERE id = {$gid}");
    }
}
