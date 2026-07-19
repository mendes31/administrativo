<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Identidade sombra: Pessoa / Vínculo / Lotação (Expand Fase 4 / ADR-0006).
 */
final class CreateRhIdentidadePessoaVinculoLotacao extends AbstractMigration
{
    public function up(): void
    {
        $this->createPessoas();
        $this->createVinculos();
        $this->createLotacoes();
        $this->backfillFromUsers();
        $this->registerPages();
    }

    public function down(): void
    {
        foreach (['RhPessoas', 'RhPessoasView'] as $controller) {
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
        if ($this->hasTable('rh_lotacoes')) {
            $this->table('rh_lotacoes')->drop()->save();
        }
        if ($this->hasTable('rh_vinculos')) {
            $this->table('rh_vinculos')->drop()->save();
        }
        if ($this->hasTable('rh_pessoas')) {
            $this->table('rh_pessoas')->drop()->save();
        }
    }

    private function createPessoas(): void
    {
        if ($this->hasTable('rh_pessoas')) {
            return;
        }

        $this->table('rh_pessoas', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('adms_user_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('cpf', 'string', ['limit' => 14, 'null' => true])
            ->addColumn('nome', 'string', ['limit' => 220])
            ->addColumn('email', 'string', ['limit' => 220, 'null' => true])
            ->addColumn('data_nascimento', 'date', ['null' => true])
            ->addColumn('sexo', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['adms_user_id'], ['unique' => true, 'name' => 'uq_rh_pessoas_user'])
            ->addIndex(['cpf'], ['unique' => true, 'name' => 'uq_rh_pessoas_cpf'])
            ->create();
    }

    private function createVinculos(): void
    {
        if ($this->hasTable('rh_vinculos')) {
            return;
        }

        $this->table('rh_vinculos', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('rh_pessoa_id', 'integer', ['signed' => false])
            ->addColumn('adms_user_id', 'integer', ['signed' => false])
            ->addColumn('status', 'string', ['limit' => 20, 'default' => 'ativo'])
            ->addColumn('data_inicio', 'date', ['null' => true])
            ->addColumn('data_fim', 'date', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['rh_pessoa_id'], ['name' => 'idx_rh_vinculos_pessoa'])
            ->addIndex(['adms_user_id'], ['name' => 'idx_rh_vinculos_user'])
            ->addIndex(['status'], ['name' => 'idx_rh_vinculos_status'])
            ->create();
    }

    private function createLotacoes(): void
    {
        if ($this->hasTable('rh_lotacoes')) {
            return;
        }

        $this->table('rh_lotacoes', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('rh_vinculo_id', 'integer', ['signed' => false])
            ->addColumn('departamento_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('cargo_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('gestor_user_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('vigente', 'boolean', ['default' => true])
            ->addColumn('data_inicio', 'date', ['null' => true])
            ->addColumn('data_fim', 'date', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['rh_vinculo_id'], ['name' => 'idx_rh_lotacoes_vinculo'])
            ->addIndex(['vigente'], ['name' => 'idx_rh_lotacoes_vigente'])
            ->create();
    }

    private function backfillFromUsers(): void
    {
        if (!$this->hasTable('adms_users') || !$this->hasTable('rh_pessoas')) {
            return;
        }

        // Pessoa 1:1 com conta — CPF duplicado: só o menor id fica com CPF.
        $this->execute(
            "INSERT INTO rh_pessoas (adms_user_id, cpf, nome, email, data_nascimento, sexo, created_at, updated_at)
             SELECT u.id,
                    CASE
                        WHEN u.cpf IS NULL OR TRIM(u.cpf) = '' THEN NULL
                        WHEN EXISTS (
                            SELECT 1 FROM adms_users u2
                            WHERE u2.cpf = u.cpf AND u2.id < u.id
                              AND u2.cpf IS NOT NULL AND TRIM(u2.cpf) <> ''
                        ) THEN NULL
                        ELSE u.cpf
                    END,
                    u.name,
                    u.email,
                    u.data_nascimento,
                    u.sexo,
                    NOW(), NOW()
             FROM adms_users u
             WHERE NOT EXISTS (
                 SELECT 1 FROM rh_pessoas p WHERE p.adms_user_id = u.id
             )"
        );

        $this->execute(
            "INSERT INTO rh_vinculos (rh_pessoa_id, adms_user_id, status, data_inicio, data_fim, created_at, updated_at)
             SELECT p.id, p.adms_user_id,
                    CASE WHEN u.data_desligamento IS NULL THEN 'ativo' ELSE 'encerrado' END,
                    u.data_admissao,
                    u.data_desligamento,
                    NOW(), NOW()
             FROM rh_pessoas p
             INNER JOIN adms_users u ON u.id = p.adms_user_id
             WHERE NOT EXISTS (
                 SELECT 1 FROM rh_vinculos v WHERE v.adms_user_id = p.adms_user_id
             )"
        );

        $this->execute(
            "INSERT INTO rh_lotacoes
                (rh_vinculo_id, departamento_id, cargo_id, gestor_user_id, vigente, data_inicio, data_fim, created_at, updated_at)
             SELECT v.id,
                    u.user_department_id,
                    u.user_position_id,
                    u.immediate_supervisor_id,
                    CASE WHEN v.status = 'ativo' THEN 1 ELSE 0 END,
                    v.data_inicio,
                    v.data_fim,
                    NOW(), NOW()
             FROM rh_vinculos v
             INNER JOIN adms_users u ON u.id = v.adms_user_id
             WHERE NOT EXISTS (
                 SELECT 1 FROM rh_lotacoes l WHERE l.rh_vinculo_id = v.id AND l.vigente = 1
             )
             AND NOT EXISTS (
                 SELECT 1 FROM rh_lotacoes l2 WHERE l2.rh_vinculo_id = v.id
             )"
        );
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
            ['Pessoas (Identidade)', 'RhPessoas', 'rh-pessoas', 'Listagem somente leitura de Pessoas sincronizadas.'],
            ['Visualizar Pessoa', 'RhPessoasView', 'rh-pessoas-view', 'Detalhe de Pessoa, Vínculo e Lotação.'],
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
