<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddMeasuresToDashboards extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('adms_dashboards');
        
        // Verificar se a coluna já existe
        if (!$table->hasColumn('measures_config')) {
            $table->addColumn('measures_config', 'text', [
                'null' => true,
                'comment' => 'Medidas calculadas (estilo DAX) em JSON',
                'after' => 'category'
            ])
            ->update();
        }
    }
}

