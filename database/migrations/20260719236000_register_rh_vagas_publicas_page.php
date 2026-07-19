<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Portal público somente leitura de vagas publicadas (Expand Fase 3).
 */
final class RegisterRhVagasPublicasPage extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $conn = $this->getAdapter()->getConnection();
        $controller = 'RhVagasPublicas';
        $existing = $this->fetchRow(
            'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($controller) . ' LIMIT 1'
        );
        if ($existing) {
            $this->execute(
                'UPDATE adms_pages SET public_page = 1, page_status = 1, updated_at = '
                . $conn->quote(date('Y-m-d H:i:s'))
                . ' WHERE id = ' . (int) $existing['id']
            );
            return;
        }

        $groupId = 30;
        $group = $this->fetchRow(
            "SELECT adms_groups_page_id FROM adms_pages WHERE controller = 'RhVagas' LIMIT 1"
        );
        if ($group && !empty($group['adms_groups_page_id'])) {
            $groupId = (int) $group['adms_groups_page_id'];
        }

        $now = date('Y-m-d H:i:s');
        $this->execute(
            'INSERT INTO adms_pages
                (name, controller, controller_url, directory, obs, public_page, default_page, page_status,
                 adms_packages_page_id, adms_groups_page_id, created_at, updated_at)
             VALUES ('
            . $conn->quote('Vagas Abertas (portal público)') . ', '
            . $conn->quote($controller) . ', '
            . $conn->quote('vagas-abertas') . ', '
            . $conn->quote('rh') . ', '
            . $conn->quote('Listagem e detalhe públicos somente leitura. Sem candidatura neste Expand.') . ', '
            . '1, 0, 1, 1, '
            . $groupId . ', '
            . $conn->quote($now) . ', '
            . $conn->quote($now)
            . ')'
        );
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $conn = $this->getAdapter()->getConnection();
        $page = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'RhVagasPublicas' LIMIT 1"
        );
        if (!$page) {
            return;
        }

        $pageId = (int) $page['id'];
        if ($this->hasTable('adms_access_levels_pages')) {
            $this->execute('DELETE FROM adms_access_levels_pages WHERE adms_page_id = ' . $pageId);
        }
        $this->execute('DELETE FROM adms_pages WHERE id = ' . $pageId);
    }
}
