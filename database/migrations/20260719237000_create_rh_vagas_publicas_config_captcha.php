<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * CAPTCHA do portal público de vagas — ativável e configurável (independente do canal de denúncias).
 */
final class CreateRhVagasPublicasConfigCaptcha extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('rh_vagas_publicas_config')) {
            $this->table('rh_vagas_publicas_config', ['id' => 'id', 'primary_key' => ['id']])
                ->addColumn('captcha_enabled', 'boolean', [
                    'default' => 0,
                    'null' => false,
                    'comment' => '1 = exigir CAPTCHA na candidatura pública',
                ])
                ->addColumn('captcha_provider', 'string', [
                    'limit' => 20,
                    'default' => 'hcaptcha',
                    'null' => false,
                ])
                ->addColumn('captcha_site_key', 'string', [
                    'limit' => 255,
                    'null' => true,
                ])
                ->addColumn('captcha_secret_key', 'string', [
                    'limit' => 255,
                    'null' => true,
                ])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', [
                    'default' => 'CURRENT_TIMESTAMP',
                    'update' => 'CURRENT_TIMESTAMP',
                ])
                ->create();

            $now = date('Y-m-d H:i:s');
            $this->table('rh_vagas_publicas_config')->insert([
                'captcha_enabled' => 0,
                'captcha_provider' => 'hcaptcha',
                'captcha_site_key' => null,
                'captcha_secret_key' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ])->save();
        }

        $this->registerConfigPage();
    }

    public function down(): void
    {
        if ($this->hasTable('adms_pages')) {
            $row = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = 'RhVagasPublicasConfig' LIMIT 1"
            );
            if ($row) {
                $pid = (int) $row['id'];
                if ($this->hasTable('adms_access_levels_pages')) {
                    $this->execute('DELETE FROM adms_access_levels_pages WHERE adms_page_id = ' . $pid);
                }
                $this->execute('DELETE FROM adms_pages WHERE id = ' . $pid);
            }
        }

        if ($this->hasTable('rh_vagas_publicas_config')) {
            $this->table('rh_vagas_publicas_config')->drop()->save();
        }
    }

    private function registerConfigPage(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $conn = $this->getAdapter()->getConnection();
        $controller = 'RhVagasPublicasConfig';
        $existing = $this->fetchRow(
            'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($controller) . ' LIMIT 1'
        );
        if ($existing) {
            $this->grantPageToRhVagasLevels((int) $existing['id']);
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
            . $conn->quote('Configuração Portal de Vagas') . ', '
            . $conn->quote($controller) . ', '
            . $conn->quote('rh-vagas-publicas-config') . ', '
            . $conn->quote('rh') . ', '
            . $conn->quote('CAPTCHA e políticas do portal público vagas-abertas.') . ', '
            . '0, 0, 1, 1, '
            . $groupId . ', '
            . $conn->quote($now) . ', '
            . $conn->quote($now)
            . ')'
        );

        $page = $this->fetchRow(
            'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($controller) . ' LIMIT 1'
        );
        if ($page) {
            $this->grantPageToRhVagasLevels((int) $page['id']);
        }
    }

    private function grantPageToRhVagasLevels(int $pageId): void
    {
        if (!$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $ref = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'RhVagas' LIMIT 1"
        );
        if (!$ref) {
            return;
        }

        $refId = (int) $ref['id'];
        $now = date('Y-m-d H:i:s');
        $this->execute(
            "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT 1, alp.adms_access_level_id, {$pageId}, '{$now}', '{$now}'
             FROM adms_access_levels_pages alp
             WHERE alp.adms_page_id = {$refId} AND alp.permission = 1
             AND NOT EXISTS (
                 SELECT 1 FROM adms_access_levels_pages x
                 WHERE x.adms_access_level_id = alp.adms_access_level_id AND x.adms_page_id = {$pageId}
             )"
        );
    }
}
