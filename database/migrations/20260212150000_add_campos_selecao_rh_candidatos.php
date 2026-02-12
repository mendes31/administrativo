<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adiciona campos importantes para seleção de candidatos:
 * - area_interesse: Área de interesse do candidato
 * - graduacao: Informações sobre graduação/formação
 * - ultima_experiencia: Última experiência profissional
 */
final class AddCamposSelecaoRhCandidatos extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('rh_candidatos')) {
            return;
        }

        $table = $this->table('rh_candidatos');

        // Área de interesse
        if (!$table->hasColumn('area_interesse')) {
            $table->addColumn('area_interesse', 'string', [
                'limit' => 100,
                'null'  => true,
                'comment' => 'Área de interesse do candidato (ex: TI, Administração, Vendas)',
                'after' => 'estado',
            ]);
        }

        // Graduação/Formação
        if (!$table->hasColumn('graduacao')) {
            $table->addColumn('graduacao', 'text', [
                'null'  => true,
                'comment' => 'Informações sobre graduação/formação (curso, instituição, ano de conclusão)',
                'after' => 'area_interesse',
            ]);
        }

        // Última experiência profissional
        if (!$table->hasColumn('ultima_experiencia')) {
            $table->addColumn('ultima_experiencia', 'text', [
                'null'  => true,
                'comment' => 'Última experiência profissional (cargo, empresa, período, atividades)',
                'after' => 'graduacao',
            ]);
        }

        // Índice para área de interesse (para filtros)
        if (!$table->hasIndex('area_interesse')) {
            $table->addIndex(['area_interesse'], ['name' => 'idx_area_interesse']);
        }

        $table->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('rh_candidatos')) {
            return;
        }

        $table = $this->table('rh_candidatos');

        if ($table->hasColumn('ultima_experiencia')) {
            $table->removeColumn('ultima_experiencia');
        }
        if ($table->hasColumn('graduacao')) {
            $table->removeColumn('graduacao');
        }
        if ($table->hasColumn('area_interesse')) {
            $table->removeColumn('area_interesse');
        }

        $table->update();
    }
}

