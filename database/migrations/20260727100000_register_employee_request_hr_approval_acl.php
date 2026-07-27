<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Concede ApproveEmployeeRequestHR a quem já tem PendingApprovals (fila de aprovações).
 */
final class RegisterEmployeeRequestHrApprovalAcl extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $conn = $this->getAdapter()->getConnection();
        $controller = 'ApproveEmployeeRequestHR';
        $existing = $this->fetchRow(
            'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($controller) . ' LIMIT 1'
        );

        $groupId = 36;
        $group = $this->fetchRow(
            "SELECT adms_groups_page_id FROM adms_pages WHERE controller = 'PendingApprovals' LIMIT 1"
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
                . $conn->quote('Aprovar Solicitação (RH)') . ', '
                . $conn->quote($controller) . ', '
                . $conn->quote('approve-employee-request-hr') . ', '
                . $conn->quote('portal') . ', '
                . $conn->quote('Endpoint para RH aprovar/rejeitar solicitação na etapa de RH.') . ', '
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
            $this->grantApproveHrToPendingApprovalsLevels((int) $existing['id']);
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $page = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'ApproveEmployeeRequestHR' LIMIT 1"
        );
        if (!$page) {
            return;
        }

        $pageId = (int) $page['id'];
        $this->execute('DELETE FROM adms_access_levels_pages WHERE adms_page_id = ' . $pageId);
    }

    private function grantApproveHrToPendingApprovalsLevels(int $approvePageId): void
    {
        if (!$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $ref = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'PendingApprovals' LIMIT 1"
        );
        if (!$ref) {
            return;
        }

        $refId = (int) $ref['id'];
        $now = date('Y-m-d H:i:s');
        $this->execute(
            "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT 1, alp.adms_access_level_id, {$approvePageId}, '{$now}', '{$now}'
             FROM adms_access_levels_pages alp
             WHERE alp.adms_page_id = {$refId}
               AND alp.permission = 1
             ON DUPLICATE KEY UPDATE permission = 1, updated_at = '{$now}'"
        );
    }
}
