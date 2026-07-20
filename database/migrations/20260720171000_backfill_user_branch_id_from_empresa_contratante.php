<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Preenche user_branch_id a partir de empresa_contratante = adms_branches.code.
 */
final class BackfillUserBranchIdFromEmpresaContratante extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_users') || !$this->hasTable('adms_branches')) {
            return;
        }

        $users = $this->table('adms_users');
        if (!$users->hasColumn('user_branch_id') || !$users->hasColumn('empresa_contratante')) {
            return;
        }

        $this->execute(
            "UPDATE adms_users u
             INNER JOIN adms_branches b ON b.code = u.empresa_contratante AND b.active = 1
             SET u.user_branch_id = b.id
             WHERE u.empresa_contratante IS NOT NULL
               AND u.empresa_contratante <> ''
               AND (u.user_branch_id IS NULL OR u.user_branch_id = 0)"
        );
    }

    public function down(): void
    {
        // Não limpa user_branch_id no down (pode haver vínculos legítimos posteriores).
    }
}
