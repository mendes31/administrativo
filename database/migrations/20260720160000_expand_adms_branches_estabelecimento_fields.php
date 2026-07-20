<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Estabelecimentos (matriz/filial): CNPJ, tipo, razão social e nome fantasia.
 * Mesma razão social pode ter vários CNPJs (ordens 0001, 0002…) com nomes fantasia distintos.
 */
final class ExpandAdmsBranchesEstabelecimentoFields extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_branches')) {
            return;
        }

        $table = $this->table('adms_branches');

        if (!$table->hasColumn('cnpj')) {
            $table->addColumn('cnpj', 'string', [
                'limit' => 14,
                'null' => true,
                'default' => null,
                'after' => 'code',
                'comment' => 'CNPJ do estabelecimento (14 dígitos)',
            ]);
        }
        if (!$table->hasColumn('establishment_type')) {
            $table->addColumn('establishment_type', 'string', [
                'limit' => 10,
                'null' => false,
                'default' => 'filial',
                'after' => 'cnpj',
                'comment' => 'matriz|filial (Receita Federal)',
            ]);
        }
        if (!$table->hasColumn('razao_social')) {
            $table->addColumn('razao_social', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
                'after' => 'establishment_type',
                'comment' => 'Nome empresarial (razão social)',
            ]);
        }
        if (!$table->hasColumn('nome_fantasia')) {
            $table->addColumn('nome_fantasia', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
                'after' => 'razao_social',
                'comment' => 'Título do estabelecimento / nome fantasia',
            ]);
        }

        $table->update();

        // Índice único só quando CNPJ preenchido (MySQL permite vários NULL)
        $indexes = $this->fetchAll("SHOW INDEX FROM adms_branches WHERE Key_name = 'uq_adms_branches_cnpj'");
        if ($indexes === []) {
            $this->execute('ALTER TABLE adms_branches ADD UNIQUE INDEX uq_adms_branches_cnpj (cnpj)');
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_branches')) {
            return;
        }

        $table = $this->table('adms_branches');
        $indexes = $this->fetchAll("SHOW INDEX FROM adms_branches WHERE Key_name = 'uq_adms_branches_cnpj'");
        if ($indexes !== []) {
            $this->execute('ALTER TABLE adms_branches DROP INDEX uq_adms_branches_cnpj');
        }

        foreach (['nome_fantasia', 'razao_social', 'establishment_type', 'cnpj'] as $col) {
            if ($table->hasColumn($col)) {
                $table->removeColumn($col);
            }
        }
        $table->update();
    }
}
