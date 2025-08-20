<?php
use Phinx\Migration\AbstractMigration;

class AddAreaAndTipoObrigatoriedadeToAdmsTrainings extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('adms_trainings');
        // Área Responsável (FK para departamento)
        if (!$table->hasColumn('area_responsavel_id')) {
            $table->addColumn('area_responsavel_id', 'integer', ['null' => true, 'signed' => false, 'after' => 'id']);
        } else {
            $table->changeColumn('area_responsavel_id', 'integer', ['null' => true, 'signed' => false]);
        }
        if (!$table->hasForeignKey('area_responsavel_id')) {
            $table->addForeignKey('area_responsavel_id', 'adms_departments', 'id', ['delete'=> 'SET_NULL', 'update'=> 'NO_ACTION']);
        }
        // Área Elaborador (FK para departamento)
        if (!$table->hasColumn('area_elaborador_id')) {
            $table->addColumn('area_elaborador_id', 'integer', ['null' => true, 'signed' => false, 'after' => 'area_responsavel_id']);
        } else {
            $table->changeColumn('area_elaborador_id', 'integer', ['null' => true, 'signed' => false]);
        }
        if (!$table->hasForeignKey('area_elaborador_id')) {
            $table->addForeignKey('area_elaborador_id', 'adms_departments', 'id', ['delete'=> 'SET_NULL', 'update'=> 'NO_ACTION']);
        }
        // Tipo de obrigatoriedade
        if (!$table->hasColumn('tipo_obrigatoriedade')) {
            $table->addColumn('tipo_obrigatoriedade', 'enum', [
                'values' => ['Legal', 'Normativa', 'Contratual', 'Corporativa', 'Técnica', 'Estratégica'],
                'default' => 'Corporativa',
                'null' => false,
                'after' => 'area_elaborador_id',
            ]);
        }
        $table->update();
    }

    public function down()
    {
        $table = $this->table('adms_trainings');
        if ($table->hasForeignKey('area_responsavel_id')) {
            $table->dropForeignKey('area_responsavel_id');
        }
        if ($table->hasColumn('area_responsavel_id')) {
            $table->removeColumn('area_responsavel_id');
        }
        if ($table->hasForeignKey('area_elaborador_id')) {
            $table->dropForeignKey('area_elaborador_id');
        }
        if ($table->hasColumn('area_elaborador_id')) {
            $table->removeColumn('area_elaborador_id');
        }
        if ($table->hasColumn('tipo_obrigatoriedade')) {
            $table->removeColumn('tipo_obrigatoriedade');
        }
        $table->update();
    }
} 