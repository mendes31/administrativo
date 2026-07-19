<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Conversão auditável oferta aceita → adms_users (fachada Pessoa/Conta).
 */
final class CreateRhConversoesAdmissao extends AbstractMigration
{
    public function up(): void
    {
        $this->createConversoes();
        $this->alterCandidatos();
        $this->alterOfertas();
        $this->registerPage();
    }

    public function down(): void
    {
        if ($this->hasTable('adms_pages')) {
            $row = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = 'RhOfertasConvert' LIMIT 1"
            );
            if ($row) {
                $pid = (int) $row['id'];
                if ($this->hasTable('adms_access_levels_pages')) {
                    $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
                }
                $this->execute("DELETE FROM adms_pages WHERE id = {$pid}");
            }
        }

        if ($this->hasTable('rh_ofertas') && $this->table('rh_ofertas')->hasColumn('rh_conversao_id')) {
            $this->table('rh_ofertas')->removeColumn('rh_conversao_id')->update();
        }
        if ($this->hasTable('rh_candidatos') && $this->table('rh_candidatos')->hasColumn('adms_user_id')) {
            $this->table('rh_candidatos')->removeColumn('adms_user_id')->update();
        }
        if ($this->hasTable('rh_conversoes_admissao')) {
            $this->table('rh_conversoes_admissao')->drop()->save();
        }
    }

    private function createConversoes(): void
    {
        if ($this->hasTable('rh_conversoes_admissao')) {
            return;
        }

        $this->table('rh_conversoes_admissao', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('rh_oferta_id', 'integer', ['signed' => false])
            ->addColumn('rh_candidatura_id', 'integer', ['signed' => false])
            ->addColumn('rh_candidato_id', 'integer', ['signed' => false])
            ->addColumn('rh_vaga_id', 'integer', ['signed' => false])
            ->addColumn('adms_user_id', 'integer', ['signed' => false])
            ->addColumn('modo', 'string', ['limit' => 20, 'default' => 'criar'])
            ->addColumn('status', 'string', ['limit' => 20, 'default' => 'concluida'])
            ->addColumn('observacoes', 'text', ['null' => true])
            ->addColumn('converted_by_user_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['rh_oferta_id'], ['unique' => true, 'name' => 'uq_rh_conversoes_oferta'])
            ->addIndex(['adms_user_id'], ['name' => 'idx_rh_conversoes_user'])
            ->addIndex(['rh_candidato_id'], ['name' => 'idx_rh_conversoes_candidato'])
            ->create();
    }

    private function alterCandidatos(): void
    {
        if (!$this->hasTable('rh_candidatos')) {
            return;
        }
        $table = $this->table('rh_candidatos');
        if (!$table->hasColumn('adms_user_id')) {
            $table->addColumn('adms_user_id', 'integer', [
                'signed' => false,
                'null' => true,
                'after' => 'id',
                'comment' => 'Conta/colaborador após conversão de oferta',
            ])->update();
        }
    }

    private function alterOfertas(): void
    {
        if (!$this->hasTable('rh_ofertas')) {
            return;
        }
        $table = $this->table('rh_ofertas');
        if (!$table->hasColumn('rh_conversao_id')) {
            $table->addColumn('rh_conversao_id', 'integer', [
                'signed' => false,
                'null' => true,
                'after' => 'status',
                'comment' => 'FK lógica rh_conversoes_admissao.id',
            ])->update();
        }
    }

    private function registerPage(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $conn = $this->getAdapter()->getConnection();
        $controller = 'RhOfertasConvert';
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
            . $conn->quote('Converter Oferta em Colaborador') . ', '
            . $conn->quote($controller) . ', '
            . $conn->quote('rh-ofertas-convert') . ', '
            . $conn->quote('rh') . ', '
            . $conn->quote('Conversão auditável de oferta aceita para adms_users.') . ', '
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
