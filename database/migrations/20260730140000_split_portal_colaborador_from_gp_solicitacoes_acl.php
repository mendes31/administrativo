<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Cinde "Gestão de Pessoas - Portal / Solicitações" em:
 * - Portal do Colaborador (self-service: início, solicitações/chamados próprios, vagas, folha do colaborador)
 * - Gestão de Pessoas - Solicitações (RH) (aprovações, tipos, delegações, admin de folha)
 *
 * Não altera adms_access_levels_pages (permissões por page_id preservadas).
 * Menu lateral já está separado; isto alinha a matriz "Autorizar grupo".
 */
final class SplitPortalColaboradorFromGpSolicitacoesAcl extends AbstractMigration
{
    private const OLD_GROUP = 'Gestão de Pessoas - Portal / Solicitações';

    private const PORTAL_GROUP = 'Portal do Colaborador';

    private const RH_GROUP = 'Gestão de Pessoas - Solicitações (RH)';

    /** Self-service do colaborador. */
    private const PORTAL_CONTROLLERS = [
        'EmployeePortal',
        'VagasInternas',
        'ListEmployeeRequests',
        'CreateEmployeeRequest',
        'ViewEmployeeRequest',
        'UpdateEmployeeRequest',
        'ListEmployeeTickets',
        'CreateEmployeeTicket',
        'ViewEmployeeTicket',
        'UpdateEmployeeTicket',
        'MyPayrollDocuments',
        'ViewPayrollDocument',
        'SignPayrollDocument',
        'ConfirmPayrollDocumentDownload',
        'PayrollSignatureReceipt',
        'ViewPayrollSignedBundle',
    ];

    public function up(): void
    {
        if (!$this->hasTable('adms_groups_pages') || !$this->hasTable('adms_pages')) {
            return;
        }

        $conn = $this->getAdapter()->getConnection();
        $now = date('Y-m-d H:i:s');

        $old = $this->fetchRow(
            'SELECT id FROM adms_groups_pages WHERE name = ' . $conn->quote(self::OLD_GROUP) . ' LIMIT 1'
        );
        if (!$old) {
            // Já renomeado ou ambiente diferente: tenta localizar pelo nome RH.
            $old = $this->fetchRow(
                'SELECT id FROM adms_groups_pages WHERE name = ' . $conn->quote(self::RH_GROUP) . ' LIMIT 1'
            );
        }
        if (!$old) {
            return;
        }
        $oldId = (int) $old['id'];

        // Renomeia o grupo antigo para RH (páginas que ficarem).
        $this->execute(
            'UPDATE adms_groups_pages SET name = ' . $conn->quote(self::RH_GROUP)
            . ', obs = ' . $conn->quote('Aprovações, tipos de solicitação, delegações e administração de documentos de folha (RH).')
            . ', updated_at = ' . $conn->quote($now)
            . ' WHERE id = ' . $oldId
            . ' AND name <> ' . $conn->quote(self::RH_GROUP)
        );

        $portalId = $this->ensureGroup(
            self::PORTAL_GROUP,
            'Self-service do colaborador: portal, minhas solicitações/chamados, vagas internas e meus documentos de folha.',
            $now,
            $conn
        );
        if ($portalId <= 0) {
            return;
        }

        foreach (self::PORTAL_CONTROLLERS as $controller) {
            $this->execute(
                'UPDATE adms_pages SET adms_groups_page_id = ' . $portalId
                . ', updated_at = ' . $conn->quote($now)
                . ' WHERE controller = ' . $conn->quote($controller)
            );
        }

        if (class_exists(\App\adms\Models\Repository\MenuPermissionUserRepository::class)) {
            \App\adms\Models\Repository\MenuPermissionUserRepository::bumpGlobalPermissionCacheVersion();
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_groups_pages') || !$this->hasTable('adms_pages')) {
            return;
        }

        $conn = $this->getAdapter()->getConnection();
        $now = date('Y-m-d H:i:s');

        $rh = $this->fetchRow(
            'SELECT id FROM adms_groups_pages WHERE name = ' . $conn->quote(self::RH_GROUP) . ' LIMIT 1'
        );
        $portal = $this->fetchRow(
            'SELECT id FROM adms_groups_pages WHERE name = ' . $conn->quote(self::PORTAL_GROUP) . ' LIMIT 1'
        );
        if (!$rh) {
            return;
        }
        $rhId = (int) $rh['id'];

        if ($portal) {
            $portalId = (int) $portal['id'];
            $this->execute(
                'UPDATE adms_pages SET adms_groups_page_id = ' . $rhId
                . ', updated_at = ' . $conn->quote($now)
                . ' WHERE adms_groups_page_id = ' . $portalId
            );
        }

        $this->execute(
            'UPDATE adms_groups_pages SET name = ' . $conn->quote(self::OLD_GROUP)
            . ', obs = ' . $conn->quote('Portal do colaborador e solicitações/aprovações (legado reunificado).')
            . ', updated_at = ' . $conn->quote($now)
            . ' WHERE id = ' . $rhId
        );

        if (class_exists(\App\adms\Models\Repository\MenuPermissionUserRepository::class)) {
            \App\adms\Models\Repository\MenuPermissionUserRepository::bumpGlobalPermissionCacheVersion();
        }
    }

    private function ensureGroup(string $name, string $obs, string $now, mixed $conn): int
    {
        $row = $this->fetchRow(
            'SELECT id FROM adms_groups_pages WHERE name = ' . $conn->quote($name) . ' LIMIT 1'
        );
        if ($row) {
            return (int) $row['id'];
        }

        $hasUpdated = $this->table('adms_groups_pages')->hasColumn('updated_at');
        if ($hasUpdated) {
            $this->execute(
                'INSERT INTO adms_groups_pages (name, obs, created_at, updated_at) VALUES ('
                . $conn->quote($name) . ', '
                . $conn->quote($obs) . ', '
                . $conn->quote($now) . ', '
                . $conn->quote($now) . ')'
            );
        } else {
            $this->execute(
                'INSERT INTO adms_groups_pages (name, obs, created_at) VALUES ('
                . $conn->quote($name) . ', '
                . $conn->quote($obs) . ', '
                . $conn->quote($now) . ')'
            );
        }

        $new = $this->fetchRow(
            'SELECT id FROM adms_groups_pages WHERE name = ' . $conn->quote($name) . ' LIMIT 1'
        );

        return (int) ($new['id'] ?? 0);
    }
}
