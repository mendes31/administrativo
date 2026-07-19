<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Expand: reenvio manual de comunicações failed/blocked.
 * - Coluna source_comunicacao_id (auditoria / anti-duplicidade).
 * - Página ACL RhEntrevistasResendComunicacao (concedida a quem já edita entrevistas).
 */
final class AddRhEntrevistaComunicacaoReenvio extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('rh_entrevista_comunicacoes')) {
            $table = $this->table('rh_entrevista_comunicacoes');
            if (!$table->hasColumn('source_comunicacao_id')) {
                $table->addColumn('source_comunicacao_id', 'integer', [
                    'signed' => false,
                    'null' => true,
                    'after' => 'outbox_event_id',
                    'comment' => 'Comunicação de origem quando esta é um reenvio',
                ])->addIndex(['source_comunicacao_id', 'status'], [
                    'name' => 'idx_rh_entrevista_comunicacoes_source_status',
                ])->update();
            }
        }

        $this->registerResendPage();
    }

    public function down(): void
    {
        if ($this->hasTable('adms_pages')) {
            $conn = $this->getAdapter()->getConnection();
            $page = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = 'RhEntrevistasResendComunicacao' LIMIT 1"
            );
            if ($page) {
                $pageId = (int) $page['id'];
                if ($this->hasTable('adms_access_levels_pages')) {
                    $this->execute('DELETE FROM adms_access_levels_pages WHERE adms_page_id = ' . $pageId);
                }
                $this->execute('DELETE FROM adms_pages WHERE id = ' . $pageId);
            }
        }

        if ($this->hasTable('rh_entrevista_comunicacoes')) {
            $table = $this->table('rh_entrevista_comunicacoes');
            if ($table->hasIndexByName('idx_rh_entrevista_comunicacoes_source_status')) {
                $table->removeIndexByName('idx_rh_entrevista_comunicacoes_source_status')->update();
            }
            if ($table->hasColumn('source_comunicacao_id')) {
                $table->removeColumn('source_comunicacao_id')->update();
            }
        }
    }

    private function registerResendPage(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $conn = $this->getAdapter()->getConnection();
        $controller = 'RhEntrevistasResendComunicacao';
        $existing = $this->fetchRow(
            'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($controller) . ' LIMIT 1'
        );

        $groupId = 30;
        $group = $this->fetchRow(
            "SELECT adms_groups_page_id FROM adms_pages WHERE controller = 'RhEntrevistas' LIMIT 1"
        );
        if ($group && !empty($group['adms_groups_page_id'])) {
            $groupId = (int) $group['adms_groups_page_id'];
        }

        $now = date('Y-m-d H:i:s');
        if (!$existing) {
            $this->execute(
                'INSERT INTO adms_pages
                    (name, controller, controller_url, directory, obs, public_page, default_page, page_status,
                     adms_packages_page_id, adms_groups_page_id, created_at, updated_at)
                 VALUES ('
                . $conn->quote('Reenviar Comunicação de Entrevista') . ', '
                . $conn->quote($controller) . ', '
                . $conn->quote('rh-entrevistas-resend-comunicacao') . ', '
                . $conn->quote('rh') . ', '
                . $conn->quote('Reenvia intenção de e-mail failed/blocked (nova intenção + outbox; sem envio imediato).') . ', '
                . '0, 0, 1, 1, '
                . $groupId . ', '
                . $conn->quote($now) . ', '
                . $conn->quote($now)
                . ')'
            );
            $existing = $this->fetchRow(
                'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($controller) . ' LIMIT 1'
            );
        }

        if ($existing) {
            $this->grantPageToRhEntrevistasEditLevels((int) $existing['id']);
        }
    }

    private function grantPageToRhEntrevistasEditLevels(int $pageId): void
    {
        if (!$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $ref = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'RhEntrevistasEdit' LIMIT 1"
        );
        if (!$ref) {
            $ref = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = 'RhEntrevistas' LIMIT 1"
            );
        }
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
                 WHERE x.adms_access_level_id = alp.adms_access_level_id
                   AND x.adms_page_id = {$pageId}
             )"
        );
    }
}
