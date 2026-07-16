<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Filial do equipamento alinhada ao cadastro de usuário (empresa_contratante),
 * em vez de adms_branches (cadastro administrativo de filiais).
 */
final class SstEquipamentoFilialEmpresaContratante extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_sst_equipamentos')) {
            return;
        }

        $table = $this->table('adms_sst_equipamentos');
        if (!$table->hasColumn('empresa_contratante')) {
            $table
                ->addColumn('empresa_contratante', 'string', [
                    'limit' => 40,
                    'null' => true,
                    'after' => 'adms_department_id',
                    'comment' => 'Mesmos slugs do cadastro de usuário (empresa contratante / filial)',
                ])
                ->addIndex(['empresa_contratante'])
                ->update();
        }

        // Remove vínculo anterior com adms_branches, se existir.
        if ($table->hasColumn('adms_branch_id')) {
            try {
                $this->execute('ALTER TABLE adms_sst_equipamentos DROP FOREIGN KEY adms_sst_equipamentos_branch_id');
            } catch (Throwable) {
            }
            try {
                $table->removeColumn('adms_branch_id')->update();
            } catch (Throwable) {
                // Já removida ou sem FK nomeada.
            }
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_sst_equipamentos')) {
            return;
        }

        $table = $this->table('adms_sst_equipamentos');
        if ($table->hasColumn('empresa_contratante')) {
            $table->removeColumn('empresa_contratante')->update();
        }
    }
}
