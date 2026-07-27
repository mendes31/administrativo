<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Expand — convite/aceite de avaliadores adicionais na entrevista.
 *
 * Estende status do painel: convidado | recusado (além de ativo | removido).
 * Principal continua ativo imediato; adicionais novos nascem convidados.
 */
final class ExpandRhEntrevistaAvaliadorConviteAceite extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('rh_entrevista_avaliadores')) {
            return;
        }

        $table = $this->table('rh_entrevista_avaliadores');

        if (!$table->hasColumn('convidado_at')) {
            $table->addColumn('convidado_at', 'datetime', [
                'null' => true,
                'after' => 'designado_at',
                'comment' => 'Quando o convite foi (re)enviado',
            ]);
        }
        if (!$table->hasColumn('respondido_at')) {
            $table->addColumn('respondido_at', 'datetime', [
                'null' => true,
                'after' => 'convidado_at',
                'comment' => 'Quando aceitou ou recusou',
            ]);
        }
        $table->update();

        // ENUM: incluir convidado e recusado (MySQL/MariaDB).
        $this->execute(
            "ALTER TABLE rh_entrevista_avaliadores
             MODIFY COLUMN status ENUM('ativo','removido','convidado','recusado')
             NOT NULL DEFAULT 'ativo'"
        );

        $this->registerAclPages();
    }

    public function down(): void
    {
        if ($this->hasTable('rh_entrevista_avaliadores')) {
            // Reverte linhas novas para removido antes de encolher o ENUM.
            $this->execute(
                "UPDATE rh_entrevista_avaliadores
                 SET status = 'removido'
                 WHERE status IN ('convidado','recusado')"
            );
            $this->execute(
                "ALTER TABLE rh_entrevista_avaliadores
                 MODIFY COLUMN status ENUM('ativo','removido')
                 NOT NULL DEFAULT 'ativo'"
            );

            $table = $this->table('rh_entrevista_avaliadores');
            if ($table->hasColumn('respondido_at')) {
                $table->removeColumn('respondido_at');
            }
            if ($table->hasColumn('convidado_at')) {
                $table->removeColumn('convidado_at');
            }
            $table->update();
        }

        $this->dropAclPages();
    }

    private function registerAclPages(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $conn = $this->getAdapter()->getConnection();
        $groupId = 30;
        $group = $this->fetchRow(
            "SELECT adms_groups_page_id FROM adms_pages WHERE controller = 'RhEntrevistas' LIMIT 1"
        );
        if ($group && !empty($group['adms_groups_page_id'])) {
            $groupId = (int) $group['adms_groups_page_id'];
        }

        $now = date('Y-m-d H:i:s');
        $pages = [
            [
                'name' => 'Aceitar convite de avaliação',
                'controller' => 'RhEntrevistasAceitarAvaliacao',
                'controller_url' => 'rh-entrevistas-aceitar-avaliacao',
                'obs' => 'Avaliador convidado aceita participar da entrevista.',
            ],
            [
                'name' => 'Recusar convite de avaliação',
                'controller' => 'RhEntrevistasRecusarAvaliacao',
                'controller_url' => 'rh-entrevistas-recusar-avaliacao',
                'obs' => 'Avaliador convidado recusa participar da entrevista.',
            ],
            [
                'name' => 'Reenviar convite de avaliador',
                'controller' => 'RhEntrevistasReenviarConviteAvaliador',
                'controller_url' => 'rh-entrevistas-reenviar-convite-avaliador',
                'obs' => 'Gestor/RH reenvia convite a avaliador adicional.',
            ],
        ];

        foreach ($pages as $page) {
            $existing = $this->fetchRow(
                'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($page['controller']) . ' LIMIT 1'
            );
            if (!$existing) {
                $this->execute(
                    'INSERT INTO adms_pages
                        (name, controller, controller_url, directory, obs, public_page, default_page, page_status,
                         adms_packages_page_id, adms_groups_page_id, created_at, updated_at)
                     VALUES ('
                    . $conn->quote($page['name']) . ', '
                    . $conn->quote($page['controller']) . ', '
                    . $conn->quote($page['controller_url']) . ', '
                    . $conn->quote('rh') . ', '
                    . $conn->quote($page['obs']) . ', '
                    . '0, 0, 1, 1, '
                    . $groupId . ', '
                    . $conn->quote($now) . ', '
                    . $conn->quote($now)
                    . ')'
                );
                $existing = $this->fetchRow(
                    'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($page['controller']) . ' LIMIT 1'
                );
            }
            if ($existing) {
                $this->grantToRhEntrevistasLevels((int) $existing['id']);
            }
        }

        if (class_exists(\App\adms\Models\Repository\MenuPermissionUserRepository::class)) {
            \App\adms\Models\Repository\MenuPermissionUserRepository::bumpGlobalPermissionCacheVersion();
        }
    }

    private function dropAclPages(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }
        $conn = $this->getAdapter()->getConnection();
        foreach ([
            'RhEntrevistasAceitarAvaliacao',
            'RhEntrevistasRecusarAvaliacao',
            'RhEntrevistasReenviarConviteAvaliador',
        ] as $controller) {
            $page = $this->fetchRow(
                'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($controller) . ' LIMIT 1'
            );
            if (!$page) {
                continue;
            }
            $pageId = (int) $page['id'];
            if ($this->hasTable('adms_access_levels_pages')) {
                $this->execute('DELETE FROM adms_access_levels_pages WHERE adms_page_id = ' . $pageId);
            }
            $this->execute('DELETE FROM adms_pages WHERE id = ' . $pageId);
        }
    }

    private function grantToRhEntrevistasLevels(int $pageId): void
    {
        if (!$this->hasTable('adms_access_levels_pages')) {
            return;
        }
        $ref = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'RhEntrevistas' LIMIT 1"
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
             WHERE alp.adms_page_id = {$refId}
               AND alp.permission = 1
             ON DUPLICATE KEY UPDATE permission = 1, updated_at = '{$now}'"
        );
    }
}
