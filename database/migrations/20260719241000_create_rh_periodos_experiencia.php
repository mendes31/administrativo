<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Período de experiência pós-conversão (Expand Fase 4).
 * Reenvio FTP: garantir ficheiro em produção após deploy multi-commit incompleto.
 */
final class CreateRhPeriodosExperiencia extends AbstractMigration
{
    public function up(): void
    {
        $this->createTable();
        $this->registerPage();
    }

    public function down(): void
    {
        if ($this->hasTable('adms_pages')) {
            $row = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = 'RhExperienciaView' LIMIT 1"
            );
            if ($row) {
                $pid = (int) $row['id'];
                if ($this->hasTable('adms_access_levels_pages')) {
                    $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
                }
                $this->execute("DELETE FROM adms_pages WHERE id = {$pid}");
            }
        }
        if ($this->hasTable('rh_periodos_experiencia')) {
            $this->table('rh_periodos_experiencia')->drop()->save();
        }
    }

    private function createTable(): void
    {
        if ($this->hasTable('rh_periodos_experiencia')) {
            return;
        }

        $this->table('rh_periodos_experiencia', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('rh_conversao_id', 'integer', ['signed' => false])
            ->addColumn('adms_user_id', 'integer', ['signed' => false])
            ->addColumn('rh_candidato_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('rh_onboarding_plano_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('status', 'string', ['limit' => 20, 'default' => 'em_andamento'])
            ->addColumn('data_inicio', 'date', ['null' => false])
            ->addColumn('data_fim_prevista', 'date', ['null' => false])
            ->addColumn('dias_prorrogacao', 'integer', ['signed' => false, 'default' => 0])
            ->addColumn('prorrogado_em', 'datetime', ['null' => true])
            ->addColumn('resultado', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('avaliado_em', 'datetime', ['null' => true])
            ->addColumn('avaliado_por_user_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('observacoes', 'text', ['null' => true])
            ->addColumn('created_by_user_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['rh_conversao_id'], ['unique' => true, 'name' => 'uq_rh_experiencia_conversao'])
            ->addIndex(['adms_user_id'], ['name' => 'idx_rh_experiencia_user'])
            ->addIndex(['status'], ['name' => 'idx_rh_experiencia_status'])
            ->addIndex(['data_fim_prevista'], ['name' => 'idx_rh_experiencia_fim'])
            ->create();
    }

    private function registerPage(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $conn = $this->getAdapter()->getConnection();
        $controller = 'RhExperienciaView';
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
            . $conn->quote('Período de Experiência') . ', '
            . $conn->quote($controller) . ', '
            . $conn->quote('rh-experiencia-view') . ', '
            . $conn->quote('rh') . ', '
            . $conn->quote('Acompanhamento e avaliação do período de experiência.') . ', '
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
