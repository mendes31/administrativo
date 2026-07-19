<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Oferta + checklist de pré-admissão (Expand Fase 3).
 */
final class CreateRhOfertasPreAdmissao extends AbstractMigration
{
    public function up(): void
    {
        $this->createOfertas();
        $this->createDocumentos();
        $this->registerPages();
    }

    public function down(): void
    {
        foreach (['RhOfertasCreate', 'RhOfertasView'] as $controller) {
            $row = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = '{$controller}' LIMIT 1"
            );
            if ($row) {
                $pid = (int) $row['id'];
                if ($this->hasTable('adms_access_levels_pages')) {
                    $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
                }
                $this->execute("DELETE FROM adms_pages WHERE id = {$pid}");
            }
        }

        if ($this->hasTable('rh_pre_admissao_documentos')) {
            $this->table('rh_pre_admissao_documentos')->drop()->save();
        }
        if ($this->hasTable('rh_ofertas')) {
            $this->table('rh_ofertas')->drop()->save();
        }
    }

    private function createOfertas(): void
    {
        if ($this->hasTable('rh_ofertas')) {
            return;
        }

        $this->table('rh_ofertas', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('rh_candidatura_id', 'integer', ['signed' => false])
            ->addColumn('rh_candidato_id', 'integer', ['signed' => false])
            ->addColumn('rh_vaga_id', 'integer', ['signed' => false])
            ->addColumn('status', 'string', ['limit' => 20, 'default' => 'rascunho'])
            ->addColumn('salario_oferecido', 'decimal', [
                'precision' => 12,
                'scale' => 2,
                'null' => true,
            ])
            ->addColumn('tipo_contrato', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('data_inicio_prevista', 'date', ['null' => true])
            ->addColumn('validade_ate', 'date', ['null' => true])
            ->addColumn('observacoes', 'text', ['null' => true])
            ->addColumn('resposta_observacoes', 'text', ['null' => true])
            ->addColumn('created_by_user_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('enviado_em', 'datetime', ['null' => true])
            ->addColumn('respondido_em', 'datetime', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['rh_candidatura_id'], ['name' => 'idx_rh_ofertas_candidatura'])
            ->addIndex(['rh_candidato_id'], ['name' => 'idx_rh_ofertas_candidato'])
            ->addIndex(['rh_vaga_id'], ['name' => 'idx_rh_ofertas_vaga'])
            ->addIndex(['status'], ['name' => 'idx_rh_ofertas_status'])
            ->create();
    }

    private function createDocumentos(): void
    {
        if ($this->hasTable('rh_pre_admissao_documentos')) {
            return;
        }

        $this->table('rh_pre_admissao_documentos', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('rh_oferta_id', 'integer', ['signed' => false])
            ->addColumn('codigo', 'string', ['limit' => 50])
            ->addColumn('titulo', 'string', ['limit' => 150])
            ->addColumn('obrigatorio', 'boolean', ['default' => true])
            ->addColumn('status', 'string', ['limit' => 20, 'default' => 'pendente'])
            ->addColumn('observacoes', 'text', ['null' => true])
            ->addColumn('received_at', 'datetime', ['null' => true])
            ->addColumn('reviewed_at', 'datetime', ['null' => true])
            ->addColumn('reviewed_by_user_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['rh_oferta_id'], ['name' => 'idx_rh_pre_adm_docs_oferta'])
            ->addIndex(['rh_oferta_id', 'codigo'], [
                'unique' => true,
                'name' => 'uq_rh_pre_adm_docs_oferta_codigo',
            ])
            ->create();
    }

    private function registerPages(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $groupId = 30;
        $group = $this->fetchRow(
            "SELECT adms_groups_page_id FROM adms_pages WHERE controller = 'RhVagas' LIMIT 1"
        );
        if ($group && !empty($group['adms_groups_page_id'])) {
            $groupId = (int) $group['adms_groups_page_id'];
        }

        $pages = [
            ['Criar Oferta', 'RhOfertasCreate', 'rh-ofertas-create', 'Criar oferta de emprego para candidatura aprovada.'],
            ['Visualizar Oferta', 'RhOfertasView', 'rh-ofertas-view', 'Detalhe, aceite/recusa e documentos de pré-admissão.'],
        ];

        $conn = $this->getAdapter()->getConnection();
        $now = date('Y-m-d H:i:s');

        foreach ($pages as [$name, $controller, $url, $obs]) {
            $existing = $this->fetchRow(
                'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($controller) . ' LIMIT 1'
            );
            if ($existing) {
                $this->grantPageToRhVagasLevels((int) $existing['id']);
                continue;
            }

            $this->execute(
                'INSERT INTO adms_pages
                    (name, controller, controller_url, directory, obs, public_page, default_page, page_status,
                     adms_packages_page_id, adms_groups_page_id, created_at, updated_at)
                 VALUES ('
                . $conn->quote($name) . ', '
                . $conn->quote($controller) . ', '
                . $conn->quote($url) . ', '
                . $conn->quote('rh') . ', '
                . $conn->quote($obs) . ', '
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
