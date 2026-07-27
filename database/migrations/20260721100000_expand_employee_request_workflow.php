<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Expand — workflow de solicitações: etapas por tipo, delegação, escalação e histórico.
 * Preserva status legado pending_manager_approval / pending_hr_approval.
 */
final class ExpandEmployeeRequestWorkflow extends AbstractMigration
{
    public function up(): void
    {
        $this->createStages();
        $this->createDelegations();
        $this->createApprovalEvents();
        $this->alterEmployeeRequests();
        $this->backfillStagesFromTypes();
        $this->backfillOpenRequests();
        $this->registerDelegationPages();
    }

    public function down(): void
    {
        if ($this->hasTable('adms_pages')) {
            foreach (['ListApprovalDelegations', 'CreateApprovalDelegation', 'DeleteApprovalDelegation'] as $controller) {
                $page = $this->fetchRow(
                    "SELECT id FROM adms_pages WHERE controller = " . $this->getAdapter()->getConnection()->quote($controller) . " LIMIT 1"
                );
                if ($page) {
                    $pageId = (int) $page['id'];
                    if ($this->hasTable('adms_access_levels_pages')) {
                        $this->execute('DELETE FROM adms_access_levels_pages WHERE adms_page_id = ' . $pageId);
                    }
                    $this->execute('DELETE FROM adms_pages WHERE id = ' . $pageId);
                }
            }
        }

        if ($this->hasTable('adms_employee_requests')) {
            $table = $this->table('adms_employee_requests');
            foreach ([
                'current_stage_code',
                'current_approver_user_id',
                'original_approver_user_id',
                'stage_started_at',
                'escalate_after_hours',
            ] as $col) {
                if ($table->hasColumn($col)) {
                    $table->removeColumn($col);
                }
            }
            $table->update();
        }

        foreach ([
            'adms_employee_request_approval_events',
            'adms_approval_delegations',
            'adms_request_type_stages',
        ] as $name) {
            if ($this->hasTable($name)) {
                $this->table($name)->drop()->save();
            }
        }
    }

