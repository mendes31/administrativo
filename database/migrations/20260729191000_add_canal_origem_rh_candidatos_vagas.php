<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Canal de origem por candidatura×vaga (UTM / portal interno).
 * Complementa rh_candidatos.origem (primeira origem do cadastro da pessoa).
 */
final class AddCanalOrigemRhCandidatosVagas extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('rh_candidatos_vagas')) {
            return;
        }

        $table = $this->table('rh_candidatos_vagas');
        if (!$table->hasColumn('canal_origem')) {
            $table
                ->addColumn('canal_origem', 'string', [
                    'limit' => 50,
                    'null' => true,
                    'default' => null,
                    'after' => 'observacoes',
                    'comment' => 'Canal desta candidatura: portal_linkedin, portal_site, portal_interno, form_trabalhe_conosco…',
                ])
                ->addIndex(['canal_origem'], ['name' => 'idx_rh_candidatos_vagas_canal_origem'])
                ->update();
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('rh_candidatos_vagas')) {
            return;
        }

        $table = $this->table('rh_candidatos_vagas');
        if ($table->hasColumn('canal_origem')) {
            if ($table->hasIndexByName('idx_rh_candidatos_vagas_canal_origem')) {
                $table->removeIndexByName('idx_rh_candidatos_vagas_canal_origem')->update();
            }
            $table->removeColumn('canal_origem')->update();
        }
    }
}
