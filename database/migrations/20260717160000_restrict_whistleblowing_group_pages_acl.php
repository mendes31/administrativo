<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Restringe as páginas do grupo "Canal de Denúncias" para que só apareçam/fiquem
 * acessíveis a:
 *   - Super Administrador (nível id 1, que também ignora ACL);
 *   - Super usuários (usam a flag e ignoram ACL, não dependem de concessão);
 *   - Níveis dedicados do canal: "Canal de Denúncias — Operador" e
 *     "Canal de Denúncias — Administrador" (membros de comitê herdam o Operador).
 *
 * Correção: as páginas públicas do grupo (canaldenuncia, whistleblowing-retention-cron)
 * haviam sido concedidas com permission=1 para TODOS os níveis de acesso, fazendo o
 * grupo aparecer para cargos que não deveriam vê-lo. Aqui revogamos (permission=0)
 * qualquer página do grupo em níveis não autorizados, preservando as concessões dos
 * níveis do canal.
 */
final class RestrictWhistleblowingGroupPagesAcl extends AbstractMigration
{
    private const GROUP_NAME = 'Canal de Denúncias';

    private const SUPER_ADMIN_LEVEL_ID = 1;

    /** @var list<string> */
    private const AUTHORIZED_LEVEL_NAMES = [
        'Canal de Denúncias — Operador',
        'Canal de Denúncias — Administrador',
    ];

    public function up(): void
    {
        if (
            !$this->hasTable('adms_groups_pages')
            || !$this->hasTable('adms_pages')
            || !$this->hasTable('adms_access_levels')
            || !$this->hasTable('adms_access_levels_pages')
        ) {
            return;
        }

        $conn = $this->getAdapter()->getConnection();

        $group = $this->fetchRow(
            'SELECT id FROM adms_groups_pages WHERE name = ' . $conn->quote(self::GROUP_NAME) . ' LIMIT 1'
        );
        if (!$group) {
            $group = $this->fetchRow(
                "SELECT id FROM adms_groups_pages WHERE name LIKE '%Denúncia%' OR name LIKE '%Whistle%' LIMIT 1"
            );
        }
        if (!$group) {
            return;
        }
        $groupId = (int) $group['id'];

        $authorizedIds = [self::SUPER_ADMIN_LEVEL_ID];
        foreach (self::AUTHORIZED_LEVEL_NAMES as $name) {
            $row = $this->fetchRow(
                'SELECT id FROM adms_access_levels WHERE name = ' . $conn->quote($name) . ' LIMIT 1'
            );
            if ($row) {
                $authorizedIds[] = (int) $row['id'];
            }
        }
        $inList = implode(',', array_map('intval', array_unique($authorizedIds)));
        $now = date('Y-m-d H:i:s');

        $this->execute(
            "UPDATE adms_access_levels_pages AS alp
             INNER JOIN adms_pages AS ap ON ap.id = alp.adms_page_id
             SET alp.permission = 0, alp.updated_at = '{$now}'
             WHERE ap.adms_groups_page_id = {$groupId}
               AND alp.permission = 1
               AND alp.adms_access_level_id NOT IN ({$inList})"
        );

        $this->bumpMenuPermissionCache();
    }

    /**
     * Sem reversão automática: restaurar o acesso amplo às páginas do Canal de
     * Denúncias reintroduziria a falha de exposição indevida. Caso precise conceder
     * uma página do canal a um nível específico, faça pela tela de permissões.
     */
    public function down(): void
    {
        $this->bumpMenuPermissionCache();
    }

    private function bumpMenuPermissionCache(): void
    {
        $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'system';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($dir . DIRECTORY_SEPARATOR . 'menu_permission_version.txt', (string) time());
    }
}
