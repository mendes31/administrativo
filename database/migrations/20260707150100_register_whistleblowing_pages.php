<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Registra páginas do Canal de Denúncias (público + gestão interna).
 */
final class RegisterWhistleblowingPages extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_groups_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $existsGroup = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'Canal de Denúncias' LIMIT 1");
        if (!$existsGroup) {
            $this->execute(
                "INSERT INTO adms_groups_pages (name, obs, created_at)
                 VALUES ('Canal de Denúncias', 'Canal anônimo de denúncias e gestão interna (Compliance)', '{$now}')"
            );
        }

        $groupRow = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'Canal de Denúncias' LIMIT 1");
        if (!$groupRow) {
            return;
        }
        $gid = (int) $groupRow['id'];

        $pages = [
            [
                'name' => 'Canal de Denúncias (público)',
                'controller' => 'CanalDenuncia',
                'controller_url' => 'canal-denuncia',
                'directory' => 'whistleblowing',
                'obs' => 'Formulário público anônimo e acompanhamento por protocolo.',
                'public_page' => 1,
                'default_page' => 0,
            ],
            [
                'name' => 'Listar Denúncias',
                'controller' => 'WhistleblowingListReports',
                'controller_url' => 'list-denuncias',
                'directory' => 'whistleblowing',
                'obs' => 'Listagem interna de denúncias recebidas.',
                'public_page' => 0,
                'default_page' => 0,
            ],
            [
                'name' => 'Visualizar Denúncia',
                'controller' => 'WhistleblowingViewReport',
                'controller_url' => 'view-denuncia',
                'directory' => 'whistleblowing',
                'obs' => 'Detalhe e linha do tempo da denúncia.',
                'public_page' => 0,
                'default_page' => 0,
            ],
            [
                'name' => 'Responder Denúncia',
                'controller' => 'WhistleblowingReplyReport',
                'controller_url' => 'reply-denuncia',
                'directory' => 'whistleblowing',
                'obs' => 'Enviar resposta ao denunciante ou nota interna.',
                'public_page' => 0,
                'default_page' => 0,
            ],
            [
                'name' => 'Atualizar Status Denúncia',
                'controller' => 'WhistleblowingUpdateStatus',
                'controller_url' => 'update-denuncia-status',
                'directory' => 'whistleblowing',
                'obs' => 'Alterar status e responsável da denúncia.',
                'public_page' => 0,
                'default_page' => 0,
            ],
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
                'obs' => $p['obs'],
                'public_page' => $p['public_page'],
                'default_page' => $p['default_page'],
                'page_status' => 1,
                'adms_packages_page_id' => 1,
                'adms_groups_page_id' => $gid,
                'created_at' => $now,
                'updated_at' => $now,
            ])->save();

            if ((int) $p['public_page'] === 0 && $this->hasTable('adms_access_levels_pages')) {
                $newRow = $this->fetchRow('SELECT LAST_INSERT_ID() AS id');
                $newId = (int) ($newRow['id'] ?? 0);
                if ($newId > 0) {
                    $this->execute(
                        "INSERT IGNORE INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                         SELECT 0, al.id, {$newId}, '{$now}', '{$now}'
                         FROM adms_access_levels al"
                    );
                }
            }
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $controllers = [
            'CanalDenuncia',
            'WhistleblowingListReports',
            'WhistleblowingViewReport',
            'WhistleblowingReplyReport',
            'WhistleblowingUpdateStatus',
        ];

        foreach ($controllers as $ctrl) {
            $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = '{$ctrl}' LIMIT 1");
            if (!$row) {
                continue;
            }
            $pid = (int) $row['id'];
            if ($this->hasTable('adms_access_levels_pages')) {
                $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
            }
            $this->execute("DELETE FROM adms_pages WHERE id = {$pid}");
        }

        $this->execute("DELETE FROM adms_groups_pages WHERE name = 'Canal de Denúncias'");
    }
}
