<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Amplia adms_branches.code (estava truncando slugs de empresa_contratante)
 * e reaplica codes + backfill de user_branch_id.
 */
final class FixAdmsBranchesCodeLengthAndRelinkUsers extends AbstractMigration
{
    /** @var array<string, string> */
    private const CNPJ_TO_SLUG = [
        '23739581000183' => 'tiaraju_farma',
        '08352440000110' => 'lab_tiaraju_matriz',
        '08352440000209' => 'lab_tiaraju_filial',
    ];

    public function up(): void
    {
        if (!$this->hasTable('adms_branches')) {
            return;
        }

        $table = $this->table('adms_branches');
        if ($table->hasColumn('code')) {
            $table->changeColumn('code', 'string', [
                'limit' => 50,
                'null' => false,
            ])->update();
        }

        foreach (self::CNPJ_TO_SLUG as $cnpj => $slug) {
            $this->execute(sprintf(
                "UPDATE adms_branches SET code = '%s' WHERE cnpj = '%s' LIMIT 1",
                $slug,
                $cnpj
            ));
        }

        if ($this->hasTable('adms_users')) {
            $users = $this->table('adms_users');
            if ($users->hasColumn('user_branch_id') && $users->hasColumn('empresa_contratante')) {
                $this->execute(
                    "UPDATE adms_users u
                     INNER JOIN adms_branches b ON b.code = u.empresa_contratante AND b.active = 1
                     SET u.user_branch_id = b.id
                     WHERE u.empresa_contratante IS NOT NULL
                       AND u.empresa_contratante <> ''
                       AND (u.user_branch_id IS NULL OR u.user_branch_id = 0)"
                );
            }
        }
    }

    public function down(): void
    {
        // Não reduz o tamanho do code (risco de truncar dados).
    }
}
