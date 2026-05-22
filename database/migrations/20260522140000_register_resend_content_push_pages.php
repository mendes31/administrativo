<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Páginas de reenvio manual de push (informativos e políticas).
 * ACL inicial copiada das páginas de edição (padrão do projeto).
 */
final class RegisterResendContentPushPages extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $this->registerPage(
            'ResendInformativoPush',
            'resend-informativo-push',
            'informativos',
            'Reenviar push — Informativo',
            'Reenvia notificações push PWA de um informativo ativo para os colaboradores elegíveis.',
            30,
            'UpdateInformativo',
            $now
        );

        $this->registerPage(
            'ResendPolicyPush',
            'resend-policy-push',
            'policies',
            'Reenviar push — Política',
            'Reenvia notificações push PWA de uma política interna ativa.',
            36,
            'UpdatePolicy',
            $now
        );
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        foreach (['ResendInformativoPush', 'ResendPolicyPush'] as $controller) {
            $row = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = '" . addslashes($controller) . "' LIMIT 1"
            );
            if (!$row) {
                continue;
            }
            $pid = (int) $row['id'];
            if ($this->hasTable('adms_access_levels_pages')) {
                $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
            }
            $this->execute("DELETE FROM adms_pages WHERE id = {$pid} LIMIT 1");
        }
    }

    private function registerPage(
        string $controller,
        string $controllerUrl,
        string $directory,
        string $name,
        string $obs,
        int $groupPageId,
        string $aclRefController,
        string $now
    ): void {
        $exists = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = '" . addslashes($controller) . "' LIMIT 1"
        );
        if ($exists) {
            return;
        }

        $this->table('adms_pages')->insert([
            'name' => $name,
            'controller' => $controller,
            'controller_url' => $controllerUrl,
            'directory' => $directory,
            'obs' => $obs,
            'public_page' => 0,
            'default_page' => 0,
            'page_status' => 1,
            'adms_packages_page_id' => 1,
            'adms_groups_page_id' => $groupPageId,
            'created_at' => $now,
            'updated_at' => $now,
        ])->save();

        $newRow = $this->fetchRow('SELECT LAST_INSERT_ID() AS id');
        $newId = (int) ($newRow['id'] ?? 0);
        if ($newId <= 0 || !$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $ref = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = '" . addslashes($aclRefController) . "' LIMIT 1"
        );
        if (!$ref) {
            return;
        }

        $refId = (int) $ref['id'];
        $this->execute(
            "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT permission, adms_access_level_id, {$newId}, '{$now}', '{$now}'
             FROM adms_access_levels_pages
             WHERE adms_page_id = {$refId}"
        );
    }
}
