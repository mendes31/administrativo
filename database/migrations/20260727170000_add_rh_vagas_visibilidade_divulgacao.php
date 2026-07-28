<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Canal de divulgação da vaga: externa | interna | ambas.
 * Portal público (vagas-abertas) só quando publicada + externa/ambas.
 * Interna recomenda anúncio via Informativos (app autenticado).
 */
final class AddRhVagasVisibilidadeDivulgacao extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('rh_vagas')) {
            return;
        }

        $table = $this->table('rh_vagas');
        if (!$table->hasColumn('visibilidade')) {
            $table->addColumn('visibilidade', 'enum', [
                'values' => ['externa', 'interna', 'ambas'],
                'default' => 'externa',
                'null' => false,
                'after' => 'publicado_em',
                'comment' => 'externa=portal público; interna=app/informativos; ambas=os dois',
            ])->update();
        }

        // Vagas já publicadas no portal permanecem externa (default).
        $this->execute(
            "UPDATE rh_vagas SET visibilidade = 'externa' WHERE publicada = 1 AND (visibilidade IS NULL OR visibilidade = '')"
        );
    }

    public function down(): void
    {
        if (!$this->hasTable('rh_vagas')) {
            return;
        }
        $table = $this->table('rh_vagas');
        if ($table->hasColumn('visibilidade')) {
            $table->removeColumn('visibilidade')->update();
        }
    }
}
