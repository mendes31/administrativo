<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Filial (adms_branches) no cadastro de equipamentos SST.
 */
final class AddSstEquipamentoBranchId extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_sst_equipamentos')) {
            return;
        }

        $table = $this->table('adms_sst_equipamentos');
        if (!$table->hasColumn('adms_branch_id')) {
            $table
                ->addColumn('adms_branch_id', 'integer', [
                    'null' => true,
                    'signed' => false,
                    'after' => 'adms_department_id',
                    'comment' => 'Filial do equipamento',
                ])
                ->addIndex(['adms_branch_id'])
                ->update();

            if ($this->hasTable('adms_branches')) {
                try {
                    $this->execute(
                        'ALTER TABLE adms_sst_equipamentos
                         ADD CONSTRAINT adms_sst_equipamentos_branch_id
                         FOREIGN KEY (adms_branch_id) REFERENCES adms_branches(id)
                         ON DELETE SET NULL ON UPDATE CASCADE'
                    );
                } catch (Throwable) {
                    // Índice/FK pode falhar em ambientes sem InnoDB consistente; coluna já criada.
                }
            }
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_sst_equipamentos')) {
            return;
        }

        $table = $this->table('adms_sst_equipamentos');
        if ($table->hasColumn('adms_branch_id')) {
            try {
                $this->execute('ALTER TABLE adms_sst_equipamentos DROP FOREIGN KEY adms_sst_equipamentos_branch_id');
            } catch (Throwable) {
            }
            $table->removeColumn('adms_branch_id')->update();
        }
    }
}
