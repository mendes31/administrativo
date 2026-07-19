<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Fase 1 — requisição de pessoal (Talentos) + vínculo opcional com rh_vagas.
 */
final class CreateRhPersonnelRequests extends AbstractMigration
{
    public function up(): void
    {
        $this->createPersonnelRequestsTable();
        $this->addVagaForeignKey();
        $this->registerPages();
    }

    public function down(): void
    {
        if ($this->hasTable('rh_vagas')) {
            $table = $this->table('rh_vagas');
            if ($table->hasForeignKey('personnel_request_id')) {
                $table->dropForeignKey('personnel_request_id')->update();
            }
            if ($table->hasColumn('personnel_request_id')) {
                $table->removeColumn('personnel_request_id')->update();
            }
        }

        if ($this->hasTable('adms_pages')) {
            $this->execute(
                "DELETE FROM adms_access_levels_pages
                 WHERE adms_page_id IN (
                    SELECT id FROM (
                        SELECT id FROM adms_pages
                        WHERE controller IN (
                            'RhPersonnelRequests',
                            'RhPersonnelRequestsCreate',
                            'RhPersonnelRequestsView',
                            'RhPersonnelRequestsApprove',
                            'RhPersonnelRequestsReject',
                            'RhPersonnelRequestsConvert'
                        )
                    ) t
                 )"
            );
            $this->execute(
                "DELETE FROM adms_pages WHERE controller IN (
                    'RhPersonnelRequests',
                    'RhPersonnelRequestsCreate',
                    'RhPersonnelRequestsView',
                    'RhPersonnelRequestsApprove',
                    'RhPersonnelRequestsReject',
                    'RhPersonnelRequestsConvert'
                )"
            );
        }

        if ($this->hasTable('rh_personnel_requests')) {
            $this->table('rh_personnel_requests')->drop()->save();
        }
    }

    private function createPersonnelRequestsTable(): void
    {
        if ($this->hasTable('rh_personnel_requests')) {
            return;
        }

        $this->table('rh_personnel_requests', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('requester_id', 'integer', ['signed' => false])
            ->addColumn('area_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('cargo_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('quantidade', 'integer', ['signed' => false, 'default' => 1])
            ->addColumn('tipo_contrato', 'string', ['limit' => 30, 'default' => 'CLT'])
            ->addColumn('motivo_tipo', 'string', [
                'limit' => 30,
                'default' => 'aumento',
                'comment' => 'aumento|reposicao',
            ])
            ->addColumn('justificativa', 'text')
            ->addColumn('data_desejada', 'date', ['null' => true])
            ->addColumn('salario_min', 'decimal', ['precision' => 12, 'scale' => 2, 'null' => true])
            ->addColumn('salario_max', 'decimal', ['precision' => 12, 'scale' => 2, 'null' => true])
            ->addColumn('status', 'string', [
                'limit' => 30,
                'default' => 'pending_approval',
                'comment' => 'pending_approval|approved|rejected|converted',
            ])
            ->addColumn('approved_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('approved_at', 'datetime', ['null' => true])
            ->addColumn('rejection_reason', 'text', ['null' => true])
            ->addColumn('converted_vaga_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['status'], ['name' => 'idx_rh_pers_req_status'])
            ->addIndex(['requester_id'], ['name' => 'idx_rh_pers_req_requester'])
            ->addForeignKey('requester_id', 'adms_users', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_rh_pers_req_requester',
            ])
            ->addForeignKey('area_id', 'adms_departments', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_rh_pers_req_area',
            ])
            ->addForeignKey('cargo_id', 'adms_positions', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_rh_pers_req_cargo',
            ])
            ->addForeignKey('approved_by', 'adms_users', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_rh_pers_req_approver',
            ])
            ->create();
    }

    private function addVagaForeignKey(): void
    {
        if (!$this->hasTable('rh_vagas') || !$this->hasTable('rh_personnel_requests')) {
            return;
        }

        $table = $this->table('rh_vagas');
        if (!$table->hasColumn('personnel_request_id')) {
            $table
                ->addColumn('personnel_request_id', 'integer', [
                    'signed' => false,
                    'null' => true,
                    'after' => 'responsavel_id',
                ])
                ->addIndex(['personnel_request_id'], [
                    'unique' => true,
                    'name' => 'uq_rh_vagas_personnel_request',
                ])
                ->update();
        }

        if (!$table->hasForeignKey('personnel_request_id')) {
            $table
                ->addForeignKey(
                    'personnel_request_id',
                    'rh_personnel_requests',
                    'id',
                    [
                        'delete' => 'SET_NULL',
                        'update' => 'NO_ACTION',
                        'constraint' => 'fk_rh_vagas_personnel_request',
                    ]
                )
                ->update();
        }
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
            ['Requisições de Pessoal', 'RhPersonnelRequests', 'rh-personnel-requests', 'Listagem de requisições de pessoal (Talentos).'],
            ['Cadastrar Requisição de Pessoal', 'RhPersonnelRequestsCreate', 'rh-personnel-requests-create', 'Criar requisição de headcount.'],
            ['Visualizar Requisição de Pessoal', 'RhPersonnelRequestsView', 'rh-personnel-requests-view', 'Detalhe da requisição de pessoal.'],
            ['Aprovar Requisição de Pessoal', 'RhPersonnelRequestsApprove', 'rh-personnel-requests-approve', 'Aprovar requisição de pessoal.'],
            ['Rejeitar Requisição de Pessoal', 'RhPersonnelRequestsReject', 'rh-personnel-requests-reject', 'Rejeitar requisição de pessoal.'],
            ['Converter Requisição em Vaga', 'RhPersonnelRequestsConvert', 'rh-personnel-requests-convert', 'Criar vaga a partir de requisição aprovada.'],
        ];

        $conn = $this->getAdapter()->getConnection();
        $now = date('Y-m-d H:i:s');

        foreach ($pages as [$name, $controller, $url, $obs]) {
            $existing = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = " . $conn->quote($controller) . " LIMIT 1"
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
                "SELECT id FROM adms_pages WHERE controller = " . $conn->quote($controller) . " LIMIT 1"
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
             WHERE alp.adms_page_id = {$refId}
               AND alp.permission = 1
             ON DUPLICATE KEY UPDATE permission = 1, updated_at = '{$now}'"
        );
    }
}