    private function createStages(): void
    {
        if ($this->hasTable('adms_request_type_stages')) {
            return;
        }

        $this->table('adms_request_type_stages', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('request_type_id', 'integer', ['signed' => false])
            ->addColumn('stage_order', 'integer', ['signed' => false, 'default' => 1])
            ->addColumn('stage_code', 'string', ['limit' => 40])
            ->addColumn('stage_label', 'string', ['limit' => 120])
            ->addColumn('approver_kind', 'string', [
                'limit' => 30,
                'default' => 'immediate',
                'comment' => 'immediate|hr|fixed_user',
            ])
            ->addColumn('fixed_user_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('escalate_after_hours', 'integer', ['signed' => false, 'default' => 72])
            ->addColumn('escalate_policy', 'string', [
                'limit' => 30,
                'default' => 'next_level',
                'comment' => 'next_level|hr|none',
            ])
            ->addColumn('is_active', 'boolean', ['default' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['request_type_id', 'stage_order'], ['name' => 'idx_request_type_stages_order'])
            ->addIndex(['request_type_id', 'stage_code'], [
                'unique' => true,
                'name' => 'uq_request_type_stages_code',
            ])
            ->create();
    }

    private function createDelegations(): void
    {
        if ($this->hasTable('adms_approval_delegations')) {
            return;
        }

        $this->table('adms_approval_delegations', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('delegator_user_id', 'integer', ['signed' => false])
            ->addColumn('delegate_user_id', 'integer', ['signed' => false])
            ->addColumn('starts_at', 'datetime')
            ->addColumn('ends_at', 'datetime')
            ->addColumn('notes', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['delegator_user_id', 'starts_at', 'ends_at'], [
                'name' => 'idx_approval_delegations_delegator_period',
            ])
            ->addIndex(['delegate_user_id', 'starts_at', 'ends_at'], [
                'name' => 'idx_approval_delegations_delegate_period',
            ])
            ->create();
    }

    private function createApprovalEvents(): void
    {
        if ($this->hasTable('adms_employee_request_approval_events')) {
            return;
        }

        $this->table('adms_employee_request_approval_events', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('request_id', 'integer', ['signed' => false])
            ->addColumn('stage_code', 'string', ['limit' => 40, 'null' => true])
            ->addColumn('action', 'string', [
                'limit' => 30,
                'comment' => 'assigned|approved|rejected|escalated|delegated_act',
            ])
            ->addColumn('actor_user_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('on_behalf_of_user_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('notes', 'text', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['request_id', 'created_at'], ['name' => 'idx_request_approval_events_request'])
            ->create();
    }

    private function alterEmployeeRequests(): void
    {
        if (!$this->hasTable('adms_employee_requests')) {
            return;
        }

        $table = $this->table('adms_employee_requests');
        if (!$table->hasColumn('current_stage_code')) {
            $table->addColumn('current_stage_code', 'string', [
                'limit' => 40,
                'null' => true,
                'after' => 'status',
            ]);
        }
        if (!$table->hasColumn('current_approver_user_id')) {
            $table->addColumn('current_approver_user_id', 'integer', [
                'signed' => false,
                'null' => true,
                'after' => 'current_stage_code',
            ]);
        }
        if (!$table->hasColumn('original_approver_user_id')) {
            $table->addColumn('original_approver_user_id', 'integer', [
                'signed' => false,
                'null' => true,
                'after' => 'current_approver_user_id',
            ]);
        }
        if (!$table->hasColumn('stage_started_at')) {
            $table->addColumn('stage_started_at', 'datetime', [
                'null' => true,
                'after' => 'original_approver_user_id',
            ]);
        }
        if (!$table->hasColumn('escalate_after_hours')) {
            $table->addColumn('escalate_after_hours', 'integer', [
                'signed' => false,
                'null' => true,
                'default' => 72,
                'after' => 'stage_started_at',
            ]);
        }
        $table->update();

        if (!$table->hasIndexByName('idx_employee_requests_approver_status')) {
            $table
                ->addIndex(['current_approver_user_id', 'status'], [
                    'name' => 'idx_employee_requests_approver_status',
                ])
                ->update();
        }
    }

    private function backfillStagesFromTypes(): void
    {
        if (!$this->hasTable('adms_request_types') || !$this->hasTable('adms_request_type_stages')) {
            return;
        }

        $types = $this->fetchAll('SELECT id, requires_manager_approval FROM adms_request_types');
        $now = date('Y-m-d H:i:s');
        $conn = $this->getAdapter()->getConnection();

        foreach ($types as $type) {
            $typeId = (int) $type['id'];
            $existing = $this->fetchRow(
                'SELECT id FROM adms_request_type_stages WHERE request_type_id = ' . $typeId . ' LIMIT 1'
            );
            if ($existing) {
                continue;
            }

            $order = 1;
            if (!empty($type['requires_manager_approval'])) {
                $this->execute(
                    'INSERT INTO adms_request_type_stages
                        (request_type_id, stage_order, stage_code, stage_label, approver_kind,
                         escalate_after_hours, escalate_policy, is_active, created_at, updated_at)
                     VALUES ('
                    . $typeId . ', ' . $order . ', '
                    . $conn->quote('immediate') . ', '
                    . $conn->quote('Gestor imediato') . ', '
                    . $conn->quote('immediate') . ', '
                    . '72, '
                    . $conn->quote('next_level') . ', 1, '
                    . $conn->quote($now) . ', ' . $conn->quote($now) . ')'
                );
                $order++;
            }

            $this->execute(
                'INSERT INTO adms_request_type_stages
                    (request_type_id, stage_order, stage_code, stage_label, approver_kind,
                     escalate_after_hours, escalate_policy, is_active, created_at, updated_at)
                 VALUES ('
                . $typeId . ', ' . $order . ', '
                . $conn->quote('hr') . ', '
                . $conn->quote('RH') . ', '
                . $conn->quote('hr') . ', '
                . '0, '
                . $conn->quote('none') . ', 1, '
                . $conn->quote($now) . ', ' . $conn->quote($now) . ')'
            );
        }
    }

    private function backfillOpenRequests(): void
    {
        if (!$this->hasTable('adms_employee_requests')) {
            return;
        }

        $this->execute(
            "UPDATE adms_employee_requests er
             INNER JOIN adms_users u ON u.id = er.employee_id
             SET er.current_stage_code = 'immediate',
                 er.current_approver_user_id = u.immediate_supervisor_id,
                 er.original_approver_user_id = u.immediate_supervisor_id,
                 er.stage_started_at = COALESCE(er.updated_at, er.created_at, NOW()),
                 er.escalate_after_hours = 72
             WHERE er.status = 'pending_manager_approval'
               AND (er.current_stage_code IS NULL OR er.current_stage_code = '')"
        );

        $this->execute(
            "UPDATE adms_employee_requests
             SET current_stage_code = 'hr',
                 current_approver_user_id = NULL,
                 original_approver_user_id = NULL,
                 stage_started_at = COALESCE(updated_at, created_at, NOW()),
                 escalate_after_hours = 0
             WHERE status = 'pending_hr_approval'
               AND (current_stage_code IS NULL OR current_stage_code = '')"
        );
    }

    private function registerDelegationPages(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $conn = $this->getAdapter()->getConnection();
        $groupId = 36;
        $ref = $this->fetchRow(
            "SELECT adms_groups_page_id FROM adms_pages WHERE controller = 'ListEmployeeRequests' LIMIT 1"
        );
        if ($ref && !empty($ref['adms_groups_page_id'])) {
            $groupId = (int) $ref['adms_groups_page_id'];
        }

        $pages = [
            [
                'name' => 'Delegações de Aprovação',
                'controller' => 'ListApprovalDelegations',
                'url' => 'list-approval-delegations',
                'obs' => 'Listar delegações temporárias de aprovação (ausência do gestor).',
            ],
            [
                'name' => 'Criar Delegação de Aprovação',
                'controller' => 'CreateApprovalDelegation',
                'url' => 'create-approval-delegation',
                'obs' => 'Criar delegação temporária de aprovação.',
            ],
            [
                'name' => 'Excluir Delegação de Aprovação',
                'controller' => 'DeleteApprovalDelegation',
                'url' => 'delete-approval-delegation',
                'obs' => 'Encerrar/excluir delegação de aprovação.',
            ],
        ];

        $now = date('Y-m-d H:i:s');
        foreach ($pages as $page) {
            $existing = $this->fetchRow(
                'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($page['controller']) . ' LIMIT 1'
            );
            if ($existing) {
                $pageId = (int) $existing['id'];
            } else {
                $this->execute(
                    'INSERT INTO adms_pages
                        (name, controller, controller_url, directory, obs, public_page, default_page, page_status,
                         adms_packages_page_id, adms_groups_page_id, created_at, updated_at)
                     VALUES ('
                    . $conn->quote($page['name']) . ', '
                    . $conn->quote($page['controller']) . ', '
                    . $conn->quote($page['url']) . ', '
                    . $conn->quote('portal') . ', '
                    . $conn->quote($page['obs']) . ', '
                    . '0, 0, 1, 1, '
                    . $groupId . ', '
                    . $conn->quote($now) . ', '
                    . $conn->quote($now) . ')'
                );
                $row = $this->fetchRow(
                    'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($page['controller']) . ' LIMIT 1'
                );
                $pageId = (int) ($row['id'] ?? 0);
            }

            if ($pageId > 0) {
                $this->grantPageToLevels($pageId, 'ListEmployeeRequests');
            }
        }
    }

    private function grantPageToLevels(int $pageId, string $refController): void
    {
        if (!$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $ref = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = "
            . $this->getAdapter()->getConnection()->quote($refController) . " LIMIT 1"
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
