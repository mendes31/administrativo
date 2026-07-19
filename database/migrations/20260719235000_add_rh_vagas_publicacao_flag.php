<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Fase 3 Expand — publicação de vagas (admin).
 * Default desligada: sem portal público neste incremento.
 */
final class AddRhVagasPublicacaoFlag extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('rh_vagas')) {
            return;
        }

        $table = $this->table('rh_vagas');
        if (!$table->hasColumn('publicada')) {
            $table->addColumn('publicada', 'boolean', [
                'signed' => false,
                'null' => false,
                'default' => 0,
                'after' => 'mostrar_salario',
                'comment' => 'Marca intenção de exibir no portal público (portal ainda não entregue)',
            ]);
        }
        if (!$table->hasColumn('publicado_em')) {
            $table->addColumn('publicado_em', 'datetime', [
                'null' => true,
                'default' => null,
                'after' => 'publicada',
                'comment' => 'Primeira/última vez em que publicada foi ligada',
            ]);
        }
        if (!$table->hasIndex(['publicada', 'status'])) {
            $table->addIndex(['publicada', 'status'], [
                'name' => 'idx_rh_vagas_publicacao',
            ]);
        }
        $table->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('rh_vagas')) {
            return;
        }

        $table = $this->table('rh_vagas');
        if ($table->hasIndexByName('idx_rh_vagas_publicacao')) {
            $table->removeIndexByName('idx_rh_vagas_publicacao')->update();
        }
        if ($table->hasColumn('publicado_em')) {
            $table->removeColumn('publicado_em')->update();
        }
        if ($table->hasColumn('publicada')) {
            $table->removeColumn('publicada')->update();
        }
    }
}
